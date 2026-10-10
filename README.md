# meimories.cam — Website Booking

Website booking untuk meimories.cam, dibangun menggunakan **React + Vite + Tailwind CSS** di sisi frontend, dan **PHP REST API + MySQL** di sisi backend.

---

## 🚀 Fitur Utama
1. **Katalog & Pemilihan Paket**: Regular Pack, Graduation Pack, Group Pack, Polaroid, dan Add-ons.
2. **Kalender Booking Interaktif**: Menampilkan slot jam yang tersedia secara real-time. Slot yang sudah dibooking otomatis terkunci.
3. **Upload Bukti Transfer Aman**: File bukti transfer divalidasi MIME type (magic bytes) dan disimpan di server lokal `/uploads/payment-proofs/` dengan proteksi eksekusi script.
4. **Admin Panel ("Jadwal & Pengingat")**:
   - Autentikasi Admin terenkripsi BCrypt (cost 12) + token hash SHA-256 at rest.
   - Perlindungan Anti Brute-Force Rate Limiting (maksimal 5 percobaan gagal per 15 menit).
   - Melihat detail lengkap booking (nama klien, WhatsApp, paket, DP/lunas, link bukti transfer, lokasi foto).
   - Filter tanggal & tombol aksi WhatsApp langsung.
   - Pembatalan/penghapusan booking dengan pencatatan audit log otomatis (`activity_logs`).

---

## 🔑 Membuat atau Mereset Akun Admin Baru

Sesuai standar keamanan (OWASP Top 10), akun admin **tidak disimpan secara hardcoded**. Anda dapat membuat akun admin baru dengan 2 cara:

### Cara 1: Menggunakan Perintah Terminal (Paling Cepat & Praktis)
Jalankan perintah berikut di terminal komputer lokal Anda:
```bash
npm run add-admin <email-admin> <password-baru>
```
*Contoh:*
```bash
npm run add-admin developer@meimories.cam RahasiaKuat2026!
```
Script akan secara otomatis membuat atau mengupdate akun di database server dengan hashing BCrypt yang aman.

### Cara 2: Melalui phpMyAdmin
1. Buka [https://databases.zedevio.com:8088](https://databases.zedevio.com:8088) dan login menggunakan user MySQL Anda.
2. Pilih database `if061026_meimories_sql` → buka tabel `admin_users`.
3. Klik tab **Insert**:
   - Kolom `email`: masukkan email admin Anda.
   - Kolom `password_hash`: masukkan hash BCrypt yang dihasilkan via PHP (atau gunakan fungsi hash phpMyAdmin).
4. Klik **Go**.

---

## 💻 Menjalankan di Komputer Lokal (Development)

1. Pastikan dependensi sudah terinstall:
   ```bash
   npm install
   ```

2. Jalankan development server:
   ```bash
   npm run dev
   ```
   Buka `http://localhost:5173`. Semua panggilan API otomatis di-proxy ke server backend live melalui `vite.config.js`.

---

## 🚢 Deploy Otomatis ke Server Hosting via FTP

Cukup jalankan satu perintah:
```bash
npm run deploy
```

Perintah di atas akan otomatis:
1. Menjalankan `vite build` untuk meng-compile frontend ke folder `dist/`.
2. Menghubungkan ke server FTP `16.78.67.164:2614`.
3. Mengunggah file frontend (`dist/`), backend API (`api/`), `.htaccess`, proteksi uploads, dan konfigurasi server.
4. Menyiapkan folder `/uploads/payment-proofs/` dengan proteksi eksekusi file script.
5. Melakukan verifikasi HTTP endpoint secara otomatis.

---

## 🗄️ Struktur Database (`database.sql`)
File [database.sql](file:///D:/repos/booking-meimories.cam/database.sql) berisi:
- Tabel `admin_users`: Kredensial akun admin.
- Tabel `admin_sessions`: Token sesi login admin dengan hash SHA-256.
- Tabel `bookings`: Data lengkap pemesanan & bukti transfer.
- Tabel `activity_logs`: Catatan pembatalan/penghapusan booking.
- Tabel `login_attempts`: Riwayat percobaan login gagal untuk rate limiting.
- Tabel `booking_rate_limits`: Pembatasan spam reservasi publik.
