# meimories.cam — Website Booking

Website booking untuk meimories.cam, dibangun menggunakan **React + Vite + Tailwind CSS** di sisi frontend, dan **PHP REST API + MySQL** di sisi backend.

---

## 🚀 Fitur Utama
1. **Katalog & Pemilihan Paket**: Regular Pack, Graduation Pack, Group Pack, Polaroid, dan Add-ons.
2. **Kalender Booking Interaktif**: Menampilkan slot jam yang tersedia secara real-time. Slot yang sudah dibooking otomatis terkunci.
3. **Upload Bukti Transfer**: File bukti transfer diupload ke server lokal `/uploads/payment-proofs/`.
4. **Admin Panel ("Jadwal & Pengingat")**:
   - Autentikasi Admin terenkripsi BCrypt.
   - Melihat detail lengkap booking (nama klien, WhatsApp, paket, DP/lunas, link bukti transfer, lokasi foto).
   - Filter tanggal & tombol aksi WhatsApp langsung.
   - Pembatalan/penghapusan booking dengan pencatatan audit log otomatis (`activity_logs`).

---

## 🛠️ Informasi Server & Database
- **Host / Staging URL**: `https://meimoriescam.zedevio.com`
- **FTP Host**: `16.78.67.164:2614` (User: `meimoriescam`)
- **phpMyAdmin**: `https://databases.zedevio.com:8088`
- **Database Server**: `10.10.16.6:3037` (Database: `if061026_meimories_sql`, User: `meimoriessql`)
- **Default Akun Admin**:
  - Email: `admin@meimories.cam`
  - Password: `AdminMeimories123!`

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
3. Mengunggah file frontend (`dist/`), backend API (`api/`), `.htaccess`, dan konfigurasi server.
4. Menyiapkan folder `/uploads/payment-proofs/`.
5. Melakukan verifikasi HTTP endpoint secara otomatis.

---

## 🗄️ Struktur Database (`database.sql`)
File [database.sql](file:///D:/repos/booking-meimories.cam/database.sql) berisi:
- Tabel `admin_users`: Kredensial akun admin.
- Tabel `admin_sessions`: Token sesi login admin.
- Tabel `bookings`: Data lengkap pemesanan & bukti transfer.
- Tabel `activity_logs`: Catatan pembatalan/penghapusan booking.
