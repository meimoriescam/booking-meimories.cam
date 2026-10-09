import React, { useState, useEffect } from 'react';
import { supabase } from './lib/supabase';
import { Lock, LogOut, Settings, Package, Image as ImageIcon, LayoutDashboard } from 'lucide-react';

export default function SuperAdmin() {
  const [session, setSession] = useState(null);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [activeTab, setActiveTab] = useState('dashboard');

  useEffect(() => {
    supabase.auth.getSession().then(({ data: { session } }) => setSession(session));
    const { data: { subscription } } = supabase.auth.onAuthStateChange((_event, session) => setSession(session));
    return () => subscription.unsubscribe();
  }, []);

  const handleLogin = async (e) => {
    e.preventDefault();
    setLoading(true);
    const { error } = await supabase.auth.signInWithPassword({ email, password });
    if (error) {
      alert(error.message);
    } else {
      // Hapus param ?superadmin=1 dari tampilan URL agar lebih bersih (opsional)
      // window.history.replaceState({}, document.title, window.location.pathname);
    }
    setLoading(false);
  };

  const handleLogout = async () => {
    await supabase.auth.signOut();
  };

  // --- KOMPONEN LOGIN ---
  if (!session) {
    return (
      <div className="min-h-screen bg-gray-50 flex flex-col justify-center items-center p-4">
        <div className="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 border border-pink-100">
          <Lock className="mx-auto text-[#a32e52] mb-4" size={40} strokeWidth={1.5} />
          <h1 className="text-3xl font-script text-[#a32e52] text-center mb-2">Super Admin</h1>
          <p className="text-sm text-center text-gray-500 mb-6">Login untuk mengelola seluruh konten website</p>
          
          <form onSubmit={handleLogin} className="space-y-4">
            <input 
              type="email" 
              value={email} 
              onChange={e => setEmail(e.target.value)} 
              placeholder="Email Admin" 
              className="w-full border border-gray-200 p-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#a32e52]" 
              required
            />
            <input 
              type="password" 
              value={password} 
              onChange={e => setPassword(e.target.value)} 
              placeholder="Password" 
              className="w-full border border-gray-200 p-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#a32e52]" 
              required
            />
            <button 
              disabled={loading} 
              className="w-full bg-[#a32e52] hover:bg-[#8a2544] text-white p-3 rounded-lg font-medium transition-colors"
            >
              {loading ? 'Memeriksa...' : 'Masuk Dashboard'}
            </button>
          </form>
          
          <div className="mt-6 text-center">
             <a href="/" className="text-sm text-[#a3748a] hover:underline">← Kembali ke Website</a>
          </div>
        </div>
      </div>
    );
  }

  // --- KOMPONEN DASHBOARD ---
  return (
    <div className="min-h-screen bg-gray-50 flex">
      {/* Sidebar Navigation */}
      <div className="w-64 bg-white border-r border-gray-200 shadow-sm flex flex-col">
        <div className="p-6 border-b border-gray-100 text-center">
          <h2 className="font-script text-3xl text-[#a32e52]">Super Admin</h2>
          <p className="text-xs text-gray-500 mt-1 truncate">{session.user.email}</p>
        </div>
        
        <nav className="flex-1 p-4 space-y-2 overflow-y-auto">
          <button 
            onClick={() => setActiveTab('dashboard')} 
            className={`flex items-center space-x-3 w-full p-3 rounded-lg transition-colors ${activeTab === 'dashboard' ? 'bg-pink-50 text-[#a32e52] font-medium' : 'text-gray-600 hover:bg-gray-50'}`}
          >
            <LayoutDashboard size={20} /> <span>Dashboard</span>
          </button>
          
          <button 
            onClick={() => setActiveTab('packages')} 
            className={`flex items-center space-x-3 w-full p-3 rounded-lg transition-colors ${activeTab === 'packages' ? 'bg-pink-50 text-[#a32e52] font-medium' : 'text-gray-600 hover:bg-gray-50'}`}
          >
            <Package size={20} /> <span>Kelola Paket</span>
          </button>
          
          <button 
            onClick={() => setActiveTab('gallery')} 
            className={`flex items-center space-x-3 w-full p-3 rounded-lg transition-colors ${activeTab === 'gallery' ? 'bg-pink-50 text-[#a32e52] font-medium' : 'text-gray-600 hover:bg-gray-50'}`}
          >
            <ImageIcon size={20} /> <span>Galeri Foto</span>
          </button>
          
          <button 
            onClick={() => setActiveTab('settings')} 
            className={`flex items-center space-x-3 w-full p-3 rounded-lg transition-colors ${activeTab === 'settings' ? 'bg-pink-50 text-[#a32e52] font-medium' : 'text-gray-600 hover:bg-gray-50'}`}
          >
            <Settings size={20} /> <span>Pengaturan Web</span>
          </button>
        </nav>
        
        <div className="p-4 border-t border-gray-100">
          <button 
            onClick={handleLogout} 
            className="flex items-center justify-center space-x-2 w-full p-3 rounded-lg text-red-600 hover:bg-red-50 transition-colors"
          >
            <LogOut size={20} /> <span>Logout</span>
          </button>
        </div>
      </div>

      {/* Main Content Area */}
      <div className="flex-1 overflow-auto">
        <header className="bg-white border-b border-gray-200 p-6 shadow-sm">
          <div className="flex justify-between items-center">
            <h1 className="text-2xl font-semibold text-gray-800 capitalize">
              {activeTab === 'packages' ? 'Kelola Harga & Paket' : 
               activeTab === 'gallery' ? 'Galeri Website' : 
               activeTab === 'settings' ? 'Pengaturan Umum' : 'Dashboard Utama'}
            </h1>
            <a href="/" target="_blank" rel="noreferrer" className="text-sm font-medium text-[#a32e52] hover:underline bg-pink-50 px-4 py-2 rounded-lg">
              Lihat Website ↗
            </a>
          </div>
        </header>

        <main className="p-8">
          {activeTab === 'dashboard' && (
            <div className="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
              <h3 className="text-xl font-bold mb-4">Selamat Datang di Super Admin! 🎉</h3>
              <p className="text-gray-600 mb-4">
                Struktur database baru telah berhasil dibuat. Saat ini Anda sedang melihat kerangka awal dari halaman pengelola konten (CMS).
              </p>
              <div className="p-4 bg-yellow-50 border border-yellow-200 rounded-lg text-yellow-800">
                <p className="font-medium">Tahap Selanjutnya:</p>
                <ul className="list-disc ml-5 mt-2 space-y-1 text-sm">
                  <li>Membuat form agar Anda bisa mulai menambah/mengedit data Paket di menu <b>Kelola Paket</b>.</li>
                  <li>Membuat form upload untuk mengganti foto di <b>Galeri</b>.</li>
                  <li>Mengubah frontend (website utama) agar berhenti menggunakan data statis dan mulai membaca data langsung dari database Supabase Anda yang baru.</li>
                </ul>
              </div>
            </div>
          )}

          {activeTab !== 'dashboard' && (
            <div className="flex flex-col items-center justify-center py-20 text-center">
              <Settings className="text-gray-300 mb-4 animate-spin-slow" size={48} />
              <h3 className="text-lg font-medium text-gray-800 mb-2">Modul Sedang Dibangun</h3>
              <p className="text-gray-500 max-w-md">
                Fitur untuk mengelola data ini akan segera kita implementasikan agar terhubung dengan database yang baru saja Anda buat.
              </p>
            </div>
          )}
        </main>
      </div>
    </div>
  );
}
