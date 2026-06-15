<?php
require_once 'config.php';

// Check if user is admin or owner
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'owner')) {
    $_SESSION['flash_message'] = 'Akses ditolak. Halaman ini hanya untuk admin dan owner.';
    header("Location: auth.php");
    exit();
}

$page = isset($_GET['page']) ? $_GET['page'] : 'transactions';
$role = $_SESSION['role'];

// --- TRANSACTIONS LOGIC ---
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
    header("Location: admin.php?page=transactions");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_transaction'])) {
    $trx_id = intval($_POST['transaction_id']);
    $new_status = $_POST['status'];
    $new_payment_status = $_POST['payment_status'];
    
    $u_stmt = $conn->prepare("UPDATE transactions SET status = ?, payment_status = ? WHERE id = ?");
    $u_stmt->bind_param("ssi", $new_status, $new_payment_status, $trx_id);
    $u_stmt->execute();
    
    log_action($conn, $_SESSION['user_id'], "Mengubah transaksi ID $trx_id menjadi status: $new_status, pembayaran: $new_payment_status");
    $_SESSION['flash_message'] = "Data transaksi berhasil diperbarui.";
    header("Location: admin.php?page=transactions");
    exit();
}

// --- USERS LOGIC ---
if (isset($_GET['verify_user_id'])) {
    $verify_id = intval($_GET['verify_user_id']);
    $stmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
    $stmt->bind_param("i", $verify_id);
    $stmt->execute();
    log_action($conn, $_SESSION['user_id'], "Memverifikasi user ID $verify_id");
    $_SESSION['flash_message'] = "Pengguna berhasil diverifikasi.";
    header("Location: admin.php?page=users");
    exit();
}

// --- STOCK LOGIC ---
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
    header("Location: admin.php?page=stock");
    exit();
}

// --- OWNER LOGIC (MOTORCYCLES CRUD) ---
if ($role === 'owner') {
    if (isset($_GET['delete_motor'])) {
        $del_id = intval($_GET['delete_motor']);
        $d_stmt = $conn->prepare("DELETE FROM motorcycles WHERE id = ?");
        $d_stmt->bind_param("i", $del_id);
        $d_stmt->execute();
        log_action($conn, $_SESSION['user_id'], "Menghapus data motor ID $del_id");
        $_SESSION['flash_message'] = "Barang berhasil dihapus.";
        header("Location: admin.php?page=motorcycles");
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
        header("Location: admin.php?page=motorcycles");
        exit();
    }
}

