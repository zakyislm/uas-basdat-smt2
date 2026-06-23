<?php
require_once 'config.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_wishlist'])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['flash_message'] = __('Please login to manage your wishlist.', 'Silakan login terlebih dahulu untuk mengelola daftar keinginan.');
        header("Location: auth");
        exit();
    }
    $user_id = $_SESSION['user_id'];
    $motor_id = intval($_POST['motorcycle_id']);
    
    $w_check = $conn->prepare("SELECT id FROM wishlists WHERE user_id = ? AND motorcycle_id = ?");
    $w_check->bind_param("ii", $user_id, $motor_id);
    $w_check->execute();
    $w_res = $w_check->get_result();
    
    if ($w_res->num_rows > 0) {
        $w_row = $w_res->fetch_assoc();
        $w_id = $w_row['id'];
        $w_del = $conn->prepare("DELETE FROM wishlists WHERE id = ?");
        $w_del->bind_param("i", $w_id);
        $w_del->execute();
        $_SESSION['flash_message'] = __('Removed from wishlist.', 'Dihapus dari daftar keinginan.');
    } else {
        $w_ins = $conn->prepare("INSERT INTO wishlists (user_id, motorcycle_id) VALUES (?, ?)");
        $w_ins->bind_param("ii", $user_id, $motor_id);
        $w_ins->execute();
        $_SESSION['flash_message'] = __('Added to wishlist!', 'Ditambahkan ke daftar keinginan!');
    }
    
    $redirect_url = 'discover';
    if (!empty($_SERVER['QUERY_STRING'])) {
        $redirect_url .= '?' . $_SERVER['QUERY_STRING'];
    }
    header("Location: " . $redirect_url);
    exit();
}


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
    
    $redirect_url = 'discover';
    if (!empty($_SERVER['QUERY_STRING'])) {
        $redirect_url .= '?' . $_SERVER['QUERY_STRING'];
    }
    header("Location: " . $redirect_url);
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
        header("Location: discover");
        exit();
    }
}


$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$selected_brands = isset($_GET['brands']) && is_array($_GET['brands']) ? $_GET['brands'] : [];
$selected_categories = isset($_GET['categories']) && is_array($_GET['categories']) ? array_map('intval', $_GET['categories']) : [];
$selected_colors = isset($_GET['colors']) && is_array($_GET['colors']) ? array_map('intval', $_GET['colors']) : [];
$selected_engine_types = isset($_GET['engine_types']) && is_array($_GET['engine_types']) ? array_map('intval', $_GET['engine_types']) : [];
$min_price = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? floatval($_GET['min_price']) : '';
$max_price = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? floatval($_GET['max_price']) : '';


$where_clauses = [];
$params = [];
$types = '';

if ($search !== '') {
    $where_clauses[] = "(m.make LIKE ? OR m.model LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if (!empty($selected_brands)) {
    $placeholders = implode(',', array_fill(0, count($selected_brands), '?'));
    $where_clauses[] = "m.make IN ($placeholders)";
    foreach ($selected_brands as $b) {
        $params[] = $b;
        $types .= 's';
    }
}

if (!empty($selected_categories)) {
    $placeholders = implode(',', array_fill(0, count($selected_categories), '?'));
    $where_clauses[] = "m.category_id IN ($placeholders)";
    foreach ($selected_categories as $c_id) {
        $params[] = $c_id;
        $types .= 'i';
    }
}

if (!empty($selected_colors)) {
    $placeholders = implode(',', array_fill(0, count($selected_colors), '?'));
    $where_clauses[] = "m.color_id IN ($placeholders)";
    foreach ($selected_colors as $cl_id) {
        $params[] = $cl_id;
        $types .= 'i';
    }
}

if (!empty($selected_engine_types)) {
    $placeholders = implode(',', array_fill(0, count($selected_engine_types), '?'));
    $where_clauses[] = "m.engine_type_id IN ($placeholders)";
    foreach ($selected_engine_types as $e_id) {
        $params[] = $e_id;
        $types .= 'i';
    }
}

if ($min_price !== '') {
    $where_clauses[] = "m.price >= ?";
    $params[] = $min_price;
    $types .= 'd';
}

