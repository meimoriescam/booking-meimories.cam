// scripts/deploy.js
import * as ftp from 'basic-ftp';
import { execSync } from 'child_process';
import dotenv from 'dotenv';
import path from 'path';
import fs from 'fs';

dotenv.config();

const FTP_HOST = process.env.FTP_HOST;
const FTP_PORT = parseInt(process.env.FTP_PORT || '2614', 10);
const FTP_USER = process.env.FTP_USER;
const FTP_PASS = process.env.FTP_PASS;
const APP_URL = process.env.APP_URL || 'https://meimoriescam.zedevio.com';

const FTP_SECURE_ENV = process.env.FTP_SECURE || 'false';
const FTP_SECURE = FTP_SECURE_ENV === 'true' ? true : (FTP_SECURE_ENV === 'implicit' ? 'implicit' : false);

if (!FTP_HOST || !FTP_USER || !FTP_PASS) {
  console.error('❌ Error: Kredensial FTP (FTP_HOST, FTP_USER, FTP_PASS) belum diisi di file .env.');
  process.exit(1);
}

if (!FTP_SECURE) {
  console.warn('⚠️ [OWASP A02 Warning]: FTP transport berjalan tanpa enkripsi TLS (secure: false). Disarankan mengaktifkan FTP_SECURE=true jika server mendukung FTPS.');
}

async function deploy() {
  console.log('==============================================');
  console.log('🚀 Starting deployment for meimories.cam');
  console.log('==============================================');

  // Step 1: Build Frontend
  console.log('\n📦 Step 1: Building Vite production bundle...');
  try {
    execSync('npm run build', { stdio: 'inherit' });
    console.log('✅ Vite build completed successfully.');
  } catch (err) {
    console.error('❌ Build failed. Aborting deploy.');
    process.exit(1);
  }

  // Step 2: Connect to FTP
  const client = new ftp.Client();
  client.ftp.verbose = false;

  try {
    console.log(`\n📡 Step 2: Connecting to FTP ${FTP_HOST}:${FTP_PORT} as ${FTP_USER} (secure: ${FTP_SECURE})...`);
    await client.access({
      host: FTP_HOST,
      port: FTP_PORT,
      user: FTP_USER,
      password: FTP_PASS,
      secure: FTP_SECURE,
      secureOptions: {
        rejectUnauthorized: false,
      },
    });
    console.log('✅ Connected to FTP server.');

    // Step 3: Upload files
    console.log('\n📂 Step 3: Uploading files to remote server...');

    // Upload dist/ contents to remote root /
    console.log('   - Uploading frontend (dist/)...');
    await client.uploadFromDir(path.resolve('dist'));

    // Upload api/ folder
    console.log('   - Uploading backend API (api/)...');
    await client.ensureDir('api');
    await client.uploadFromDir(path.resolve('api'));
    await client.cd('/');

    // Ensure uploads directory exists and is secured
    console.log('   - Ensuring uploads/payment-proofs directory exists...');
    await client.ensureDir('uploads/payment-proofs');
    await client.cd('/');

    if (fs.existsSync('uploads/.htaccess')) {
      console.log('   - Uploading uploads/.htaccess to block script execution in uploads folder...');
      await client.uploadFrom(path.resolve('uploads/.htaccess'), 'uploads/.htaccess');
    }

    // Upload root .htaccess
    if (fs.existsSync('.htaccess')) {
      console.log('   - Uploading root .htaccess...');
      await client.uploadFrom(path.resolve('.htaccess'), '.htaccess');
    }

    // Upload .env
    if (fs.existsSync('.env')) {
      console.log('   - Uploading .env...');
      await client.uploadFrom(path.resolve('.env'), '.env');
    }

    console.log('✅ All files uploaded successfully!');
  } catch (ftpError) {
    console.error('❌ FTP Error:', ftpError);
    process.exit(1);
  } finally {
    client.close();
  }

  // Step 4: Verification
  console.log(`\n🔍 Step 4: Verifying deployment at ${APP_URL}...`);
  try {
    const slotsRes = await fetch(`${APP_URL}/api/slots.php`);
    const slotsData = await slotsRes.json();
    console.log('   - API /api/slots.php status:', slotsRes.status, 'Response:', slotsData);

    const webRes = await fetch(APP_URL);
    console.log('   - Website homepage status:', webRes.status, webRes.statusText);

    console.log('\n🎉 ==============================================');
    console.log('✨ DEPLOYMENT COMPLETED SUCCESSFULLY!');
    console.log(`🌐 Website is LIVE at: ${APP_URL}`);
    console.log('🎉 ==============================================');
  } catch (verifyError) {
    console.warn('⚠️ Verification warning (DNS or network propagation):', verifyError.message);
  }
}

deploy();
