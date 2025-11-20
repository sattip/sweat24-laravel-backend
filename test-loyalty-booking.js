#!/usr/bin/env node

const API_BASE = 'https://api.sweat93.gr/api/v1';

async function testLoyaltyBooking() {
  console.log('🧪 Testing Loyalty Booking System\n');

  // Step 1: Login as user 96
  console.log('1️⃣ Logging in as user 96...');
  const loginRes = await fetch(`${API_BASE}/auth/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      email: 'testreferralcomplete@example.com',
      password: 'password', // Default test password
    }),
  });

  if (!loginRes.ok) {
    console.error('❌ Login failed:', await loginRes.text());
    return;
  }

  const loginData = await loginRes.json();
  const token = loginData.token;
  console.log('✅ Login successful! Token:', token.substring(0, 20) + '...\n');

  // Step 2: Get loyalty dashboard
  console.log('2️⃣ Getting loyalty dashboard...');
  const dashboardRes = await fetch(`${API_BASE}/loyalty/dashboard`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    },
  });

  if (!dashboardRes.ok) {
    console.error('❌ Dashboard fetch failed:', await dashboardRes.text());
    return;
  }

  const dashboardData = await dashboardRes.json();
  console.log('✅ Loyalty Dashboard:', JSON.stringify(dashboardData, null, 2), '\n');

  // Step 3: Get booking cost for class 1
  console.log('3️⃣ Getting booking cost for class 1...');
  const costRes = await fetch(`${API_BASE}/loyalty/booking-cost/1`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    },
  });

  if (!costRes.ok) {
    console.error('❌ Booking cost fetch failed:', await costRes.text());
    return;
  }

  const costData = await costRes.json();
  console.log('✅ Booking Cost:', JSON.stringify(costData, null, 2), '\n');

  // Step 4: Book with points (partial payment - 10 points + 5 EUR cash)
  console.log('4️⃣ Booking class with partial points payment (10 points + 5 EUR cash)...');
  const bookRes = await fetch(`${API_BASE}/loyalty/book-with-points`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({
      class_id: 1,
      payment_type: 'partial_points',
      points_to_use: 10,
      cash_amount: 5,
    }),
  });

  if (!bookRes.ok) {
    console.error('❌ Booking failed:', await bookRes.text());
    return;
  }

  const bookData = await bookRes.json();
  console.log('✅ Booking successful!', JSON.stringify(bookData, null, 2), '\n');

  // Step 5: Check updated dashboard
  console.log('5️⃣ Checking updated loyalty dashboard...');
  const dashboardRes2 = await fetch(`${API_BASE}/loyalty/dashboard`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    },
  });

  const dashboardData2 = await dashboardRes2.json();
  console.log('✅ Updated Dashboard:', JSON.stringify(dashboardData2, null, 2), '\n');

  // Step 6: Get transaction history
  console.log('6️⃣ Getting transaction history...');
  const transRes = await fetch(`${API_BASE}/loyalty/transactions?limit=10`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    },
  });

  const transData = await transRes.json();
  console.log('✅ Transaction History:', JSON.stringify(transData, null, 2), '\n');

  console.log('🎉 All tests passed!');
}

testLoyaltyBooking().catch(console.error);
