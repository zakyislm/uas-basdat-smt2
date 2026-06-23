<?php
require_once 'config.php';
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = __('Please login to view your cart.', 'Silakan login terlebih dahulu untuk melihat keranjang.');
    header("Location: auth");
    exit();
}
$user_id = $_SESSION['user_id'];


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout_submit'])) {
    $selected = isset($_POST['selected_items']) && is_array($_POST['selected_items']) ? array_map('intval', $_POST['selected_items']) : [];
    $_SESSION['checkout_cart_ids'] = $selected;
    header("Location: checkout");
    exit();
}


if (isset($_POST['update_cart'])) {
    $cart_id = intval($_POST['cart_id']);
    $qty = intval($_POST['quantity']);
    if ($qty > 0) {
        $c_stmt = $conn->prepare("SELECT motorcycle_id FROM carts WHERE id = ?");
        $c_stmt->bind_param("i", $cart_id);
        $c_stmt->execute();
        $m_id = $c_stmt->get_result()->fetch_assoc()['motorcycle_id'];
        $s_stmt = $conn->prepare("SELECT stock FROM motorcycles WHERE id = ?");
        $s_stmt->bind_param("i", $m_id);
        $s_stmt->execute();
        $stock = $s_stmt->get_result()->fetch_assoc()['stock'];
        if ($qty <= $stock) {
            $u_stmt = $conn->prepare("UPDATE carts SET quantity = ? WHERE id = ? AND user_id = ?");
            $u_stmt->bind_param("iii", $qty, $cart_id, $user_id);
            $u_stmt->execute();
        } else {
            $_SESSION['flash_message'] = __('Insufficient stock for this amount.', 'Stok tidak mencukupi untuk jumlah tersebut.');
        }
    } else {
        $d_stmt = $conn->prepare("DELETE FROM carts WHERE id = ? AND user_id = ?");
        $d_stmt->bind_param("ii", $cart_id, $user_id);
        $d_stmt->execute();
    }
    header("Location: cart");
    exit();
}


if (isset($_GET['remove'])) {
    $cart_id = intval($_GET['remove']);
    $d_stmt = $conn->prepare("DELETE FROM carts WHERE id = ? AND user_id = ?");
    $d_stmt->bind_param("ii", $cart_id, $user_id);
    $d_stmt->execute();
    $_SESSION['flash_message'] = __('Item removed from cart.', 'Barang dihapus dari keranjang.');
    header("Location: cart");
    exit();
}

$sql = "
    SELECT c.id as cart_id, c.quantity, m.id as motor_id, m.make, m.model, m.price, m.stock, m.discount_percent, m.discount_until
    FROM carts c
    JOIN motorcycles m ON c.motorcycle_id = m.id
    WHERE c.user_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result();
