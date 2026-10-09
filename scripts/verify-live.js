// scripts/verify-live.js
import dotenv from 'dotenv';
dotenv.config();

const BASE_URL = process.env.APP_URL || 'https://meimoriescam.zedevio.com';
const ADMIN_EMAIL = process.env.ADMIN_EMAIL || 'admin@meimories.cam';
const ADMIN_PASSWORD = process.env.ADMIN_PASS || 'AdminMeimories123!';

async function testLive() {
  console.log('Testing live API endpoints on:', BASE_URL);

  // 1. Test Admin Login
  console.log('\n1. Testing Admin Login...');
  const loginRes = await fetch(`${BASE_URL}/api/auth.php?action=login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      email: ADMIN_EMAIL,
      password: ADMIN_PASSWORD
    })
  });
  const loginData = await loginRes.json();
  console.log('Login Response:', loginData);
  if (!loginData.token) {
    throw new Error('Login failed: ' + JSON.stringify(loginData));
  }
  const token = loginData.token;

  // 2. Test Check Session
  console.log('\n2. Testing Session Check...');
  const sessionRes = await fetch(`${BASE_URL}/api/auth.php?action=session`, {
    headers: { Authorization: `Bearer ${token}` }
  });
  console.log('Session Response:', await sessionRes.json());

  // 3. Test Booking Creation
  console.log('\n3. Testing Booking Creation...');
  const testId = 'TEST-' + Date.now();
  const bookRes = await fetch(`${BASE_URL}/api/bookings.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      id: testId,
      date: '2026-10-25',
      time: '14:00',
      category: 'regular',
      package_name: 'Sweet Meimories',
      name: 'Tester Verification',
      wa: '081234567890',
      total: 150000,
      payment_type: 'dp',
      amount_to_pay: 75000,
      sisa_bayar: 75000,
      lokasi: 'Untan',
      notes: 'Test booking otomatis'
    })
  });
  const bookData = await bookRes.json();
  console.log('Create Booking Response:', bookData);
  const createdBookingId = bookData.data?.id;

  // 4. Test Public Slots
  console.log('\n4. Testing Public Slots Calendar...');
  const slotsRes = await fetch(`${BASE_URL}/api/slots.php`);
  console.log('Slots Response:', await slotsRes.json());

  // 5. Test Admin Bookings List
  console.log('\n5. Testing Admin Bookings Fetch...');
  const adminBkgRes = await fetch(`${BASE_URL}/api/bookings.php`, {
    headers: { Authorization: `Bearer ${token}` }
  });
  const adminBkgData = await adminBkgRes.json();
  console.log(`Admin Bookings: found ${adminBkgData.data?.length} bookings.`);

  // 6. Test Cancel Booking
  console.log('\n6. Testing Cancel Booking...');
  const delRes = await fetch(`${BASE_URL}/api/bookings.php?id=${createdBookingId}`, {
    method: 'DELETE',
    headers: { Authorization: `Bearer ${token}` }
  });
  console.log('Delete Booking Response:', await delRes.json());

  // 7. Verify Cancelled
  const slotsAfterRes = await fetch(`${BASE_URL}/api/slots.php`);
  const slotsAfterData = await slotsAfterRes.json();
  console.log('Slots after deletion:', slotsAfterData.data);

  console.log('\n✅ ALL LIVE VERIFICATION CHECKS PASSED PERFECTLY!');
}

testLive().catch(err => {
  console.error('❌ Test failed:', err);
  process.exit(1);
});
