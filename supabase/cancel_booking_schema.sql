-- ============================================================
-- meimories.cam - Pembatalan Booking & Activity Logs
-- Jalankan file ini di Supabase Dashboard > SQL Editor
-- ============================================================

-- 1. Buat tabel activity_logs untuk menyimpan riwayat pembatalan
CREATE TABLE IF NOT EXISTS public.activity_logs (
  id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
  action text NOT NULL,
  details text NOT NULL,
  created_at timestamptz DEFAULT now()
);

-- Aktifkan RLS untuk activity_logs
ALTER TABLE public.activity_logs ENABLE ROW LEVEL SECURITY;

-- Hanya admin yang boleh melihat dan menambah log
CREATE POLICY "Admin can read activity_logs" ON public.activity_logs FOR SELECT TO authenticated USING (true);
CREATE POLICY "Admin can insert activity_logs" ON public.activity_logs FOR INSERT TO authenticated WITH CHECK (true);

-- 2. Buat fungsi trigger untuk mencatat pembatalan booking otomatis
CREATE OR REPLACE FUNCTION log_booking_deletion()
RETURNS TRIGGER AS $$
BEGIN
  INSERT INTO public.activity_logs (action, details)
  VALUES (
    'BOOKING_CANCELLED',
    'Booking atas nama ' || OLD.name || ' (' || OLD.wa || ') pada ' || OLD.date || ' jam ' || OLD.time || ' untuk paket ' || OLD.package_name || ' telah dibatalkan/dihapus.'
  );
  RETURN OLD;
END;
$$ LANGUAGE plpgsql;

-- 3. Pasang trigger pada tabel bookings
DROP TRIGGER IF EXISTS trg_log_booking_deletion ON public.bookings;
CREATE TRIGGER trg_log_booking_deletion
AFTER DELETE ON public.bookings
FOR EACH ROW
EXECUTE FUNCTION log_booking_deletion();

-- 4. Tambahkan policy agar Admin bisa menghapus data bookings
-- Hapus policy jika sudah pernah ada, untuk menghindari error
DROP POLICY IF EXISTS "Admin can delete booking" ON public.bookings;

CREATE POLICY "Admin can delete booking"
  ON public.bookings
  FOR DELETE
  TO authenticated
  USING (true);
