import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  server: {
    proxy: {
      '/api': {
        target: 'https://meimoriescam.zedevio.com',
        changeOrigin: true,
      },
      '/uploads': {
        target: 'https://meimoriescam.zedevio.com',
        changeOrigin: true,
      },
    },
  },
});
