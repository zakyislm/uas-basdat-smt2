<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = "Silakan login terlebih dahulu untuk melihat keranjang.";
    header("Location: auth");
    exit();
}

$user_id = $_SESSION['user_id'];

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
            $_SESSION['flash_message'] = "Stok tidak mencukupi untuk jumlah tersebut.";
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
    $_SESSION['flash_message'] = "Barang dihapus dari keranjang.";
    header("Location: cart");
    exit();
}

$sql = "
    SELECT c.id as cart_id, c.quantity, m.id as motor_id, m.make, m.model, m.price, m.stock 
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
    <title>MotoTrack Pro | Shopping Cart</title>
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
    <?php include 'header.php'; ?>

    <main class="w-full flex-grow max-w-[1280px] mx-auto px-8 py-12">
        <div class="mb-8">
            <h1 class="text-4xl font-extrabold text-slate-900">
                Shopping Cart
            </h1>
            <p class="text-slate-500 mt-2 text-lg">Review your selected items before checkout.</p>
        </div>

        <?php if ($cart_items->num_rows > 0): ?>
        <div class="flex flex-col lg:flex-row gap-8">
            
            <div class="w-full lg:w-2/3 space-y-4">
                <?php 
                $cart_arr = [];
                while($item = $cart_items->fetch_assoc()): 
                    $subtotal = $item['price'] * $item['quantity'];
                    $grand_total += $subtotal;
                    $cart_arr[] = $item;
                ?>
                <div class="bg-white border border-slate-200 rounded-xl p-4 flex gap-4 items-center">
                    <div class="w-24 h-24 bg-slate-100 rounded-lg flex items-center justify-center font-bold text-slate-400 text-xl uppercase">
                        <?= substr(htmlspecialchars($item['make']), 0, 3) ?>
                    </div>
                    <div class="flex-grow">
                        <h3 class="text-lg font-bold text-slate-900"><?= htmlspecialchars($item['make'] . ' ' . $item['model']) ?></h3>
                        <p class="text-secondary font-bold">Rp <?= number_format($item['price'], 0, ',', '.') ?></p>
                        <p class="text-xs text-slate-500 mt-1">Stock available: <?= $item['stock'] ?></p>
                    </div>
                    <div class="flex items-center gap-4">
                        <form method="POST" action="cart" class="flex items-center gap-2">
                            <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                            <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>" class="w-16 border-slate-300 rounded p-1 text-center font-bold text-slate-700">
                            <button type="submit" name="update_cart" class="text-xs bg-slate-100 text-slate-600 px-2 py-1.5 rounded font-bold hover:bg-slate-200">Update</button>
                        </form>
                        <a href="cart?remove=<?= $item['cart_id'] ?>" class="text-red-500 hover:text-red-700 p-2" title="Remove item">
                            <span class="material-symbols-outlined">delete</span>
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <div class="w-full lg:w-1/3">
                <div class="bg-white border border-slate-200 rounded-xl p-6 sticky top-24 shadow-sm">
                    <h3 class="text-xl font-bold text-slate-900 mb-6 border-b pb-4">Order Summary</h3>
                    
                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between text-slate-600">
                            <span>Subtotal</span>
                            <span class="font-bold">Rp <?= number_format($grand_total, 0, ',', '.') ?></span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>Processing Fee</span>
                            <span class="font-bold">Rp 0</span>
                        </div>
                        <div class="flex justify-between text-xl text-slate-900 font-extrabold pt-4 border-t border-slate-200 mt-4">
                            <span>Total</span>
                            <span class="text-secondary">Rp <?= number_format($grand_total, 0, ',', '.') ?></span>
                        </div>
                    </div>
                    
                    <a href="checkout" class="w-full block text-center bg-slate-900 text-white py-3.5 rounded-lg font-bold text-lg hover:bg-secondary transition-all shadow-lg shadow-slate-200">
                        Proceed to Checkout
                    </a>
                    
                    <a href="discover" class="block text-center mt-4 text-slate-500 font-bold text-sm hover:text-secondary">
                        Continue Shopping
                    </a>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="text-center py-20 bg-white rounded-xl border border-slate-200 border-dashed">
            <span class="material-symbols-outlined text-6xl text-slate-300 mb-4 block">remove_shopping_cart</span>
            <p class="text-slate-500 font-medium text-xl mb-6">Your cart is currently empty.</p>
            <a href="discover" class="bg-slate-900 text-white px-8 py-3 rounded-lg font-bold hover:bg-secondary transition-colors inline-block">
                Start Discovering
            </a>
        </div>
        <?php endif; ?>
    </main>

    <?php include 'footer.php'; ?>

    <nav class="md:hidden fixed bottom-0 left-0 right-0 w-full bg-white border-t border-slate-200 z-[999]" style="padding-bottom: env(safe-area-inset-bottom);">
        <div class="flex justify-around items-center h-16">
            <a href="/" class="flex flex-col items-center justify-center w-full h-full text-slate-500 hover:text-secondary transition-colors">
                <span class="material-symbols-outlined text-[24px]">home</span>
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
