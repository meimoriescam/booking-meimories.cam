-- 1. TABEL KATEGORI PAKET
CREATE TABLE package_categories (
  id text PRIMARY KEY,
  label text NOT NULL,
  icon text NOT NULL,
  tagline text,
  sort_order integer DEFAULT 0
);

-- 2. TABEL DETAIL PAKET
CREATE TABLE packages (
  id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
  category_id text REFERENCES package_categories(id) ON DELETE CASCADE,
  name text NOT NULL,
  price integer NOT NULL,
  duration text,
  photos text,
  description text,
  benefits jsonb DEFAULT '[]'::jsonb, -- Array teks
  extra text,
  people text,
  per_person_price integer,
  favorite boolean DEFAULT false,
  sort_order integer DEFAULT 0
);

-- 3. TABEL ADDONS (Tambahan)
CREATE TABLE addons (
  id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
  label text NOT NULL,
  price integer NOT NULL,
  note text,
  sort_order integer DEFAULT 0
);

-- 4. TABEL GALERI
CREATE TABLE gallery (
  id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
  image_url text NOT NULL,
  sort_order integer DEFAULT 0
);

-- 5. TABEL PENGATURAN UMUM
CREATE TABLE site_settings (
  key text PRIMARY KEY,
  value jsonb NOT NULL
);

-- SETTING ROW LEVEL SECURITY (RLS)
-- Mengizinkan semua orang (publik) untuk membaca data
ALTER TABLE package_categories ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Public can read package categories" ON package_categories FOR SELECT USING (true);

ALTER TABLE packages ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Public can read packages" ON packages FOR SELECT USING (true);

ALTER TABLE addons ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Public can read addons" ON addons FOR SELECT USING (true);

ALTER TABLE gallery ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Public can read gallery" ON gallery FOR SELECT USING (true);

ALTER TABLE site_settings ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Public can read settings" ON site_settings FOR SELECT USING (true);

-- Mengizinkan admin (yang sudah login) untuk menambah/mengubah/menghapus data
CREATE POLICY "Admin can modify package categories" ON package_categories USING (auth.role() = 'authenticated');
CREATE POLICY "Admin can modify packages" ON packages USING (auth.role() = 'authenticated');
CREATE POLICY "Admin can modify addons" ON addons USING (auth.role() = 'authenticated');
CREATE POLICY "Admin can modify gallery" ON gallery USING (auth.role() = 'authenticated');
CREATE POLICY "Admin can modify settings" ON site_settings USING (auth.role() = 'authenticated');

-- INSERT DATA AWAL (DEFAULT) AGAR WEBSITE TIDAK KOSONG
INSERT INTO package_categories (id, label, icon, tagline, sort_order) VALUES
('regular', 'Regular Pack', 'Camera', 'Untuk sempro, semhas, sidang & momen harian', 1),
('graduation', 'Graduation Pack', 'GraduationCap', 'For the milestone you’ll never forget — Yudisium & Wisuda', 2),
('group', 'Group Pack', 'Users', 'Because the best memories are made together', 3),
('polaroid', 'Foto Polaroid', 'Sparkles', 'Celebrating your achievement, preserving your meimories — via Instax Mini 8', 4);

-- Insert contoh 1 paket agar terlihat formatnya
INSERT INTO packages (category_id, name, price, duration, photos, description, benefits, favorite, sort_order) VALUES
('regular', 'Sweet Meimories', 150000, '45 menit', '30 foto edit warna', 'Paket favorit untuk kamu yang ingin leluasa mengabadikan momen.', '["Unlimited shoot (sesuai durasi)", "Durasi 45 menit", "1 lokasi (berbagai spot menyesuaikan)", "30 foto hasil edit warna", "Semua file dikirim via Google Drive", "Tidak ada batasan orang yang ikut berfoto dalam satu sesi"]'::jsonb, true, 1);

-- Insert Setting Default
INSERT INTO site_settings (key, value) VALUES
('bank_accounts', '[{"bank": "Bank Jago", "account": "103959772276", "name": "Orien Meidina Raihan"}, {"bank": "Bank BCA", "account": "0292757806", "name": "Orien Meidina Raihan"}]'::jsonb),
('dp_min_percent', '50'::jsonb),
('terms', '["Reschedule hanya dapat dilakukan 1x, selama masih ada slot yang tersedia.", "File foto diberikan ketika sudah pelunasan.", "Spot foto free khusus area Untan, Polnep, UMP, dan UPB (area/spot lain kena fee transport tambahan).", "Jika terjadi pembatalan, DP hangus dan tidak bisa dikembalikan dalam bentuk dan alasan apapun."]'::jsonb);
