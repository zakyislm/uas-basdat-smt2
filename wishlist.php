<?php
require_once 'config.php';
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = __('Please login to view your wishlist.', 'Silakan login untuk melihat daftar keinginan Anda.');
    header("Location: auth");
    exit();
}

$user_id = $_SESSION['user_id'];


if (isset($_GET['remove'])) {
    $wish_id = intval($_GET['remove']);
    $stmt = $conn->prepare("DELETE FROM wishlists WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $wish_id, $user_id);
    if ($stmt->execute()) {
        $_SESSION['flash_message'] = __('Item removed from wishlist.', 'Item berhasil dihapus dari daftar keinginan.');
    }
    header("Location: wishlist");
    exit();
}


if (isset($_POST['add_to_cart'])) {
    $motor_id = intval($_POST['motorcycle_id']);
    
    
    $s_stmt = $conn->prepare("SELECT stock FROM motorcycles WHERE id = ?");
    $s_stmt->bind_param("i", $motor_id);
    $s_stmt->execute();
    $m_stock = $s_stmt->get_result()->fetch_assoc()['stock'];
    
    if ($m_stock > 0) {
        
        $c_check = $conn->prepare("SELECT id, quantity FROM carts WHERE user_id = ? AND motorcycle_id = ?");
        $c_check->bind_param("ii", $user_id, $motor_id);
        $c_check->execute();
        $c_res = $c_check->get_result();
        
        if ($c_res->num_rows > 0) {
            $cart_item = $c_res->fetch_assoc();
            $new_qty = $cart_item['quantity'] + 1;
            if ($new_qty <= $m_stock) {
                $u_stmt = $conn->prepare("UPDATE carts SET quantity = ? WHERE id = ?");
                $u_stmt->bind_param("ii", $new_qty, $cart_item['id']);
                $u_stmt->execute();
                $_SESSION['flash_message'] = __('Cart updated.', 'Keranjang belanja berhasil diperbarui.');
            } else {
                $_SESSION['flash_message'] = __('Cannot add more of this item, stock limit reached.', 'Gagal menambah barang, batas stok tercapai.');
            }
        } else {
            $i_stmt = $conn->prepare("INSERT INTO carts (user_id, motorcycle_id, quantity) VALUES (?, ?, 1)");
            $i_stmt->bind_param("ii", $user_id, $motor_id);
            $i_stmt->execute();
            $_SESSION['flash_message'] = __('Item added to cart.', 'Item berhasil ditambahkan ke keranjang.');
        }
    } else {
        $_SESSION['flash_message'] = __('Sorry, this motorcycle is out of stock.', 'Maaf, motor ini sedang kehabisan stok.');
    }
    header("Location: wishlist");
    exit();
}


$stmt = $conn->prepare("
    SELECT w.id as wish_id, m.id as motor_id, m.make, m.model, m.price, m.stock, m.year, m.discount_percent, m.discount_until
    FROM wishlists w
    JOIN motorcycles m ON w.motorcycle_id = m.id
    WHERE w.user_id = ?
    ORDER BY w.added_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$wishlist_items = $stmt->get_result();

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
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>MotoInfy | <?= __('My Wishlist', 'Daftar Keinginan Saya') ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <?php include 'theme_config.php'; ?>
</head>
<body class="bg-background text-on-surface flex flex-col min-h-screen pb-16 md:pb-0">
    <?php include 'header.php'; ?>
    
    <main class="w-full flex-grow max-w-[1280px] mx-auto px-4 md:px-8 py-12">
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-slate-900">
                <?= __('My Wishlist', 'Daftar Keinginan Saya') ?>
            </h1>
            <p class="text-slate-500 mt-1"><?= __('Save and review your favorite high-performance motorcycles.', 'Simpan dan lihat kembali sepeda motor performa tinggi favorit Anda.') ?></p>
        </div>

        <?php if ($wishlist_items->num_rows == 0): ?>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-12 text-center max-w-md mx-auto shadow-sm">
                <span class="material-symbols-outlined text-[64px] text-slate-300 dark:text-slate-700 mb-4">bookmark_border</span>
                <h3 class="text-xl font-bold mb-2"><?= __('Your Wishlist is Empty', 'Daftar Keinginan Kosong') ?></h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-6"><?= __('Browse our catalog to add bikes you love.', 'Telusuri katalog kami untuk menambahkan motor kesukaan Anda.') ?></p>
                <a href="discover" class="inline-block bg-slate-900 text-white font-bold px-6 py-3 rounded-lg hover:bg-secondary transition-colors"><?= __('Discover Bikes', 'Telusuri Motor') ?></a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php 
                while($motor = $wishlist_items->fetch_assoc()): 
                    $img_index = $motor['motor_id'] % count($image_pool);
                    $img = $image_pool[$img_index];

                    
                    $is_discounted = false;
                    $final_price = $motor['price'];
                    if ($motor['discount_percent'] > 0 && ($motor['discount_until'] === null || strtotime($motor['discount_until']) > time())) {
                        $is_discounted = true;
                        $final_price = $motor['price'] * (1 - ($motor['discount_percent'] / 100));
                    }
                ?>
                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group flex flex-col relative">
                    <a href="detail?id=<?= $motor['motor_id'] ?>" class="block relative h-48 w-full bg-surface-container overflow-hidden">
                        <img src="<?= $img ?>" alt="<?= htmlspecialchars($motor['make']) ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 to-transparent"></div>
                        <div class="absolute bottom-3 left-3">
                            <span class="px-2.5 py-1 bg-black/50 backdrop-blur-sm text-[#ffffff] text-xs font-black tracking-widest uppercase rounded-lg shadow-md">
                                <?= htmlspecialchars($motor['make']) ?>
                            </span>
                        </div>
                        <?php if ($is_discounted): ?>
                            <span class="absolute top-3 left-3 bg-red-600 text-white text-xs font-bold px-2.5 py-1 rounded-full shadow-lg">
                                <?= $motor['discount_percent'] ?>% OFF
                            </span>
                        <?php endif; ?>
                    </a>
                    
                    <div class="p-5 flex-grow flex flex-col gap-2">
                        <div class="flex justify-between items-start gap-2">
                            <h3 class="font-bold text-lg group-hover:text-secondary transition-colors truncate">
                                <a href="detail?id=<?= $motor['motor_id'] ?>"><?= htmlspecialchars($motor['model']) ?></a>
                            </h3>
                            <a href="wishlist?remove=<?= $motor['wish_id'] ?>" class="text-slate-400 hover:text-red-600 transition-colors" title="<?= __('Remove from Wishlist', 'Hapus dari Daftar Keinginan') ?>">
                                <span class="material-symbols-outlined text-[20px]">delete</span>
                            </a>
                        </div>
                        
                        <p class="text-sm text-slate-500 dark:text-slate-400 font-medium"><?= htmlspecialchars($motor['year']) ?> &bull; <?= $motor['stock'] > 0 ? __('In Stock', 'Tersedia') : __('Out of Stock', 'Habis') ?></p>
                        
                        <div class="mt-2 flex flex-col">
                            <?php if ($is_discounted): ?>
                                <span class="text-xs text-red-500 line-through">Rp <?= number_format($motor['price'], 0, ',', '.') ?></span>
                                <span class="text-xl font-extrabold text-slate-900">Rp <?= number_format($final_price, 0, ',', '.') ?></span>
                            <?php else: ?>
                                <span class="text-xl font-extrabold text-slate-900">Rp <?= number_format($motor['price'], 0, ',', '.') ?></span>
                            <?php endif; ?>
                        </div>

                        <form method="POST" action="wishlist" class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                            <input type="hidden" name="motorcycle_id" value="<?= $motor['motor_id'] ?>">
                            <button type="submit" name="add_to_cart" class="w-full flex items-center justify-center gap-2 bg-slate-900 text-white py-2.5 rounded-lg font-bold text-sm hover:bg-secondary transition-colors" <?= $motor['stock'] == 0 ? 'disabled' : '' ?>>
                                <span class="material-symbols-outlined text-[18px]">shopping_cart</span>
                                <span><?= __('Add to Cart', 'Tambah ke Keranjang') ?></span>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>
