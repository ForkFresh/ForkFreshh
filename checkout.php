<?php
include 'db.php';
$user_id = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $street_address = $_POST['street_address'];
    $apartment_suite = $_POST['apartment_suite'] ?? null;
    $city = $_POST['city'];
    $country = $_POST['country'];
    $phone_number = $_POST['phone_number'];
    $delivery_time_type = $_POST['delivery_time_type'] ?? 'ASAP';
    $scheduled_date = $_POST['scheduled_date'] ?? null;
    $is_saved = isset($_POST['is_saved']) ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO addresses (user_id, street_address, apartment_suite, city, country, phone_number, is_saved) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssi", $user_id, $street_address, $apartment_suite, $city, $country, $phone_number, $is_saved);
    
    if ($stmt->execute()) {
        $address_id = $stmt->insert_id;
        header("Location: payment.php?address_id=" . $address_id);
        exit();
    }
}

// Fetch dynamic cart items and calculate totals from database
$cart_res = $conn->query("SELECT ci.quantity, p.price, p.name FROM cart_items ci JOIN products p ON ci.product_id = p.product_id WHERE ci.user_id = $user_id");
$subtotal = 0;
$total_items = 0;
$cart_items = [];
if ($cart_res) {
    while ($row = $cart_res->fetch_assoc()) {
        $item_total = $row['price'] * $row['quantity'];
        $subtotal += $item_total;
        $total_items += $row['quantity'];
        $cart_items[] = $row;
    }
}
$delivery_fee = 1000.00;
$grand_total = $subtotal > 0 ? $subtotal + $delivery_fee : 0.00;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Address - ForkFresh</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body { 
            background-color: #f7f5f0; 
            font-family: Arial, sans-serif; 
            color: #333; 
            margin: 0; 
            padding: 10px; 
        }
        .page-wrapper { 
            max-width: 1200px; 
            margin: 10px auto; 
            background: #fdfbf7; 
            border: 1px solid #e0d8c5; 
            border-radius: 10px; 
            padding: 20px; 
        }

        /* Header */
        .header-top { 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            margin-bottom: 5px; 
        }
        .logo { 
            font-size: 24px; 
            font-weight: bold; 
            color: #2e7d32; 
            text-transform: lowercase; 
        }
        .tagline {
            font-size: 11px; 
            color: #777; 
            margin-bottom: 15px;
        }

        /* Step Tracker */
        .step-tracker {
            display: flex; 
            gap: 20px; 
            margin-bottom: 25px; 
            border-bottom: 1px solid #ccc; 
            padding-bottom: 10px;
        }
        .step-item {
            font-weight: bold; 
            color: #555;
            font-size: 14px;
        }
        .step-item.active {
            color: #2e7d32; 
            border-bottom: 2px solid #2e7d32; 
            padding-bottom: 8px;
        }

        /* Main Content Grid */
        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
            align-items: start;
        }
        @media (min-width: 900px) {
            .checkout-grid {
                grid-template-columns: 1fr 350px;
            }
            .page-wrapper {
                padding: 30px;
                margin: 20px auto;
            }
        }

        /* Form Controls */
        .form-grid { 
            display: grid; 
            grid-template-columns: 1fr;
            gap: 15px; 
            margin-bottom: 15px; 
        }
        @media (min-width: 600px) {
            .form-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        .form-group { 
            margin-bottom: 5px; 
        }
        .form-group label { 
            display: block; 
            font-size: 13px; 
            margin-bottom: 5px; 
            color: #444; 
        }
        input[type="text"], input[type="tel"], select { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ccc; 
            border-radius: 6px; 
            background: #fff; 
            font-size: 14px; 
        }

        /* Phone Row */
        .phone-row {
            display: grid;
            grid-template-columns: 1fr 80px;
            gap: 10px;
            align-items: end;
            margin-bottom: 15px;
        }

        /* Delivery Time Options */
        .schedule-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin-top: 5px;
        }

        /* Order Summary */
        .order-summary-box { 
            background: #faf8f3; 
            border: 1px solid #e0d8c5; 
            padding: 20px; 
            border-radius: 8px; 
            width: 100%;
        }
        .summary-row { 
            display: flex; 
            justify-content: space-between; 
            margin-bottom: 10px; 
            font-size: 14px; 
            color: #555; 
        }
        .summary-row.total { 
            font-weight: bold; 
            color: #000; 
            font-size: 16px; 
            margin-top: 10px; 
        }
        .action-btn { 
            display: block; 
            width: 100%; 
            background: #2e7d32; 
            color: #fff; 
            text-align: center; 
            padding: 12px; 
            border-radius: 6px; 
            font-weight: bold; 
            border: none; 
            cursor: pointer; 
            margin-top: 20px; 
            text-decoration: none; 
        }
        .action-btn:hover { 
            background: #27692b; 
        }

        .promo-box {
            margin-top: 20px; 
            display: flex; 
            gap: 5px;
        }

        /* Footer Responsive Banner */
        .footer-banner {
            background: #10522c; 
            color: white; 
            padding: 25px 20px; 
            margin-top: 40px; 
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            border-radius: 6px; 
            font-size: 13px;
        }
        @media (min-width: 600px) {
            .footer-banner {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (min-width: 900px) {
            .footer-banner {
                grid-template-columns: 2fr repeat(4, 1fr);
            }
        }
    </style>
</head>
<body>
<div class="page-wrapper">
    <div class="header-top">
        <div class="logo">
            <a href="address.php" style="text-decoration: none; display: inline-flex; align-items: center;">
                <img src="IMG_0720.jpg" alt="ForkFresh Logo" style="height: 35px; width: auto;" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                <span style="display: none; align-items: center; gap: 8px; color: #2e7d32;">
                    <span style="background: #2e7d32; color: white; border-radius: 50%; width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; font-size: 14px;">🌱</span> 
                    forkfresh
                </span>
            </a>
        </div>
    </div>
    <div class="tagline">AFRICAN DELICACIES, DELIVERED FRESH</div>
    
    <!-- Step Tracker -->
    <div class="step-tracker">
        <div class="step-item active">1. Address</div>
        <div class="step-item">2. Payment</div>
        <div class="step-item">3. Review</div>
    </div>

    <div style="font-size: 20px; font-weight: bold; margin-bottom: 15px; color: #333;">Delivery Address</div>

    <!-- Main Layout -->
    <div class="checkout-grid">
        <div class="form-container">
            <?php if ($total_items == 0): ?>
                <div style="background: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 15px; border-radius: 6px; margin-bottom: 20px; font-size: 14px;">
                    Your cart is currently empty.
                </div>
            <?php endif; ?>

            <form action="payment.php" method="POST" id="addressForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label>First Name*</label>
                        <input type="text" name="first_name" required placeholder="First Name">
                    </div>
                    <div class="form-group">
                        <label>Last Name*</label>
                        <input type="text" name="last_name" required placeholder="Last Name">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Street Address*</label>
                        <input type="text" name="street_address" required placeholder="Street Address">
                    </div>
                    <div class="form-group">
                        <label>Apartment/Suite/Unit</label>
                        <input type="text" name="apartment_suite" placeholder="Apartment/Suite/Unit">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>City*</label>
                        <input type="text" name="city" required placeholder="City">
                    </div>
                    <div class="form-group">
                        <label style="color: #666; font-size: 12px;">Country</label>
                        <select name="country">
                            <option value="Cameroon">Cameroon</option>
                        </select>
                    </div>
                </div>

                <div class="phone-row">
                    <div>
                        <label style="display: block; font-size: 13px; margin-bottom: 5px; color: #444;">Phone Number</label>
                        <input type="tel" name="phone_number" required placeholder="Phone Number" maxlength="9" pattern="[0-9]{9}" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 9);" title="Please enter exactly 9 digits">
                    </div>
                    <div>
                        <input type="text" value="+237" disabled style="background: #e9ecef; text-align: center; font-weight: bold;">
                    </div>
                </div>

                <div style="margin: 20px 0;">
                    <label style="font-weight: bold; display: block; margin-bottom: 10px;">Delivery Time</label>
                    <div style="margin-bottom: 8px;">
                        <label><input type="radio" name="delivery_time_type" value="ASAP" checked> As soon as possible</label>
                    </div>
                    <div class="schedule-row">
                        <label><input type="radio" name="delivery_time_type" value="Schedule"> Schedule</label>
                        <select name="scheduled_date" style="width: auto; padding: 6px;">
                            <option value="Today, Oct 27">Today, Oct 27</option>
                            <option value="Tomorrow, Oct 28">Tomorrow, Oct 28</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 15px;">
                    <label><input type="checkbox" name="is_saved" value="1"> Save this address for future use</label>
                </div>
            </form>
        </div>

        <div class="order-summary-box">
            <h3 style="margin-top: 0; font-size: 18px; margin-bottom: 15px;">Order Summary</h3>
            
            <?php if ($total_items > 0): ?>
                <?php foreach ($cart_items as $ci): ?>
                    <div class="summary-row" style="font-size: 13px;">
                        <span><?php echo htmlspecialchars($ci['name']); ?> (x<?php echo $ci['quantity']; ?>)</span>
                        <span>FCFA <?php echo number_format($ci['price'] * $ci['quantity']); ?></span>
                    </div>
                <?php endforeach; ?>
                <hr style="border: none; border-top: 1px solid #ddd; margin: 10px 0;">
            <?php else: ?>
                <div class="summary-row" style="font-size: 13px; color: #888;">
                    <span>0 Items</span>
                    <span>FCFA 0</span>
                </div>
            <?php endif; ?>

            <div class="summary-row">
                <span>Delivery Fee</span>
                <span>FCFA <?php echo number_format($delivery_fee); ?></span>
            </div>
            <hr style="border: none; border-top: 1px solid #ddd; margin: 15px 0;">
            <div class="summary-row total">
                <span>Total</span>
                <span>FCFA <?php echo number_format($grand_total); ?></span>
            </div>
            
            <div class="promo-box">
                <input type="text" placeholder="Promo code (optional)" style="flex: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                <button type="button" style="padding: 8px 15px; background: #2e7d32; color: #fff; border: none; border-radius: 4px; cursor: pointer;">Apply</button>
            </div>

            <?php if ($total_items > 0): ?>
                <button type="submit" form="addressForm" class="action-btn">Proceed to Payment</button>
            <?php else: ?>
                <button type="button" class="action-btn" style="background: #ccc; cursor: not-allowed;" disabled>Cart is Empty</button>
            <?php endif; ?>
        </div>
    </div>
<?php include 'footer.php'; ?>
</div>
</body>
</html>-+