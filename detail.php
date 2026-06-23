<?php
require_once 'config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;


$stmt = $conn->prepare("
    SELECT m.*, 
    (SELECT COUNT(*) FROM transactions t WHERE t.motorcycle_id = m.id AND t.payment_status = 'paid') as sold
    FROM motorcycles m 
    WHERE m.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$motor = $stmt->get_result()->fetch_assoc();

if (!$motor) {
    header("Location: discover");
    exit();
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;


$can_review = false;
if ($user_id) {
    $stmt_rev = $conn->prepare("
        SELECT id FROM transactions 
        WHERE user_id = ? AND motorcycle_id = ? AND status = 'confirmed' AND payment_status = 'paid' 
        LIMIT 1
    ");
    $stmt_rev->bind_param("ii", $user_id, $id);
    $stmt_rev->execute();
    if ($stmt_rev->get_result()->num_rows > 0) {
        $can_review = true;
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!$user_id) {
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
        $check->bind_param("ii", $user_id, $m_id);
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
            $insert->bind_param("ii", $user_id, $m_id);
            $insert->execute();
            $_SESSION['flash_message'] = __('Item added to cart.', 'Barang berhasil ditambahkan ke keranjang!');
        }
    } else {
        $_SESSION['flash_message'] = __('Sorry, out of stock.', 'Maaf, stok barang sedang habis.');
    }
    header("Location: detail?id=" . $m_id);
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_now'])) {
    if (!$user_id) {
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
        $check->bind_param("ii", $user_id, $m_id);
        $check->execute();
        $res = $check->get_result();
        if ($row = $res->fetch_assoc()) {
            $cart_id = $row['id'];
        } else {
            $insert = $conn->prepare("INSERT INTO carts (user_id, motorcycle_id, quantity) VALUES (?, ?, 1)");
            $insert->bind_param("ii", $user_id, $m_id);
            $insert->execute();
            $cart_id = $conn->insert_id;
        }
        $_SESSION['checkout_cart_ids'] = [$cart_id];
        header("Location: checkout");
        exit();
    } else {
        $_SESSION['flash_message'] = __('Sorry, out of stock.', 'Maaf, stok barang sedang habis.');
        header("Location: detail?id=" . $m_id);
        exit();
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!$can_review) {
        $_SESSION['flash_message'] = __('You are not eligible to review this motorcycle.', 'Anda tidak memenuhi syarat untuk memberikan ulasan pada motor ini.');
        header("Location: detail?id=" . $id);
        exit();
    }
    
    $rating = intval($_POST['rating']);
    if ($rating < 1) $rating = 1;
    if ($rating > 5) $rating = 5;
    $comment = trim($_POST['comment']);
    $anonymous = isset($_POST['anonymous']) ? 1 : 0;
    
    $ins_rev = $conn->prepare("INSERT INTO reviews (user_id, motorcycle_id, rating, comment, is_anonymous) VALUES (?, ?, ?, ?, ?)");
    $ins_rev->bind_param("iiisi", $user_id, $id, $rating, $comment, $anonymous);
    if ($ins_rev->execute()) {
        $_SESSION['flash_message'] = __('Thank you for your review!', 'Terima kasih atas ulasan Anda!');
    } else {
        $_SESSION['flash_message'] = __('Failed to submit review.', 'Gagal mengirimkan ulasan.');
    }
    header("Location: detail?id=" . $id);
    exit();
}


$reviews_stmt = $conn->prepare("
    SELECT r.*, u.username 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.motorcycle_id = ? 
    ORDER BY r.created_at DESC
");
$reviews_stmt->bind_param("i", $id);
$reviews_stmt->execute();
$reviews_res = $reviews_stmt->get_result();

$placeholders = [
    'https://images.unsplash.com/photo-1568772585407-9361f9bf3a87?auto=format&fit=crop&q=80&w=800',
    'https://images.unsplash.com/photo-1558981403-c5f9899a28bc?auto=format&fit=crop&q=80&w=800',
    'https://images.unsplash.com/photo-1558981806-ec527fa84c39?auto=format&fit=crop&q=80&w=800',
    'https://images.unsplash.com/photo-1449426468159-d96dbf08f19f?auto=format&fit=crop&q=80&w=800',
    'https://images.unsplash.com/photo-1614162692292-7ac56d7f7f1e?auto=format&fit=crop&q=80&w=800'
];
$imgUrl = $placeholders[$motor['id'] % count($placeholders)];


$is_discounted = false;
$final_price = $motor['price'];
if ($motor['discount_percent'] > 0 && ($motor['discount_until'] === null || strtotime($motor['discount_until']) > time())) {
    $is_discounted = true;
    $final_price = $motor['price'] * (1 - ($motor['discount_percent'] / 100));
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title><?= htmlspecialchars($motor['make'] . ' ' . $motor['model']) ?> - MotoInfy</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <?php include 'theme_config.php'; ?>
</head>
<body class="bg-background text-on-surface flex flex-col min-h-screen pb-16 md:pb-0">
    <?php include 'header.php'; ?>
    
    <main class="flex-grow w-full max-w-[1280px] mx-auto px-4 md:px-8 py-8 md:py-12">
        <a href="discover" class="inline-flex items-center gap-2 text-slate-500 hover:text-secondary font-bold mb-6 transition-colors">
            <span class="material-symbols-outlined">arrow_back</span>
            <?= __('Back to Catalog', 'Kembali ke Katalog') ?>
        </a>
        
        <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant overflow-hidden mb-12">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-0">
                <div class="relative h-[300px] md:h-auto min-h-[400px] bg-surface-container group overflow-hidden">
                    <img src="<?= $imgUrl ?>" alt="<?= htmlspecialchars($motor['model']) ?>" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                    <div class="absolute top-4 left-4 flex gap-2">
                        <span class="px-3 py-1 bg-black/50 backdrop-blur-sm text-[#ffffff] text-xs font-black tracking-wider uppercase rounded-full shadow-md">
                            <?= htmlspecialchars($motor['make']) ?>
                        </span>
                        <?php if ($is_discounted): ?>
                            <span class="px-3 py-1 bg-red-600 text-white text-xs font-black tracking-wider uppercase rounded-full shadow-sm">
                                <?= $motor['discount_percent'] ?>% OFF
                            </span>
                        <?php endif; ?>
                        <?php if ($motor['stock'] == 0): ?>
                            <span class="px-3 py-1 bg-red-500/90 backdrop-blur-sm text-white text-xs font-black tracking-wider uppercase rounded-full shadow-sm">
                                <?= __('Out of Stock', 'Stok Habis') ?>
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
                    
                    <div class="mt-4 mb-8 pb-8 border-b border-outline-variant flex flex-col">
                        <?php if ($is_discounted): ?>
                            <span class="text-sm text-red-500 line-through">Rp <?= number_format($motor['price'], 0, ',', '.') ?></span>
                            <span class="text-4xl font-black text-slate-900 tracking-tight">
                                Rp <?= number_format($final_price, 0, ',', '.') ?>
                            </span>
                        <?php else: ?>
                            <span class="text-4xl font-black text-slate-900 tracking-tight">
                                Rp <?= number_format($motor['price'], 0, ',', '.') ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <div class="bg-surface-container-low p-4 rounded-xl border border-outline-variant">
                            <div class="flex items-center gap-2 text-slate-500 mb-1">
                                <span class="text-xs font-bold uppercase tracking-wider"><?= __('Year', 'Tahun') ?></span>
                            </div>
                            <span class="text-lg font-black text-slate-900"><?= $motor['year'] ?></span>
                        </div>
                        <div class="bg-surface-container-low p-4 rounded-xl border border-outline-variant">
                            <div class="flex items-center gap-2 text-slate-500 mb-1">
                                <span class="text-xs font-bold uppercase tracking-wider"><?= __('Mileage', 'Jarak Tempuh') ?></span>
                            </div>
                            <span class="text-lg font-black text-slate-900"><?= number_format($motor['mileage'], 0, ',', '.') ?> KM</span>
                        </div>
                        <div class="bg-surface-container-low p-4 rounded-xl border border-outline-variant">
                            <div class="flex items-center gap-2 text-slate-500 mb-1">
                                <span class="text-xs font-bold uppercase tracking-wider"><?= __('Stock', 'Stok') ?></span>
                            </div>
                            <span class="text-lg font-black <?= $motor['stock'] > 0 ? 'text-slate-900' : 'text-red-500' ?>"><?= $motor['stock'] ?> Left</span>
                        </div>
                        <div class="bg-surface-container-low p-4 rounded-xl border border-outline-variant">
                            <div class="flex items-center gap-2 text-slate-500 mb-1">
                                <span class="text-xs font-bold uppercase tracking-wider"><?= __('Sold', 'Terjual') ?></span>
                            </div>
                            <span class="text-lg font-black text-slate-900"><?= $motor['sold'] ?> Units</span>
                        </div>
                    </div>
                    
                    <div class="mb-8 flex-grow">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-3 flex items-center gap-2">
                            <?= __('Description', 'Deskripsi') ?>
                        </h3>
                        <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                            <?= nl2br(htmlspecialchars($motor['description'] ?: __('No description available.', 'Belum ada deskripsi untuk motor ini.'))) ?>
                        </p>
                    </div>
                    
                    <div class="mt-auto pt-6 border-t border-outline-variant">
                        <?php if ($motor['stock'] > 0): ?>
                            <form method="POST" action="detail?id=<?= $motor['id'] ?>">
                                <input type="hidden" name="motorcycle_id" value="<?= $motor['id'] ?>">
                                <div class="flex flex-row gap-3 w-full">
                                    <button type="submit" name="buy_now" class="flex-1 flex items-center justify-center gap-2 bg-secondary text-white py-4 rounded-xl font-bold hover:bg-opacity-95 hover:shadow-lg transition-all text-lg">
                                        <?= __('Buy Now', 'Beli Sekarang') ?>
                                    </button>
                                    <button type="submit" name="add_to_cart" class="flex items-center justify-center gap-2 bg-slate-900 text-white px-8 py-4 rounded-xl font-bold hover:bg-slate-800 hover:shadow-lg transition-all text-lg" title="<?= __('Add to Cart', 'Tambah ke Keranjang') ?>">
                                        <span class="material-symbols-outlined">add_shopping_cart</span>
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="w-full bg-surface-container border border-outline-variant text-slate-500 py-4 rounded-xl font-bold text-center flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined">event_busy</span>
                                <?= __('Currently Unavailable', 'Stok Sedang Habis') ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        
        <section class="bg-surface-container-lowest rounded-2xl border border-outline-variant p-6 md:p-8 shadow-sm">
            <h3 class="text-2xl font-extrabold text-slate-900 mb-6">
                <?= __('Customer Reviews', 'Ulasan Pelanggan') ?>
            </h3>

            
            <?php if ($can_review): ?>
                <div class="bg-surface-container-low border border-outline-variant rounded-2xl p-5 mb-8">
                    <h4 class="font-bold text-slate-900 mb-4"><?= __('Write a Review', 'Tulis Ulasan Anda') ?></h4>
                    <form method="POST" action="detail?id=<?= $id ?>" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2"><?= __('Rating', 'Penilaian') ?></label>
                            <select name="rating" required class="border border-outline-variant bg-surface-container text-on-surface rounded-lg p-2 font-bold focus:ring-secondary">
                                <option value="5">★★★★★ (5 Star)</option>
                                <option value="4">★★★★☆ (4 Star)</option>
                                <option value="3">★★★☆☆ (3 Star)</option>
                                <option value="2">★★☆☆☆ (2 Star)</option>
                                <option value="1">★☆☆☆☆ (1 Star)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2"><?= __('Your Comment', 'Komentar Anda') ?></label>
                            <textarea name="comment" required rows="3" placeholder="<?= __('Write your review here...', 'Tulis komentar ulasan Anda di sini...') ?>" class="w-full border border-outline-variant bg-surface-container rounded-lg p-3 outline-none focus:ring-1 focus:ring-secondary"></textarea>
                        </div>
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 text-sm cursor-pointer select-none">
                                <input type="checkbox" name="anonymous" value="1" class="rounded border-outline-variant text-secondary focus:ring-secondary">
                                <span><?= __('Write Anonymously', 'Tulis secara Anonim') ?></span>
                            </label>
                            <button type="submit" name="submit_review" class="bg-secondary text-white font-bold px-6 py-2 rounded-xl text-sm hover:bg-opacity-90 transition-all">
                                <?= __('Submit Review', 'Kirim Ulasan') ?>
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            
            <div class="space-y-4">
                <?php if ($reviews_res->num_rows == 0): ?>
                    <p class="text-slate-500 dark:text-slate-400 italic text-center py-6"><?= __('No reviews yet. Be the first verified buyer to write one!', 'Belum ada ulasan untuk motor ini. Jadilah pembeli pertama yang memberikan ulasan!') ?></p>
                <?php else: ?>
                    <?php 
                    while($rev = $reviews_res->fetch_assoc()): 
                        
                        $author = htmlspecialchars($rev['username']);
                        if ($rev['is_anonymous']) {
                            $author = 'Anonymous User #' . (($rev['user_id'] * 73) % 9000 + 1000);
                        }
                    ?>
                    <div class="border-b border-outline-variant pb-4 last:border-0 last:pb-0 flex flex-col gap-1.5">
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-slate-900"><?= $author ?></span>
                            <span class="text-xs text-slate-400 font-medium"><?= date('d M Y', strtotime($rev['created_at'])) ?></span>
                        </div>
                        <div class="flex text-amber-500">
                            <?php for($i = 0; $i < 5; $i++): ?>
                                <span class="material-symbols-outlined text-[16px]" style="font-variation-settings: 'FILL' <?= $i < $rev['rating'] ? 1 : 0 ?>;">star</span>
                            <?php endfor; ?>
                        </div>
                        <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed mt-1">
                            <?= nl2br(htmlspecialchars($rev['comment'])) ?>
                        </p>
                    </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>
