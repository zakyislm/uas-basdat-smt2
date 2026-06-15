<?php
require_once 'config.php';

// Handle Add to Cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['flash_message'] = "Silakan login terlebih dahulu untuk menambah barang ke keranjang.";
        header("Location: auth.php");
        exit();
    }
    
    $m_id = intval($_POST['motorcycle_id']);
    
    // Check stock first
    $s_stmt = $conn->prepare("SELECT stock FROM motorcycles WHERE id = ?");
    $s_stmt->bind_param("i", $m_id);
    $s_stmt->execute();
    $s_res = $s_stmt->get_result()->fetch_assoc();
    
    if ($s_res && $s_res['stock'] > 0) {
        // Check if already in cart
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
    
    header("Location: discover.php");
    exit();
}

// Handle Buy Now
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_now'])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['flash_message'] = "Silakan login terlebih dahulu untuk membeli barang.";
        header("Location: auth.php");
        exit();
    }
    
    $m_id = intval($_POST['motorcycle_id']);
    
    // Check stock first
    $s_stmt = $conn->prepare("SELECT stock FROM motorcycles WHERE id = ?");
    $s_stmt->bind_param("i", $m_id);
    $s_stmt->execute();
    $s_res = $s_stmt->get_result()->fetch_assoc();
    
    if ($s_res && $s_res['stock'] > 0) {
        // Create transaction directly
        $insert = $conn->prepare("INSERT INTO transactions (user_id, motorcycle_id, quantity, type, payment_status, status) VALUES (?, ?, 1, 'buy', 'unpaid', 'pending')");
        $insert->bind_param("ii", $_SESSION['user_id'], $m_id);
        $insert->execute();
        $trx_id = $conn->insert_id;
        
        // Deduct stock
        $update_stock = $conn->prepare("UPDATE motorcycles SET stock = stock - 1 WHERE id = ?");
        $update_stock->bind_param("i", $m_id);
        $update_stock->execute();
        
        header("Location: payment.php?id=" . $trx_id);
        exit();
    } else {
        $_SESSION['flash_message'] = "Maaf, stok barang sedang habis.";
        header("Location: discover.php");
        exit();
    }
}

// Pagination & Filter setup
$limit = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$brand = isset($_GET['brand']) ? trim($_GET['brand']) : '';

$where_clauses = [];
$params = [];
$types = '';

