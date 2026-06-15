<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'owner')) {
    $_SESSION['flash_message'] = 'Akses ditolak. Halaman ini hanya untuk admin dan owner.';
    header("Location: auth");
    exit();
}

$page = isset($_GET['page']) ? $_GET['page'] : 'transactions';
$role = $_SESSION['role'];

if (isset($_GET['delete_transaction'])) {
    $trx_id = intval($_GET['delete_transaction']);
    $b_stmt = $conn->prepare("SELECT motorcycle_id, quantity FROM transactions WHERE id = ?");
    $b_stmt->bind_param("i", $trx_id);
    $b_stmt->execute();
    $b_res = $b_stmt->get_result();
    
    if ($b_res->num_rows > 0) {
        $trx = $b_res->fetch_assoc();
        $m_stmt = $conn->prepare("UPDATE motorcycles SET stock = stock + ? WHERE id = ?");
        $m_stmt->bind_param("ii", $trx['quantity'], $trx['motorcycle_id']);
        $m_stmt->execute();
        
        $d_stmt = $conn->prepare("DELETE FROM transactions WHERE id = ?");
        $d_stmt->bind_param("i", $trx_id);
        $d_stmt->execute();
        
        log_action($conn, $_SESSION['user_id'], "Menghapus transaksi ID $trx_id dan mengembalikan $trx[quantity] stok ke motor ID $trx[motorcycle_id]");
        $_SESSION['flash_message'] = "Transaksi dihapus dan stok telah dikembalikan.";
    }
    header("Location: admin?page=transactions");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_transaction'])) {
    $trx_id = intval($_POST['transaction_id']);
    $new_status = $_POST['status'];
    $new_payment_status = $_POST['payment_status'];
    
    $u_stmt = $conn->prepare("UPDATE transactions SET status = ?, payment_status = ? WHERE id = ?");
    $u_stmt->bind_param("ssi", $new_status, $new_payment_status, $trx_id);
    $u_stmt->execute();

    $t_stmt = $conn->query("SELECT user_id FROM transactions WHERE id = $trx_id");
    if ($t_stmt && $t_row = $t_stmt->fetch_assoc()) {
        $trx_user_id = $t_row['user_id'];
        $conn->query("INSERT INTO notifications (user_id, message, link, icon, color, bg) VALUES ($trx_user_id, 'Status pesanan #TX-$trx_id diperbarui: $new_status ($new_payment_status)', 'history.php', 'info', 'text-blue-500', 'bg-blue-100')");
    }
    
    log_action($conn, $_SESSION['user_id'], "Mengubah transaksi ID $trx_id menjadi status: $new_status, pembayaran: $new_payment_status");
    $_SESSION['flash_message'] = "Data transaksi berhasil diperbarui.";
    if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'page=users') !== false) {
        header("Location: admin?page=users");
    } else {
        header("Location: admin?page=transactions");
    }
    exit();
}

if (isset($_GET['verify_user_id'])) {
    $verify_id = intval($_GET['verify_user_id']);
    $stmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
    $stmt->bind_param("i", $verify_id);
    $stmt->execute();

    $conn->query("INSERT INTO notifications (user_id, message, link, icon, color, bg) VALUES ($verify_id, 'Akun Anda telah diverifikasi! Sekarang Anda dapat melakukan transaksi.', 'index.php', 'verified', 'text-emerald-500', 'bg-emerald-100')");
    
    log_action($conn, $_SESSION['user_id'], "Memverifikasi user ID $verify_id");
    $_SESSION['flash_message'] = "Pengguna berhasil diverifikasi.";
    header("Location: admin?page=users");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_stock'])) {
    $m_id = intval($_POST['motorcycle_id']);
    $new_stock = intval($_POST['stock']);
    
    if ($new_stock >= 0) {
        $s_stmt = $conn->prepare("UPDATE motorcycles SET stock = ? WHERE id = ?");
        $s_stmt->bind_param("ii", $new_stock, $m_id);
        $s_stmt->execute();
        log_action($conn, $_SESSION['user_id'], "Mengubah stok motor ID $m_id menjadi $new_stock");
        $_SESSION['flash_message'] = "Stok barang berhasil diperbarui.";
    } else {
        $_SESSION['flash_message'] = "Stok tidak boleh minus.";
    }
    header("Location: admin?page=stock");
    exit();
}

if ($role === 'owner') {
    if (isset($_GET['delete_motor'])) {
        $del_id = intval($_GET['delete_motor']);
        $d_stmt = $conn->prepare("DELETE FROM motorcycles WHERE id = ?");
        $d_stmt->bind_param("i", $del_id);
        $d_stmt->execute();
        log_action($conn, $_SESSION['user_id'], "Menghapus data motor ID $del_id");
        $_SESSION['flash_message'] = "Barang berhasil dihapus.";
        header("Location: admin?page=motorcycles");
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_motor'])) {
        $make = $_POST['make'];
        $model = $_POST['model'];
        $year = intval($_POST['year']);
        $price = floatval($_POST['price']);
        $description = $_POST['description'];
        $stock = intval($_POST['stock']);

        if (isset($_POST['motor_id']) && !empty($_POST['motor_id'])) {
            $id = intval($_POST['motor_id']);
            $u_stmt = $conn->prepare("UPDATE motorcycles SET make=?, model=?, year=?, price=?, description=?, stock=? WHERE id=?");
            $u_stmt->bind_param("ssidsii", $make, $model, $year, $price, $description, $stock, $id);
            $u_stmt->execute();
            log_action($conn, $_SESSION['user_id'], "Mengupdate data motor ID $id menjadi $make $model (Stok: $stock)");
            $_SESSION['flash_message'] = "Barang berhasil diupdate.";
        } else {
            $i_stmt = $conn->prepare("INSERT INTO motorcycles (make, model, year, price, description, stock) VALUES (?, ?, ?, ?, ?, ?)");
            $i_stmt->bind_param("ssidsi", $make, $model, $year, $price, $description, $stock);
            $i_stmt->execute();
            $new_id = $conn->insert_id;
            log_action($conn, $_SESSION['user_id'], "Menambahkan motor baru: $make $model (ID: $new_id)");
            $_SESSION['flash_message'] = "Barang baru berhasil ditambahkan.";
        }
        header("Location: admin?page=motorcycles");
        exit();
    }
}

