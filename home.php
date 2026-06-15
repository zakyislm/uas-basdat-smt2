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
    
    header("Location: /#katalog");
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
        
        $insert = $conn->prepare("INSERT INTO transactions (user_id, motorcycle_id, quantity, type, payment_status, status) VALUES (?, ?, 1, 'buy', 'unpaid', 'pending')");
        $insert->bind_param("ii", $_SESSION['user_id'], $m_id);
        $insert->execute();
        $trx_id = $conn->insert_id;

        $uid = $_SESSION['user_id'];
        $conn->query("INSERT INTO notifications (user_id, message, link, icon, color, bg) VALUES ($uid, 'Menunggu pembayaran: #TX-$trx_id', 'payment.php?id=$trx_id', 'warning', 'text-orange-500', 'bg-orange-100')");

        $conn->query("INSERT INTO notifications (target_role, message, link, icon, color, bg) VALUES ('admin', 'Pesanan baru: #TX-$trx_id', 'admin.php?page=transactions', 'receipt_long', 'text-blue-500', 'bg-blue-100')");

        $update_stock = $conn->prepare("UPDATE motorcycles SET stock = stock - 1 WHERE id = ?");
        $update_stock->bind_param("i", $m_id);
        $update_stock->execute();
        
        header("Location: payment.php?id=" . $trx_id);
        exit();
    } else {
        $_SESSION['flash_message'] = "Maaf, stok barang sedang habis.";
        header("Location: /#katalog");
        exit();
    }
}

$sql = "
    SELECT m.*, COALESCE(SUM(t.quantity), 0) as sold 
    FROM motorcycles m 
    LEFT JOIN transactions t ON m.id = t.motorcycle_id AND t.status != 'cancelled' 
    WHERE m.stock > 0 
    GROUP BY m.id 
    ORDER BY sold DESC 
    LIMIT 8
";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>MotoTrack Pro | Best Sellers</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <style>
        body { font-family: 'Hanken Grotesk', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .glass-card { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(8px); }
        .hero-gradient { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); }
    </style>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "secondary": "#bb0112",
                        "secondary-container": "#e02928",
                        "on-secondary-container": "#fffbff",
                        "primary-fixed-dim": "#bec6e0",
                        "background": "#f7f9fb",
                        "surface-container-lowest": "#ffffff",
                        "outline-variant": "#c6c6cd",
                        "on-surface": "#191c1e",
                        "on-surface-variant": "#45464d",
                        "emerald-success": "#10b981",
                        "slate-900": "#0f172a",
                        "slate-800": "#1e293b",
                        "slate-200": "#e2e8f0"
                    },
                    "borderRadius": { "DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px" },
                    "spacing": { "base": "4px", "stack-sm": "8px", "stack-md": "16px", "gutter": "24px", "stack-lg": "32px", "margin-desktop": "32px", "container-max": "1280px" },
                    "fontFamily": {
                        "label-md": ["Hanken Grotesk"], "label-sm": ["Hanken Grotesk"], "body-sm": ["Hanken Grotesk"],
                        "headline-md": ["Hanken Grotesk"], "display-lg": ["Hanken Grotesk"], "headline-lg": ["Hanken Grotesk"],
                        "body-md": ["Hanken Grotesk"], "headline-sm": ["Hanken Grotesk"], "body-lg": ["Hanken Grotesk"]
                    },
                },
            },
        }
    </script>
</head>

