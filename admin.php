<?php
require_once 'config.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'owner')) {
    $_SESSION['flash_message'] = __('Access denied. This page is only for admin and owner.', 'Akses ditolak. Halaman ini hanya untuk admin dan owner.');
    header("Location: auth");
    exit();
}
$page = isset($_GET['page']) ? $_GET['page'] : 'reports';
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
        log_action($conn, $_SESSION['user_id'], "Deleted transaction ID $trx_id and restored {$trx['quantity']} stock to motorcycle ID {$trx['motorcycle_id']} || Menghapus transaksi ID $trx_id dan mengembalikan {$trx['quantity']} stok ke motor ID {$trx['motorcycle_id']}");
        $_SESSION['flash_message'] = __("Transaction deleted and stock restored.", "Transaksi dihapus dan stok telah dikembalikan.");
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
        $conn->query("INSERT INTO notifications (user_id, message, link, icon, color, bg) VALUES ($trx_user_id, 'Order status #TX-$trx_id updated: $new_status ($new_payment_status) || Status pesanan #TX-$trx_id diperbarui: $new_status ($new_payment_status)', 'history', 'info', 'text-blue-500', 'bg-blue-100')");
    }
    log_action($conn, $_SESSION['user_id'], "Changed transaction ID $trx_id status to: $new_status, payment: $new_payment_status || Mengubah transaksi ID $trx_id menjadi status: $new_status, pembayaran: $new_payment_status");
    $_SESSION['flash_message'] = __("Transaction data successfully updated.", "Data transaksi berhasil diperbarui.");
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
    $conn->query("INSERT INTO notifications (user_id, message, link, icon, color, bg) VALUES ($verify_id, 'Your account has been verified! You can now make transactions. || Akun Anda telah diverifikasi! Sekarang Anda dapat melakukan transaksi.', '/', 'verified', 'text-emerald-500', 'bg-emerald-100')");
    log_action($conn, $_SESSION['user_id'], "Verified user ID $verify_id || Memverifikasi user ID $verify_id");
    $_SESSION['flash_message'] = __("User successfully verified.", "Pengguna berhasil diverifikasi.");
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
        log_action($conn, $_SESSION['user_id'], "Changed stock of motorcycle ID $m_id to $new_stock || Mengubah stok motor ID $m_id menjadi $new_stock");
        $_SESSION['flash_message'] = __("Stock successfully updated.", "Stok barang berhasil diperbarui.");
    } else {
        $_SESSION['flash_message'] = __("Stock cannot be negative.", "Stok tidak boleh minus.");
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
        log_action($conn, $_SESSION['user_id'], "Deleted motorcycle ID $del_id || Menghapus data motor ID $del_id");
        $_SESSION['flash_message'] = __("Item successfully deleted.", "Barang berhasil dihapus.");
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
            log_action($conn, $_SESSION['user_id'], "Updated motorcycle ID $id to $make $model (Stock: $stock) || Mengupdate data motor ID $id menjadi $make $model (Stok: $stock)");
            $_SESSION['flash_message'] = __("Item successfully updated.", "Barang berhasil diupdate.");
        } else {
            $i_stmt = $conn->prepare("INSERT INTO motorcycles (make, model, year, price, description, stock) VALUES (?, ?, ?, ?, ?, ?)");
            $i_stmt->bind_param("ssidsi", $make, $model, $year, $price, $description, $stock);
            $i_stmt->execute();
            $new_id = $conn->insert_id;
            log_action($conn, $_SESSION['user_id'], "Added new motorcycle: $make $model (ID: $new_id) || Menambahkan motor baru: $make $model (ID: $new_id)");
            $_SESSION['flash_message'] = __("New item successfully added.", "Barang baru berhasil ditambahkan.");
        }
        header("Location: admin?page=motorcycles");
        exit();
    }
    
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_direct_discount'])) {
        $m_id = $_POST['motorcycle_id'];
        $percentage = intval($_POST['percentage']);
        $duration_days = intval($_POST['duration_days']);
        $until = date('Y-m-d H:i:s', strtotime("+$duration_days days"));

        if ($m_id === 'all') {
            $stmt = $conn->prepare("UPDATE motorcycles SET discount_percent = ?, discount_until = ?");
            $stmt->bind_param("is", $percentage, $until);
            $stmt->execute();
            log_action($conn, $_SESSION['user_id'], "Applied direct discount of $percentage% to all motorcycles for $duration_days days || Menerapkan diskon langsung $percentage% untuk semua motor selama $duration_days hari");
            $_SESSION['flash_message'] = __("Direct discount of $percentage% applied to all motorcycles.", "Diskon langsung $percentage% berhasil diterapkan ke semua motor.");
        } else {
            $m_id = intval($m_id);
            $stmt = $conn->prepare("UPDATE motorcycles SET discount_percent = ?, discount_until = ? WHERE id = ?");
            $stmt->bind_param("isi", $percentage, $until, $m_id);
            $stmt->execute();
            log_action($conn, $_SESSION['user_id'], "Applied direct discount of $percentage% to motorcycle ID $m_id for $duration_days days || Menerapkan diskon langsung $percentage% untuk motor ID $m_id selama $duration_days hari");
            $_SESSION['flash_message'] = __("Direct discount of $percentage% applied to selected motorcycle.", "Diskon langsung $percentage% berhasil diterapkan ke motor terpilih.");
        }
        header("Location: admin?page=sales");
        exit();
    }

    if (isset($_GET['clear_direct_discount'])) {
        $m_id = intval($_GET['clear_direct_discount']);
        $stmt = $conn->prepare("UPDATE motorcycles SET discount_percent = 0, discount_until = NULL WHERE id = ?");
        $stmt->bind_param("i", $m_id);
        $stmt->execute();
        log_action($conn, $_SESSION['user_id'], "Removed direct discount on motorcycle ID $m_id || Menghapus diskon langsung pada motor ID $m_id");
        $_SESSION['flash_message'] = __("Direct discount removed.", "Diskon langsung telah dihapus.");
        header("Location: admin?page=sales");
        exit();
    }

    
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_coupon'])) {
        $code = strtoupper(trim($_POST['code']));
        $percentage = intval($_POST['percentage']);
        $usage_limit = intval($_POST['usage_limit']);
        $duration_days = intval($_POST['duration_days']);
        $until = date('Y-m-d H:i:s', strtotime("+$duration_days days"));

        $chk = $conn->prepare("SELECT id FROM discounts WHERE code = ?");
        $chk->bind_param("s", $code);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $_SESSION['flash_message'] = __("Coupon code already exists.", "Kode kupon sudah ada.");
        } else {
            $stmt = $conn->prepare("INSERT INTO discounts (code, percentage, valid_until, usage_limit, used_count, is_active) VALUES (?, ?, ?, ?, 0, 1)");
            $stmt->bind_param("sisi", $code, $percentage, $until, $usage_limit);
            $stmt->execute();
            log_action($conn, $_SESSION['user_id'], "Created new promo coupon: $code ($percentage% OFF, limit: $usage_limit) || Membuat kupon diskon baru: $code ($percentage% OFF, limit: $usage_limit)");
            $_SESSION['flash_message'] = __("Coupon $code created successfully.", "Kupon $code berhasil dibuat.");
        }
        header("Location: admin?page=sales");
        exit();
    }

    if (isset($_GET['delete_coupon'])) {
        $c_id = intval($_GET['delete_coupon']);
        $stmt = $conn->prepare("DELETE FROM discounts WHERE id = ?");
        $stmt->bind_param("i", $c_id);
        $stmt->execute();
        log_action($conn, $_SESSION['user_id'], "Deleted coupon ID $c_id || Menghapus kupon ID $c_id");
        $_SESSION['flash_message'] = __("Coupon code successfully deleted.", "Kupon diskon berhasil dihapus.");
        header("Location: admin?page=sales");
        exit();
    }

    
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_shipping'])) {
        $method_name = trim($_POST['method_name']);
        $base_cost = floatval($_POST['base_cost']);

        if (isset($_POST['shipping_id']) && !empty($_POST['shipping_id'])) {
            $s_id = intval($_POST['shipping_id']);
            $stmt = $conn->prepare("UPDATE shipping_methods SET method_name = ?, base_cost = ? WHERE id = ?");
            $stmt->bind_param("sdi", $method_name, $base_cost, $s_id);
            $stmt->execute();
            log_action($conn, $_SESSION['user_id'], "Updated shipping method ID $s_id to $method_name (Cost: $base_cost) || Mengupdate metode pengiriman ID $s_id menjadi $method_name (Biaya: $base_cost)");
            $_SESSION['flash_message'] = __("Shipping method updated successfully.", "Metode pengiriman berhasil diupdate.");
        } else {
            $stmt = $conn->prepare("INSERT INTO shipping_methods (method_name, base_cost) VALUES (?, ?)");
            $stmt->bind_param("sd", $method_name, $base_cost);
            $stmt->execute();
            $new_id = $conn->insert_id;
            log_action($conn, $_SESSION['user_id'], "Added new shipping method: $method_name (ID: $new_id, Cost: $base_cost) || Menambahkan metode pengiriman baru: $method_name (ID: $new_id, Biaya: $base_cost)");
            $_SESSION['flash_message'] = __("New shipping method added successfully.", "Metode pengiriman baru berhasil ditambahkan.");
        }
        header("Location: admin?page=shipping");
        exit();
    }

    if (isset($_GET['delete_shipping'])) {
        $s_id = intval($_GET['delete_shipping']);
        $chk = $conn->prepare("SELECT base_cost FROM shipping_methods WHERE id = ?");
        $chk->bind_param("i", $s_id);
        $chk->execute();
        $res = $chk->get_result();
        if ($res->num_rows > 0) {
            $shp = $res->fetch_assoc();
            if ($shp['base_cost'] <= 0) {
                $_SESSION['flash_message'] = __("Free/default shipping method cannot be deleted.", "Metode pengiriman gratis/default tidak dapat dihapus.");
            } else {
                $cnt = $conn->query("SELECT COUNT(*) AS total FROM shipping_methods WHERE base_cost > 0");
                $cnt_row = $cnt->fetch_assoc();
                if ($cnt_row['total'] <= 1) {
                    $_SESSION['flash_message'] = __("Failed. There must be at least 1 active paid shipping method!", "Gagal. Harus ada minimal 1 metode pengiriman berbayar yang aktif!");
                } else {
                    $stmt = $conn->prepare("DELETE FROM shipping_methods WHERE id = ?");
                    $stmt->bind_param("i", $s_id);
                    $stmt->execute();
                    log_action($conn, $_SESSION['user_id'], "Deleted shipping method ID $s_id || Menghapus metode pengiriman ID $s_id");
                    $_SESSION['flash_message'] = __("Shipping method deleted successfully.", "Metode pengiriman berhasil dihapus.");
                }
            }
        }
        header("Location: admin?page=shipping");
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
$t_search = isset($_GET['t_search']) ? $conn->real_escape_string($_GET['t_search']) : '';
$t_query = "
    SELECT t.id, t.transaction_date, t.quantity, t.type, t.status, t.payment_status, u.username, u.email, m.make, m.model, m.price
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    JOIN motorcycles m ON t.motorcycle_id = m.id
    WHERE 1=1
";
if ($t_search !== '') {
    $t_query .= " AND (t.id LIKE '%$t_search%' OR u.username LIKE '%$t_search%' OR u.email LIKE '%$t_search%' OR m.make LIKE '%$t_search%' OR m.model LIKE '%$t_search%' OR t.status LIKE '%$t_search%' OR t.payment_status LIKE '%$t_search%')";
}
$transactions = $conn->query($t_query . " ORDER BY t.id DESC");
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
    <title>MotoInfy | Admin Panel</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <?php include 'theme_config.php'; ?>
    <style>
        .terminal-scrollbar::-webkit-scrollbar { width: 6px; }
        .terminal-scrollbar::-webkit-scrollbar-track { background: #000; }
        .terminal-scrollbar::-webkit-scrollbar-thumb { background: #009668; border-radius: 3px; }
        
        aside::-webkit-scrollbar { width: 4px; }
        aside::-webkit-scrollbar-track { background: transparent; }
        aside::-webkit-scrollbar-thumb { background: var(--color-border); border-radius: 2px; }
        @media (max-width: 768px) {
            table, thead, tbody, th, td, tr { display: block; }
            thead tr { display: none; }
            tr { 
                border: 1px solid var(--color-border); 
                border-radius: 1.25rem; 
                margin-bottom: 1.5rem; 
                background: var(--color-surface); 
                box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
                overflow: hidden;
            }
            html.dark tr { box-shadow: 0 10px 25px -5px rgba(0,0,0,0.5); }
            td { 
                border: none; 
                border-bottom: 1px solid var(--color-border); 
                position: relative; 
                padding: 1.25rem 1.25rem 1.25rem 40% !important; 
                text-align: right; 
                min-height: 4rem;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: flex-end;
                gap: 0.25rem;
            }
            td:last-child { 
                border-bottom: none; 
                padding: 1.5rem !important; 
                background: var(--color-slate-50);
                align-items: center;
            }
            html.dark td:last-child { background: rgba(0,0,0,0.2); }
            td::before {
                content: attr(data-label);
                position: absolute; 
                left: 1.25rem; 
                top: 50%; 
                transform: translateY(-50%); 
                width: 35%;
                text-align: left; 
                font-weight: 800; 
                font-size: 0.7rem;
                letter-spacing: 0.05em;
                text-transform: uppercase; 
                color: var(--color-text-variant);
            }
            td:last-child::before { display: none; }
        }
    </style>
</head>
<body class="bg-background text-on-surface min-h-screen flex">
    <div id="adminOverlay" class="fixed inset-0 bg-black/50 z-40 hidden opacity-0 transition-opacity duration-300 md:hidden"></div>
    <aside class="fixed left-0 top-0 h-full w-[280px] bg-surface-container-low border-r border-outline-variant flex flex-col p-4 gap-2 z-50 transform -translate-x-full md:translate-x-0 transition-transform duration-300 overflow-y-auto">
        <div class="flex items-center gap-3 px-2 py-4">
            <div>
                <h1 class="font-headline-sm <?= $role === 'owner' ? 'text-on-surface' : 'text-secondary' ?> leading-none">
                    <?= $role === 'owner' ? 'Control Panel' : 'Admin Panel' ?>
                </h1>
                <p class="text-xs text-on-surface-variant">Precision Management</p>
            </div>
        </div>
        <nav class="flex-1 flex flex-col gap-1 mt-4">
            <a class="rounded-lg px-4 py-3 flex items-center gap-3 transition-all duration-200 <?= $page === 'reports' ? 'text-slate-900 bg-slate-200 font-bold' : 'text-on-surface-variant hover:bg-surface-container-highest hover:translate-x-1' ?>" href="admin?page=reports">
                <span class="material-symbols-outlined" <?= $page === 'reports' ? 'style="font-variation-settings: \'FILL\' 1;"' : '' ?>>monitoring</span>
                <span class="font-label-md">Dashboard</span>
            </a>
            <a class="rounded-lg px-4 py-3 flex items-center gap-3 transition-all duration-200 <?= $page === 'transactions' ? 'text-slate-900 bg-slate-200 font-bold' : 'text-on-surface-variant hover:bg-surface-container-highest hover:translate-x-1' ?>" href="admin?page=transactions">
                <span class="material-symbols-outlined" <?= $page === 'transactions' ? 'style="font-variation-settings: \'FILL\' 1;"' : '' ?>>receipt_long</span>
                <span class="font-label-md">Transactions</span>
            </a>
            <a class="rounded-lg px-4 py-3 flex items-center gap-3 transition-all duration-200 <?= $page === 'users' ? 'text-slate-900 bg-slate-200 font-bold' : 'text-on-surface-variant hover:bg-surface-container-highest hover:translate-x-1' ?>" href="admin?page=users">
                <span class="material-symbols-outlined" <?= $page === 'users' ? 'style="font-variation-settings: \'FILL\' 1;"' : '' ?>>group</span>
                <span class="font-label-md">Users Management</span>
            </a>
            <a class="rounded-lg px-4 py-3 flex items-center gap-3 transition-all duration-200 <?= $page === 'stock' ? 'text-slate-900 bg-slate-200 font-bold' : 'text-on-surface-variant hover:bg-surface-container-highest hover:translate-x-1' ?>" href="admin?page=stock">
                <span class="material-symbols-outlined" <?= $page === 'stock' ? 'style="font-variation-settings: \'FILL\' 1;"' : '' ?>>inventory_2</span>
                <span class="font-label-md">Stock Management</span>
            </a>
            <a class="rounded-lg px-4 py-3 flex items-center gap-3 transition-all duration-200 <?= $page === 'academic_demo' ? 'text-slate-900 bg-slate-200 font-bold' : 'text-on-surface-variant hover:bg-surface-container-highest hover:translate-x-1' ?>" href="admin?page=academic_demo">
                <span class="material-symbols-outlined" <?= $page === 'academic_demo' ? 'style="font-variation-settings: \'FILL\' 1;"' : '' ?>>science</span>
                <span class="font-label-md">TCL & Locking Demo</span>
            </a>
            <?php if ($role === 'owner'): ?>
            <div class="mt-4 mb-2 px-4 text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Owner Only</div>
            <a class="rounded-lg px-4 py-3 flex items-center gap-3 transition-all duration-200 <?= $page === 'motorcycles' ? 'text-slate-900 bg-slate-200 font-bold' : 'text-on-surface-variant hover:bg-surface-container-highest hover:translate-x-1' ?>" href="admin?page=motorcycles">
                <span class="material-symbols-outlined" <?= $page === 'motorcycles' ? 'style="font-variation-settings: \'FILL\' 1;"' : '' ?>>two_wheeler</span>
                <span class="font-label-md">Motorcycle Catalog</span>
            </a>
            <a class="rounded-lg px-4 py-3 flex items-center gap-3 transition-all duration-200 <?= $page === 'sales' ? 'text-slate-900 bg-slate-200 font-bold' : 'text-on-surface-variant hover:bg-surface-container-highest hover:translate-x-1' ?>" href="admin?page=sales">
                <span class="material-symbols-outlined" <?= $page === 'sales' ? 'style="font-variation-settings: \'FILL\' 1;"' : '' ?>>local_offer</span>
                <span class="font-label-md">Sales</span>
            </a>
            <a class="rounded-lg px-4 py-3 flex items-center gap-3 transition-all duration-200 <?= $page === 'shipping' ? 'text-slate-900 bg-slate-200 font-bold' : 'text-on-surface-variant hover:bg-surface-container-highest hover:translate-x-1' ?>" href="admin?page=shipping">
                <span class="material-symbols-outlined" <?= $page === 'shipping' ? 'style="font-variation-settings: \'FILL\' 1;"' : '' ?>>local_shipping</span>
                <span class="font-label-md">Shipping</span>
            </a>
            <a class="rounded-lg px-4 py-3 flex items-center gap-3 transition-all duration-200 <?= $page === 'logs' ? 'text-slate-900 bg-slate-200 font-bold' : 'text-on-surface-variant hover:bg-surface-container-highest hover:translate-x-1' ?>" href="admin?page=logs">
                <span class="material-symbols-outlined" <?= $page === 'logs' ? 'style="font-variation-settings: \'FILL\' 1;"' : '' ?>>terminal</span>
                <span class="font-label-md">System Logs</span>
            </a>
            <?php endif; ?>
        </nav>
        <div class="mt-auto flex flex-col gap-1">
            <div class="mt-4 p-4 bg-surface-container-high rounded-xl flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-slate-300 flex items-center justify-center font-bold text-on-surface">
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
                <span class="text-headline-sm font-extrabold text-secondary hidden md:block">MotoInfy</span>
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
                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 mb-6 flex flex-col md:flex-row gap-4 items-center justify-between shadow-sm">
                    <form method="GET" action="admin" class="flex-1 w-full relative">
                        <input type="hidden" name="page" value="transactions">
                        <span class="material-symbols-outlined absolute left-3 top-2.5 text-on-surface-variant">search</span>
                        <input type="text" name="t_search" value="<?= htmlspecialchars($t_search) ?>" placeholder="Search Transaction ID, Customer, Motor, or Status..." class="w-full pl-10 border border-outline-variant rounded-lg p-2 focus:ring-1 focus:ring-[#0047b3] outline-none">
                    </form>
                </div>
                <div class="md:bg-surface-container-lowest md:border md:border-outline-variant md:rounded-xl overflow-x-auto">
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
                            <tr class="hover:bg-surface-container-low transition-colors group">
                                <td data-label="ID" class="px-6 py-4 font-mono text-sm">
                                    #TX-<?= $t['id'] ?>
                                    <?php if($t['payment_status'] == 'pending_verification'): ?>
                                        <?php if($t['type'] == 'booking' && is_booking_dp_verified($conn, $t['id'])): ?>
                                            <span class="block mt-1 text-[10px] font-bold text-blue-600 bg-blue-100 px-2 py-0.5 rounded-full w-max">Need Settlement Verification</span>
                                        <?php else: ?>
                                            <span class="block mt-1 text-[10px] font-bold text-amber-600 bg-amber-100 px-2 py-0.5 rounded-full w-max">Need Verification</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Customer" class="px-6 py-4">
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
                                <td data-label="Motor" class="px-6 py-4">
                                    <p class="font-label-md"><?= htmlspecialchars($t['make']) ?></p>
                                    <p class="text-xs text-on-surface-variant"><?= htmlspecialchars($t['model']) ?></p>
                                </td>
                                 <td data-label="Qty/Type" class="px-6 py-4 text-xs">
                                     <p class="font-bold text-sm text-slate-900"><?= sprintf("%02d", $t['quantity']) ?> Unit(s)</p>
                                     <div class="mt-1 flex items-center gap-1.5">
                                         <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-slate-200 text-on-surface uppercase"><?= $t['type'] ?></span>
                                         <span class="text-slate-500 font-bold">Total: Rp <?= number_format($t['price'] * $t['quantity'], 0, ',', '.') ?></span>
                                     </div>
                                     <?php if ($t['type'] === 'booking'): ?>
                                         <div class="mt-2 text-[10px] text-slate-500 space-y-0.5 bg-slate-50 dark:bg-slate-900/50 p-1.5 rounded border border-outline-variant">
                                             <p>DP (20%): <strong class="text-emerald-600">Rp <?= number_format($t['price'] * $t['quantity'] * 0.20, 0, ',', '.') ?></strong></p>
                                             <p>Pelunasan (80%): <strong>Rp <?= number_format($t['price'] * $t['quantity'] * 0.80, 0, ',', '.') ?></strong></p>
                                             <?php if (is_booking_dp_verified($conn, $t['id'])): ?>
                                                 <p class="text-[9px] font-bold text-emerald-600 mt-1">&bull; DP is Verified (Paid)</p>
                                             <?php else: ?>
                                                 <p class="text-[9px] font-bold text-amber-600 mt-1">&bull; DP is Unverified/Unpaid</p>
                                             <?php endif; ?>
                                         </div>
                                     <?php endif; ?>
                                 </td>
                                <td data-label="Status & Actions" class="px-6 py-4">
                                    <form method="POST" action="admin?page=transactions" class="flex flex-col gap-2">
                                        <input type="hidden" name="transaction_id" value="<?= $t['id'] ?>">
                                        <div class="flex gap-2">
                                            <div>
                                                <label class="text-[10px] uppercase font-bold text-slate-500"><?= __('Process', 'Proses') ?></label>
                                                <select name="status" class="w-full text-xs border border-outline-variant rounded p-1.5 bg-surface-container-lowest focus:ring-0">
                                                    <option value="pending" <?= $t['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="confirmed" <?= $t['status'] == 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                                    <option value="cancelled" <?= $t['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="text-[10px] uppercase font-bold text-slate-500"><?= __('Payment', 'Bayar') ?></label>
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
                                            <a href="admin?page=transactions&delete_transaction=<?= $t['id'] ?>" onclick="return confirm('<?= __('Are you sure you want to delete this transaction? Motorcycle stock will be automatically restored.', 'Yakin ingin menghapus transaksi ini? Stok barang akan dikembalikan otomatis.') ?>')" class="bg-slate-200 text-red-600 text-[11px] font-bold px-3 py-1.5 rounded hover:bg-red-100 flex items-center justify-center">
                                                <?= __('Delete', 'Hapus') ?>
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
                                <div class="w-12 h-12 bg-surface-container rounded-lg flex items-center justify-center text-blue-600 dark:text-blue-400 border border-outline-variant">
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
                                <p class="font-bold text-on-surface text-sm">#USR-<?= str_pad($u['id'], 4, '0', STR_PAD_LEFT) ?></p>
                            </div>
                            <div class="p-3 text-center">
                                <p class="text-[10px] text-on-surface-variant font-bold uppercase mb-0.5">Access Level</p>
                                <p class="font-bold text-blue-600 dark:text-blue-400 text-sm capitalize"><?= $u['role'] === 'user' ? 'Standard User' : ($u['role'] === 'owner' ? 'Global Admin' : 'Admin') ?></p>
                            </div>
                        </div>
                        <div class="p-5 flex-1 bg-surface-container-lowest">
                            <h4 class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-3 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[14px]">point_of_sale</span> Active Transactions
                            </h4>
                            <?php 
                            $u_trx_stmt = $conn->prepare("SELECT t.*, m.make, m.model FROM transactions t JOIN motorcycles m ON t.motorcycle_id = m.id WHERE t.user_id = ? AND (t.status = 'pending' OR t.payment_status IN ('unpaid', 'pending_verification')) ORDER BY t.transaction_date DESC");
                            $u_trx_stmt->bind_param("i", $u['id']);
                            $u_trx_stmt->execute();
                            $u_trxs = $u_trx_stmt->get_result();
                            if ($u_trxs->num_rows > 0): 
                            ?>
                            <div class="flex flex-wrap gap-2">
                                <?php 
                                while($ut = $u_trxs->fetch_assoc()):
                                    $bg_color = "bg-surface-container border-outline-variant text-on-surface";
                                    if ($ut['status'] === 'cancelled') {
                                        $bg_color = "bg-red-50 dark:bg-red-950/20 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800/40";
                                    } elseif ($ut['payment_status'] === 'paid') {
                                        $bg_color = "bg-emerald-50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800/40";
                                    } elseif ($ut['payment_status'] === 'pending_verification') {
                                        $bg_color = "bg-amber-50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800/40";
                                    }
                                ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 border rounded-lg text-xs font-mono font-bold <?= $bg_color ?>">
                                    #TX-<?= $ut['id'] ?>
                                    <span class="text-[9px] px-1 bg-black/5 dark:bg-white/10 rounded font-sans uppercase font-normal text-slate-500"><?= $ut['status'] ?></span>
                                </span>
                                <?php endwhile; ?>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-4 border border-dashed border-outline-variant rounded-lg bg-surface-container-low">
                                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">No active transactions</p>
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
                        <input type="text" name="m_search" value="<?= htmlspecialchars($m_search) ?>" placeholder="Search SKU, Brand or Model..." class="w-full pl-10 border border-outline-variant rounded-lg p-2 focus:ring-1 focus:ring-[#0047b3] outline-none">
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
                                <p class="font-bold text-slate-900 text-xl"><?= $m['stock'] ?> units</p>
                            </div>
                            <div class="flex items-center gap-1 bg-surface-container rounded-full p-1 border border-outline-variant">
                                <form method="POST" action="admin?page=stock" class="m-0 p-0">
                                    <input type="hidden" name="motorcycle_id" value="<?= $m['id'] ?>">
                                    <input type="hidden" name="stock" value="<?= max(0, $m['stock'] - 1) ?>">
                                    <button type="submit" name="update_stock" class="w-8 h-8 rounded-full bg-red-600 dark:bg-red-500 text-white flex items-center justify-center hover:bg-red-700 dark:hover:bg-red-600 shadow-sm border border-red-700 dark:border-red-600 disabled:opacity-50 disabled:cursor-not-allowed" <?= $m['stock'] <= 0 ? 'disabled' : '' ?>>
                                        <span class="material-symbols-outlined text-[18px]">remove</span>
                                    </button>
                                </form>
                                <form method="POST" action="admin?page=stock" class="m-0 p-0">
                                    <input type="hidden" name="motorcycle_id" value="<?= $m['id'] ?>">
                                    <input type="hidden" name="stock" value="<?= $m['stock'] + 1 ?>">
                                    <button type="submit" name="update_stock" class="w-8 h-8 rounded-full bg-blue-600 dark:bg-blue-400 text-white dark:text-slate-900 flex items-center justify-center hover:bg-blue-700 dark:hover:bg-blue-500 shadow-sm border border-blue-700 dark:border-blue-500">
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
                        <h2 class="text-2xl font-bold text-slate-900 mb-6">
                            <?= $edit_motor ? 'Edit Unit' : 'Add New Unit' ?>
                        </h2>
                        <form method="POST" action="admin?page=motorcycles" class="space-y-4">
                            <?php if ($edit_motor): ?>
                                <input type="hidden" name="motor_id" value="<?= $edit_motor['id'] ?>">
                            <?php endif; ?>
                            <div>
                                <label class="block text-sm font-bold text-on-surface-variant mb-1">Brand</label>
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
                                <button type="submit" name="save_motor" class="flex-1 bg-blue-600 dark:bg-blue-400 text-white dark:text-slate-950 py-2 rounded-lg font-bold hover:bg-blue-700 dark:hover:bg-blue-500 transition-colors">
                                    <?= $edit_motor ? 'Save Changes' : 'Add Unit' ?>
                                </button>
                                <?php if ($edit_motor): ?>
                                    <a href="admin?page=motorcycles" class="px-4 py-2 bg-surface-container-high text-on-surface rounded-lg font-bold hover:bg-surface-dim transition-colors text-center">Cancel</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                    <div class="w-full lg:w-2/3">
                        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 mb-6 flex flex-col md:flex-row gap-4 items-center justify-between shadow-sm">
                            <form method="GET" action="admin" class="flex-1 w-full relative">
                                <input type="hidden" name="page" value="motorcycles">
                                <span class="material-symbols-outlined absolute left-3 top-2.5 text-on-surface-variant">search</span>
                                <input type="text" name="m_search" value="<?= htmlspecialchars($m_search) ?>" placeholder="Search SKU, Brand or Model..." class="w-full pl-10 border border-outline-variant rounded-lg p-2 focus:ring-1 focus:ring-[#0047b3] outline-none">
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
                                    <span class="bg-surface-container text-on-surface text-[10px] font-bold px-2 py-1 rounded border border-outline-variant">Stock: <?= $m['stock'] ?></span>
                                </div>
                                <div class="mt-2 text-slate-900 font-bold font-mono">
                                    Rp <?= number_format($m['price'], 0, ',', '.') ?>
                                </div>
                                <div class="border-t border-outline-variant pt-3 flex justify-between items-center mt-auto">
                                    <a href="admin?page=motorcycles&edit_motor_id=<?= $m['id'] ?>" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 font-bold text-xs flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">edit</span> Edit</a>
                                    <a href="admin?page=motorcycles&delete_motor=<?= $m['id'] ?>" onclick="return confirm('<?= __('Are you sure you want to delete this item?', 'Yakin ingin menghapus barang ini?') ?>')" class="text-red-600 hover:text-red-800 font-bold text-xs flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">delete</span> Delete</a>
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
                        <h3 class="text-2xl font-bold text-primary">System Logs</h3>
                    </div>
                </div>
                <div class="bg-black rounded-lg border-t-8 border-slate-800 shadow-2xl overflow-hidden font-mono text-[13px] leading-relaxed">
                    <div class="px-4 py-1.5 bg-slate-800 flex items-center gap-2 border-b border-slate-700">
                        <div class="flex gap-1.5">
                            <div class="w-3 h-3 rounded-full bg-red-500/80"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-500/80"></div>
                            <div class="w-3 h-3 rounded-full bg-green-500/80"></div>
                        </div>
                        <span class="text-slate-400 text-[11px] ml-4">root@motoinfy: /var/log/audit</span>
                    </div>
                    <div class="p-6 h-[400px] overflow-y-auto terminal-scrollbar bg-[#0a0a0a] text-slate-300" id="terminal-content">
                        <?php 
                        $query = "
                            SELECT 
                                l.created_at as time, 
                                CONCAT('[', u.username, '#', u.role, '] ', l.action_detail) as detail, 
                                'APP' as source 
                            FROM logs l 
                            JOIN users u ON l.user_id = u.id 
                            UNION ALL 
                            SELECT 
                                occurred_at as time, 
                                CONCAT('[', table_name, '] (', action_type, ') ', description) as detail, 
                                'DB_TRIG' as source 
                            FROM audit_logs 
                            ORDER BY time DESC 
                            LIMIT 300
                        ";
                        $logs = $conn->query($query);
                        if($logs): while($log = $logs->fetch_assoc()): 
                            $timeStr = date('H:i:s', strtotime($log['time']));
                            $dateStr = date('Y-m-d', strtotime($log['time']));
                            $actionColor = "text-slate-400";
                            $tag = "INFO:";
                            $tagColor = "text-blue-400";
                            $textLower = strtolower($log['detail']);
                            
                            if ($log['source'] === 'DB_TRIG') {
                                $tag = "DB_TRG:"; $tagColor = "text-purple-400";
                                if (strpos($textLower, 'delete') !== false || strpos($textLower, 'hapus') !== false) {
                                    $tagColor = "text-red-400";
                                } elseif (strpos($textLower, 'update') !== false) {
                                    $tagColor = "text-amber-400";
                                } else {
                                    $tagColor = "text-emerald-400";
                                }
                            } else {
                                if (strpos($textLower, 'login') !== false || strpos($textLower, 'mendaftar') !== false) {
                                    $tag = "AUTH:"; $tagColor = "text-yellow-400";
                                } elseif (strpos($textLower, 'transaksi') !== false || strpos($textLower, 'stok') !== false) {
                                    $tag = "ACTION:"; $tagColor = "text-emerald-success";
                                } elseif (strpos($textLower, 'hapus') !== false || strpos($textLower, 'cancelled') !== false) {
                                    $tag = "WARN:"; $tagColor = "text-secondary";
                                }
                            }
                        ?>
                        <div class="flex flex-col md:flex-row md:gap-4 mb-3 md:mb-2">
                            <span class="text-emerald-success shrink-0">[<?= $dateStr ?> <?= $timeStr ?>]</span>
                            <div class="flex gap-2 md:gap-4 mt-1 md:mt-0">
                                <span class="<?= $tagColor ?> font-bold w-[70px] inline-block"><?= $tag ?></span>
                                <span class="<?= $actionColor ?>">
                                    <?php
                                    $logDetail = $log['detail'];
                                    if (preg_match('/^(\[[^\]]+\]\s*)(.*)$/', $logDetail, $matches)) {
                                        $prefix = $matches[1];
                                        $msgPart = $matches[2];
                                        if (strpos($msgPart, ' || ') !== false) {
                                            $msgParts = explode(' || ', $msgPart);
                                            $logDetail = $prefix . __($msgParts[0], $msgParts[1]);
                                        }
                                    } else {
                                        if (strpos($logDetail, ' || ') !== false) {
                                            $parts = explode(' || ', $logDetail);
                                            $logDetail = __($parts[0], $parts[1]);
                                        }
                                    }
                                    ?>
                                    <?= htmlspecialchars($logDetail) ?>
                                </span>
                            </div>
                        </div>
                        <?php endwhile; endif; ?>
                        <div class="flex flex-col md:flex-row md:gap-4 mt-4">
                            <span class="text-emerald-success shrink-0" id="live-timestamp"></span>
                            <div class="flex items-center gap-2 mt-1 md:mt-0">
                                <span class="text-white">Waiting for new events...</span>
                                <span class="animate-pulse bg-surface-container-lowest w-2 h-4 inline-block align-middle ml-1"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <?php elseif ($page === 'reports'): ?>
                <?php include '_reports.php'; ?>
            <?php elseif ($page === 'academic_demo'): ?>
                <?php include '_academic_demo.php'; ?>
            <?php elseif ($page === 'sales' && $role === 'owner'): ?>
                <?php include '_sales_mgmt.php'; ?>
            <?php elseif ($page === 'shipping' && $role === 'owner'): ?>
                <?php include '_shipping_mgmt.php'; ?>
            <?php endif; ?>
        </div>
    </main>
    <script>
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
        function updateTerminalTime() {
            const now = new Date();
            const timeStr = `[${now.getFullYear()}-${(now.getMonth()+1).toString().padStart(2, '0')}-${now.getDate().toString().padStart(2, '0')} ${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}:${now.getSeconds().toString().padStart(2, '0')}]`;
            const tsElem = document.getElementById('live-timestamp');
            if(tsElem) tsElem.textContent = timeStr;
        }
        setInterval(updateTerminalTime, 1000);
        updateTerminalTime();
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
