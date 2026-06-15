<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'unpaid';

// Define queries based on tab
$where_clause = "";
if ($tab === 'unpaid') {
    $where_clause = "AND t.payment_status = 'unpaid' AND t.status != 'cancelled'";
} elseif ($tab === 'diproses') {
    $where_clause = "AND t.payment_status = 'paid' AND t.status = 'pending'";
} elseif ($tab === 'done') {
    $where_clause = "AND (t.status = 'confirmed' OR t.status = 'cancelled')";
} else {
    $where_clause = "AND t.payment_status = 'unpaid' AND t.status != 'cancelled'";
    $tab = 'unpaid';
}

$sql = "
    SELECT t.id, t.transaction_date, t.quantity, t.type, t.status, t.payment_status, 
           m.make, m.model, m.price 
    FROM transactions t
    JOIN motorcycles m ON t.motorcycle_id = m.id
    WHERE t.user_id = ? $where_clause
    ORDER BY t.transaction_date DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$history = $stmt->get_result();

// Count for badges
$c_unpaid = $conn->query("SELECT count(*) as c FROM transactions WHERE user_id = $user_id AND payment_status = 'unpaid' AND status != 'cancelled'")->fetch_assoc()['c'];
$c_proses = $conn->query("SELECT count(*) as c FROM transactions WHERE user_id = $user_id AND payment_status = 'paid' AND status = 'pending'")->fetch_assoc()['c'];
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>MotoTrack Pro | Transaction History</title>
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
    <!-- TopNavBar -->
    <header class="w-full top-0 sticky z-50 bg-surface-container-lowest border-b border-outline-variant shadow-sm">
        <div class="flex justify-between items-center px-4 md:px-8 py-2 w-full max-w-[1280px] mx-auto h-16">
            <div class="flex items-center gap-4">
                <a href="index.php" class="text-xl font-bold text-secondary">MotoTrack Pro</a>
            </div>
            
            <div class="flex items-center gap-2">
                <!-- Desktop Only Links -->
                <nav class="hidden md:flex items-center gap-2 mr-2 border-r border-outline-variant pr-4">
                    <a href="index.php" class="text-slate-600 hover:text-secondary p-2 transition-colors flex items-center justify-center rounded-full hover:bg-slate-50" title="Home">
                        <span class="material-symbols-outlined text-[24px]">home</span>
                    </a>
                    <a href="discover.php" class="text-slate-600 hover:text-secondary p-2 transition-colors flex items-center justify-center rounded-full hover:bg-slate-50" title="Discover">
                        <span class="material-symbols-outlined text-[24px]">travel_explore</span>
                    </a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="history.php" class="text-secondary p-2 transition-colors flex items-center justify-center rounded-full hover:bg-slate-50" title="History">
                            <span class="material-symbols-outlined text-[24px]" style="font-variation-settings: 'FILL' 1;">receipt_long</span>
                        </a>
                        <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'owner'): ?>
                            <a href="admin.php" class="text-slate-600 hover:text-secondary p-2 transition-colors flex items-center justify-center rounded-full hover:bg-slate-50" title="Admin Panel">
                                <span class="material-symbols-outlined text-[24px]">admin_panel_settings</span>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </nav>

                <!-- Always visible Cart & Settings -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php include 'notifications_ui.php'; ?>
                    <a href="cart.php" class="text-slate-600 hover:text-secondary p-2 transition-colors flex items-center justify-center rounded-full hover:bg-slate-50 relative" title="Cart">
                        <span class="material-symbols-outlined text-[24px]">shopping_cart</span>
                        <?php if (isset($cart_count) && $cart_count > 0): ?>
                            <span class="absolute top-0 right-0 bg-secondary text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full min-w-[18px] text-center border-2 border-white"><?= $cart_count ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="settings.php" class="text-slate-600 hover:text-secondary p-2 transition-colors flex items-center justify-center rounded-full hover:bg-slate-50" title="Settings">
                        <span class="material-symbols-outlined text-[24px]">settings</span>
                    </a>
                <?php else: ?>
                    <a href="auth.php" class="bg-slate-900 text-white px-5 py-2 rounded-lg font-bold text-sm hover:bg-secondary transition-colors">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="bg-emerald-600 text-white px-8 py-3 text-center font-bold text-sm">
            <?= htmlspecialchars($_SESSION['flash_message']) ?>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <main class="w-full flex-grow max-w-[1280px] mx-auto px-8 py-12">
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-slate-900">
                Transaction History
            </h1>
            <p class="text-slate-500 mt-1">Track your past and current orders.</p>
        </div>

        <!-- Tabs -->
        <div class="flex gap-2 border-b border-outline-variant mb-8 overflow-x-auto no-scrollbar">
            <a href="?tab=unpaid" class="px-6 py-3 font-bold text-sm border-b-2 whitespace-nowrap <?= $tab === 'unpaid' ? 'border-secondary text-secondary' : 'border-transparent text-slate-500 hover:text-slate-800' ?>">
                Belum Dibayar 
                <?php if ($c_unpaid > 0): ?><span class="ml-1 bg-red-100 text-red-600 px-2 py-0.5 rounded-full text-xs"><?= $c_unpaid ?></span><?php endif; ?>
            </a>
            <a href="?tab=diproses" class="px-6 py-3 font-bold text-sm border-b-2 whitespace-nowrap <?= $tab === 'diproses' ? 'border-secondary text-secondary' : 'border-transparent text-slate-500 hover:text-slate-800' ?>">
                Diproses
                <?php if ($c_proses > 0): ?><span class="ml-1 bg-blue-100 text-blue-600 px-2 py-0.5 rounded-full text-xs"><?= $c_proses ?></span><?php endif; ?>
            </a>
            <a href="?tab=done" class="px-6 py-3 font-bold text-sm border-b-2 whitespace-nowrap <?= $tab === 'done' ? 'border-secondary text-secondary' : 'border-transparent text-slate-500 hover:text-slate-800' ?>">
                Selesai / Batal
            </a>
        </div>

        <!-- Orders List -->
        <div class="space-y-4">
            <?php if ($history->num_rows > 0): ?>
                <?php while($h = $history->fetch_assoc()): ?>
                <div class="bg-white border border-slate-200 rounded-xl p-5 hover:shadow-md transition-shadow">
                    <div class="flex flex-col sm:flex-row justify-between sm:items-center border-b border-slate-100 pb-3 mb-4 gap-2">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-slate-400">shopping_bag</span>
                            <span class="font-bold text-slate-700 text-sm">Order #TX-<?= $h['id'] ?></span>
                            <span class="text-xs text-slate-400">&bull;</span>
                            <span class="text-xs text-slate-500"><?= date('d M Y, H:i', strtotime($h['transaction_date'])) ?></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 text-[10px] font-bold uppercase rounded <?= $h['type'] == 'booking' ? 'bg-purple-100 text-purple-700' : 'bg-slate-100 text-slate-700' ?>">
                                <?= $h['type'] ?>
                            </span>
                            <?php if ($h['payment_status'] === 'unpaid' && $h['status'] !== 'cancelled'): ?>
                                <a href="payment.php?id=<?= $h['id'] ?>" class="px-4 py-2 bg-secondary text-white text-xs font-bold rounded-lg shadow-sm hover:bg-secondary-container transition-colors">
                                    Bayar Sekarang
                                </a>
                            <?php elseif ($h['payment_status'] === 'pending_verification'): ?>
                                <span class="px-3 py-1 bg-amber-100 text-amber-600 text-xs font-bold rounded-full border border-amber-200">
                                    Menunggu Verifikasi
                                </span>
                            <?php elseif ($h['status'] === 'cancelled'): ?>
                                <span class="px-3 py-1 bg-slate-100 text-slate-500 text-xs font-bold rounded-full">
                                    Batal / Kadaluarsa
                                </span>
                            <?php else: ?>
                                <span class="px-3 py-1 <?= $h['status'] === 'confirmed' ? 'bg-emerald-100 text-emerald-600 border border-emerald-200' : 'bg-slate-100 text-slate-600' ?> text-xs font-bold rounded-full">
                                    <?= ucfirst($h['status']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-4">
                        <div class="w-20 h-20 bg-slate-100 rounded-lg flex items-center justify-center font-bold text-slate-400 text-lg uppercase shrink-0">
                            <?= substr(htmlspecialchars($h['make']), 0, 3) ?>
                        </div>
                        <div class="flex-grow">
                            <h3 class="text-lg font-bold text-slate-900"><?= htmlspecialchars($h['make'] . ' ' . $h['model']) ?></h3>
                            <p class="text-xs text-slate-500 mt-1"><?= $h['quantity'] ?> item(s) x Rp <?= number_format($h['price'], 0, ',', '.') ?></p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-slate-500 mb-1">Total Order</p>
                            <p class="font-black text-secondary text-lg">Rp <?= number_format($h['price'] * $h['quantity'], 0, ',', '.') ?></p>
                        </div>
                    </div>

                    <?php if ($tab === 'unpaid'): ?>
                    <div class="mt-4 pt-4 border-t border-slate-100 flex justify-end">
                        <!-- Simulated Payment Button -->
                        <button onclick="alert('Dalam sistem produksi, ini akan mengarah ke payment gateway.')" class="bg-slate-900 text-white px-6 py-2 rounded-lg text-sm font-bold hover:bg-secondary transition-colors">
                            Bayar Sekarang
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-20 bg-white rounded-xl border border-slate-200 border-dashed">
                    <span class="material-symbols-outlined text-6xl text-slate-300 mb-4 block">receipt_long</span>
                    <p class="text-slate-500 font-medium text-lg">Belum ada transaksi di bagian ini.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <!-- Bottom Nav (Mobile) -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 w-full bg-white border-t border-slate-200 z-[999]" style="padding-bottom: env(safe-area-inset-bottom);">
        <div class="flex justify-around items-center h-16">
            <a href="index.php" class="flex flex-col items-center justify-center w-full h-full text-slate-500 hover:text-secondary transition-colors">
                <span class="material-symbols-outlined text-[24px]">home</span>
                <span class="text-[10px] font-bold mt-1">Home</span>
            </a>
            <a href="discover.php" class="flex flex-col items-center justify-center w-full h-full text-slate-500 hover:text-secondary transition-colors">
                <span class="material-symbols-outlined text-[24px]">travel_explore</span>
                <span class="text-[10px] font-bold mt-1">Discover</span>
            </a>
            <a href="history.php" class="flex flex-col items-center justify-center w-full h-full text-secondary hover:text-secondary transition-colors">
                <span class="material-symbols-outlined text-[24px]" style="font-variation-settings: 'FILL' 1;">receipt_long</span>
                <span class="text-[10px] font-bold mt-1">History</span>
            </a>
        </div>
    </nav>
</body>
</html>