// --- FETCH DATA ---
$users = $conn->query("SELECT * FROM users");
$motorcycles = $conn->query("SELECT * FROM motorcycles ORDER BY id DESC");
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
        
        .active-link { background-color: #e02928; color: #ffffff; }
        .inactive-link { color: #45464d; }
        .inactive-link:hover { background-color: #e0e3e5; transform: translateX(4px); }
    </style>
</head>

<body class="bg-background text-on-surface min-h-screen flex">
    <!-- SideNavBar -->
    <aside class="fixed left-0 top-0 h-full w-[280px] bg-surface-container-low border-r border-outline-variant flex flex-col p-4 gap-2 z-50">
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
            <a class="rounded-lg px-4 py-3 flex items-center gap-3 transition-all duration-200 <?= $page === 'transactions' ? 'active-link' : 'inactive-link' ?>" href="admin.php?page=transactions">
                <span class="material-symbols-outlined" <?= $page === 'transactions' ? 'style="font-variation-settings: \'FILL\' 1;"' : '' ?>>receipt_long</span>
                <span class="font-label-md">Transactions</span>
            </a>
            <a class="rounded-lg px-4 py-3 flex items-center gap-3 transition-all duration-200 <?= $page === 'users' ? 'active-link' : 'inactive-link' ?>" href="admin.php?page=users">
                <span class="material-symbols-outlined" <?= $page === 'users' ? 'style="font-variation-settings: \'FILL\' 1;"' : '' ?>>group</span>
                <span class="font-label-md">Users Management</span>
            </a>
            <a class="rounded-lg px-4 py-3 flex items-center gap-3 transition-all duration-200 <?= $page === 'stock' ? 'active-link' : 'inactive-link' ?>" href="admin.php?page=stock">
                <span class="material-symbols-outlined" <?= $page === 'stock' ? 'style="font-variation-settings: \'FILL\' 1;"' : '' ?>>inventory_2</span>
                <span class="font-label-md">Stock Management</span>
            </a>
            
            <?php if ($role === 'owner'): ?>
            <div class="mt-4 mb-2 px-4 text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Owner Only</div>
            <a class="rounded-lg px-4 py-3 flex items-center gap-3 transition-all duration-200 <?= $page === 'motorcycles' ? 'active-link' : 'inactive-link' ?>" href="admin.php?page=motorcycles">
                <span class="material-symbols-outlined" <?= $page === 'motorcycles' ? 'style="font-variation-settings: \'FILL\' 1;"' : '' ?>>two_wheeler</span>
                <span class="font-label-md">Motorcycle Catalog</span>
            </a>
            <a class="rounded-lg px-4 py-3 flex items-center gap-3 transition-all duration-200 <?= $page === 'logs' ? 'active-link' : 'inactive-link' ?>" href="admin.php?page=logs">
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

    <!-- Main Content -->
    <main class="ml-[280px] flex-1 flex flex-col">
        <!-- TopNavBar -->
        <header class="w-full top-0 sticky z-40 bg-surface-container-lowest border-b border-outline-variant flex justify-between items-center px-8 py-4">
            <div class="flex items-center gap-8">
                <span class="text-headline-sm font-extrabold text-secondary">MotoTrack Pro</span>
            </div>
            <div class="flex items-center gap-4">
                <a href="index.php" class="p-2 text-on-surface-variant hover:text-secondary font-label-md transition-colors">Go to Store</a>
                <?php include 'notifications_ui.php'; ?>
                <a href="settings.php" class="p-2 text-on-surface-variant hover:text-secondary font-label-md transition-colors flex items-center" title="Settings">
                    <span class="material-symbols-outlined">settings</span>
                </a>
            </div>
        </header>

        <div class="p-8 space-y-12 max-w-[1280px] mx-auto w-full">
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="bg-blue-50 text-blue-800 p-4 rounded-lg font-bold">
                    <?= htmlspecialchars($_SESSION['flash_message']) ?>
                </div>
                <?php unset($_SESSION['flash_message']); ?>
            <?php endif; ?>

            <?php if ($page === 'transactions'): ?>
            <!-- Transactions Section -->
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
                                <td class="px-6 py-4 font-mono text-sm">
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
                                    <form method="POST" action="admin.php?page=transactions" class="flex flex-col gap-2">
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
                                            <a href="admin.php?page=transactions&delete_transaction=<?= $t['id'] ?>" onclick="return confirm('Yakin ingin menghapus transaksi ini? Stok barang akan dikembalikan otomatis.')" class="bg-slate-200 text-red-600 text-[11px] font-bold px-3 py-1.5 rounded hover:bg-red-100 flex items-center justify-center">
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
            <!-- Users Section -->
            <section id="users">
                <div class="flex justify-between items-end mb-6">
                    <div>
                        <h2 class="font-headline-lg text-primary text-2xl font-bold">Users Management</h2>
                    </div>
                </div>
                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low border-b border-outline-variant">
                                <th class="px-6 py-3 font-label-md text-on-surface-variant uppercase">ID</th>
                                <th class="px-6 py-3 font-label-md text-on-surface-variant uppercase">User</th>
                                <th class="px-6 py-3 font-label-md text-on-surface-variant uppercase">Role</th>
                                <th class="px-6 py-3 font-label-md text-on-surface-variant uppercase">Status</th>
                                <th class="px-6 py-3 font-label-md text-on-surface-variant uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant">
                            <?php while($u = $users->fetch_assoc()): ?>
                            <tr class="hover:bg-surface-container-low">
                                <td class="px-6 py-3 font-mono"><?= $u['id'] ?></td>
                                <td class="px-6 py-3">
                                    <p class="font-bold"><?= htmlspecialchars($u['username']) ?></p>
                                    <p class="text-xs text-on-surface-variant"><?= htmlspecialchars($u['email']) ?></p>
                                </td>
                                <td class="px-6 py-3 font-bold uppercase text-xs"><?= $u['role'] ?></td>
                                <td class="px-6 py-3">
                                    <?php if ($u['is_verified']): ?>
                                        <span class="text-emerald-600 bg-emerald-100 px-2 py-1 rounded text-xs font-bold">Verified</span>
                                    <?php else: ?>
                                        <span class="text-orange-600 bg-orange-100 px-2 py-1 rounded text-xs font-bold">Unverified</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-3">
                                    <?php if (!$u['is_verified']): ?>
                                        <a href="admin.php?page=users&verify_user_id=<?= $u['id'] ?>" class="text-xs bg-emerald-600 text-white font-bold px-3 py-1.5 rounded hover:bg-emerald-700 inline-flex items-center gap-1 shadow-sm"><span class="material-symbols-outlined text-[14px]">verified</span> Verify</a>
                                    <?php else: ?>
                                        <span class="text-slate-400 font-bold">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <?php endif; ?>

            <?php if ($page === 'stock'): ?>
            <!-- Stock Management Section -->
            <section id="stock">
                <div class="flex justify-between items-end mb-6">
                    <div>
                        <h2 class="font-headline-lg text-primary text-2xl font-bold">Quick Stock Edit</h2>
                    </div>
                </div>
                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low border-b border-outline-variant">
                                <th class="px-6 py-3 font-label-md text-on-surface-variant uppercase">Motor</th>
                                <th class="px-6 py-3 font-label-md text-on-surface-variant uppercase">Current Stock</th>
                                <th class="px-6 py-3 font-label-md text-on-surface-variant uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant">
                            <?php while($m = $motorcycles->fetch_assoc()): ?>
                            <tr class="hover:bg-surface-container-low">
                                <td class="px-6 py-3 font-bold"><?= htmlspecialchars($m['make'] . ' ' . $m['model']) ?></td>
                                <td class="px-6 py-3 font-mono text-lg"><?= $m['stock'] ?></td>
                                <td class="px-6 py-3">
                                    <form method="POST" action="admin.php?page=stock" class="flex items-center gap-2">
                                        <input type="hidden" name="motorcycle_id" value="<?= $m['id'] ?>">
                                        <input type="number" name="stock" value="<?= $m['stock'] ?>" min="0" required class="w-20 border border-outline-variant rounded p-1 text-center font-mono">
                                        <button type="submit" name="update_stock" class="bg-primary text-white px-3 py-1.5 rounded text-xs font-bold hover:bg-opacity-80">Set</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php $motorcycles->data_seek(0); // Reset pointer if needed ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <?php endif; ?>

            <?php if ($page === 'motorcycles' && $role === 'owner'): ?>
            <!-- Motorcycles Catalog Section (Owner Only) -->
            <section id="motorcycles">
                <div class="flex justify-between items-end mb-6">
                    <div>
                        <h2 class="font-headline-lg text-primary text-2xl font-bold">Motorcycle Catalog</h2>
                        <p class="text-on-surface-variant">Add, edit, or remove master motorcycle data.</p>
                    </div>
                </div>
                <div class="flex flex-col lg:flex-row gap-8 items-start">
                    <!-- Add/Edit Form -->
                    <div class="w-full lg:w-1/3 bg-surface-container-lowest border border-outline-variant rounded-xl p-6 shadow-sm sticky top-24">
                        <h2 class="text-2xl font-bold text-slate-900 mb-6 flex items-center gap-2">
                            <span class="material-symbols-outlined"><?= $edit_motor ? 'edit_document' : 'add_circle' ?></span>
                            <?= $edit_motor ? 'Edit Unit' : 'Add New Unit' ?>
                        </h2>
                        
                        <form method="POST" action="admin.php?page=motorcycles" class="space-y-4">
                            <?php if ($edit_motor): ?>
                                <input type="hidden" name="motor_id" value="<?= $edit_motor['id'] ?>">
                            <?php endif; ?>
                            
                            <div>
                                <label class="block text-sm font-bold text-on-surface-variant mb-1">Make</label>
                                <input type="text" name="make" value="<?= $edit_motor ? htmlspecialchars($edit_motor['make']) : '' ?>" required class="w-full border-outline-variant rounded-lg p-2 focus:border-secondary focus:ring-1 focus:ring-secondary">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-on-surface-variant mb-1">Model</label>
                                <input type="text" name="model" value="<?= $edit_motor ? htmlspecialchars($edit_motor['model']) : '' ?>" required class="w-full border-outline-variant rounded-lg p-2 focus:border-secondary focus:ring-1 focus:ring-secondary">
                            </div>
                            <div class="flex gap-4">
                                <div class="flex-1">
                                    <label class="block text-sm font-bold text-on-surface-variant mb-1">Year</label>
                                    <input type="number" name="year" value="<?= $edit_motor ? $edit_motor['year'] : '2024' ?>" required class="w-full border-outline-variant rounded-lg p-2 focus:border-secondary focus:ring-1 focus:ring-secondary">
                                </div>
                                <div class="flex-1">
                                    <label class="block text-sm font-bold text-on-surface-variant mb-1">Stock</label>
                                    <input type="number" name="stock" value="<?= $edit_motor ? $edit_motor['stock'] : '1' ?>" min="0" required class="w-full border-outline-variant rounded-lg p-2 focus:border-secondary focus:ring-1 focus:ring-secondary">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-on-surface-variant mb-1">Price (Rp)</label>
                                <input type="number" name="price" value="<?= $edit_motor ? $edit_motor['price'] : '' ?>" required class="w-full border-outline-variant rounded-lg p-2 focus:border-secondary focus:ring-1 focus:ring-secondary font-mono">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-on-surface-variant mb-1">Description</label>
                                <textarea name="description" rows="3" class="w-full border-outline-variant rounded-lg p-2 focus:border-secondary focus:ring-1 focus:ring-secondary"><?= $edit_motor ? htmlspecialchars($edit_motor['description']) : '' ?></textarea>
                            </div>
                            
                            <div class="pt-4 flex gap-2">
                                <button type="submit" name="save_motor" class="flex-1 bg-slate-900 text-white py-2 rounded-lg font-bold hover:bg-slate-800 transition-colors">
                                    <?= $edit_motor ? 'Save Changes' : 'Add Unit' ?>
                                </button>
                                <?php if ($edit_motor): ?>
                                    <a href="admin.php?page=motorcycles" class="px-4 py-2 bg-surface-container-high text-slate-700 rounded-lg font-bold hover:bg-surface-dim transition-colors text-center">Cancel</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>

                    <!-- Motorcycle List Table -->
                    <div class="w-full lg:w-2/3 bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-sm">
                        <div class="p-6 border-b border-outline-variant bg-surface-container-low flex justify-between items-center">
                            <h3 class="text-xl font-bold text-slate-900">Fleet Catalog</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-outline-variant">
                                        <th class="px-4 py-3 font-label-md text-on-surface-variant uppercase text-xs">ID</th>
                                        <th class="px-4 py-3 font-label-md text-on-surface-variant uppercase text-xs">Make & Model</th>
                                        <th class="px-4 py-3 font-label-md text-on-surface-variant uppercase text-xs">Price</th>
                                        <th class="px-4 py-3 font-label-md text-on-surface-variant uppercase text-xs">Stock</th>
                                        <th class="px-4 py-3 font-label-md text-on-surface-variant uppercase text-xs text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-outline-variant">
                                    <?php while($m = $motorcycles->fetch_assoc()): ?>
                                    <tr class="hover:bg-surface-container-low transition-colors">
                                        <td class="px-4 py-3 font-mono text-sm text-slate-500">#<?= $m['id'] ?></td>
                                        <td class="px-4 py-3">
                                            <p class="font-bold text-slate-900"><?= htmlspecialchars($m['make']) ?></p>
                                            <p class="text-sm text-on-surface-variant"><?= htmlspecialchars($m['model']) ?></p>
                                        </td>
                                        <td class="px-4 py-3 font-mono text-emerald-600 font-bold">Rp <?= number_format($m['price'], 0, ',', '.') ?></td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-1 bg-slate-200 text-slate-800 rounded font-bold text-xs"><?= $m['stock'] ?></span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <a href="admin.php?page=motorcycles&edit_motor_id=<?= $m['id'] ?>" class="text-blue-600 hover:text-blue-800 font-bold text-sm mr-3">Edit</a>
                                            <a href="admin.php?page=motorcycles&delete_motor=<?= $m['id'] ?>" onclick="return confirm('Yakin ingin menghapus barang ini?')" class="text-secondary hover:text-red-800 font-bold text-sm">Delete</a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <?php if ($page === 'logs' && $role === 'owner'): ?>
            <!-- Owner Section: System Logs -->
            <section id="logs" class="space-y-4">
                <div class="flex justify-between items-end mb-2">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-secondary">terminal</span>
                        <h3 class="text-2xl font-bold text-primary">System Logs (Audit Trail)</h3>
                        <span class="ml-2 px-2 py-0.5 bg-primary text-on-primary text-[10px] font-bold uppercase rounded">Owner Access Only</span>
                    </div>
                </div>
                <div class="bg-black rounded-lg border-t-8 border-slate-800 shadow-2xl overflow-hidden font-mono text-[13px] leading-relaxed">
                    <!-- Terminal Header -->
                    <div class="px-4 py-1.5 bg-slate-800 flex items-center gap-2 border-b border-slate-700">
                        <div class="flex gap-1.5">
                            <div class="w-3 h-3 rounded-full bg-red-500/80"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-500/80"></div>
                            <div class="w-3 h-3 rounded-full bg-green-500/80"></div>
                        </div>
                        <span class="text-slate-400 text-[11px] ml-4">root@mototrack-pro: /var/log/audit</span>
                    </div>
                    <!-- Terminal Content -->
                    <div class="p-6 h-[400px] overflow-y-auto terminal-scrollbar bg-[#0a0a0a] text-slate-300" id="terminal-content">
                        <?php 
                        $logs = $conn->query("SELECT l.*, u.username, u.role FROM logs l JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 200");
                        while($log = $logs->fetch_assoc()): 
                            $timeStr = date('H:i:s', strtotime($log['created_at']));
                            $dateStr = date('Y-m-d', strtotime($log['created_at']));
                            
                            // Determine text color based on action keywords for visual flair
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
</body>
</html>
