<?php
require_once 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: auth");
    exit();
}
$user_id = $_SESSION['user_id'];
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'unpaid';
$where_clause = "";
if ($tab === 'unpaid') {
    $where_clause = "AND t.payment_status = 'unpaid' AND t.status != 'cancelled'";
} elseif ($tab === 'diproses') {
    $where_clause = "AND t.status = 'pending' AND (t.payment_status = 'paid' OR t.payment_status = 'pending_verification')";
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
$c_unpaid = $conn->query("SELECT count(*) as c FROM transactions WHERE user_id = $user_id AND payment_status = 'unpaid' AND status != 'cancelled'")->fetch_assoc()['c'];
$c_proses = $conn->query("SELECT count(*) as c FROM transactions WHERE user_id = $user_id AND status = 'pending' AND (payment_status = 'paid' OR payment_status = 'pending_verification')")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>MotoInfy | Transaction History</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <?php include 'theme_config.php'; ?>
</head>
<body class="bg-background text-on-surface flex flex-col min-h-screen pb-16 md:pb-0">
    <?php include 'header.php'; ?>
    <main class="w-full flex-grow max-w-[1280px] mx-auto px-8 py-12">
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-slate-900">
                Transaction History
            </h1>
            <p class="text-slate-500 mt-1">Track your past and current orders.</p>
        </div>
        <div class="flex gap-2 border-b border-outline-variant mb-8 overflow-x-auto no-scrollbar">
            <a href="?tab=unpaid" class="px-6 py-3 font-bold text-sm border-b-2 whitespace-nowrap <?= $tab === 'unpaid' ? 'border-secondary text-secondary' : 'border-transparent text-slate-500 hover:text-on-surface' ?>">
                <?= __('Unpaid', 'Belum Dibayar') ?> 
                <?php if ($c_unpaid > 0): ?><span class="ml-1 bg-red-100 dark:bg-red-950/40 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800/40 px-2 py-0.5 rounded-full text-xs"><?= $c_unpaid ?></span><?php endif; ?>
            </a>
            <a href="?tab=diproses" class="px-6 py-3 font-bold text-sm border-b-2 whitespace-nowrap <?= $tab === 'diproses' ? 'border-secondary text-secondary' : 'border-transparent text-slate-500 hover:text-on-surface' ?>">
                <?= __('Processing', 'Diproses') ?>
                <?php if ($c_proses > 0): ?><span class="ml-1 bg-blue-100 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-800/40 px-2 py-0.5 rounded-full text-xs"><?= $c_proses ?></span><?php endif; ?>
            </a>
            <a href="?tab=done" class="px-6 py-3 font-bold text-sm border-b-2 whitespace-nowrap <?= $tab === 'done' ? 'border-secondary text-secondary' : 'border-transparent text-slate-500 hover:text-on-surface' ?>">
                <?= __('Completed / Cancelled', 'Selesai / Batal') ?>
            </a>
        </div>
        <div class="space-y-4">
            <?php if ($history->num_rows > 0): ?>
                <?php while($h = $history->fetch_assoc()): ?>
                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 hover:shadow-md transition-shadow">
                    <div class="flex flex-col sm:flex-row justify-between sm:items-center border-b border-slate-100 pb-3 mb-4 gap-2">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-slate-400">shopping_bag</span>
                            <span class="font-bold text-on-surface text-sm">Order #TX-<?= $h['id'] ?></span>
                            <span class="text-xs text-slate-400">&bull;</span>
                            <span class="text-xs text-slate-500"><?= date('d M Y, H:i', strtotime($h['transaction_date'])) ?></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 text-[10px] font-bold uppercase rounded <?= $h['type'] == 'booking' ? 'bg-purple-100 text-purple-700' : 'bg-surface-container text-on-surface' ?>">
                                <?= $h['type'] ?>
                            </span>
                            <?php if ($h['payment_status'] === 'unpaid' && $h['status'] !== 'cancelled'): ?>
                                <a href="payment?id=<?= $h['id'] ?>" class="px-4 py-2 bg-secondary text-white text-xs font-bold rounded-lg shadow-sm hover:bg-secondary-container transition-colors">
                                    <?= __('Pay Now', 'Bayar Sekarang') ?>
                                </a>
                            <?php elseif ($h['payment_status'] === 'pending_verification'): ?>
                                <span class="px-3 py-1 bg-amber-100 text-amber-600 text-xs font-bold rounded-full border border-amber-200">
                                    <?php
                                    if ($h['type'] === 'booking' && is_booking_dp_verified($conn, $h['id'])) {
                                        echo __('Awaiting Settlement Verification', 'Menunggu Verifikasi Pelunasan');
                                    } else {
                                        echo __('Awaiting Verification', 'Menunggu Verifikasi');
                                    }
                                    ?>
                                </span>
                            <?php elseif ($h['status'] === 'cancelled'): ?>
                                <span class="px-3 py-1 bg-surface-container text-slate-500 text-xs font-bold rounded-full">
                                    <?= __('Cancelled / Expired', 'Batal / Kadaluarsa') ?>
                                </span>
                            <?php else: ?>
                                <span class="px-3 py-1 <?= $h['status'] === 'confirmed' ? 'bg-emerald-100 text-emerald-600 border border-emerald-200' : 'bg-surface-container text-slate-600' ?> text-xs font-bold rounded-full">
                                    <?php 
                                    if ($h['type'] === 'booking' && $h['status'] === 'pending' && $h['payment_status'] === 'paid') {
                                        echo __('DP Paid', 'DP Lunas');
                                    } else {
                                        echo $h['status'] === 'confirmed' ? __('Confirmed', 'Dikonfirmasi') : __('Pending', 'Tertunda');
                                    }
                                    ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-20 h-20 bg-surface-container rounded-lg flex items-center justify-center font-bold text-slate-400 text-lg uppercase shrink-0">
                            <?= substr(htmlspecialchars($h['make']), 0, 3) ?>
                        </div>
                        <div class="flex-grow">
                            <h3 class="text-lg font-bold text-slate-900"><?= htmlspecialchars($h['make'] . ' ' . $h['model']) ?></h3>
                            <p class="text-xs text-slate-500 mt-1"><?= $h['quantity'] ?> item(s) x Rp <?= number_format($h['price'], 0, ',', '.') ?></p>
                            <?php if ($h['type'] === 'booking'): ?>
                                <div class="text-[11px] text-slate-500 mt-2 space-y-0.5 bg-slate-50 dark:bg-slate-900/50 p-2.5 rounded-lg border border-outline-variant w-max">
                                    <p><?= __('Down Payment (20%)', 'Uang Muka (20%)') ?>: <strong class="text-emerald-600">Rp <?= number_format($h['price'] * $h['quantity'] * 0.20, 0, ',', '.') ?></strong></p>
                                    <p><?= __('Remaining Balance (80%)', 'Sisa Pelunasan (80%)') ?>: <strong>Rp <?= number_format($h['price'] * $h['quantity'] * 0.80, 0, ',', '.') ?></strong></p>
                                    <?php if ($h['status'] === 'confirmed'): ?>
                                        <p class="text-[10px] text-emerald-600 font-bold mt-1"><?= __('Motorcycle has been fully settled and ready for delivery/pickup.', 'Motor telah dilunasi sepenuhnya dan siap dikirim/diambil.') ?></p>
                                    <?php else: ?>
                                        <p class="text-[10px] text-amber-600 font-bold mt-1"><?= __('New unit will be delivered after 100% settlement.', 'Unit baru dikirim setelah pelunasan 100%.') ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="text-right font-mono">
                            <p class="text-xs text-slate-500 mb-1"><?= $h['type'] === 'booking' ? 'Down Payment (20%)' : 'Total Order' ?></p>
                            <p class="font-black text-secondary text-lg">Rp <?= number_format($h['type'] === 'booking' ? $h['price'] * $h['quantity'] * 0.20 : $h['price'] * $h['quantity'], 0, ',', '.') ?></p>
                        </div>
                    </div>
                    <?php if ($tab === 'unpaid'): ?>
                    <div class="mt-4 pt-4 border-t border-slate-100 flex justify-end">
                        <a href="payment?id=<?= $h['id'] ?>" class="bg-slate-900 text-white px-6 py-2 rounded-lg text-sm font-bold hover:bg-secondary transition-colors inline-block text-center">
                            <?= __('Pay Now', 'Bayar Sekarang') ?>
                        </a>
                    </div>
                    <?php elseif ($tab === 'diproses' && $h['type'] === 'booking' && $h['status'] === 'pending' && $h['payment_status'] === 'paid'): ?>
                    <div class="mt-4 pt-4 border-t border-slate-100 flex justify-end">
                        <a href="payment?id=<?= $h['id'] ?>" class="bg-secondary text-white px-6 py-2 rounded-lg text-sm font-bold hover:bg-secondary-container transition-colors inline-block text-center shadow-sm">
                            <?= __('Pay the Rest', 'Bayar Pelunasan') ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-20 bg-surface-container-lowest rounded-xl border border-outline-variant border-dashed">
                    <span class="material-symbols-outlined text-6xl text-slate-300 mb-4 block">receipt_long</span>
                    <p class="text-slate-500 font-medium text-lg"><?= __('No transactions in this section.', 'Belum ada transaksi di bagian ini.') ?></p>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <?php include 'footer.php'; ?>
    <nav class="md:hidden fixed bottom-0 left-0 right-0 w-full bg-surface-container-lowest border-t border-outline-variant z-[999]" style="padding-bottom: env(safe-area-inset-bottom);">
        <div class="flex justify-around items-center h-16">
            <a href="/" class="flex flex-col items-center justify-center w-full h-full text-slate-500 hover:text-secondary transition-colors">
                <span class="material-symbols-outlined text-[24px]">home</span>
                <span class="text-[10px] font-bold mt-1">Home</span>
            </a>
            <a href="discover" class="flex flex-col items-center justify-center w-full h-full text-slate-500 hover:text-secondary transition-colors">
                <span class="material-symbols-outlined text-[24px]">travel_explore</span>
                <span class="text-[10px] font-bold mt-1">Discover</span>
            </a>
            <a href="history" class="flex flex-col items-center justify-center w-full h-full text-secondary hover:text-secondary transition-colors">
                <span class="material-symbols-outlined text-[24px]" style="font-variation-settings: 'FILL' 1;">receipt_long</span>
                <span class="text-[10px] font-bold mt-1">History</span>
            </a>
        </div>
    </nav>
</body>
</html>
