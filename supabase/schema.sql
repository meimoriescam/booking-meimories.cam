-- ============================================================
-- meimories.cam booking website — Supabase schema
-- Jalankan seluruh isi file ini di: Supabase Dashboard > SQL Editor > New query > Run
-- ============================================================

-- 1) Tabel booking
create table if not exists public.bookings (
  id uuid primary key default gen_random_uuid(),
  date text not null,              -- format YYYY-MM-DD
  time text not null,              -- format HH:MM
  category text,
  package_name text,
  addons text[] default '{}',
  total numeric default 0,
  payment_type text,               -- 'dp' atau 'lunas'
  amount_to_pay numeric default 0,
  sisa_bayar numeric default 0,
  name text,
  wa text,
  lokasi text,
  notes text,
  payment_proof_url text,
  payment_proof_name text,
  created_at timestamptz default now()
);

-- 2) Aktifkan Row Level Security
alter table public.bookings enable row level security;

-- 3) Izinkan siapa saja (termasuk customer yang belum login) untuk MENGISI booking baru
create policy "Siapa saja boleh insert booking"
  on public.bookings
  for insert
  to anon
  with check (true);

-- 4) Hanya admin yang sudah login (authenticated) boleh baca data booking LENGKAP
--    (nama, WA, bukti transfer, dsb). Ini bagian dari sistem login sungguhan
--    lewat Supabase Auth — lihat README bagian "Bagian 5".
--    (drop dulu kebijakan lama kalau kamu sebelumnya sudah pernah run schema versi awal)
drop policy if exists "Siapa saja boleh baca booking" on public.bookings;
drop policy if exists "Hanya admin login boleh baca semua data booking" on public.bookings;

create policy "Hanya admin login boleh baca semua data booking"
  on public.bookings
  for select
  to authenticated
  using (true);

-- 4b) View publik TERBATAS untuk kalender booking (tanpa data pribadi customer)
--     Kolom yang diekspos cuma date, time, package_name — dipakai supaya
--     calon customer bisa lihat slot yang sudah penuh tanpa perlu login,
--     dan tanpa bisa mengintip nama/WA/bukti transfer orang lain.
create or replace view public.public_slots as
  select date, time, package_name from public.bookings;

grant select on public.public_slots to anon, authenticated;

-- ============================================================
-- 5) Storage bucket untuk bukti pembayaran
--    Bagian ini HARUS dijalankan lewat menu Storage di dashboard,
--    bukan lewat SQL editor. Langkahnya:
--    a. Buka menu "Storage" di sidebar kiri Supabase
--    b. Klik "New bucket", beri nama: payment-proofs
--    c. Toggle "Public bucket" -> ON (supaya admin bisa lihat gambarnya)
--    d. Klik "Create bucket"
-- ============================================================
