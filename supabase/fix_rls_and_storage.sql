-- ============================================================
-- FIX RLS UNTUK TABEL BOOKINGS & STORAGE BUKTI BAYAR
-- Jalankan query ini di: Supabase Dashboard -> SQL Editor -> New Query -> Run
-- ============================================================

-- 1. Pastikan siapa saja (baik anonim maupun admin yang sedang login) bisa INSERT ke tabel bookings
DROP POLICY IF EXISTS "Siapa saja boleh insert booking" ON public.bookings;
DROP POLICY IF EXISTS "Admin boleh insert booking" ON public.bookings;

CREATE POLICY "Siapa saja boleh insert booking"
  ON public.bookings
  FOR INSERT
  TO public
  WITH CHECK (true);

-- 2. Pastikan bucket payment-proofs ada dan berstatus public
INSERT INTO storage.buckets (id, name, public)
VALUES ('payment-proofs', 'payment-proofs', true)
ON CONFLICT (id) DO UPDATE SET public = true;

-- 3. Izinkan upload file ke bucket payment-proofs untuk siapa saja
DROP POLICY IF EXISTS "Izinkan pengunjung upload bukti" ON storage.objects;
DROP POLICY IF EXISTS "Anon dapat upload bukti bayar" ON storage.objects;
DROP POLICY IF EXISTS "Siapa saja boleh upload bukti bayar" ON storage.objects;

CREATE POLICY "Siapa saja boleh upload bukti bayar"
  ON storage.objects
  FOR INSERT
  TO public
  WITH CHECK (bucket_id = 'payment-proofs');

-- 4. Izinkan siapa saja membaca (melihat) gambar di bucket payment-proofs
DROP POLICY IF EXISTS "Siapa saja boleh lihat bukti bayar" ON storage.objects;

CREATE POLICY "Siapa saja boleh lihat bukti bayar"
  ON storage.objects
  FOR SELECT
  TO public
  USING (bucket_id = 'payment-proofs');

