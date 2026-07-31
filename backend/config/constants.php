<?php

// config/constants.php  –  App-wide constants


// ── Base URL (adjust if you use a virtual host) 
define('BASE_URL', 'http://localhost/ForkFresh/backend');

// ── CORS allowed origin 
define('ALLOWED_ORIGIN', 'http://localhost');

// ── VAPID keys for Web Push  
// Generate your own with: https://vapidkeys.com/
// Or run in PHP:
//   $keys = \Minishlink\WebPush\VAPID::createVapidKeys();
// Replace the placeholders below with your real keys.
define('VAPID_SUBJECT',    'mailto:admin@forkfresh.com');
define('VAPID_PUBLIC_KEY', 'BEl62iUYgUivxIkv69yViEuiBIa-Ib9-SkvMeAtA3LFgDzkrxZJjSgSnfckjBJuBkr3qBUYIHBQFLXYp5Nksh8U');
define('VAPID_PRIVATE_KEY','UDF5XMRqKbxJ2ZLcxmBB7YXxhZ1N9JiT9FEf0N6Pgc4');

// ── Order status flow 
define('ORDER_STATUSES', [
    'pending',
    'assigned',
    'preparing',
    'on_the_way',
    'out_for_delivery',
    'delivered',
    'cancelled',
]);

// Human-readable labels shown in notifications
define('STATUS_LABELS', [
    'pending'          => 'Order Placed',
    'assigned'         => 'Rider Assigned',
    'preparing'        => 'Preparing Your Order',
    'on_the_way'       => 'Rider On The Way',
    'out_for_delivery' => 'Out for Delivery',
    'delivered'        => 'Order Delivered',
    'cancelled'        => 'Order Cancelled',
]);

// ── GPS tracking interval (seconds) 
define('GPS_INTERVAL_SEC', 10);

// ── Push notification titles 
define('PUSH_TITLE', 'ForkFresh 🍴');

// ── Token / Auth 
// Simple shared secret for rider API calls.
// In production replace with JWT / OAuth.
define('API_SECRET', 'forkfresh_dev_secret_2024');

// ── Timezone 
date_default_timezone_set('Africa/Douala');
