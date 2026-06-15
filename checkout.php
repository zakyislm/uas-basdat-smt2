<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'Silakan masuk untuk bertransaksi.';
    header("Location: auth");
    exit();
}

if ($_SESSION['is_verified'] != 1) {
    $_SESSION['flash_message'] = 'Maaf, akun Anda belum diverifikasi. Hanya pengguna yang sudah diverifikasi yang dapat melakukan transaksi.';
    header("Location: index");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';

$stmt = $conn->prepare("
    SELECT c.id as cart_id, c.quantity, m.id as motor_id, m.make, m.model, m.price, m.stock 
    FROM carts c
    JOIN motorcycles m ON c.motorcycle_id = m.id
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result();

if ($cart_items->num_rows == 0) {
    $_SESSION['flash_message'] = 'Keranjang Anda kosong. Silakan pilih motor terlebih dahulu.';
    header("Location: discover");
    exit();
}

$cart_data = [];
$grand_total = 0;
while($row = $cart_items->fetch_assoc()) {
    $cart_data[] = $row;
    $grand_total += $row['price'] * $row['quantity'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['checkout'])) {
    $type = $_POST['type']; 
    
    if ($type !== 'booking' && $type !== 'buy') {
        $error = "Tipe transaksi tidak valid.";
    } else {
        
        $stock_ok = true;
        foreach ($cart_data as $item) {
            if ($item['quantity'] > $item['stock']) {
                $stock_ok = false;
                $error = "Stok untuk " . $item['make'] . " " . $item['model'] . " tidak mencukupi (Sisa: " . $item['stock'] . ").";
                break;
            }
        }
        
        if ($stock_ok) {
            
            $t_stmt = $conn->prepare("INSERT INTO transactions (user_id, motorcycle_id, quantity, type) VALUES (?, ?, ?, ?)");
            $u_stmt = $conn->prepare("UPDATE motorcycles SET stock = stock - ? WHERE id = ?");
            $c_stmt = $conn->prepare("DELETE FROM carts WHERE id = ?");
            
            $items_count = 0;
            foreach ($cart_data as $item) {
                
                $t_stmt->bind_param("iiis", $user_id, $item['motor_id'], $item['quantity'], $type);
                $t_stmt->execute();
                $trx_id = $conn->insert_id;

                $conn->query("INSERT INTO notifications (user_id, message, link, icon, color, bg) VALUES ($user_id, 'Menunggu pembayaran: #TX-$trx_id', 'payment.php?id=$trx_id', 'warning', 'text-orange-500', 'bg-orange-100')");

                $conn->query("INSERT INTO notifications (target_role, message, link, icon, color, bg) VALUES ('admin', 'Pesanan baru: #TX-$trx_id', 'admin.php?page=transactions', 'receipt_long', 'text-blue-500', 'bg-blue-100')");

                $u_stmt->bind_param("ii", $item['quantity'], $item['motor_id']);
                $u_stmt->execute();

                $c_stmt->bind_param("i", $item['cart_id']);
                $c_stmt->execute();
                
                $items_count++;
            }
            
            log_action($conn, $user_id, "Melakukan checkout keranjang ($type) untuk $items_count macam barang.");
            $_SESSION['flash_message'] = "Checkout berhasil diproses! Barang Anda akan segera kami siapkan.";
            header("Location: history");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>MotoTrack Pro | Secure Checkout</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <style>
        body { font-family: 'Hanken Grotesk', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    </style>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "secondary": "#bb0112",
                        "background": "#f7f9fb",
                        "surface-container-lowest": "#ffffff",
                        "outline-variant": "#c6c6cd",
                        "on-surface": "#191c1e",
                        "on-surface-variant": "#45464d",
                    },
                },
            },
        }
    </script>
</head>

<body class="bg-background text-on-surface flex flex-col min-h-screen pb-16 md:pb-0">
    <?php include 'header.php'; ?>

    <main class="w-full flex-grow max-w-[1280px] mx-auto px-8 py-12">
        <div class="mb-10 text-center max-w-2xl mx-auto">
            <h1 class="text-4xl font-extrabold text-slate-900">Secure Checkout</h1>
            <p class="text-slate-500 mt-2">Selesaikan pesanan Anda untuk segera memiliki motor impian.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="max-w-4xl mx-auto mb-6 bg-red-50 text-red-800 border border-red-200 px-6 py-4 rounded-xl font-bold text-sm flex items-center gap-3">
                <span class="material-symbols-outlined text-red-600">error</span>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="max-w-4xl mx-auto flex flex-col md:flex-row gap-8">
            
            <div class="w-full md:w-2/3 space-y-6">
                <div class="bg-white border border-slate-200 rounded-xl p-6">
                    <h3 class="text-xl font-bold text-slate-900 mb-6 border-b border-slate-100 pb-4">Order Items (<?= count($cart_data) ?>)</h3>
                    
                    <div class="space-y-4">
                        <?php foreach($cart_data as $item): ?>
                        <div class="flex items-center justify-between border-b border-slate-50 pb-4 last:border-0 last:pb-0">
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 bg-slate-100 rounded-lg flex items-center justify-center font-bold text-slate-400 uppercase">
                                    <?= substr(htmlspecialchars($item['make']), 0, 3) ?>
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-900"><?= htmlspecialchars($item['make'] . ' ' . $item['model']) ?></h4>
                                    <p class="text-xs text-slate-500"><?= $item['quantity'] ?> Units x Rp <?= number_format($item['price'], 0, ',', '.') ?></p>
                                </div>
                            </div>
                            <span class="font-bold text-secondary">Rp <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="w-full md:w-1/3">
                <div class="bg-white border border-slate-200 rounded-xl p-6 sticky top-24">
                    <h3 class="text-xl font-bold text-slate-900 mb-6 border-b border-slate-100 pb-4">Payment Method</h3>
                    
                    <form method="POST" action="checkout" class="space-y-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Tipe Transaksi</label>
                            <div class="space-y-3">
                                <label class="flex items-start gap-3 p-3 border border-slate-200 rounded-lg cursor-pointer hover:bg-slate-50 transition-colors">
                                    <input type="radio" name="type" value="booking" required class="mt-1 text-secondary focus:ring-secondary">
                                    <div>
                                        <span class="block font-bold text-slate-900">Booking / DP</span>
                                        <span class="block text-xs text-slate-500">Amankan stok dengan uang muka, pelunasan di dealer.</span>
                                    </div>
                                </label>
                                <label class="flex items-start gap-3 p-3 border border-slate-200 rounded-lg cursor-pointer hover:bg-slate-50 transition-colors">
                                    <input type="radio" name="type" value="buy" required class="mt-1 text-secondary focus:ring-secondary">
                                    <div>
                                        <span class="block font-bold text-slate-900">Beli Langsung (Cash)</span>
                                        <span class="block text-xs text-slate-500">Bayar lunas melalui transfer / Virtual Account.</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-100 space-y-3">
                            <div class="flex justify-between text-slate-600">
                                <span>Total Amount</span>
                                <span class="font-extrabold text-slate-900 text-xl">Rp <?= number_format($grand_total, 0, ',', '.') ?></span>
                            </div>
                        </div>
                        
                        <button type="submit" name="checkout" class="w-full flex items-center justify-center gap-2 bg-secondary text-white py-3.5 rounded-lg font-bold text-lg hover:bg-red-800 transition-all shadow-lg shadow-red-200">
                            Confirm Order
                            <span class="material-symbols-outlined text-[20px]">check_circle</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <nav class="md:hidden fixed bottom-0 left-0 right-0 w-full bg-white border-t border-slate-200 z-[999]" style="padding-bottom: env(safe-area-inset-bottom);">
        <div class="flex justify-around items-center h-16">
            <a href="/" class="flex flex-col items-center justify-center w-full h-full text-slate-500 hover:text-secondary transition-colors">
                <span class="material-symbols-outlined text-[24px]">home</span>
                <span class="text-[10px] font-bold mt-1">Home</span>
            </a>
            <a href="discover" class="flex flex-col items-center justify-center w-full h-full text-slate-500 hover:text-secondary transition-colors">
                <span class="material-symbols-outlined text-[24px]">travel_explore</span>
                <span class="text-[10px] font-bold mt-1">Discover</span>
            </a>
            <a href="history" class="flex flex-col items-center justify-center w-full h-full text-slate-500 hover:text-secondary transition-colors">
                <span class="material-symbols-outlined text-[24px]">receipt_long</span>
                <span class="text-[10px] font-bold mt-1">History</span>
            </a>
        </div>
    </nav>
</body>
</html>