<body class="bg-background text-on-surface flex flex-col min-h-screen pb-16 md:pb-0">
    <?php include 'header.php'; ?>

    <main class="w-full">
        
        <section class="relative py-32 px-margin-desktop overflow-hidden bg-slate-900">
            
            <div class="absolute inset-0 z-0">
                <img src="https://images.unsplash.com/photo-1631823479646-9d8ec78161b6?q=80&w=2070&auto=format&fit=crop" alt="Hero Background" class="w-full h-full object-cover opacity-30">
            </div>
            
            <div class="absolute inset-0 opacity-20 pointer-events-none z-0">
                <div class="absolute top-0 right-0 w-96 h-96 bg-secondary blur-3xl rounded-full translate-x-1/2 -translate-y-1/2"></div>
                <div class="absolute bottom-0 left-0 w-64 h-64 bg-slate-900 blur-3xl rounded-full -translate-x-1/2 translate-y-1/2"></div>
            </div>
            <div class="relative z-10 max-w-container-max mx-auto flex flex-col items-center text-center">
                <span class="bg-secondary-container text-white px-stack-md py-base rounded-full text-xs uppercase tracking-widest mb-stack-md font-bold">
                    
                </span>
                <h1 class="text-5xl md:text-6xl text-white mb-stack-md leading-none font-extrabold tracking-tight">MotoTrack <span class="text-transparent bg-clip-text bg-gradient-to-r from-red-400 to-secondary">Store</span></h1>
                <p class="text-lg text-slate-300 max-w-2xl mb-stack-lg">
                    Discover our best selling machines and elevate your ride. Explore the complete catalog and checkout directly.
                </p>
                <div class="flex gap-4">
                    <a href="#katalog" class="bg-secondary text-white px-8 py-3 rounded-lg font-bold hover:scale-105 transition-transform shadow-lg shadow-secondary/30">
                        View Best Sellers
                    </a>
                    <a href="discover" class="bg-white/10 text-white border border-white/20 px-8 py-3 rounded-lg font-bold hover:bg-white/20 transition-colors backdrop-blur-sm">
                        Discover All
                    </a>
                </div>
            </div>
        </section>

        <section id="katalog" class="max-w-container-max mx-auto px-margin-desktop py-20">
            <div class="flex flex-col md:flex-row justify-between items-end mb-10 gap-stack-md">
                <div>
                    <h2 class="text-3xl font-extrabold text-slate-900 mb-2">Best Sellers</h2>
                    <p class="text-slate-500 mt-1">Our most popular and highly demanded motorcycles.</p>
                </div>
                <a href="discover" class="text-secondary font-bold hover:underline flex items-center gap-1">
                    See All <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>
            
            <?php if ($result->num_rows > 0): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php 
                $image_pool = [
                    'https://images.unsplash.com/photo-1558981403-c5f9899a28bc?w=500&q=80',
                    'https://images.unsplash.com/photo-1558980394-4c7c9299fe96?w=500&q=80',
                    'https://images.unsplash.com/photo-1558981806-ec527fa84c39?w=500&q=80',
                    'https://images.unsplash.com/photo-1449426468159-d96dbf08f19f?w=500&q=80',
                    'https://images.unsplash.com/photo-1609630875171-b1321377ee65?w=500&q=80',
                    'https://images.unsplash.com/photo-1502744688674-c619d1586c9e?w=500&q=80',
                    'https://images.unsplash.com/photo-1471440671318-55bdbb772f93?w=500&q=80',
                    'https://images.unsplash.com/photo-1498887960847-2a5e46312788?w=500&q=80'
                ];
                while($motor = $result->fetch_assoc()): 
                    $img_index = (isset($motor['id']) ? $motor['id'] : rand(0, 100)) % count($image_pool);
                    $imgUrl = $image_pool[$img_index];
                ?>
                <div class="bg-white border border-slate-200 rounded-xl overflow-hidden hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group flex flex-col">
                    <a href="detail?id=<?= $motor['id'] ?>" class="block relative h-48 bg-slate-100 overflow-hidden group">
                        <img src="<?= $imgUrl ?>" alt="<?= htmlspecialchars($motor['model']) ?>" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                        <div class="absolute top-3 left-3 flex gap-2">
                            <span class="px-2.5 py-1 bg-white/90 backdrop-blur-sm text-slate-900 text-[10px] font-black tracking-wider uppercase rounded-full shadow-sm">
                                <?= htmlspecialchars($motor['make']) ?>
                            </span>
                        </div>
                        <?php if ($motor['sold'] > 0): ?>
                        <span class="absolute top-3 right-3 bg-secondary text-white text-[10px] font-bold px-2 py-1 rounded shadow-lg uppercase tracking-wider flex items-center gap-1 z-10">
                            <span class="material-symbols-outlined text-[12px]">star</span> Hot
                        </span>
                        <?php endif; ?>
                    </a>
                    <div class="p-5 flex-grow flex flex-col">
                        <div class="mb-1">
                            <a href="detail?id=<?= $motor['id'] ?>" class="block group/link">
                                <h3 class="text-lg font-black text-slate-900 leading-tight group-hover/link:text-secondary transition-colors">
                                    <?= htmlspecialchars($motor['model']) ?>
                                </h3>
                            </a>
                            <p class="text-xs text-slate-500 uppercase tracking-widest mt-1"><?= $motor['year'] ?></p>
                        </div>
                        <div class="mb-4">
                            <span class="text-xl font-black text-emerald-600 tracking-tight">Rp <?= number_format($motor['price'], 0, ',', '.') ?></span>
                        </div>
                        <div class="mt-auto pt-4 border-t border-slate-100">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-bold text-slate-500">Stock: <span class="text-slate-900"><?= $motor['stock'] ?> left</span></span>
                                <span class="text-xs font-bold text-slate-500"><?= $motor['sold'] ?> sold</span>
                            </div>
                            <form method="POST" action="index">
                                <input type="hidden" name="motorcycle_id" value="<?= $motor['id'] ?>">
                                <div class="flex gap-2 w-full">
                                    <button type="submit" name="buy_now" class="flex-1 flex items-center justify-center gap-1 bg-secondary text-white py-2.5 rounded-lg font-bold text-sm hover:bg-secondary-container hover:shadow-lg hover:shadow-secondary/30 transition-all">
                                        <span class="material-symbols-outlined text-[18px]">payments</span>
                                        Buy
                                    </button>
                                    <button type="submit" name="add_to_cart" class="flex-1 flex items-center justify-center gap-1 bg-slate-900 text-white py-2.5 rounded-lg font-bold text-sm hover:bg-slate-800 hover:shadow-lg transition-all" title="Add to Cart">
                                        <span class="material-symbols-outlined text-[18px]">add_shopping_cart</span>
                                        <span class="hidden md:inline">Cart</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
                <div class="text-center py-16 bg-white rounded-xl border border-slate-200 border-dashed">
                    <span class="material-symbols-outlined text-5xl text-slate-300 mb-3 block">inventory_2</span>
                    <p class="text-slate-500 font-medium">Maaf, saat ini tidak ada motor yang tersedia.</p>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <?php include 'footer.php'; ?>

    <nav class="md:hidden fixed bottom-0 left-0 right-0 w-full bg-white border-t border-slate-200 z-[999]" style="padding-bottom: env(safe-area-inset-bottom);">
        <div class="flex justify-around items-center h-16">
            <a href="/" class="flex flex-col items-center justify-center w-full h-full text-secondary hover:text-secondary transition-colors">
                <span class="material-symbols-outlined text-[24px]" style="font-variation-settings: 'FILL' 1;">home</span>
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
