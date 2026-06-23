<?php
require_once 'config.php';
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = __('Please login to check out.', 'Silakan masuk untuk bertransaksi.');
    header("Location: auth");
    exit();
}
if ($_SESSION['is_verified'] != 1) {
    $_SESSION['flash_message'] = __('Your account is not verified yet.', 'Maaf, akun Anda belum diverifikasi. Hanya pengguna yang sudah diverifikasi yang dapat melakukan transaksi.');
    header("Location: index");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$coupon_msg = '';
$coupon_err = '';


$cart_ids = isset($_SESSION['checkout_cart_ids']) ? $_SESSION['checkout_cart_ids'] : [];


if (!empty($cart_ids)) {
    $placeholders = implode(',', array_fill(0, count($cart_ids), '?'));
    $stmt = $conn->prepare("
        SELECT c.id as cart_id, c.quantity, m.id as motor_id, m.make, m.model, m.price, m.stock, m.discount_percent, m.discount_until
        FROM carts c
        JOIN motorcycles m ON c.motorcycle_id = m.id
        WHERE c.user_id = ? AND c.id IN ($placeholders)
    ");
    $stmt->bind_param(str_repeat('i', count($cart_ids) + 1), $user_id, ...$cart_ids);
} else {
    $stmt = $conn->prepare("
        SELECT c.id as cart_id, c.quantity, m.id as motor_id, m.make, m.model, m.price, m.stock, m.discount_percent, m.discount_until
        FROM carts c
        JOIN motorcycles m ON c.motorcycle_id = m.id
        WHERE c.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$cart_items = $stmt->get_result();

if ($cart_items->num_rows == 0) {
    $_SESSION['flash_message'] = __('Your cart is empty or selection is invalid.', 'Keranjang Anda kosong atau pilihan tidak valid.');
    header("Location: discover");
    exit();
}

$cart_data = [];
$subtotal = 0;
while($row = $cart_items->fetch_assoc()) {
    
    $is_discounted = false;
    $final_price = $row['price'];
    if ($row['discount_percent'] > 0 && ($row['discount_until'] === null || strtotime($row['discount_until']) > time())) {
        $is_discounted = true;
        $final_price = $row['price'] * (1 - ($row['discount_percent'] / 100));
    }
    
    $row['checkout_price'] = $final_price;
    $row['is_discounted'] = $is_discounted;
    $cart_data[] = $row;
    $subtotal += $final_price * $row['quantity'];
}


$shipping_res = $conn->query("SELECT * FROM shipping_methods ORDER BY base_cost ASC");
$shipping_methods = [];
while($sm = $shipping_res->fetch_assoc()) {
    $shipping_methods[] = $sm;
}


$dealers_res = $conn->query("
    SELECT d.id, d.name as dealer_name, d.address, c.name as city_name, p.name as province_name, p.id as province_id
    FROM dealers d
    JOIN cities c ON d.city_id = c.id
    JOIN provinces p ON c.province_id = p.id
    ORDER BY d.name ASC
");
$dealers = [];
while($dl = $dealers_res->fetch_assoc()) {
    $dealers[] = $dl;
}


$tax_res = $conn->query("SELECT province_id, percentage FROM tax_rates");
$tax_rates = [];
while($tr = $tax_res->fetch_assoc()) {
    $tax_rates[$tr['province_id']] = intval($tr['percentage']);
}


$payment_res = $conn->query("SELECT * FROM payment_methods ORDER BY method_name ASC");
$payment_methods = [];
while($pm = $payment_res->fetch_assoc()) {
    $payment_methods[] = $pm;
}


$coupon_code = isset($_POST['coupon_code']) ? trim($_POST['coupon_code']) : (isset($_SESSION['checkout_coupon_code']) ? $_SESSION['checkout_coupon_code'] : '');
$coupon_discount = 0;
$coupon_id = null;

if ($coupon_code !== '') {
    $c_stmt = $conn->prepare("SELECT id, percentage, usage_limit, used_count FROM discounts WHERE code = ? AND is_active = 1 AND valid_until >= NOW()");
    $c_stmt->bind_param("s", $coupon_code);
    $c_stmt->execute();
    $c_res = $c_stmt->get_result();
    if ($c_res->num_rows > 0) {
        $coupon = $c_res->fetch_assoc();
        if ($coupon['used_count'] < $coupon['usage_limit']) {
            $coupon_discount = intval($coupon['percentage']);
            $coupon_id = $coupon['id'];
            $_SESSION['checkout_coupon_code'] = $coupon_code;
            $_SESSION['checkout_coupon_discount'] = $coupon_discount;
            $_SESSION['checkout_coupon_id'] = $coupon_id;
            $coupon_msg = __('Coupon applied successfully!', 'Kode promo berhasil digunakan!');
        } else {
            $coupon_err = __('Coupon usage limit has been reached.', 'Batas pemakaian kode promo telah habis.');
            unset($_SESSION['checkout_coupon_code'], $_SESSION['checkout_coupon_discount'], $_SESSION['checkout_coupon_id']);
            $coupon_code = '';
        }
    } else {
        $coupon_err = __('Invalid or expired coupon code.', 'Kode promo tidak valid atau sudah kedaluwarsa.');
        unset($_SESSION['checkout_coupon_code'], $_SESSION['checkout_coupon_discount'], $_SESSION['checkout_coupon_id']);
        $coupon_code = '';
    }
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['checkout'])) {
    $type = $_POST['type']; 
    $shipping_id = intval($_POST['shipping_method_id']);
    $dealer_id = intval($_POST['dealer_id']);
    $payment_id = intval($_POST['payment_method_id']);
    
    
    $stock_ok = true;
    foreach ($cart_data as $item) {
        if ($item['quantity'] > $item['stock']) {
            $stock_ok = false;
            $error = sprintf(__('Stock for %s %s is insufficient.', 'Stok untuk %s %s tidak mencukupi.'), $item['make'], $item['model']);
            break;
        }
    }
    
    if ($stock_ok) {
        
        $ship_stmt = $conn->prepare("SELECT base_cost FROM shipping_methods WHERE id = ?");
        $ship_stmt->bind_param("i", $shipping_id);
        $ship_stmt->execute();
        $ship_cost = $ship_stmt->get_result()->fetch_assoc()['base_cost'];
        
        
        $dl_stmt = $conn->prepare("
            SELECT p.id as province_id 
            FROM dealers d 
            JOIN cities c ON d.city_id = c.id 
            JOIN provinces p ON c.province_id = p.id 
            WHERE d.id = ?
        ");
        $dl_stmt->bind_param("i", $dealer_id);
        $dl_stmt->execute();
        $prov_id = $dl_stmt->get_result()->fetch_assoc()['province_id'];
        $tax_pct = isset($tax_rates[$prov_id]) ? $tax_rates[$prov_id] : 0;
        
        
        $calc_stmt = $conn->prepare("SELECT calculate_final_price(?, ?, ?) AS final_total");
        if ($calc_stmt) {
            $calc_stmt->bind_param("dii", $subtotal, $coupon_discount, $tax_pct);
            $calc_stmt->execute();
            $calc_res = $calc_stmt->get_result();
            if ($calc_res && $row = $calc_res->fetch_assoc()) {
                $final_computed_total = floatval($row['final_total']) + $ship_cost;
            } else {
                $discount_amount = $subtotal * ($coupon_discount / 100);
                $tax_amount = ($subtotal - $discount_amount) * ($tax_pct / 100);
                $final_computed_total = ($subtotal - $discount_amount + $tax_amount) + $ship_cost;
            }
            $calc_stmt->close();
        } else {
            $discount_amount = $subtotal * ($coupon_discount / 100);
            $tax_amount = ($subtotal - $discount_amount) * ($tax_pct / 100);
            $final_computed_total = ($subtotal - $discount_amount + $tax_amount) + $ship_cost;
        }
        
        
        $conn->begin_transaction();
        try {
            $t_stmt = $conn->prepare("INSERT INTO transactions (user_id, motorcycle_id, quantity, type, payment_status, status) VALUES (?, ?, ?, ?, 'unpaid', 'pending')");
            $u_stmt = $conn->prepare("UPDATE motorcycles SET stock = stock - ? WHERE id = ?");
            $c_stmt = $conn->prepare("DELETE FROM carts WHERE id = ? AND user_id = ?");
            
            $items_count = 0;
            foreach ($cart_data as $item) {
                $t_stmt->bind_param("iiis", $user_id, $item['motor_id'], $item['quantity'], $type);
                $t_stmt->execute();
                $trx_id = $conn->insert_id;
                
                
                $conn->query("INSERT INTO notifications (user_id, message, link, icon, color, bg) VALUES ($user_id, 'Awaiting payment: #TX-$trx_id || Menunggu pembayaran: #TX-$trx_id', 'payment?id=$trx_id', 'warning', 'text-orange-500', 'bg-orange-100')");
                $conn->query("INSERT INTO notifications (target_role, message, link, icon, color, bg) VALUES ('admin', 'New order: #TX-$trx_id || Pesanan baru: #TX-$trx_id', 'admin?page=transactions', 'receipt_long', 'text-blue-500', 'bg-blue-100')");
                
                
                $u_stmt->bind_param("ii", $item['quantity'], $item['motor_id']);
                $u_stmt->execute();
                
                
                $c_stmt->bind_param("ii", $item['cart_id'], $user_id);
                $c_stmt->execute();
                
                $items_count++;
            }
            
            
            if ($coupon_id !== null) {
                $conn->query("UPDATE discounts SET used_count = used_count + 1 WHERE id = $coupon_id");
            }
            
            log_action($conn, $user_id, "Checked out cart ($type) with $items_count item(s). || Melakukan checkout keranjang ($type) sebanyak $items_count item.");
            $conn->commit();
            
            
            unset($_SESSION['checkout_cart_ids'], $_SESSION['checkout_coupon_code'], $_SESSION['checkout_coupon_discount'], $_SESSION['checkout_coupon_id']);
            
            $_SESSION['flash_message'] = __('Checkout successful! Please complete your payment.', 'Checkout berhasil diproses! Silakan selesaikan pembayaran Anda.');
            header("Location: history");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = __('Database transaction failed: ', 'Transaksi database gagal: ') . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>MotoInfy | <?= __('Secure Checkout', 'Pembayaran Aman') ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <?php include 'theme_config.php'; ?>
</head>
<body class="bg-background text-on-surface flex flex-col min-h-screen pb-16 md:pb-0">
    <?php include 'header.php'; ?>
    
    <main class="w-full flex-grow max-w-[1280px] mx-auto px-4 md:px-8 py-12">
        <div class="mb-10 text-center max-w-2xl mx-auto">
            <h1 class="text-4xl font-extrabold text-slate-900"><?= __('Secure Checkout', 'Pembayaran Aman') ?></h1>
            <p class="text-slate-500 dark:text-slate-400 mt-2"><?= __('Complete your order details below to secure your motorcycle.', 'Lengkapi rincian pesanan Anda di bawah untuk mengamankan motor Anda.') ?></p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="max-w-4xl mx-auto mb-6 bg-red-50 text-red-800 border border-red-200 px-6 py-4 rounded-xl font-bold text-sm flex items-center gap-3">
                <span class="material-symbols-outlined text-red-600">error</span>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="max-w-5xl mx-auto flex flex-col lg:flex-row gap-8">
            
            <div class="w-full lg:w-2/3 space-y-6">
                
                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 shadow-sm">
                    <h3 class="text-xl font-bold text-slate-900 mb-6 border-b pb-4 flex justify-between">
                        <span><?= __('Order Items', 'Barang Pesanan') ?></span>
                        <span class="text-sm font-medium text-slate-500">(<?= count($cart_data) ?>)</span>
                    </h3>
                    <div class="space-y-4">
                        <?php foreach($cart_data as $item): ?>
                        <div class="flex items-center justify-between border-b border-slate-50 dark:border-slate-800/40 pb-4 last:border-0 last:pb-0">
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 bg-surface-container rounded-lg flex items-center justify-center font-bold text-slate-400 uppercase text-xs">
                                    <?= substr(htmlspecialchars($item['make']), 0, 3) ?>
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-900"><?= htmlspecialchars($item['make'] . ' ' . $item['model']) ?></h4>
                                    <p class="text-xs text-slate-500"><?= $item['quantity'] ?> Units x Rp <?= number_format($item['checkout_price'], 0, ',', '.') ?></p>
                                </div>
                            </div>
                            <span class="font-bold text-secondary">Rp <?= number_format($item['checkout_price'] * $item['quantity'], 0, ',', '.') ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                
                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 shadow-sm">
                    <h3 class="text-xl font-bold text-slate-900 mb-6 border-b pb-4"><?= __('Delivery & Pickup Options', 'Opsi Pengiriman & Cabang') ?></h3>
                    
                    <form id="checkoutMainForm" method="POST" action="checkout" class="space-y-6" onsubmit="return handleMainCheckoutSubmit(event)">
                        
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-3"><?= __('Transaction Type', 'Tipe Transaksi') ?></label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <label class="flex items-start gap-3 p-4 border border-outline-variant rounded-xl cursor-pointer hover:bg-surface-container-low transition-colors">
                                    <input type="radio" name="type" value="booking" onchange="calculateBilling()" required class="mt-1 text-secondary focus:ring-secondary">
                                    <div>
                                        <span class="block font-bold text-slate-900">Booking / DP</span>
                                        <span class="block text-xs text-slate-500 mt-1"><?= __('Pay down payment, settle remainder at dealership.', 'Amankan stok dengan uang muka, pelunasan di dealer.') ?></span>
                                    </div>
                                </label>
                                <label class="flex items-start gap-3 p-4 border border-outline-variant rounded-xl cursor-pointer hover:bg-surface-container-low transition-colors">
                                    <input type="radio" name="type" value="buy" checked onchange="calculateBilling()" required class="mt-1 text-secondary focus:ring-secondary">
                                    <div>
                                        <span class="block font-bold text-slate-900"><?= __('Cash Payment', 'Beli Langsung (Cash)') ?></span>
                                        <span class="block text-xs text-slate-500 mt-1"><?= __('Settle the full invoice amount securely via VA.', 'Bayar lunas melalui transfer / Virtual Account.') ?></span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2"><?= __('Dealership Location', 'Pilih Lokasi Dealer') ?></label>
                            <select name="dealer_id" id="dealer_id" onchange="updateTaxAndDealer()" class="w-full border border-outline-variant rounded-lg p-3 bg-surface-container text-on-surface focus:ring-secondary focus:border-secondary">
                                <?php foreach($dealers as $dl): ?>
                                    <option value="<?= $dl['id'] ?>" data-province-id="<?= $dl['province_id'] ?>">
                                        <?= htmlspecialchars($dl['dealer_name']) ?> (<?= htmlspecialchars($dl['city_name']) ?>, <?= htmlspecialchars($dl['province_name']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2"><?= __('Shipping Method', 'Metode Pengiriman') ?></label>
                            <select name="shipping_method_id" id="shipping_method_id" onchange="updateShippingCost()" class="w-full border border-outline-variant rounded-lg p-3 bg-surface-container text-on-surface focus:ring-secondary focus:border-secondary">
                                <?php foreach($shipping_methods as $sm): ?>
                                    <option value="<?= $sm['id'] ?>" data-cost="<?= $sm['base_cost'] ?>">
                                        <?= htmlspecialchars($sm['method_name']) ?> (Rp <?= number_format($sm['base_cost'], 0, ',', '.') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2"><?= __('Payment Method', 'Metode Pembayaran') ?></label>
                            <select name="payment_method_id" class="w-full border border-outline-variant rounded-lg p-3 bg-surface-container text-on-surface focus:ring-secondary focus:border-secondary">
                                <?php foreach($payment_methods as $pm): ?>
                                    <option value="<?= $pm['id'] ?>"><?= htmlspecialchars($pm['method_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <input type="hidden" name="checkout" value="1">
                    </form>
                </div>
            </div>

            
            <div class="w-full lg:w-1/3">
                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 sticky top-24 shadow-sm space-y-6">
                    <h3 class="text-xl font-bold text-slate-900 border-b pb-4"><?= __('Billing Details', 'Rincian Pembayaran') ?></h3>
                    
                    
                    <form method="POST" action="checkout" class="flex gap-2">
                        <input type="text" name="coupon_code" value="<?= htmlspecialchars($coupon_code) ?>" placeholder="<?= __('Promo Code...', 'Kode Promo...') ?>" class="flex-grow p-2.5 text-xs border border-outline-variant rounded-lg bg-surface-container outline-none focus:ring-1 focus:ring-secondary">
                        <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white font-bold px-4 py-2 text-xs rounded-lg transition-colors">
                            Apply
                        </button>
                    </form>

                    <?php if ($coupon_msg): ?>
                        <p class="text-xs text-green-600 font-bold"><?= $coupon_msg ?></p>
                    <?php endif; ?>
                    <?php if ($coupon_err): ?>
                        <p class="text-xs text-red-500 font-bold"><?= $coupon_err ?></p>
                    <?php endif; ?>

                    
                    <div class="space-y-3 pt-2 text-sm text-slate-600 dark:text-slate-400">
                        <div class="flex justify-between">
                            <span><?= __('Subtotal', 'Subtotal') ?></span>
                            <span class="font-bold text-slate-900">Rp <?= number_format($subtotal, 0, ',', '.') ?></span>
                        </div>
                        
                        <?php if ($coupon_discount > 0): ?>
                            <div class="flex justify-between text-green-600 font-bold">
                                <span><?= __('Coupon Promo', 'Promo Kupon') ?> (<?= $coupon_discount ?>%)</span>
                                <span>-Rp <?= number_format($subtotal * ($coupon_discount / 100), 0, ',', '.') ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="flex justify-between">
                            <span><?= __('Shipping Cost', 'Biaya Pengiriman') ?></span>
                            <span id="label-shipping-cost" class="font-bold text-slate-900">Rp 0</span>
                        </div>

                        <div class="flex justify-between">
                            <span><?= __('Tax', 'Pajak PPn') ?> (<span id="label-tax-pct">0</span>%)</span>
                            <span id="label-tax-amount" class="font-bold text-slate-900">Rp 0</span>
                        </div>

                        <div class="flex justify-between text-xl text-slate-900 font-extrabold pt-4 border-t border-outline-variant mt-4">
                            <span>Total</span>
                            <span id="label-final-total" class="text-secondary">Rp 0</span>
                        </div>
                        
                        <div id="booking-breakdown" class="hidden border-t border-dashed border-outline-variant pt-3 space-y-2 text-xs">
                            <div class="flex justify-between font-bold text-slate-900">
                                <span><?= __('Pay Now (DP 20%)', 'Bayar Sekarang (DP 20%)') ?></span>
                                <span id="label-dp-amount" class="text-emerald-600">Rp 0</span>
                            </div>
                            <div class="flex justify-between text-slate-500">
                                <span><?= __('Remaining Balance at Dealership (80%)', 'Sisa Pelunasan di Dealer (80%)') ?></span>
                                <span id="label-remainder-amount">Rp 0</span>
                            </div>
                        </div>
                    </div>

                    
                    <button type="button" onclick="triggerCheckoutModal()" class="w-full flex items-center justify-center gap-2 bg-secondary text-white py-3.5 rounded-lg font-bold text-lg hover:bg-opacity-90 transition-all shadow-lg">
                        <span><?= __('Pay & Complete Order', 'Bayar & Selesaikan') ?></span>
                        <span class="material-symbols-outlined text-[20px]">verified_user</span>
                    </button>
                </div>
            </div>
        </div>

        
        <div id="checkoutConfirmModal" class="fixed inset-0 bg-black/50 z-[9999] hidden flex items-center justify-center p-4">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-2xl max-w-md w-full">
                <div class="flex items-start gap-4 mb-4">
                    <span class="material-symbols-outlined text-amber-500 text-4xl">warning</span>
                    <div>
                        <h3 class="text-xl font-bold mb-1"><?= __('Confirm Purchase', 'Konfirmasi Pembelian') ?></h3>
                        <p class="text-sm text-slate-500 leading-relaxed"><?= __('Are you sure you want to purchase these items? This action will process stock reductions and coupons immediately.', 'Apakah Anda yakin ingin memproses pembelian ini? Stok motor dan kuota kupon Anda akan langsung dikurangi.') ?></p>
                    </div>
                </div>
                <div class="flex gap-3 justify-end pt-2">
                    <button type="button" onclick="closeConfirmModal()" class="px-5 py-2.5 border border-outline-variant rounded-xl font-bold text-sm hover:bg-surface-container transition-colors">
                        <?= __('No, Go Back', 'Batal') ?>
                    </button>
                    
                    <button type="button" id="btn-confirm-yes" disabled class="px-6 py-2.5 bg-secondary text-white rounded-xl font-bold text-sm disabled:bg-slate-400 disabled:cursor-not-allowed transition-all">
                        <?= __('Yes', 'Ya') ?> (5)
                    </button>
                </div>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <script>
    const subtotal = <?= $subtotal ?>;
    const couponPct = <?= $coupon_discount ?>;
    const taxRates = <?= json_encode($tax_rates) ?>;
    
    function updateShippingCost() {
        const select = document.getElementById('shipping_method_id');
        const activeOption = select.options[select.selectedIndex];
        return parseFloat(activeOption.dataset.cost || 0);
    }

    function updateTaxAndDealer() {
        const select = document.getElementById('dealer_id');
        const activeOption = select.options[select.selectedIndex];
        const provinceId = parseInt(activeOption.dataset.provinceId || 0);
        return taxRates[provinceId] || 0;
    }

    function calculateBilling() {
        const shipCost = updateShippingCost();
        const taxPct = updateTaxAndDealer();
        
        
        const discountAmount = subtotal * (couponPct / 100);
        const priceAfterDiscount = subtotal - discountAmount;
        
        
        const taxAmount = priceAfterDiscount * (taxPct / 100);
        const finalTotal = priceAfterDiscount + taxAmount + shipCost;

        
        const formatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });
        
        document.getElementById('label-shipping-cost').innerText = formatter.format(shipCost).replace('IDR', 'Rp').trim();
        document.getElementById('label-tax-pct').innerText = taxPct;
        document.getElementById('label-tax-amount').innerText = formatter.format(taxAmount).replace('IDR', 'Rp').trim();
        document.getElementById('label-final-total').innerText = formatter.format(finalTotal).replace('IDR', 'Rp').trim();
        
        
        const typeEl = document.querySelector('input[name="type"]:checked');
        const isBooking = typeEl && typeEl.value === 'booking';
        if (isBooking) {
            const dpAmount = finalTotal * 0.20;
            const remainderAmount = finalTotal * 0.80;
            document.getElementById('label-dp-amount').innerText = formatter.format(dpAmount).replace('IDR', 'Rp').trim();
            document.getElementById('label-remainder-amount').innerText = formatter.format(remainderAmount).replace('IDR', 'Rp').trim();
            document.getElementById('booking-breakdown').classList.remove('hidden');
        } else {
            document.getElementById('booking-breakdown').classList.add('hidden');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        calculateBilling();
    });

    
    let countdownInterval;
    function triggerCheckoutModal() {
        const modal = document.getElementById('checkoutConfirmModal');
        const yesBtn = document.getElementById('btn-confirm-yes');
        
        
        modal.classList.remove('hidden');
        
        
        let timeLeft = 5;
        yesBtn.disabled = true;
        yesBtn.innerText = '<?= __('Yes', 'Ya') ?> (' + timeLeft + ')';
        
        clearInterval(countdownInterval);
        countdownInterval = setInterval(() => {
            timeLeft--;
            if (timeLeft > 0) {
                yesBtn.innerText = '<?= __('Yes', 'Ya') ?> (' + timeLeft + ')';
            } else {
                clearInterval(countdownInterval);
                yesBtn.disabled = false;
                yesBtn.innerText = '<?= __('Yes, Place Order', 'Ya, Kirim Pesanan') ?>';
                yesBtn.onclick = function() {
                    document.getElementById('checkoutMainForm').submit();
                };
            }
        }, 1000);
    }

    function closeConfirmModal() {
        document.getElementById('checkoutConfirmModal').classList.add('hidden');
        clearInterval(countdownInterval);
    }
    
    function handleMainCheckoutSubmit(e) {
        
        return true;
    }
    </script>
</body>
</html>
