<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['flash_message'] = "Silakan login terlebih dahulu untuk menambah barang ke keranjang.";
        header("Location: auth");
        exit();
    }
    
    $m_id = intval($_POST['motorcycle_id']);
    
    $s_stmt = $conn->prepare("SELECT stock FROM motorcycles WHERE id = ?");
    $s_stmt->bind_param("i", $m_id);
    $s_stmt->execute();
    $s_res = $s_stmt->get_result()->fetch_assoc();
    
    if ($s_res && $s_res['stock'] > 0) {
        $check = $conn->prepare("SELECT id, quantity FROM carts WHERE user_id = ? AND motorcycle_id = ?");
        $check->bind_param("ii", $_SESSION['user_id'], $m_id);
        $check->execute();
        $res = $check->get_result();
        
        if ($row = $res->fetch_assoc()) {
            if ($row['quantity'] < $s_res['stock']) {
                $update = $conn->prepare("UPDATE carts SET quantity = quantity + 1 WHERE id = ?");
                $update->bind_param("i", $row['id']);
                $update->execute();
                $_SESSION['flash_message'] = "Jumlah barang di keranjang ditambahkan!";
            } else {
                $_SESSION['flash_message'] = "Stok tidak mencukupi untuk menambah lagi.";
            }
        } else {
            $insert = $conn->prepare("INSERT INTO carts (user_id, motorcycle_id, quantity) VALUES (?, ?, 1)");
            $insert->bind_param("ii", $_SESSION['user_id'], $m_id);
            $insert->execute();
            $_SESSION['flash_message'] = "Barang berhasil ditambahkan ke keranjang!";
        }
    } else {
        $_SESSION['flash_message'] = "Maaf, stok barang sedang habis.";
    }
    
    header("Location: detail.php?id=" . $m_id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_now'])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['flash_message'] = "Silakan login terlebih dahulu untuk membeli barang.";
        header("Location: auth");
        exit();
    }
    
    $m_id = intval($_POST['motorcycle_id']);
    
    $s_stmt = $conn->prepare("SELECT stock FROM motorcycles WHERE id = ?");
    $s_stmt->bind_param("i", $m_id);
    $s_stmt->execute();
    $s_res = $s_stmt->get_result()->fetch_assoc();
    
    if ($s_res && $s_res['stock'] > 0) {
        $check = $conn->prepare("SELECT id, quantity FROM carts WHERE user_id = ? AND motorcycle_id = ?");
        $check->bind_param("ii", $_SESSION['user_id'], $m_id);
        $check->execute();
        $res = $check->get_result();
        
        if (!$res->fetch_assoc()) {
            $insert = $conn->prepare("INSERT INTO carts (user_id, motorcycle_id, quantity) VALUES (?, ?, 1)");
            $insert->bind_param("ii", $_SESSION['user_id'], $m_id);
            $insert->execute();
        }
        
        header("Location: checkout");
        exit();
    } else {
        $_SESSION['flash_message'] = "Maaf, stok barang sedang habis.";
        header("Location: detail.php?id=" . $m_id);
        exit();
    }
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $conn->prepare("SELECT m.*, 
    (SELECT COUNT(*) FROM transactions t WHERE t.motorcycle_id = m.id AND t.payment_status = 'paid') as sold
    FROM motorcycles m WHERE m.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$motor = $stmt->get_result()->fetch_assoc();

if (!$motor) {
    header("Location: index");
    exit();
}

$imgHash = md5($motor['make'] . $motor['model']);
$unsplashKeyword = urlencode(strtolower($motor['make'] . ' motorcycle'));
$imgUrl = "https://source.unsplash.com/800x600/?{$unsplashKeyword}&sig={$imgHash}";

$placeholders = [
    'https://images.unsplash.com/photo-1568772585407-9361f9bf3a87?auto=format&fit=crop&q=80&w=800',
    'https://images.unsplash.com/photo-1558981403-c5f9899a28bc?auto=format&fit=crop&q=80&w=800',
    'https://images.unsplash.com/photo-1558981806-ec527fa84c39?auto=format&fit=crop&q=80&w=800',
    'https://images.unsplash.com/photo-1449426468159-d96dbf08f19f?auto=format&fit=crop&q=80&w=800',
    'https://images.unsplash.com/photo-1614162692292-7ac56d7f7f1e?auto=format&fit=crop&q=80&w=800'
];
$imgUrl = $placeholders[$motor['id'] % count($placeholders)];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?= htmlspecialchars($motor['make'] . ' ' . $motor['model']) ?> - MotoTrack Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0f172a',
                        secondary: '#dc2626',
                        'secondary-container': '#f87171',
                        background: '#f8fafc',
                        surface: '#ffffff',
                        'surface-container-lowest': '#ffffff',
                        'surface-container-low': '#f1f5f9',
                        'surface-container': '#e2e8f0',
                        'surface-container-high': '#cbd5e1',
                        'surface-container-highest': '#94a3b8',
                        'on-surface': '#0f172a',
                        'on-surface-variant': '#475569',
                        'outline': '#64748b',
                        'outline-variant': '#cbd5e1',
                    },
                    fontFamily: {
                        sans: ['Hanken Grotesk', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-background text-on-surface flex flex-col min-h-screen pb-16 md:pb-0">
    <?php include 'header.php'; ?>

    <main class="flex-grow w-full max-w-[1280px] mx-auto px-4 md:px-8 py-8 md:py-12">
        <a href="javascript:history.back()" class="inline-flex items-center gap-2 text-slate-500 hover:text-secondary font-bold mb-6 transition-colors">
            <span class="material-symbols-outlined">arrow_back</span>
            Back to Catalog
        </a>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-0">
                
                <div class="relative h-[300px] md:h-full min-h-[400px] bg-slate-100 group overflow-hidden">
                    <img src="<?= $imgUrl ?>" alt="<?= htmlspecialchars($motor['model']) ?>" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                    <div class="absolute top-4 left-4 flex gap-2">
                        <span class="px-3 py-1 bg-white/90 backdrop-blur-sm text-slate-900 text-xs font-black tracking-wider uppercase rounded-full shadow-sm">
                            <?= htmlspecialchars($motor['make']) ?>
                        </span>
                        <?php if ($motor['stock'] == 0): ?>
                            <span class="px-3 py-1 bg-red-500/90 backdrop-blur-sm text-white text-xs font-black tracking-wider uppercase rounded-full shadow-sm">
                                Out of Stock
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="p-6 md:p-10 flex flex-col h-full">
                    <div class="mb-2">
                        <h1 class="text-3xl md:text-4xl font-black text-slate-900 tracking-tight leading-tight mb-2">
                            <?= htmlspecialchars($motor['model']) ?>
                        </h1>
                        <p class="text-sm text-slate-500 uppercase tracking-widest font-bold">
                            <?= $motor['year'] ?> Edition
                        </p>
                    </div>

                    <div class="mt-4 mb-8 pb-8 border-b border-slate-100">
                        <span class="text-4xl font-black text-emerald-600 tracking-tight">
                            Rp <?= number_format($motor['price'], 0, ',', '.') ?>
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <div class="flex items-center gap-2 text-slate-500 mb-1">
                                <span class="text-xs font-bold uppercase tracking-wider">Year</span>
                            </div>
                            <span class="text-lg font-black text-slate-900"><?= $motor['year'] ?></span>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <div class="flex items-center gap-2 text-slate-500 mb-1">
                                <span class="text-xs font-bold uppercase tracking-wider">Mileage</span>
                            </div>
                            <span class="text-lg font-black text-slate-900"><?= number_format($motor['mileage'], 0, ',', '.') ?> KM</span>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <div class="flex items-center gap-2 text-slate-500 mb-1">
                                <span class="text-xs font-bold uppercase tracking-wider">Availability</span>
                            </div>
                            <span class="text-lg font-black <?= $motor['stock'] > 0 ? 'text-slate-900' : 'text-red-500' ?>"><?= $motor['stock'] ?> Left</span>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <div class="flex items-center gap-2 text-slate-500 mb-1">
                                <span class="text-xs font-bold uppercase tracking-wider">Sold</span>
                            </div>
                            <span class="text-lg font-black text-slate-900"><?= $motor['sold'] ?> Units</span>
                        </div>
                    </div>

                    <div class="mb-8 flex-grow">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-3 flex items-center gap-2">
                            Description
                        </h3>
                        <p class="text-slate-600 leading-relaxed">
                            <?= nl2br(htmlspecialchars($motor['description'] ?: 'Belum ada deskripsi untuk motor ini.')) ?>
                        </p>
                    </div>

                    <div class="mt-auto pt-6 border-t border-slate-100">
                        <?php if ($motor['stock'] > 0): ?>
                            <form method="POST" action="detail?id=<?= $motor['id'] ?>">
                                <input type="hidden" name="motorcycle_id" value="<?= $motor['id'] ?>">
                                <div class="flex flex-row gap-3 w-full">
                                    <button type="submit" name="buy_now" class="flex-1 flex items-center justify-center gap-2 bg-secondary text-white py-4 rounded-xl font-bold hover:bg-secondary-container hover:shadow-lg hover:shadow-secondary/30 transition-all text-lg">
                                        Buy Now
                                    </button>
                                    <button type="submit" name="add_to_cart" class="flex items-center justify-center gap-2 bg-slate-900 text-white px-8 py-4 rounded-xl font-bold hover:bg-slate-800 hover:shadow-lg transition-all text-lg" title="Add to Cart">
                                        <span class="material-symbols-outlined">add_shopping_cart</span>
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="w-full bg-slate-100 border border-slate-200 text-slate-500 py-4 rounded-xl font-bold text-center flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined">event_busy</span>
                                Currently Unavailable
                            </div>
                        <?php endif; ?>
                    </div>
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
