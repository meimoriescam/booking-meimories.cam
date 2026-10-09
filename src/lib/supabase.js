import { createClient } from '@supabase/supabase-js';

const rawUrl = (import.meta.env.VITE_SUPABASE_URL || '').trim();
// Otomatis bersihkan /rest/v1 atau slash di akhir jika pengguna salah menyalin dari dashboard Supabase
const supabaseUrl = rawUrl.replace(/\/rest\/v1\/?$/, '').replace(/\/+$/, '');
const supabaseAnonKey = (import.meta.env.VITE_SUPABASE_ANON_KEY || '').trim();

if (!supabaseUrl || !supabaseAnonKey) {
  // eslint-disable-next-line no-console
  console.warn(
    'Supabase belum dikonfigurasi. Buat file .env (lihat .env.example) berisi ' +
    'VITE_SUPABASE_URL dan VITE_SUPABASE_ANON_KEY dari project Supabase kamu.'
  );
}

export const supabase = createClient(supabaseUrl || '', supabaseAnonKey || '');