$u_search = isset($_GET['u_search']) ? $conn->real_escape_string($_GET['u_search']) : '';
$u_filter = isset($_GET['u_filter']) ? $_GET['u_filter'] : 'all';

$u_query = "SELECT * FROM users WHERE 1=1";
if ($u_search !== '') {
    $u_query .= " AND (username LIKE '%$u_search%' OR email LIKE '%$u_search%' OR id LIKE '%$u_search%')";
}
if ($u_filter === 'verified') $u_query .= " AND is_verified = 1";
elseif ($u_filter === 'unverified') $u_query .= " AND is_verified = 0";
elseif ($u_filter === 'admin') $u_query .= " AND role IN ('admin', 'owner')";
$users = $conn->query($u_query . " ORDER BY id DESC");

$m_search = isset($_GET['m_search']) ? $conn->real_escape_string($_GET['m_search']) : '';
$m_filter = isset($_GET['m_filter']) ? $_GET['m_filter'] : 'all';

$m_query = "SELECT * FROM motorcycles WHERE 1=1";
if ($m_search !== '') {
    $m_query .= " AND (make LIKE '%$m_search%' OR model LIKE '%$m_search%' OR id LIKE '%$m_search%')";
}
if ($m_filter === 'low') $m_query .= " AND stock < 2";
elseif ($m_filter === 'instock') $m_query .= " AND stock >= 2";
$motorcycles = $conn->query($m_query . " ORDER BY id DESC");
$transactions = $conn->query("
    SELECT t.id, t.transaction_date, t.quantity, t.type, t.status, t.payment_status, u.username, u.email, m.make, m.model 
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    JOIN motorcycles m ON t.motorcycle_id = m.id
    ORDER BY t.id DESC
");

$edit_motor = null;
if ($role === 'owner' && isset($_GET['edit_motor_id'])) {
    $e_stmt = $conn->prepare("SELECT * FROM motorcycles WHERE id = ?");
    $e_stmt->bind_param("i", intval($_GET['edit_motor_id']));
    $e_stmt->execute();
    $edit_motor = $e_stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>MotoTrack Pro | Admin Panel</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "secondary": "#bb0112",
                        "secondary-container": "#e02928",
                        "on-secondary-container": "#fffbff",
                        "primary": "#000000",
                        "on-primary": "#ffffff",
                        "background": "#f7f9fb",
                        "surface-container-lowest": "#ffffff",
                        "surface-container-low": "#f2f4f6",
                        "surface-container-high": "#e6e8ea",
                        "surface-container-highest": "#e0e3e5",
                        "outline-variant": "#c6c6cd",
                        "on-surface": "#191c1e",
                        "on-surface-variant": "#45464d",
                    },
                    "fontFamily": {
                        "body-sm": ["Hanken Grotesk"], "body-md": ["Hanken Grotesk"],
                        "label-sm": ["Hanken Grotesk"], "label-md": ["Hanken Grotesk"],
                        "headline-sm": ["Hanken Grotesk"], "headline-lg": ["Hanken Grotesk"],
                        "mono": ["JetBrains Mono", "monospace"]
                    }
                },
            },
        }
    </script>
    <style>
        body { font-family: 'Hanken Grotesk', sans-serif; }
        .terminal-scrollbar::-webkit-scrollbar { width: 6px; }
        .terminal-scrollbar::-webkit-scrollbar-track { background: #000; }
        .terminal-scrollbar::-webkit-scrollbar-thumb { background: #009668; border-radius: 3px; }
        
        @media (max-width: 768px) {
            table, thead, tbody, th, td, tr { display: block; }
            thead tr { display: none; }
            tr { border: 1px solid #c6c6cd; border-radius: 0.75rem; margin-bottom: 1rem; background: #fff; padding: 0.5rem; }
            td { border: none; border-bottom: 1px solid #f2f4f6; position: relative; padding: 0.75rem 0.5rem 0.75rem 45% !important; text-align: right; min-height: 2.5rem; display: flex; justify-content: flex-end; align-items: center; }
            td:last-child { border-bottom: none; justify-content: center; padding-left: 0.5rem !important; margin-top: 0.5rem; }
            td::before {
                content: attr(data-label);
                position: absolute; left: 0.5rem; top: 50%; transform: translateY(-50%); width: 40%;
                text-align: left; font-weight: 700; font-size: 0.75rem;
                text-transform: uppercase; color: #45464d;
            }
            td:last-child::before { display: none; }
        }
    </style>
</head>

<body class="bg-background text-on-surface min-h-screen flex">
    
    <div id="adminOverlay" class="fixed inset-0 bg-black/50 z-40 hidden opacity-0 transition-opacity duration-300 md:hidden"></div>

    <aside class="fixed left-0 top-0 h-full w-[280px] bg-surface-container-low border-r border-outline-variant flex flex-col p-4 gap-2 z-50 transform -translate-x-full md:translate-x-0 transition-transform duration-300">
        <div class="flex items-center gap-3 px-2 py-4">
            <div class="w-10 h-10 rounded-full <?= $role === 'owner' ? 'bg-slate-800' : 'bg-secondary' ?> flex items-center justify-center text-white font-bold text-xl">M</div>
            <div>
                <h1 class="font-headline-sm <?= $role === 'owner' ? 'text-slate-800' : 'text-secondary' ?> leading-none">
                    <?= $role === 'owner' ? 'Owner Panel' : 'Admin Panel' ?>
                </h1>
                <p class="text-xs text-on-surface-variant">Precision Management</p>
            </div>
        </div>
        <nav class="flex-1 flex flex-col gap-1 mt-4">
            <a class="rounded-lg px-4 py-3 flex items-center gap-3 transition-all duration-200 <?= $page === 'transactions' ? 'text-secondary bg-red-50 font-bold' : 'text-[#45464d] hover:bg-surface-container-highest hover:translate-x-1' ?>" href="admin?page=transactions">
                <span class="material-symbols-outlined" <?= $page === 'transactions' ? 'style="font-variation-settings: \'FILL\' 1;"' : '' ?>>receipt_long</span>
                <span class="font-label-md">Transactions</span>
            </a>
            <a class="rounded-lg px-4 py-3 flex items-center gap-3 transition-all duration-200 <?= $page === 'users' ? 'text-secondary bg-red-50 font-bold' : 'text-[#45464d] hover:bg-surface-container-highest hover:translate-x-1' ?>" href="admin?page=users">
                <span class="material-symbols-outlined" <?= $page === 'users' ? 'style="font-variation-settings: \'FILL\' 1;"' : '' ?>>group</span>
                <span class="font-label-md">Users Management</span>
            </a>
            <a class="rounded-lg px-4 py-3 flex items-center gap-3 transition-all duration-200 <?= $page === 'stock' ? 'text-secondary bg-red-50 font-bold' : 'text-[#45464d] hover:bg-surface-container-highest hover:translate-x-1' ?>" href="admin?page=stock">
                <span class="material-symbols-outlined" <?= $page === 'stock' ? 'style="font-variation-settings: \'FILL\' 1;"' : '' ?>>inventory_2</span>
                <span class="font-label-md">Stock Management</span>
            </a>
            
            <?php if ($role === 'owner'): ?>
            <div class="mt-4 mb-2 px-4 text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Owner Only</div>
            <a class="rounded-lg px-4 py-3 flex items-center gap-3 transition-all duration-200 <?= $page === 'motorcycles' ? 'text-slate-900 bg-slate-200 font-bold' : 'text-[#45464d] hover:bg-surface-container-highest hover:translate-x-1' ?>" href="admin?page=motorcycles">
                <span class="material-symbols-outlined" <?= $page === 'motorcycles' ? 'style="font-variation-settings: \'FILL\' 1;"' : '' ?>>two_wheeler</span>
                <span class="font-label-md">Motorcycle Catalog</span>
            </a>
            <a class="rounded-lg px-4 py-3 flex items-center gap-3 transition-all duration-200 <?= $page === 'logs' ? 'text-slate-900 bg-slate-200 font-bold' : 'text-[#45464d] hover:bg-surface-container-highest hover:translate-x-1' ?>" href="admin?page=logs">
                <span class="material-symbols-outlined" <?= $page === 'logs' ? 'style="font-variation-settings: \'FILL\' 1;"' : '' ?>>terminal</span>
                <span class="font-label-md">System Logs</span>
            </a>
            <?php endif; ?>
        </nav>
        <div class="mt-auto flex flex-col gap-1">
            <div class="mt-4 p-4 bg-surface-container-high rounded-xl flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-slate-300 flex items-center justify-center font-bold text-slate-700">
                    <?= substr(htmlspecialchars($_SESSION['username']), 0, 1) ?>
                </div>
                <div class="overflow-hidden">
                    <p class="font-label-md text-label-md truncate"><?= htmlspecialchars($_SESSION['username']) ?></p>
                    <p class="text-[10px] text-on-surface-variant uppercase tracking-widest"><?= htmlspecialchars($_SESSION['role']) ?></p>
                </div>
            </div>
        </div>
    </aside>

    <main class="md:ml-[280px] flex-1 flex flex-col">
        
        <header class="w-full top-0 sticky z-30 bg-surface-container-lowest border-b border-outline-variant flex justify-between items-center px-4 md:px-8 py-4">
            <div class="flex items-center gap-4">
                <button id="adminMenuBtn" class="md:hidden text-slate-600 hover:text-secondary p-2 transition-colors flex items-center justify-center rounded-full hover:bg-slate-50">
                    <span class="material-symbols-outlined text-[24px]">menu</span>
                </button>
                <span class="text-headline-sm font-extrabold text-secondary">MotoTrack Pro</span>
            </div>
            <div class="flex items-center gap-4">
                <a href="/" class="p-2 text-on-surface-variant hover:text-secondary font-label-md transition-colors">Go to Store</a>
                <?php include 'notifications_ui.php'; ?>
                <a href="settings" class="p-2 text-on-surface-variant hover:text-secondary font-label-md transition-colors flex items-center" title="Settings">
                    <span class="material-symbols-outlined">settings</span>
                </a>
            </div>
        </header>

        <div class="p-8 space-y-12 max-w-[1280px] mx-auto w-full">

            <?php if ($page === 'transactions'): ?>
            
            <section id="transactions">
                <div class="flex justify-between items-end mb-6">
                    <div>
                        <h2 class="font-headline-lg text-primary text-3xl font-bold">Transaction Management</h2>
                        <p class="text-on-surface-variant">Monitor and manage high-performance asset movements.</p>
                    </div>
                </div>
                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low border-b border-outline-variant">
                                <th class="px-6 py-4 font-label-md text-on-surface-variant uppercase tracking-wider">ID</th>
                                <th class="px-6 py-4 font-label-md text-on-surface-variant uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-4 font-label-md text-on-surface-variant uppercase tracking-wider">Motor</th>
                                <th class="px-6 py-4 font-label-md text-on-surface-variant uppercase tracking-wider">Qty/Type</th>
                                <th class="px-6 py-4 font-label-md text-on-surface-variant uppercase tracking-wider">Status & Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant">
                            <?php while($t = $transactions->fetch_assoc()): ?>
                            <tr class="<?= $t['payment_status'] == 'pending_verification' ? 'bg-amber-50' : 'hover:bg-surface-container-low' ?> transition-colors group">
                                <td data-label="ID" class="px-6 py-4 font-mono text-sm">
                                    #TX-<?= $t['id'] ?>
                                    <?php if($t['payment_status'] == 'pending_verification'): ?>
                                        <span class="block mt-1 text-[10px] font-bold text-amber-600 bg-amber-100 px-2 py-0.5 rounded-full w-max">Need Verification</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-slate-200 rounded-full flex items-center justify-center font-bold text-slate-600">
                                            <?= strtoupper(substr($t['username'], 0, 2)) ?>
                                        </div>
                                        <div>
                                            <p class="font-label-md"><?= htmlspecialchars($t['username']) ?></p>
                                            <p class="text-xs text-on-surface-variant"><?= htmlspecialchars($t['email']) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-label-md"><?= htmlspecialchars($t['make']) ?></p>
                                    <p class="text-xs text-on-surface-variant"><?= htmlspecialchars($t['model']) ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-bold"><?= sprintf("%02d", $t['quantity']) ?> Units</p>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700 uppercase"><?= $t['type'] ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <form method="POST" action="admin?page=transactions" class="flex flex-col gap-2">
                                        <input type="hidden" name="transaction_id" value="<?= $t['id'] ?>">
                                        <div class="flex gap-2">
                                            <div>
                                                <label class="text-[10px] uppercase font-bold text-slate-500">Proses</label>
                                                <select name="status" class="w-full text-xs border border-outline-variant rounded p-1.5 bg-white focus:ring-0">
                                                    <option value="pending" <?= $t['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="confirmed" <?= $t['status'] == 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                                    <option value="cancelled" <?= $t['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="text-[10px] uppercase font-bold text-slate-500">Bayar</label>
                                                <select name="payment_status" class="w-full text-xs border border-outline-variant rounded p-1.5 focus:ring-0">
                                                    <option value="unpaid" <?= $t['payment_status'] == 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                                                    <option value="pending_verification" <?= $t['payment_status'] == 'pending_verification' ? 'selected' : '' ?>>Pending Verification</option>
                                                    <option value="paid" <?= $t['payment_status'] == 'paid' ? 'selected' : '' ?>>Paid</option>
                                                    <option value="refunded" <?= $t['payment_status'] == 'refunded' ? 'selected' : '' ?>>Refunded</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="flex gap-2 mt-1">
                                            <button type="submit" name="update_transaction" class="flex-1 bg-secondary text-white text-[11px] font-bold px-3 py-1.5 rounded hover:bg-opacity-80">
                                                Update Status
                                            </button>
                                            <a href="admin?page=transactions&delete_transaction=<?= $t['id'] ?>" onclick="return confirm('Yakin ingin menghapus transaksi ini? Stok barang akan dikembalikan otomatis.')" class="bg-slate-200 text-red-600 text-[11px] font-bold px-3 py-1.5 rounded hover:bg-red-100 flex items-center justify-center">
                                                Hapus
                                            </a>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if ($transactions->num_rows == 0): ?>
                            <tr><td colspan="5" class="px-6 py-8 text-center text-on-surface-variant">No transactions found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <?php endif; ?>

            <?php if ($page === 'users'): ?>
            
            <section id="users">
                <div class="flex justify-between items-end mb-6">
                    <div>
                        <h2 class="font-headline-lg text-primary text-2xl font-bold">User Management</h2>
                    </div>
                </div>

                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 mb-6 flex flex-col md:flex-row gap-4 items-center justify-between shadow-sm">
                    <form method="GET" action="admin" class="flex-1 w-full relative">
                        <input type="hidden" name="page" value="users">
                        <span class="material-symbols-outlined absolute left-3 top-2.5 text-on-surface-variant">search</span>
                        <input type="text" name="u_search" value="<?= htmlspecialchars($u_search) ?>" placeholder="Search Username or ID..." class="w-full pl-10 border border-outline-variant rounded-lg p-2 focus:ring-1 focus:ring-[#0047b3] outline-none">
                    </form>
                    <form method="GET" action="admin" class="flex items-center gap-2 w-full md:w-auto" id="filterFormUsers">
                        <input type="hidden" name="page" value="users">
                        <?php if($u_search) echo '<input type="hidden" name="u_search" value="'.htmlspecialchars($u_search).'">'; ?>
                        <span class="material-symbols-outlined text-on-surface-variant">filter_list</span>
                        <select name="u_filter" onchange="document.getElementById('filterFormUsers').submit()" class="border border-outline-variant rounded-lg p-2 focus:ring-0 outline-none w-full md:w-auto font-bold text-sm">
                            <option value="all" <?= $u_filter === 'all' ? 'selected' : '' ?>>All Users</option>
                            <option value="verified" <?= $u_filter === 'verified' ? 'selected' : '' ?>>Verified</option>
                            <option value="unverified" <?= $u_filter === 'unverified' ? 'selected' : '' ?>>Unverified</option>
                            <option value="admin" <?= $u_filter === 'admin' ? 'selected' : '' ?>>Admins/Owners</option>
                        </select>
                    </form>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <?php while($u = $users->fetch_assoc()): ?>
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl flex flex-col overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                        
                        <div class="p-5 flex justify-between items-start">
                            <div class="flex gap-4 items-center">
                                <div class="w-12 h-12 bg-slate-100 rounded-lg flex items-center justify-center text-[#0047b3] border border-slate-200">
                                    <span class="material-symbols-outlined text-2xl">person</span>
                                </div>
                                <div>
                                    <h3 class="font-bold text-lg text-primary leading-tight"><?= htmlspecialchars($u['username']) ?></h3>
                                    <p class="text-xs text-on-surface-variant mt-0.5"><?= htmlspecialchars($u['email']) ?></p>
                                </div>
                            </div>
                            <?php if ($u['is_verified']): ?>
                                <span class="text-emerald-700 bg-emerald-50 px-2 py-1 rounded text-[10px] font-bold border border-emerald-200 flex items-center gap-1"><span class="material-symbols-outlined text-[12px]">check_circle</span> VERIFIED</span>
                            <?php else: ?>
                                <span class="text-orange-700 bg-orange-50 px-2 py-1 rounded text-[10px] font-bold border border-orange-200 flex items-center gap-1"><span class="material-symbols-outlined text-[12px]">pending</span> UNVERIFIED</span>
                            <?php endif; ?>
                        </div>

                        <div class="grid grid-cols-2 border-y border-outline-variant bg-slate-50">
                            <div class="p-3 text-center border-r border-outline-variant">
                                <p class="text-[10px] text-on-surface-variant font-bold uppercase mb-0.5">User ID</p>
                                <p class="font-bold text-slate-800 text-sm">#USR-<?= str_pad($u['id'], 4, '0', STR_PAD_LEFT) ?></p>
                            </div>
                            <div class="p-3 text-center">
                                <p class="text-[10px] text-on-surface-variant font-bold uppercase mb-0.5">Access Level</p>
                                <p class="font-bold text-[#0047b3] text-sm capitalize"><?= $u['role'] === 'user' ? 'Standard User' : ($u['role'] === 'owner' ? 'Global Admin' : 'Admin') ?></p>
                            </div>
                        </div>

                        <div class="p-5 flex-1 bg-white">
                            <h4 class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-3 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[14px]">point_of_sale</span> Active Transactions
                            </h4>
                            
                            <?php 
                            
                            $u_trx_stmt = $conn->prepare("SELECT t.*, m.make, m.model FROM transactions t JOIN motorcycles m ON t.motorcycle_id = m.id WHERE t.user_id = ? AND (t.status = 'pending' OR t.payment_status IN ('unpaid', 'pending_verification')) ORDER BY t.transaction_date DESC");
                            $u_trx_stmt->bind_param("i", $u['id']);
                            $u_trx_stmt->execute();
                            $u_trxs = $u_trx_stmt->get_result();
                            
                            if ($u_trxs->num_rows > 0): 
                                while($ut = $u_trxs->fetch_assoc()):
                            ?>
                            <div class="border border-outline-variant rounded-lg p-4 mb-3 bg-slate-50 relative">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <p class="text-[10px] text-slate-500 font-bold uppercase mb-0.5">TXN: <?= $ut['id'] ?>-B</p>
                                        <p class="font-bold text-sm text-slate-800 leading-tight"><?= htmlspecialchars($ut['make'] . ' ' . $ut['model']) ?></p>
                                    </div>
                                    <span class="bg-indigo-50 text-indigo-700 text-[10px] font-bold px-2 py-0.5 rounded border border-indigo-100 uppercase">Qty: <?= str_pad($ut['quantity'], 2, '0', STR_PAD_LEFT) ?></span>
                                </div>
                                <form method="POST" action="admin?page=users" class="m-0">
                                    <input type="hidden" name="transaction_id" value="<?= $ut['id'] ?>">
                                    <div class="grid grid-cols-2 gap-3 mb-3">
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-500 mb-1">Process Status</label>
                                            <select name="status" class="w-full text-[11px] font-medium border border-outline-variant rounded p-1.5 focus:ring-1 focus:ring-[#0047b3] outline-none bg-white">
                                                <option value="pending" <?= $ut['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="confirmed" <?= $ut['status'] == 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                                <option value="cancelled" <?= $ut['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-500 mb-1">Payment State</label>
                                            <select name="payment_status" class="w-full text-[11px] font-medium border border-outline-variant rounded p-1.5 focus:ring-1 focus:ring-[#0047b3] outline-none bg-white">
                                                <option value="unpaid" <?= $ut['payment_status'] == 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                                                <option value="pending_verification" <?= $ut['payment_status'] == 'pending_verification' ? 'selected' : '' ?>>Pending Verify</option>
                                                <option value="paid" <?= $ut['payment_status'] == 'paid' ? 'selected' : '' ?>>Paid</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="submit" name="update_transaction" class="flex-1 bg-[#0047b3] text-white text-[11px] font-bold px-3 py-2 rounded hover:bg-blue-800 transition-colors shadow-sm">
                                            Update Status
                                        </button>
                                        <a href="admin?page=transactions&delete_transaction=<?= $ut['id'] ?>" onclick="return confirm('Yakin ingin menghapus transaksi ini?')" class="w-[34px] rounded border border-red-200 bg-red-50 text-red-600 flex items-center justify-center hover:bg-red-100 transition-colors shadow-sm">
                                            <span class="material-symbols-outlined text-[16px]">delete</span>
                                        </a>
                                    </div>
                                </form>
                            </div>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <div class="text-center py-6 border border-dashed border-outline-variant rounded-lg bg-slate-50">
                                <p class="text-xs text-slate-400 font-medium">No active transactions</p>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!$u['is_verified']): ?>
                                <a href="admin?page=users&verify_user_id=<?= $u['id'] ?>" class="mt-4 w-full text-center text-[11px] bg-emerald-600 text-white font-bold px-3 py-2.5 rounded hover:bg-emerald-700 block shadow-sm transition-colors flex items-center justify-center gap-1"><span class="material-symbols-outlined text-[16px]">verified</span> Verify User</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </section>
            <?php endif; ?>

            <?php if ($page === 'stock'): ?>
            
            <section id="stock">
                <div class="flex justify-between items-end mb-6">
                    <div>
                        <h2 class="font-headline-lg text-primary text-2xl font-bold">Stock Management</h2>
                    </div>
                </div>

                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 mb-6 flex flex-col md:flex-row gap-4 items-center justify-between shadow-sm">
                    <form method="GET" action="admin" class="flex-1 w-full relative">
                        <input type="hidden" name="page" value="stock">
                        <span class="material-symbols-outlined absolute left-3 top-2.5 text-on-surface-variant">search</span>
                        <input type="text" name="m_search" value="<?= htmlspecialchars($m_search) ?>" placeholder="Search SKU, Make or Model..." class="w-full pl-10 border border-outline-variant rounded-lg p-2 focus:ring-1 focus:ring-[#0047b3] outline-none">
                    </form>
                    <form method="GET" action="admin" class="flex items-center gap-2 w-full md:w-auto" id="filterFormStock">
                        <input type="hidden" name="page" value="stock">
                        <?php if($m_search) echo '<input type="hidden" name="m_search" value="'.htmlspecialchars($m_search).'">'; ?>
                        <span class="material-symbols-outlined text-on-surface-variant">filter_list</span>
                        <select name="m_filter" onchange="document.getElementById('filterFormStock').submit()" class="border border-outline-variant rounded-lg p-2 focus:ring-0 outline-none w-full md:w-auto font-bold text-sm">
                            <option value="all" <?= $m_filter === 'all' ? 'selected' : '' ?>>All Inventory</option>
                            <option value="instock" <?= $m_filter === 'instock' ? 'selected' : '' ?>>In Stock</option>
                            <option value="low" <?= $m_filter === 'low' ? 'selected' : '' ?>>Low Stock (<2)</option>
                        </select>
                    </form>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <?php while($m = $motorcycles->fetch_assoc()): ?>
                    <?php $sku = "MTO-" . strtoupper(substr($m['make'], 0, 3)) . "-" . str_pad($m['id'], 4, '0', STR_PAD_LEFT); ?>
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 flex flex-col gap-4 relative shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-[10px] font-bold text-on-surface-variant tracking-wider uppercase mb-1">SKU: <?= $sku ?></p>
                                <h3 class="font-bold text-lg text-primary leading-tight"><?= htmlspecialchars($m['make'] . ' ' . $m['model']) ?></h3>
                            </div>
                            <?php if($m['stock'] < 2): ?>
                            <span class="bg-red-50 text-red-600 text-[10px] font-bold px-2 py-1 rounded border border-red-100 whitespace-nowrap">Low Stock</span>
                            <?php endif; ?>
                        </div>
                        <div class="border-t border-outline-variant pt-4 flex justify-between items-center mt-auto">
                            <div>
                                <p class="text-[10px] text-on-surface-variant font-medium">Available Inventory</p>
                                <p class="font-bold text-[#0047b3] text-xl"><?= $m['stock'] ?> units</p>
                            </div>
                            <div class="flex items-center gap-1 bg-blue-50 rounded-full p-1 border border-blue-100">
                                <form method="POST" action="admin?page=stock" class="m-0 p-0">
                                    <input type="hidden" name="motorcycle_id" value="<?= $m['id'] ?>">
                                    <input type="hidden" name="stock" value="<?= max(0, $m['stock'] - 1) ?>">
                                    <button type="submit" name="update_stock" class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-slate-400 hover:text-slate-600 shadow-sm border border-slate-200" <?= $m['stock'] <= 0 ? 'disabled' : '' ?>>
                                        <span class="material-symbols-outlined text-[18px]">remove</span>
                                    </button>
                                </form>
                                <form method="POST" action="admin?page=stock" class="m-0 p-0">
                                    <input type="hidden" name="motorcycle_id" value="<?= $m['id'] ?>">
                                    <input type="hidden" name="stock" value="<?= $m['stock'] + 1 ?>">
                                    <button type="submit" name="update_stock" class="w-8 h-8 rounded-full bg-[#0047b3] flex items-center justify-center text-white hover:bg-blue-800 shadow-sm">
                                        <span class="material-symbols-outlined text-[18px]">add</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <?php $motorcycles->data_seek(0); ?>
                </div>
            </section>
            <?php endif; ?>

            <?php if ($page === 'motorcycles' && $role === 'owner'): ?>
            
            <section id="motorcycles">
                <div class="flex justify-between items-end mb-6">
                    <div>
                        <h2 class="font-headline-lg text-primary text-2xl font-bold">Motorcycle Catalog</h2>
                        <p class="text-on-surface-variant">Add, edit, or remove master motorcycle data.</p>
                    </div>
                </div>
                
                <div class="flex flex-col lg:flex-row gap-8 items-start">
                    
                    <div class="w-full lg:w-1/3 bg-surface-container-lowest border border-outline-variant rounded-xl p-6 shadow-sm lg:sticky lg:top-24">
                        <h2 class="text-2xl font-bold text-slate-900 mb-6 flex items-center gap-2">
                            <span class="material-symbols-outlined"><?= $edit_motor ? 'edit_document' : 'add_circle' ?></span>
                            <?= $edit_motor ? 'Edit Unit' : 'Add New Unit' ?>
                        </h2>
                        
                        <form method="POST" action="admin?page=motorcycles" class="space-y-4">
                            <?php if ($edit_motor): ?>
                                <input type="hidden" name="motor_id" value="<?= $edit_motor['id'] ?>">
                            <?php endif; ?>
                            
                            <div>
                                <label class="block text-sm font-bold text-on-surface-variant mb-1">Make</label>
                                <input type="text" name="make" value="<?= $edit_motor ? htmlspecialchars($edit_motor['make']) : '' ?>" required class="w-full border-outline-variant rounded-lg p-2 focus:border-[#0047b3] focus:ring-1 focus:ring-[#0047b3]">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-on-surface-variant mb-1">Model</label>
                                <input type="text" name="model" value="<?= $edit_motor ? htmlspecialchars($edit_motor['model']) : '' ?>" required class="w-full border-outline-variant rounded-lg p-2 focus:border-[#0047b3] focus:ring-1 focus:ring-[#0047b3]">
                            </div>
                            <div class="flex gap-4">
                                <div class="flex-1">
                                    <label class="block text-sm font-bold text-on-surface-variant mb-1">Year</label>
                                    <input type="number" name="year" value="<?= $edit_motor ? $edit_motor['year'] : '2024' ?>" required class="w-full border-outline-variant rounded-lg p-2 focus:border-[#0047b3] focus:ring-1 focus:ring-[#0047b3]">
                                </div>
                                <div class="flex-1">
                                    <label class="block text-sm font-bold text-on-surface-variant mb-1">Stock</label>
                                    <input type="number" name="stock" value="<?= $edit_motor ? $edit_motor['stock'] : '1' ?>" min="0" required class="w-full border-outline-variant rounded-lg p-2 focus:border-[#0047b3] focus:ring-1 focus:ring-[#0047b3]">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-on-surface-variant mb-1">Price (Rp)</label>
                                <input type="number" name="price" value="<?= $edit_motor ? $edit_motor['price'] : '' ?>" required class="w-full border-outline-variant rounded-lg p-2 focus:border-[#0047b3] focus:ring-1 focus:ring-[#0047b3] font-mono">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-on-surface-variant mb-1">Description</label>
                                <textarea name="description" rows="3" class="w-full border-outline-variant rounded-lg p-2 focus:border-[#0047b3] focus:ring-1 focus:ring-[#0047b3]"><?= $edit_motor ? htmlspecialchars($edit_motor['description']) : '' ?></textarea>
                            </div>
                            
                            <div class="pt-4 flex gap-2">
                                <button type="submit" name="save_motor" class="flex-1 bg-[#0047b3] text-white py-2 rounded-lg font-bold hover:bg-blue-800 transition-colors">
                                    <?= $edit_motor ? 'Save Changes' : 'Add Unit' ?>
                                </button>
                                <?php if ($edit_motor): ?>
                                    <a href="admin?page=motorcycles" class="px-4 py-2 bg-surface-container-high text-slate-700 rounded-lg font-bold hover:bg-surface-dim transition-colors text-center">Cancel</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>

                    <div class="w-full lg:w-2/3">
                        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 mb-6 flex flex-col md:flex-row gap-4 items-center justify-between shadow-sm">
                            <form method="GET" action="admin" class="flex-1 w-full relative">
                                <input type="hidden" name="page" value="motorcycles">
                                <span class="material-symbols-outlined absolute left-3 top-2.5 text-on-surface-variant">search</span>
                                <input type="text" name="m_search" value="<?= htmlspecialchars($m_search) ?>" placeholder="Search SKU, Make or Model..." class="w-full pl-10 border border-outline-variant rounded-lg p-2 focus:ring-1 focus:ring-[#0047b3] outline-none">
                            </form>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php while($m = $motorcycles->fetch_assoc()): ?>
                            <?php $sku = "MTO-" . strtoupper(substr($m['make'], 0, 3)) . "-" . str_pad($m['id'], 4, '0', STR_PAD_LEFT); ?>
                            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 flex flex-col gap-4 relative shadow-sm hover:shadow-md transition-shadow">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-[10px] font-bold text-on-surface-variant tracking-wider uppercase mb-1">SKU: <?= $sku ?></p>
                                        <h3 class="font-bold text-lg text-primary leading-tight"><?= htmlspecialchars($m['make'] . ' ' . $m['model']) ?></h3>
                                    </div>
                                    <span class="bg-slate-100 text-slate-700 text-[10px] font-bold px-2 py-1 rounded border border-slate-200">Stock: <?= $m['stock'] ?></span>
                                </div>
                                <div class="mt-2 text-[#0047b3] font-bold font-mono">
                                    Rp <?= number_format($m['price'], 0, ',', '.') ?>
                                </div>
                                <div class="border-t border-outline-variant pt-3 flex justify-between items-center mt-auto">
                                    <a href="admin?page=motorcycles&edit_motor_id=<?= $m['id'] ?>" class="text-[#0047b3] hover:text-blue-800 font-bold text-xs flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">edit</span> Edit</a>
                                    <a href="admin?page=motorcycles&delete_motor=<?= $m['id'] ?>" onclick="return confirm('Yakin ingin menghapus barang ini?')" class="text-red-600 hover:text-red-800 font-bold text-xs flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">delete</span> Delete</a>
                                </div>
                            </div>
                            <?php endwhile; ?>
                            <?php $motorcycles->data_seek(0); ?>
                        </div>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <?php if ($page === 'logs' && $role === 'owner'): ?>
            
            <section id="logs" class="space-y-4">
                <div class="flex justify-between items-end mb-2">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-secondary">terminal</span>
                        <h3 class="text-2xl font-bold text-primary">System Logs (Audit Trail)</h3>
                        <span class="ml-2 px-2 py-0.5 bg-primary text-on-primary text-[10px] font-bold uppercase rounded">Owner Access Only</span>
                    </div>
                </div>
                <div class="bg-black rounded-lg border-t-8 border-slate-800 shadow-2xl overflow-hidden font-mono text-[13px] leading-relaxed">
                    
                    <div class="px-4 py-1.5 bg-slate-800 flex items-center gap-2 border-b border-slate-700">
                        <div class="flex gap-1.5">
                            <div class="w-3 h-3 rounded-full bg-red-500/80"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-500/80"></div>
                            <div class="w-3 h-3 rounded-full bg-green-500/80"></div>
                        </div>
                        <span class="text-slate-400 text-[11px] ml-4">root@mototrack-pro: /var/log/audit</span>
                    </div>
                    
                    <div class="p-6 h-[400px] overflow-y-auto terminal-scrollbar bg-[#0a0a0a] text-slate-300" id="terminal-content">
                        <?php 
                        $logs = $conn->query("SELECT l.*, u.username, u.role FROM logs l JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 200");
                        while($log = $logs->fetch_assoc()): 
                            $timeStr = date('H:i:s', strtotime($log['created_at']));
                            $dateStr = date('Y-m-d', strtotime($log['created_at']));

                            $actionColor = "text-slate-400";
                            $tag = "INFO:";
                            $tagColor = "text-blue-400";
                            $textLower = strtolower($log['action_detail']);
                            
                            if (strpos($textLower, 'login') !== false || strpos($textLower, 'mendaftar') !== false) {
                                $tag = "AUTH:"; $tagColor = "text-yellow-400";
                            } elseif (strpos($textLower, 'transaksi') !== false || strpos($textLower, 'stok') !== false) {
                                $tag = "ACTION:"; $tagColor = "text-[#009668]";
                            } elseif (strpos($textLower, 'hapus') !== false || strpos($textLower, 'cancelled') !== false) {
                                $tag = "WARN:"; $tagColor = "text-[#bb0112]";
                            }
                        ?>
                        <div class="flex gap-4 mb-2">
                            <span class="text-[#009668] shrink-0">[<?= $dateStr ?> <?= $timeStr ?>]</span>
                            <span class="<?= $tagColor ?> font-bold"><?= $tag ?></span>
                            <span class="<?= $actionColor ?>">
                                [<?= htmlspecialchars($log['username']) ?>#<?= htmlspecialchars($log['role']) ?>] <?= htmlspecialchars($log['action_detail']) ?>
                            </span>
                        </div>
                        <?php endwhile; ?>
                        <div class="flex gap-4 mt-4">
                            <span class="text-[#009668] shrink-0" id="live-timestamp"></span>
                            <span class="text-white">Waiting for new events...</span>
                            <span class="animate-pulse bg-white w-2 h-4 inline-block align-middle ml-1"></span>
                        </div>
                    </div>
                </div>
            </section>
            <?php endif; ?>

        </div>
    </main>

    <script>
        // Mobile Sidebar Logic
        document.addEventListener('DOMContentLoaded', () => {
            const adminBtn = document.getElementById('adminMenuBtn');
            const adminSidebar = document.querySelector('aside');
            const adminOverlay = document.getElementById('adminOverlay');

            if(adminBtn && adminSidebar && adminOverlay) {
                adminBtn.addEventListener('click', () => {
                    adminOverlay.classList.remove('hidden');
                    setTimeout(() => adminOverlay.classList.remove('opacity-0'), 10);
                    adminSidebar.classList.remove('-translate-x-full');
                });

                const closeAdmin = () => {
                    adminSidebar.classList.add('-translate-x-full');
                    adminOverlay.classList.add('opacity-0');
                    setTimeout(() => adminOverlay.classList.add('hidden'), 300);
                };

                adminOverlay.addEventListener('click', closeAdmin);
            }
        });

        // Micro-interaction for Terminal Live Timestamp
        function updateTerminalTime() {
            const now = new Date();
            const timeStr = `[${now.getFullYear()}-${(now.getMonth()+1).toString().padStart(2, '0')}-${now.getDate().toString().padStart(2, '0')} ${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}:${now.getSeconds().toString().padStart(2, '0')}]`;
            const tsElem = document.getElementById('live-timestamp');
            if(tsElem) tsElem.textContent = timeStr;
        }
        setInterval(updateTerminalTime, 1000);
        updateTerminalTime();

        // Auto-scroll terminal
        const terminal = document.getElementById('terminal-content');
        if(terminal) terminal.scrollTop = terminal.scrollHeight;
    </script>

    <?php if (isset($_SESSION['flash_message'])): ?>
    <div id="flash-popup" class="fixed top-24 right-4 md:right-8 bg-slate-900 text-white px-6 py-4 rounded-xl shadow-2xl z-[100] flex items-center gap-4 transition-all duration-300 transform translate-y-0 opacity-100">
        <span class="material-symbols-outlined text-secondary">info</span>
        <span class="font-medium text-sm"><?= htmlspecialchars($_SESSION['flash_message']) ?></span>
        <button onclick="document.getElementById('flash-popup').style.opacity='0'; setTimeout(()=>document.getElementById('flash-popup').remove(), 300)" class="text-slate-400 hover:text-white ml-2">
            <span class="material-symbols-outlined text-[18px]">close</span>
        </button>
    </div>
    <script>
        setTimeout(() => {
            const popup = document.getElementById('flash-popup');
            if(popup) {
                popup.style.opacity = '0';
                setTimeout(() => popup.remove(), 300);
            }
        }, 3000);
    </script>
    <?php unset($_SESSION['flash_message']); endif; ?>
    <?php include_once 'skeleton.php'; ?>
</body>
</html>