$grand_total = 0;
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>MotoInfy | <?= __('Shopping Cart', 'Keranjang Belanja') ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <?php include 'theme_config.php'; ?>
</head>
<body class="bg-background text-on-surface flex flex-col min-h-screen pb-16 md:pb-0">
    <?php include 'header.php'; ?>
    
    <main class="w-full flex-grow max-w-[1280px] mx-auto px-4 md:px-8 py-12">
        <div class="mb-8">
            <h1 class="text-4xl font-extrabold text-slate-900">
                <?= __('Shopping Cart', 'Keranjang Belanja') ?>
            </h1>
            <p class="text-slate-500 dark:text-slate-400 mt-2 text-lg"><?= __('Review your selected items before checkout.', 'Tinjau barang terpilih Anda sebelum checkout.') ?></p>
        </div>

        <?php if ($cart_items->num_rows > 0): ?>
        <form id="cartForm" method="POST" action="cart">
            <div class="flex flex-col lg:flex-row gap-8">
                
                <div class="w-full lg:w-2/3 space-y-4">
                    
                    <div class="bg-surface-container-low border border-outline-variant rounded-xl p-4 flex items-center justify-between shadow-sm select-none">
                        <label class="flex items-center gap-3 cursor-pointer font-bold text-sm">
                            <input type="checkbox" id="selectAll" checked class="rounded border-outline-variant text-secondary focus:ring-secondary">
                            <span><?= __('Select All Items', 'Pilih Semua Barang') ?></span>
                        </label>
                    </div>

                    <?php 
                    $cart_arr = [];
                    while($item = $cart_items->fetch_assoc()): 
                        
                        $is_discounted = false;
                        $final_price = $item['price'];
                        if ($item['discount_percent'] > 0 && ($item['discount_until'] === null || strtotime($item['discount_until']) > time())) {
                            $is_discounted = true;
                            $final_price = $item['price'] * (1 - ($item['discount_percent'] / 100));
                        }

                        $subtotal = $final_price * $item['quantity'];
                        $grand_total += $subtotal;
                        $cart_arr[] = $item;
                    ?>
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 flex gap-4 items-center relative hover:shadow-md transition-shadow">
                        
                        <div class="pl-2">
                            <input type="checkbox" name="selected_items[]" value="<?= $item['cart_id'] ?>" checked data-price="<?= $final_price ?>" data-qty="<?= $item['quantity'] ?>" class="cart-item-checkbox rounded border-outline-variant text-secondary focus:ring-secondary">
                        </div>

                        <div class="w-20 h-20 bg-surface-container rounded-lg flex items-center justify-center font-bold text-slate-400 text-lg uppercase">
                            <?= substr(htmlspecialchars($item['make']), 0, 3) ?>
                        </div>
                        
                        <div class="flex-grow">
                            <h3 class="text-base md:text-lg font-bold text-slate-900"><?= htmlspecialchars($item['make'] . ' ' . $item['model']) ?></h3>
                            <div class="flex flex-col md:flex-row md:items-center gap-1 md:gap-3 mt-1">
                                <?php if ($is_discounted): ?>
                                    <span class="text-xs text-red-500 line-through">Rp <?= number_format($item['price'], 0, ',', '.') ?></span>
                                    <span class="text-secondary font-bold text-sm md:text-base">Rp <?= number_format($final_price, 0, ',', '.') ?></span>
                                    <span class="text-[10px] font-bold text-red-600 bg-red-100 px-1.5 py-0.5 rounded"><?= $item['discount_percent'] ?>% OFF</span>
                                <?php else: ?>
                                    <span class="text-secondary font-bold text-sm md:text-base">Rp <?= number_format($item['price'], 0, ',', '.') ?></span>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs text-slate-500 mt-1"><?= __('Stock available', 'Stok tersedia') ?>: <?= $item['stock'] ?></p>
                        </div>
                        
                        
                        <div class="flex flex-col md:flex-row items-end md:items-center gap-3">
                            <button type="button" onclick="openUpdateQty(<?= $item['cart_id'] ?>, <?= $item['quantity'] ?>, <?= $item['stock'] ?>)" class="text-xs font-bold text-slate-500 hover:text-secondary flex items-center gap-1">
                                <span class="material-symbols-outlined text-[16px]">edit</span>
                                <span>Qty (<?= $item['quantity'] ?>)</span>
                            </button>
                            
                            <a href="cart?remove=<?= $item['cart_id'] ?>" class="text-red-500 hover:text-red-700 p-1" title="<?= __('Remove item', 'Hapus barang') ?>">
                                <span class="material-symbols-outlined text-[20px]">delete</span>
                            </a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                
                
                <div class="w-full lg:w-1/3">
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 sticky top-24 shadow-sm">
                        <h3 class="text-xl font-bold text-slate-900 mb-6 border-b pb-4"><?= __('Order Summary', 'Ringkasan Belanja') ?></h3>
                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between text-slate-600 dark:text-slate-400">
                                <span><?= __('Subtotal', 'Subtotal') ?></span>
                                <span id="summary-subtotal" class="font-bold text-slate-900">Rp <?= number_format($grand_total, 0, ',', '.') ?></span>
                            </div>
                            <div class="flex justify-between text-xl text-slate-900 font-extrabold pt-4 border-t border-outline-variant mt-4">
                                <span>Total</span>
                                <span id="summary-total" class="text-secondary">Rp <?= number_format($grand_total, 0, ',', '.') ?></span>
                            </div>
                        </div>
                        
                        <button type="submit" name="checkout_submit" class="w-full text-center bg-slate-900 hover:bg-slate-800 text-white py-3.5 rounded-lg font-bold text-lg hover:bg-secondary transition-all shadow-lg">
                            <?= __('Proceed to Checkout', 'Lanjut ke Checkout') ?>
                        </button>
                        <a href="discover" class="block text-center mt-4 text-slate-500 font-bold text-sm hover:text-secondary">
                            <?= __('Continue Shopping', 'Kembali Belanja') ?>
                        </a>
                    </div>
                </div>
            </div>
        </form>
        
        
        <div id="qtyModal" class="fixed inset-0 bg-black/50 z-[999] hidden flex items-center justify-center p-4">
            <div class="bg-surface-container-lowest rounded-2xl p-6 border border-outline-variant shadow-2xl max-w-sm w-full">
                <h3 class="font-bold text-lg mb-4"><?= __('Update Quantity', 'Ubah Jumlah') ?></h3>
                <form method="POST" action="cart">
                    <input type="hidden" name="cart_id" id="qty-cart-id">
                    <div class="mb-4">
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-2"><?= __('Quantity', 'Jumlah') ?></label>
                        <input type="number" name="quantity" id="qty-input" min="1" class="w-full p-2 border border-outline-variant rounded-lg bg-surface-container text-center font-bold text-lg">
                        <p class="text-xs text-slate-400 mt-1" id="qty-stock-hint"></p>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" onclick="closeQtyModal()" class="flex-1 border border-outline-variant py-2 rounded-xl text-sm font-bold hover:bg-surface-container transition-colors"><?= __('Cancel', 'Batal') ?></button>
                        <button type="submit" name="update_cart" class="flex-1 bg-secondary text-white py-2 rounded-xl text-sm font-bold hover:bg-opacity-90 transition-all"><?= __('Save', 'Simpan') ?></button>
                    </div>
                </form>
            </div>
        </div>

        <?php else: ?>
        <div class="text-center py-20 bg-surface-container-lowest rounded-xl border border-outline-variant border-dashed">
            <span class="material-symbols-outlined text-6xl text-slate-300 mb-4 block">remove_shopping_cart</span>
            <p class="text-slate-500 font-medium text-xl mb-6"><?= __('Your cart is currently empty.', 'Keranjang belanja Anda kosong.') ?></p>
            <a href="discover" class="bg-slate-900 text-white px-8 py-3 rounded-lg font-bold hover:bg-secondary transition-colors inline-block">
                <?= __('Start Discovering', 'Mulai Cari Motor') ?>
            </a>
        </div>
        <?php endif; ?>
    </main>
    
    <?php include 'footer.php'; ?>
    
    <script>
    function openUpdateQty(cartId, currentQty, stockLimit) {
        document.getElementById('qty-cart-id').value = cartId;
        const qtyInput = document.getElementById('qty-input');
        qtyInput.value = currentQty;
        qtyInput.max = stockLimit;
        document.getElementById('qty-stock-hint').innerText = '<?= __('Max Stock', 'Batas Maksimal Stok') ?>: ' + stockLimit;
        
        document.getElementById('qtyModal').classList.remove('hidden');
    }
    
    function closeQtyModal() {
        document.getElementById('qtyModal').classList.add('hidden');
    }

    
    document.addEventListener('DOMContentLoaded', () => {
        const checkboxes = document.querySelectorAll('.cart-item-checkbox');
        const selectAll = document.getElementById('selectAll');
        const summarySubtotal = document.getElementById('summary-subtotal');
        const summaryTotal = document.getElementById('summary-total');
        
        function recalculate() {
            let total = 0;
            let checkedCount = 0;
            checkboxes.forEach(cb => {
                if (cb.checked) {
                    const price = parseFloat(cb.dataset.price);
                    const qty = parseInt(cb.dataset.qty);
                    total += price * qty;
                    checkedCount++;
                }
            });
            
            
            const formatter = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            });
            const formatted = formatter.format(total).replace('IDR', 'Rp').trim();
            
            if(summarySubtotal) summarySubtotal.innerText = formatted;
            if(summaryTotal) summaryTotal.innerText = formatted;
            
            if (selectAll) {
                selectAll.checked = (checkedCount === checkboxes.length);
            }
        }
        
        checkboxes.forEach(cb => {
            cb.addEventListener('change', recalculate);
        });
        
        if (selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => {
                    cb.checked = this.checked;
                });
                recalculate();
            });
        }
        
        recalculate();
    });
    </script>
</body>
</html>