if ($search !== '') {
    $where_clauses[] = "(make LIKE ? OR model LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if ($brand !== '') {
    $where_clauses[] = "make = ?";
    $params[] = $brand;
    $types .= 's';
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Count total
$count_sql = "SELECT COUNT(*) as total FROM motorcycles $where_sql";
$count_stmt = $conn->prepare($count_sql);
if ($types !== '') {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_rows = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Fetch items
$fetch_sql = "SELECT * FROM motorcycles $where_sql ORDER BY id DESC LIMIT ? OFFSET ?";
$fetch_stmt = $conn->prepare($fetch_sql);
$fetch_params = $params;
$fetch_params[] = $limit;
$fetch_params[] = $offset;
$fetch_types = $types . 'ii';
$fetch_stmt->bind_param($fetch_types, ...$fetch_params);
$fetch_stmt->execute();
$result = $fetch_stmt->get_result();

// Fetch distinct brands for filter dropdown
$brands_res = $conn->query("SELECT DISTINCT make FROM motorcycles ORDER BY make ASC");
$all_brands = [];
while($b = $brands_res->fetch_assoc()) {
    $all_brands[] = $b['make'];
}

// Build query string for pagination links
$query_params = [];
if ($search !== '') $query_params['search'] = $search;
if ($brand !== '') $query_params['brand'] = $brand;
$qs = http_build_query($query_params);
$qs_prefix = $qs ? "&" . $qs : "";
?>

<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>MotoTrack Pro | Discover Catalog</title>
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
                    <a href="discover.php" class="text-secondary p-2 transition-colors flex items-center justify-center rounded-full hover:bg-slate-50" title="Discover">
                        <span class="material-symbols-outlined text-[24px]" style="font-variation-settings: 'FILL' 1;">travel_explore</span>
                    </a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="history.php" class="text-slate-600 hover:text-secondary p-2 transition-colors flex items-center justify-center rounded-full hover:bg-slate-50" title="History">
                            <span class="material-symbols-outlined text-[24px]">receipt_long</span>
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
        <div class="bg-slate-800 text-white px-8 py-3 text-center font-bold text-sm">
            <?= htmlspecialchars($_SESSION['flash_message']) ?>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <main class="w-full flex-grow max-w-[1280px] mx-auto px-8 py-12">
        <div class="mb-8">
            <h1 class="text-4xl font-extrabold text-slate-900">
                Discover Motorcycles
            </h1>
            <p class="text-slate-500 mt-2 text-lg">Browse our complete collection of high-performance motorcycles.</p>
        </div>

        <!-- Filter & Search Bar -->
        <div class="bg-white border border-slate-200 rounded-xl p-4 mb-8 shadow-sm">
            <form method="GET" action="discover.php" class="flex flex-col md:flex-row gap-4 items-center">
                <div class="flex-grow w-full relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search model or make..." class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg focus:border-secondary focus:ring-1 focus:ring-secondary outline-none transition-all">
                </div>
                <div class="w-full md:w-64">
                    <select name="brand" class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-secondary focus:ring-1 focus:ring-secondary outline-none transition-all">
                        <option value="">All Brands</option>
                        <?php foreach($all_brands as $b): ?>
                            <option value="<?= htmlspecialchars($b) ?>" <?= $brand === $b ? 'selected' : '' ?>><?= htmlspecialchars($b) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="w-full md:w-auto bg-slate-900 text-white px-6 py-2.5 rounded-lg font-bold hover:bg-secondary transition-colors flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">filter_list</span> Filter
                </button>
            </form>
        </div>
        
        <?php if ($result->num_rows > 0): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-12">
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
                $img = $image_pool[$img_index];
            ?>
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group flex flex-col">
                <a href="detail.php?id=<?= $motor['id'] ?>" class="block relative h-48 w-full bg-slate-100 overflow-hidden group">
                    <img src="<?= $img ?>" alt="<?= htmlspecialchars($motor['make']) ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 to-transparent"></div>
                    <div class="absolute bottom-3 left-3">
                        <span class="text-white font-black text-xl uppercase tracking-widest drop-shadow-md">
                            <?= htmlspecialchars($motor['make']) ?>
                        </span>
                    </div>
                    <?php if ($motor['stock'] == 0): ?>
                        <span class="absolute inset-0 bg-white/60 backdrop-blur-sm flex items-center justify-center z-10">
                            <span class="bg-red-600 text-white font-bold px-4 py-1.5 rounded uppercase tracking-widest rotate-[-15deg] shadow-lg">Out of Stock</span>
                        </span>
                    <?php endif; ?>
                </a>
                <div class="p-5 flex-grow flex flex-col">
                    <div class="mb-1">
                        <a href="detail.php?id=<?= $motor['id'] ?>" class="block group/link">
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
                            <span class="text-xs font-bold text-slate-500">Stock: <span class="<?= $motor['stock'] > 0 ? 'text-slate-900' : 'text-red-500' ?>"><?= $motor['stock'] ?> left</span></span>
                        </div>
                        <?php if ($motor['stock'] > 0): ?>
                            <form method="POST" action="discover.php">
                                <input type="hidden" name="motorcycle_id" value="<?= $motor['id'] ?>">
                                <div class="flex gap-2 w-full mt-2">
                                    <button type="submit" name="buy_now" class="flex-1 flex items-center justify-center gap-1 bg-secondary text-white py-2 rounded-lg font-bold text-sm hover:bg-secondary-container hover:shadow-lg transition-all" <?= $motor['stock'] == 0 ? 'disabled' : '' ?>>
                                        <span class="material-symbols-outlined text-[16px]">payments</span>
                                        Buy
                                    </button>
                                    <button type="submit" name="add_to_cart" class="flex-1 flex items-center justify-center gap-1 bg-slate-900 text-white py-2 rounded-lg font-bold text-sm hover:bg-slate-800 hover:shadow-lg transition-all" title="Add to Cart" <?= $motor['stock'] == 0 ? 'disabled' : '' ?>>
                                        <span class="material-symbols-outlined text-[16px]">add_shopping_cart</span>
                                        <span class="hidden md:inline">Cart</span>
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <button disabled class="w-full bg-slate-200 text-slate-400 py-2.5 rounded-lg font-bold text-sm cursor-not-allowed">
                                Unavailable
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="flex justify-center items-center gap-2 mt-8">
            <!-- First Page -->
            <a href="?page=1<?= $qs_prefix ?>" class="p-2 <?= $page <= 1 ? 'bg-slate-100 text-slate-400 cursor-not-allowed pointer-events-none' : 'bg-white hover:bg-slate-50 text-slate-700' ?> border border-slate-300 rounded-lg flex items-center justify-center transition-colors">
                <span class="material-symbols-outlined text-[20px]">keyboard_double_arrow_left</span>
            </a>
            
            <!-- Previous Page -->
            <a href="?page=<?= max(1, $page - 1) ?><?= $qs_prefix ?>" class="p-2 <?= $page <= 1 ? 'bg-slate-100 text-slate-400 cursor-not-allowed pointer-events-none' : 'bg-white hover:bg-slate-50 text-slate-700' ?> border border-slate-300 rounded-lg flex items-center justify-center transition-colors">
                <span class="material-symbols-outlined text-[20px]">chevron_left</span>
            </a>
            
            <!-- Current Page -->
            <div class="px-4 py-2 bg-secondary text-white font-bold text-sm rounded-lg flex items-center justify-center min-w-[40px]">
                <?= $page ?>
            </div>
            
            <!-- Next Page -->
            <a href="?page=<?= min($total_pages, $page + 1) ?><?= $qs_prefix ?>" class="p-2 <?= $page >= $total_pages ? 'bg-slate-100 text-slate-400 cursor-not-allowed pointer-events-none' : 'bg-white hover:bg-slate-50 text-slate-700' ?> border border-slate-300 rounded-lg flex items-center justify-center transition-colors">
                <span class="material-symbols-outlined text-[20px]">chevron_right</span>
            </a>
            
            <!-- Last Page -->
            <a href="?page=<?= $total_pages ?><?= $qs_prefix ?>" class="p-2 <?= $page >= $total_pages ? 'bg-slate-100 text-slate-400 cursor-not-allowed pointer-events-none' : 'bg-white hover:bg-slate-50 text-slate-700' ?> border border-slate-300 rounded-lg flex items-center justify-center transition-colors">
                <span class="material-symbols-outlined text-[20px]">keyboard_double_arrow_right</span>
            </a>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
            <div class="text-center py-16 bg-white rounded-xl border border-slate-200 border-dashed">
                <span class="material-symbols-outlined text-6xl text-slate-300 mb-4 block">search_off</span>
                <p class="text-slate-500 font-medium text-lg">Tidak ada motor yang cocok dengan pencarian Anda.</p>
                <a href="discover.php" class="text-secondary font-bold hover:underline mt-2 inline-block">Clear Filters</a>
            </div>
        <?php endif; ?>
    </main>

    <?php include 'footer.php'; ?>

    <!-- Bottom Nav (Mobile) -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 w-full bg-white border-t border-slate-200 z-[999]" style="padding-bottom: env(safe-area-inset-bottom);">
        <div class="flex justify-around items-center h-16">
            <a href="index.php" class="flex flex-col items-center justify-center w-full h-full text-slate-500 hover:text-secondary transition-colors">
                <span class="material-symbols-outlined text-[24px]">home</span>
                <span class="text-[10px] font-bold mt-1">Home</span>
            </a>
            <a href="discover.php" class="flex flex-col items-center justify-center w-full h-full text-secondary hover:text-secondary transition-colors">
                <span class="material-symbols-outlined text-[24px]" style="font-variation-settings: 'FILL' 1;">travel_explore</span>
                <span class="text-[10px] font-bold mt-1">Discover</span>
            </a>
            <a href="history.php" class="flex flex-col items-center justify-center w-full h-full text-slate-500 hover:text-secondary transition-colors">
                <span class="material-symbols-outlined text-[24px]">receipt_long</span>
                <span class="text-[10px] font-bold mt-1">History</span>
            </a>
        </div>
    </nav>
</body>
</html>
