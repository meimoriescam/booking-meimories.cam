// src/lib/api.js
// Client API untuk meimories.cam (menggantikan Supabase dengan MySQL + PHP REST API)

const API_BASE = '/api';
const TOKEN_KEY = 'meimories_admin_token';
const listeners = new Set();

function notifyAuthChange(session) {
  listeners.forEach((cb) => {
    try {
      cb('SIGNED_IN_OR_OUT', session);
    } catch (e) {
      console.error(e);
    }
  });
}

export const api = {
  auth: {
    async getSession() {
      const token = localStorage.getItem(TOKEN_KEY);
      if (!token) return { data: { session: null }, error: null };

      try {
        const res = await fetch(`${API_BASE}/auth.php?action=session`, {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        });
        const data = await res.json();
        if (data && data.session) {
          return { data: { session: data.session }, error: null };
        }
        localStorage.removeItem(TOKEN_KEY);
        return { data: { session: null }, error: null };
      } catch (err) {
        console.error('Session check error:', err);
        return { data: { session: null }, error: err };
      }
    },

    onAuthStateChange(callback) {
      listeners.add(callback);
      return {
        data: {
          subscription: {
            unsubscribe: () => listeners.delete(callback),
          },
        },
      };
    },

    async signInWithPassword({ email, password }) {
      try {
        const res = await fetch(`${API_BASE}/auth.php?action=login`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ email, password }),
        });
        const data = await res.json();
        if (!res.ok || data.error) {
          return { error: new Error(data.error || 'Login gagal.') };
        }

        const session = {
          token: data.token,
          user: data.user,
        };
        localStorage.setItem(TOKEN_KEY, data.token);
        notifyAuthChange(session);
        return { data: { session }, error: null };
      } catch (err) {
        return { error: err };
      }
    },

    async signOut() {
      const token = localStorage.getItem(TOKEN_KEY);
      if (token) {
        try {
          await fetch(`${API_BASE}/auth.php?action=logout`, {
            method: 'POST',
            headers: {
              Authorization: `Bearer ${token}`,
            },
          });
        } catch (e) {
          // ignore
        }
      }
      localStorage.removeItem(TOKEN_KEY);
      notifyAuthChange(null);
      return { error: null };
    },
  },

  async loadSharedSlots() {
    try {
      const res = await fetch(`${API_BASE}/slots.php`);
      const result = await res.json();
      if (!res.ok || result.error) {
        throw new Error(result.error || 'Gagal memuat jadwal slot.');
      }
      const data = result.data || [];
      const map = {};
      data.forEach((row) => {
        if (!map[row.date]) map[row.date] = [];
        map[row.date].push({ time: row.time, label: row.package_name });
      });
      return map;
    } catch (err) {
      console.error('gagal ambil slot', err);
      return {};
    }
  },

  async uploadPaymentProof(file) {
    const formData = new FormData();
    formData.append('file', file);

    const res = await fetch(`${API_BASE}/upload.php`, {
      method: 'POST',
      body: formData,
    });
    const result = await res.json();
    if (!res.ok || result.error) {
      throw new Error(result.error || 'Gagal mengupload bukti pembayaran.');
    }
    return result.publicUrl;
  },

  async insertBooking(booking) {
    const bookingId = 'BK-' + Date.now().toString(36).toUpperCase();
    const payload = {
      id: bookingId,
      date: booking.date,
      time: booking.time,
      category: booking.category,
      package_name: booking.packageName,
      addons: booking.addons,
      total: booking.total,
      payment_type: booking.paymentType,
      amount_to_pay: booking.amountToPay,
      sisa_bayar: booking.sisaBayar,
      name: booking.name,
      wa: booking.wa,
      lokasi: booking.lokasi,
      notes: booking.notes,
      payment_proof_url: booking.paymentProofUrl || null,
      payment_proof_name: booking.paymentProofName || null,
    };

    const res = await fetch(`${API_BASE}/bookings.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    });

    const result = await res.json();
    if (!res.ok || result.error) {
      throw new Error(result.error || 'Gagal menyimpan booking.');
    }
    return { id: bookingId, created_at: new Date().toISOString() };
  },

  async loadBookings() {
    const token = localStorage.getItem(TOKEN_KEY);
    if (!token) return [];

    try {
      const res = await fetch(`${API_BASE}/bookings.php`, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });
      const result = await res.json();
      if (!res.ok || result.error) {
        console.error('gagal ambil booking', result.error);
        return [];
      }
      const data = result.data || [];
      return data.map((b) => ({
        id: b.id,
        date: b.date,
        time: b.time,
        category: b.category,
        packageName: b.package_name,
        addons: b.addons || [],
        total: b.total,
        paymentType: b.payment_type,
        amountToPay: b.amount_to_pay,
        sisaBayar: b.sisa_bayar,
        name: b.name,
        wa: b.wa,
        lokasi: b.lokasi,
        notes: b.notes,
        paymentProof: b.payment_proof_url,
        paymentProofName: b.payment_proof_name,
        createdAt: b.created_at,
      }));
    } catch (err) {
      console.error('gagal ambil booking', err);
      return [];
    }
  },

  async deleteBooking(id) {
    const token = localStorage.getItem(TOKEN_KEY);
    if (!token) throw new Error('Akses ditolak: Anda belum login.');

    const res = await fetch(`${API_BASE}/bookings.php?id=${encodeURIComponent(id)}`, {
      method: 'DELETE',
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });

    const result = await res.json();
    if (!res.ok || result.error) {
      throw new Error(result.error || 'Gagal menghapus booking.');
    }
    return result;
  },
};