if ($max_price !== '') {
    $where_clauses[] = "m.price <= ?";
    $params[] = $max_price;
    $types .= 'd';
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";


$limit = 12; 
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$count_sql = "SELECT COUNT(*) as total FROM motorcycles m $where_sql";
$count_stmt = $conn->prepare($count_sql);
if ($types !== '') {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_rows = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);


$fetch_sql = "
    SELECT m.*, c.name as category_name, cl.color_name, e.type_name as engine_type_name
    FROM motorcycles m
    LEFT JOIN categories c ON m.category_id = c.id
    LEFT JOIN colors cl ON m.color_id = cl.id
    LEFT JOIN engine_types e ON m.engine_type_id = e.id
    $where_sql 
    ORDER BY m.id DESC LIMIT ? OFFSET ?
";
$fetch_stmt = $conn->prepare($fetch_sql);
$fetch_params = $params;
$fetch_params[] = $limit;
$fetch_params[] = $offset;
$fetch_types = $types . 'ii';
$fetch_stmt->bind_param($fetch_types, ...$fetch_params);
$fetch_stmt->execute();
$result = $fetch_stmt->get_result();


$brands_res = $conn->query("SELECT DISTINCT make FROM motorcycles ORDER BY make ASC");
$all_brands = [];
while($b = $brands_res->fetch_assoc()) {
    $all_brands[] = $b['make'];
}

$categories_res = $conn->query("SELECT * FROM categories ORDER BY name ASC");
$all_categories = [];
while($cat = $categories_res->fetch_assoc()) {
    $all_categories[] = $cat;
}

$colors_res = $conn->query("SELECT * FROM colors ORDER BY color_name ASC");
$all_colors = [];
while($col = $colors_res->fetch_assoc()) {
    $all_colors[] = $col;
}

$engines_res = $conn->query("SELECT * FROM engine_types ORDER BY type_name ASC");
$all_engines = [];
while($eng = $engines_res->fetch_assoc()) {
    $all_engines[] = $eng;
}


$wishlisted_ids = [];
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $w_res = $conn->query("SELECT motorcycle_id FROM wishlists WHERE user_id = $uid");
    while($w_row = $w_res->fetch_assoc()) {
        $wishlisted_ids[] = $w_row['motorcycle_id'];
    }
}


$qs = http_build_query(array_filter([
    'search' => $search,
    'brands' => $selected_brands,
    'categories' => $selected_categories,
    'colors' => $selected_colors,
    'engine_types' => $selected_engine_types,
    'min_price' => $min_price,
    'max_price' => $max_price
]));
$qs_prefix = $qs ? "&" . $qs : "";

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
    <title>MotoInfy | <?= __('Discover Motorcycles', 'Telusuri Motor') ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <?php include 'theme_config.php'; ?>
