<?php
require_once 'config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['flash_message'] = __('Please login to add items to cart.', 'Silakan login terlebih dahulu untuk menambah barang ke keranjang.');
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
                $_SESSION['flash_message'] = __('Cart updated.', 'Jumlah barang di keranjang ditambahkan!');
            } else {
                $_SESSION['flash_message'] = __('Cannot add more, stock limit reached.', 'Stok tidak mencukupi untuk menambah lagi.');
            }
        } else {
            $insert = $conn->prepare("INSERT INTO carts (user_id, motorcycle_id, quantity) VALUES (?, ?, 1)");
            $insert->bind_param("ii", $_SESSION['user_id'], $m_id);
            $insert->execute();
            $_SESSION['flash_message'] = __('Item added to cart.', 'Barang berhasil ditambahkan ke keranjang!');
        }
    } else {
        $_SESSION['flash_message'] = __('Sorry, out of stock.', 'Maaf, stok barang sedang habis.');
    }
    header("Location: /#katalog");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_now'])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['flash_message'] = __('Please login to buy items.', 'Silakan login terlebih dahulu untuk membeli barang.');
        header("Location: auth");
        exit();
    }
    $m_id = intval($_POST['motorcycle_id']);
    $s_stmt = $conn->prepare("SELECT stock FROM motorcycles WHERE id = ?");
    $s_stmt->bind_param("i", $m_id);
    $s_stmt->execute();
    $s_res = $s_stmt->get_result()->fetch_assoc();
    if ($s_res && $s_res['stock'] > 0) {
        $check = $conn->prepare("SELECT id FROM carts WHERE user_id = ? AND motorcycle_id = ?");
        $check->bind_param("ii", $_SESSION['user_id'], $m_id);
        $check->execute();
        $res = $check->get_result();
        if ($row = $res->fetch_assoc()) {
            $cart_id = $row['id'];
        } else {
            $insert = $conn->prepare("INSERT INTO carts (user_id, motorcycle_id, quantity) VALUES (?, ?, 1)");
            $insert->bind_param("ii", $_SESSION['user_id'], $m_id);
            $insert->execute();
            $cart_id = $conn->insert_id;
        }
        $_SESSION['checkout_cart_ids'] = [$cart_id];
        header("Location: checkout");
        exit();
    } else {
        $_SESSION['flash_message'] = __('Sorry, out of stock.', 'Maaf, stok barang sedang habis.');
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
    <title>MotoInfy | Best Sellers</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <?php include 'theme_config.php'; ?>
</head>
<body class="bg-background text-on-surface flex flex-col min-h-screen pb-16 md:pb-0">
    <?php include 'header.php'; ?>
    <main class="w-full">
        <section class="relative min-h-[calc(100vh-64px)] flex flex-col items-center justify-center px-margin-desktop overflow-hidden bg-slate-900">
            <div class="absolute inset-0 z-0">
                <img src="https://images.unsplash.com/photo-1631823479646-9d8ec78161b6?q=80&w=2070&auto=format&fit=crop" alt="Hero Background" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-black/60"></div>
            </div>
            <div class="relative z-10 max-w-container-max mx-auto flex flex-col items-center text-center">
                <h1 class="text-5xl md:text-7xl text-[#ffffff] mb-stack-md leading-none font-extrabold tracking-tight drop-shadow-lg">MotoInfy <span class="text-transparent bg-clip-text bg-gradient-to-r from-red-400 to-secondary">Store</span></h1>
                <p class="text-lg text-[#cbd5e1] max-w-2xl mb-stack-lg">
                    Discover our best selling machines and elevate your ride. Explore the complete catalog and checkout directly.
                </p>
                <div class="flex gap-4">
                    <a href="#katalog" class="bg-secondary text-[#ffffff] px-8 py-3 rounded-lg font-bold hover:scale-105 transition-transform shadow-lg shadow-secondary/30">
                        View Best Sellers
                    </a>
                    <a href="discover" class="bg-surface-container-lowest/10 text-[#ffffff] border border-white/20 px-8 py-3 rounded-lg font-bold hover:bg-surface-container-lowest/20 transition-colors backdrop-blur-sm">
                        Discover All
                    </a>
                </div>
            </div>
        </section>
        <section id="katalog" class="max-w-container-max mx-auto px-margin-desktop py-20">
            <div class="flex flex-col md:flex-row justify-between items-end mb-10 gap-stack-md">
                <div>
                    <h2 class="text-3xl font-extrabold text-slate-900 mb-2"><?= __('Best Sellers', 'Terlaris') ?></h2>
                    <p class="text-slate-500 mt-1"><?= __('Our most popular and highly demanded motorcycles.', 'Motor terpopuler dan paling diminati.') ?></p>
                </div>
                <a href="discover" class="text-secondary font-bold hover:underline flex items-center gap-1">
                    <?= __('See All', 'Lihat Semua') ?> <span class="material-symbols-outlined text-sm">arrow_forward</span>
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
                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group flex flex-col">
                    <a href="detail?id=<?= $motor['id'] ?>" class="block relative h-48 bg-surface-container overflow-hidden group">
                        <img src="<?= $imgUrl ?>" alt="<?= htmlspecialchars($motor['model']) ?>" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                        <div class="absolute top-3 left-3 flex gap-2">
                            <span class="px-2.5 py-1 bg-black/50 backdrop-blur-sm text-[#ffffff] text-[10px] font-black tracking-wider uppercase rounded-full shadow-sm">
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
                            <span class="text-xl font-black text-slate-900 tracking-tight">Rp <?= number_format($motor['price'], 0, ',', '.') ?></span>
                        </div>
                        <div class="mt-auto pt-4 border-t border-slate-100">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-bold text-slate-500"><?= __('Stock', 'Stok') ?>: <span class="text-slate-900"><?= $motor['stock'] ?> <?= __('left', 'tersisa') ?></span></span>
                                <span class="text-xs font-bold text-slate-500"><?= $motor['sold'] ?> <?= __('sold', 'terjual') ?></span>
                            </div>
                            <form method="POST" action="index">
                                <input type="hidden" name="motorcycle_id" value="<?= $motor['id'] ?>">
                                <div class="flex gap-2 w-full">
                                    <button type="submit" name="buy_now" class="flex-1 flex items-center justify-center gap-1 bg-secondary text-white py-2.5 rounded-lg font-bold text-sm hover:bg-secondary-container hover:shadow-lg hover:shadow-secondary/30 transition-all">
                                        <span class="material-symbols-outlined text-[18px]">payments</span>
                                        <?= __('Buy', 'Beli') ?>
                                    </button>
                                    <button type="submit" name="add_to_cart" class="flex-1 flex items-center justify-center gap-1 bg-slate-900 text-white py-2.5 rounded-lg font-bold text-sm hover:bg-slate-800 hover:shadow-lg transition-all" title="<?= __('Add to Cart', 'Tambah ke Keranjang') ?>">
                                        <span class="material-symbols-outlined text-[18px]">add_shopping_cart</span>
                                        <span class="hidden md:inline"><?= __('Cart', 'Keranjang') ?></span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
                <div class="text-center py-16 bg-surface-container-lowest rounded-xl border border-outline-variant border-dashed">
                    <span class="material-symbols-outlined text-5xl text-slate-300 mb-3 block">inventory_2</span>
                    <p class="text-slate-500 font-medium"><?= __('Sorry, no motorcycles are currently available.', 'Maaf, saat ini tidak ada motor yang tersedia.') ?></p>
                </div>
            <?php endif; ?>
        </section>
    </main>
    <?php include 'footer.php'; ?>
    <nav class="md:hidden fixed bottom-0 left-0 right-0 w-full bg-surface-container-lowest border-t border-outline-variant z-[999]" style="padding-bottom: env(safe-area-inset-bottom);">
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