</head>
<body class="bg-background text-on-surface flex flex-col min-h-screen pb-16 md:pb-0">
    <?php include 'header.php'; ?>
    
    <main class="w-full flex-grow max-w-[1280px] mx-auto px-4 md:px-8 py-12">
        <div class="flex gap-8 items-start">
            
            
            <aside class="hidden md:block w-72 bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-sm sticky top-24">
                <div class="flex justify-between items-center mb-6 pb-4 border-b border-outline-variant">
                    <h3 class="font-bold text-lg text-slate-900">
                        <?= __('Filters', 'Filter') ?>
                    </h3>
                    <a href="discover" class="text-xs text-secondary font-bold hover:underline"><?= __('Reset All', 'Hapus Semua') ?></a>
                </div>
                
                <form method="GET" action="discover" class="space-y-6">
                    
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2"><?= __('Search', 'Cari') ?></label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-400 text-[18px]">search</span>
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="<?= __('Model name...', 'Nama model...') ?>" class="w-full pl-9 pr-3 py-2 text-sm border border-outline-variant rounded-lg bg-surface-container focus:ring-1 focus:ring-secondary focus:border-secondary outline-none transition-all">
                        </div>
                    </div>
                    
                    
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2"><?= __('Brands', 'Merek') ?></label>
                        <div class="space-y-2 max-h-40 overflow-y-auto pr-2">
                            <?php foreach($all_brands as $b): ?>
                                <label class="flex items-center gap-2.5 text-sm cursor-pointer select-none">
                                    <input type="checkbox" name="brands[]" value="<?= htmlspecialchars($b) ?>" <?= in_array($b, $selected_brands) ? 'checked' : '' ?> class="rounded border-outline-variant text-secondary focus:ring-secondary">
                                    <span class="text-on-surface"><?= htmlspecialchars($b) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2"><?= __('Categories', 'Kategori') ?></label>
                        <div class="space-y-2">
                            <?php foreach($all_categories as $cat): ?>
                                <label class="flex items-center gap-2.5 text-sm cursor-pointer select-none">
                                    <input type="checkbox" name="categories[]" value="<?= $cat['id'] ?>" <?= in_array($cat['id'], $selected_categories) ? 'checked' : '' ?> class="rounded border-outline-variant text-secondary focus:ring-secondary">
                                    <span class="text-on-surface"><?= htmlspecialchars($cat['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2"><?= __('Colors', 'Warna') ?></label>
                        <div class="space-y-2">
                            <?php foreach($all_colors as $col): ?>
                                <label class="flex items-center gap-2.5 text-sm cursor-pointer select-none">
                                    <input type="checkbox" name="colors[]" value="<?= $col['id'] ?>" <?= in_array($col['id'], $selected_colors) ? 'checked' : '' ?> class="rounded border-outline-variant text-secondary focus:ring-secondary">
                                    <span class="text-on-surface"><?= htmlspecialchars($col['color_name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2"><?= __('Engine Capacity', 'Kapasitas Mesin') ?></label>
                        <div class="space-y-2">
                            <?php foreach($all_engines as $eng): ?>
                                <label class="flex items-center gap-2.5 text-sm cursor-pointer select-none">
                                    <input type="checkbox" name="engine_types[]" value="<?= $eng['id'] ?>" <?= in_array($eng['id'], $selected_engine_types) ? 'checked' : '' ?> class="rounded border-outline-variant text-secondary focus:ring-secondary">
                                    <span class="text-on-surface"><?= htmlspecialchars($eng['type_name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2"><?= __('Price Range', 'Rentang Harga') ?></label>
                        <div class="flex gap-2">
                            <input type="number" name="min_price" value="<?= htmlspecialchars($min_price) ?>" placeholder="Min" class="w-1/2 p-2 text-xs border border-outline-variant rounded-lg bg-surface-container outline-none">
                            <input type="number" name="max_price" value="<?= htmlspecialchars($max_price) ?>" placeholder="Max" class="w-1/2 p-2 text-xs border border-outline-variant rounded-lg bg-surface-container outline-none">
                        </div>
                    </div>
                    
                    <button type="submit" class="w-full bg-secondary hover:bg-secondary-container text-white font-bold py-2.5 rounded-xl text-sm transition-all shadow-md hover:shadow-lg">
                        <?= __('Apply Filters', 'Terapkan Filter') ?>
                    </button>
                </form>
            </aside>

            
            <div class="flex-1" id="products-area">
                
                <div class="mb-8 flex justify-between items-end">
                    <div>
                        <h1 class="text-4xl font-extrabold text-slate-900">
                            <?= __('Discover Motorcycles', 'Telusuri Motor') ?>
                        </h1>
                        <p class="text-slate-600 mt-2 text-lg"><?= __('Browse our complete collection of high-performance motorcycles.', 'Lihat koleksi lengkap motor performa tinggi kami.') ?></p>
                    </div>
                    
                    
                    <button onclick="toggleMobileFilters(true)" class="md:hidden flex items-center gap-2 bg-slate-900 text-white px-5 py-2.5 rounded-lg font-bold text-sm">
                        <span class="material-symbols-outlined text-[18px]">filter_list</span>
                        <span><?= __('Filters', 'Filter') ?></span>
                    </button>
                </div>
                <?php if ($result->num_rows > 0): ?>
                    <div id="skeleton-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                        <?php for($i=0; $i<6; $i++): ?>
                            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden h-[380px] flex flex-col">
                                <div class="h-48 w-full bg-slate-200 dark:bg-slate-800 animate-pulse"></div>
                                <div class="p-5 flex-grow flex flex-col gap-3">
                                    <div class="h-6 w-3/4 bg-slate-200 dark:bg-slate-800 animate-pulse rounded"></div>
                                    <div class="h-4 w-1/4 bg-slate-200 dark:bg-slate-800 animate-pulse rounded"></div>
                                    <div class="h-6 w-1/2 bg-slate-200 dark:bg-slate-800 animate-pulse rounded mt-2"></div>
                                    <div class="mt-auto pt-4 border-t border-outline-variant flex gap-2">
                                        <div class="h-10 w-full bg-slate-200 dark:bg-slate-800 animate-pulse rounded-lg"></div>
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                    
                    <div id="real-content-container" class="hidden grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                        <?php 
                        while($motor = $result->fetch_assoc()): 
                            $img_index = $motor['id'] % count($image_pool);
                            $img = $image_pool[$img_index];
                            $is_wishlisted = in_array($motor['id'], $wishlisted_ids);

                            
                            $is_discounted = false;
                            $final_price = $motor['price'];
                            if ($motor['discount_percent'] > 0 && ($motor['discount_until'] === null || strtotime($motor['discount_until']) > time())) {
                                $is_discounted = true;
                                $final_price = $motor['price'] * (1 - ($motor['discount_percent'] / 100));
                            }
                        ?>
                        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group flex flex-col relative">
                            
                            <form method="POST" action="discover<?= !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '' ?>" class="absolute top-3 right-3 z-20">
                                <input type="hidden" name="motorcycle_id" value="<?= $motor['id'] ?>">
                                <button type="submit" name="toggle_wishlist" class="group/wishbtn w-8 h-8 rounded-full bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm flex items-center justify-center shadow hover:bg-white dark:hover:bg-slate-900 hover:scale-110 transition-all">
                                    <span class="material-symbols-outlined text-lg <?= $is_wishlisted ? 'text-amber-500' : 'text-[#ffffff]' ?> group-hover/wishbtn:text-[#000000]" <?= $is_wishlisted ? 'style="font-variation-settings: \'FILL\' 1;"' : '' ?>>
                                        <?= $is_wishlisted ? 'star' : 'star_border' ?>
                                    </span>
                                </button>
                            </form>

                            <a href="detail?id=<?= $motor['id'] ?>" class="block relative h-48 w-full bg-surface-container overflow-hidden">
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
                                <?php if ($motor['stock'] == 0): ?>
                                    <span class="absolute inset-0 bg-surface-container-lowest/60 backdrop-blur-sm flex items-center justify-center z-10">
                                        <span class="bg-red-600 text-white font-bold px-4 py-1.5 rounded uppercase tracking-widest rotate-[-15deg] shadow-lg"><?= __('Out of Stock', 'Stok Habis') ?></span>
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
                                    
                                    <div class="flex flex-wrap gap-1.5 mt-2">
                                        <?php if (!empty($motor['category_name'])): ?>
                                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 border border-blue-100 dark:border-blue-950/60"><?= htmlspecialchars($motor['category_name']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($motor['color_name'])): ?>
                                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 border border-purple-100 dark:border-purple-950/60"><?= htmlspecialchars($motor['color_name']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($motor['engine_type_name'])): ?>
                                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border border-amber-100 dark:border-amber-950/60"><?= htmlspecialchars($motor['engine_type_name']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-xs text-slate-500 uppercase tracking-widest mt-2"><?= $motor['year'] ?></p>
                                </div>
                                
                                <div class="mb-4 mt-2 flex flex-col">
                                    <?php if ($is_discounted): ?>
                                        <span class="text-xs text-red-500 line-through">Rp <?= number_format($motor['price'], 0, ',', '.') ?></span>
                                        <span class="text-xl font-black text-slate-900 tracking-tight">Rp <?= number_format($final_price, 0, ',', '.') ?></span>
                                    <?php else: ?>
                                        <span class="text-xl font-black text-slate-900 tracking-tight">Rp <?= number_format($motor['price'], 0, ',', '.') ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mt-auto pt-4 border-t border-slate-100 dark:border-slate-800">
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-xs font-bold text-slate-500"><?= __('Stock', 'Stok') ?>: <span class="<?= $motor['stock'] > 0 ? 'text-slate-900' : 'text-red-500' ?>"><?= $motor['stock'] ?> <?= __('left', 'tersisa') ?></span></span>
                                    </div>
                                    
                                    <?php if ($motor['stock'] > 0): ?>
                                        <form method="POST" action="discover<?= !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '' ?>">
                                            <input type="hidden" name="motorcycle_id" value="<?= $motor['id'] ?>">
                                            <div class="flex gap-2 w-full mt-2">
                                                <button type="submit" name="buy_now" class="flex-1 flex items-center justify-center gap-1 bg-secondary text-white py-2 rounded-lg font-bold text-sm hover:bg-secondary-container hover:shadow-lg transition-all">
                                                    <span class="material-symbols-outlined text-[16px]">payments</span>
                                                    <?= __('Buy', 'Beli') ?>
                                                </button>
                                                <button type="submit" name="add_to_cart" class="flex-1 flex items-center justify-center gap-1 bg-slate-900 text-white py-2 rounded-lg font-bold text-sm hover:bg-slate-800 hover:shadow-lg transition-all" title="<?= __('Add to Cart', 'Tambah ke Keranjang') ?>">
                                                    <span class="material-symbols-outlined text-[16px]">add_shopping_cart</span>
                                                    <span><?= __('Cart', 'Keranjang') ?></span>
                                                </button>
                                            </div>
                                        </form>
                                    <?php else: ?>
                                        <button disabled class="w-full bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-600 py-2.5 rounded-lg font-bold text-sm cursor-not-allowed">
                                            <?= __('Unavailable', 'Habis') ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <?php if ($total_pages > 1): ?>
                        <div class="flex justify-center items-center gap-2 mt-8" id="pagination-container">
                            <a href="?page=1<?= $qs_prefix ?>" class="p-2 <?= $page <= 1 ? 'bg-surface-container text-slate-400 cursor-not-allowed pointer-events-none' : 'bg-surface-container-lowest hover:bg-surface-container-low text-on-surface' ?> border border-outline-variant rounded-lg flex items-center justify-center transition-colors">
                                <span class="material-symbols-outlined text-[20px]">keyboard_double_arrow_left</span>
                            </a>
                            <a href="?page=<?= max(1, $page - 1) ?><?= $qs_prefix ?>" class="p-2 <?= $page <= 1 ? 'bg-surface-container text-slate-400 cursor-not-allowed pointer-events-none' : 'bg-surface-container-lowest hover:bg-surface-container-low text-on-surface' ?> border border-outline-variant rounded-lg flex items-center justify-center transition-colors">
                                <span class="material-symbols-outlined text-[20px]">chevron_left</span>
                            </a>
                            <div class="px-4 py-2 bg-secondary text-white font-bold text-sm rounded-lg flex items-center justify-center min-w-[40px]">
                                <?= $page ?>
                            </div>
                            <a href="?page=<?= min($total_pages, $page + 1) ?><?= $qs_prefix ?>" class="p-2 <?= $page >= $total_pages ? 'bg-surface-container text-slate-400 cursor-not-allowed pointer-events-none' : 'bg-surface-container-lowest hover:bg-surface-container-low text-on-surface' ?> border border-outline-variant rounded-lg flex items-center justify-center transition-colors">
                                <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                            </a>
                            <a href="?page=<?= $total_pages ?><?= $qs_prefix ?>" class="p-2 <?= $page >= $total_pages ? 'bg-surface-container text-slate-400 cursor-not-allowed pointer-events-none' : 'bg-surface-container-lowest hover:bg-surface-container-low text-on-surface' ?> border border-outline-variant rounded-lg flex items-center justify-center transition-colors">
                                <span class="material-symbols-outlined text-[20px]">keyboard_double_arrow_right</span>
                            </a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-16 bg-surface-container-lowest rounded-xl border border-outline-variant border-dashed">
                        <span class="material-symbols-outlined text-6xl text-slate-300 mb-4 block">search_off</span>
                        <p class="text-slate-500 font-medium text-lg"><?= __('No motorcycles match your filter.', 'Tidak ada motor yang cocok dengan pencarian/filter Anda.') ?></p>
                        <a href="discover" class="text-secondary font-bold hover:underline mt-2 inline-block"><?= __('Clear Filters', 'Hapus Filter') ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    
    <div id="mobileFilterModal" class="fixed inset-0 bg-black/50 z-[999] hidden opacity-0 transition-opacity duration-300 flex items-end justify-center">
        
        <div id="mobileFilterDrawer" class="w-full bg-surface-container-lowest rounded-t-2xl p-6 shadow-2xl border-t border-outline-variant max-h-[85vh] overflow-y-auto transform translate-y-full transition-transform duration-300 flex flex-col gap-6">
            <div class="flex justify-between items-center pb-4 border-b border-outline-variant">
                <h3 class="font-bold text-lg text-slate-900">
                    <?= __('Filters', 'Filter') ?>
                </h3>
                <button onclick="toggleMobileFilters(false)" class="p-1 rounded-full hover:bg-surface-container text-slate-500">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            
            <form method="GET" action="discover" class="space-y-6">
                
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2"><?= __('Search', 'Cari') ?></label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-400 text-[18px]">search</span>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="<?= __('Model name...', 'Nama model...') ?>" class="w-full pl-9 pr-3 py-2 text-sm border border-outline-variant rounded-lg bg-surface-container outline-none">
                    </div>
                </div>
                
                
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2"><?= __('Brands', 'Merek') ?></label>
                    <div class="grid grid-cols-2 gap-2">
                        <?php foreach($all_brands as $b): ?>
                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="checkbox" name="brands[]" value="<?= htmlspecialchars($b) ?>" <?= in_array($b, $selected_brands) ? 'checked' : '' ?> class="rounded border-outline-variant text-secondary">
                                <span class="truncate"><?= htmlspecialchars($b) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2"><?= __('Categories', 'Kategori') ?></label>
                    <div class="grid grid-cols-2 gap-2">
                        <?php foreach($all_categories as $cat): ?>
                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="checkbox" name="categories[]" value="<?= $cat['id'] ?>" <?= in_array($cat['id'], $selected_categories) ? 'checked' : '' ?> class="rounded border-outline-variant text-secondary">
                                <span><?= htmlspecialchars($cat['name']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2"><?= __('Colors', 'Warna') ?></label>
                    <div class="grid grid-cols-2 gap-2">
                        <?php foreach($all_colors as $col): ?>
                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="checkbox" name="colors[]" value="<?= $col['id'] ?>" <?= in_array($col['id'], $selected_colors) ? 'checked' : '' ?> class="rounded border-outline-variant text-secondary">
                                <span><?= htmlspecialchars($col['color_name']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2"><?= __('Engine Capacity', 'Kapasitas Mesin') ?></label>
                    <div class="grid grid-cols-2 gap-2">
                        <?php foreach($all_engines as $eng): ?>
                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="checkbox" name="engine_types[]" value="<?= $eng['id'] ?>" <?= in_array($eng['id'], $selected_engine_types) ? 'checked' : '' ?> class="rounded border-outline-variant text-secondary">
                                <span><?= htmlspecialchars($eng['type_name']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2"><?= __('Price Range', 'Rentang Harga') ?></label>
                    <div class="flex gap-2">
                        <input type="number" name="min_price" value="<?= htmlspecialchars($min_price) ?>" placeholder="Min" class="w-1/2 p-2 text-xs border border-outline-variant rounded-lg bg-surface-container outline-none">
                        <input type="number" name="max_price" value="<?= htmlspecialchars($max_price) ?>" placeholder="Max" class="w-1/2 p-2 text-xs border border-outline-variant rounded-lg bg-surface-container outline-none">
                    </div>
                </div>
                
                <div class="pt-4 flex gap-2">
                    <a href="discover" class="flex-1 text-center border border-outline-variant py-2.5 rounded-xl text-sm font-bold hover:bg-surface-container transition-colors"><?= __('Clear', 'Hapus') ?></a>
                    <button type="submit" class="flex-1 bg-secondary text-white font-bold py-2.5 rounded-xl text-sm hover:bg-opacity-90 transition-all">
                        <?= __('Apply', 'Terapkan') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <nav class="md:hidden fixed bottom-0 left-0 right-0 w-full bg-surface-container-lowest border-t border-outline-variant z-[99]" style="padding-bottom: env(safe-area-inset-bottom);">
        <div class="flex justify-around items-center h-16">
            <a href="/" class="flex flex-col items-center justify-center w-full h-full text-slate-500 hover:text-secondary transition-colors">
                <span class="material-symbols-outlined text-[24px]">home</span>
                <span class="text-[10px] font-bold mt-1">Home</span>
            </a>
            <a href="discover" class="flex flex-col items-center justify-center w-full h-full text-secondary hover:text-secondary transition-colors">
                <span class="material-symbols-outlined text-[24px]" style="font-variation-settings: 'FILL' 1;">travel_explore</span>
                <span class="text-[10px] font-bold mt-1">Discover</span>
            </a>
            <a href="history" class="flex flex-col items-center justify-center w-full h-full text-slate-500 hover:text-secondary transition-colors">
                <span class="material-symbols-outlined text-[24px]">receipt_long</span>
                <span class="text-[10px] font-bold mt-1">History</span>
            </a>
        </div>
    </nav>

    <script>
    function hideSkeleton() {
        const skeleton = document.getElementById('skeleton-container');
        const realContent = document.getElementById('real-content-container');
        if(skeleton && realContent) {
            skeleton.classList.add('hidden');
            realContent.classList.remove('hidden');
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(hideSkeleton, 500);
        });
    } else {
        setTimeout(hideSkeleton, 500);
    }

    function toggleMobileFilters(show) {
        const modal = document.getElementById('mobileFilterModal');
        const drawer = document.getElementById('mobileFilterDrawer');
        if (show) {
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                drawer.classList.remove('translate-y-full');
            }, 10);
        } else {
            modal.classList.add('opacity-0');
            drawer.classList.add('translate-y-full');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }
    }
    </script>
</body>
</html>
