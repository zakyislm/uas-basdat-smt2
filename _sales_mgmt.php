<?php
if (!defined('DB_HOST') && !isset($conn)) {
    exit('Direct access not allowed.');
}


$coupons_res = $conn->query("SELECT * FROM discounts ORDER BY id DESC");


$discounts_res = $conn->query("
    SELECT id, make, model, price, discount_percent, discount_until 
    FROM motorcycles 
    WHERE discount_percent > 0 
    ORDER BY discount_until ASC
");


$m_list_res = $conn->query("SELECT id, make, model FROM motorcycles ORDER BY make, model ASC");
$all_m = [];
while($row = $m_list_res->fetch_assoc()) {
    $all_m[] = $row;
}
?>
<section id="sales_management" class="space-y-8">
    <div class="mb-6">
        <h2 class="font-headline-lg text-primary text-3xl font-bold"><?= __('Sales & Promotions', 'Penjualan & Promosi') ?></h2>
        <p class="text-on-surface-variant"><?= __('Manage coupon discount codes and direct motorcycle price reductions.', 'Kelola kode kupon diskon dan potongan harga motor langsung.') ?></p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
        
        
        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-sm space-y-6">
            <h3 class="text-xl font-bold text-slate-900">
                <?= __('Direct Discounts', 'Potongan Langsung') ?>
            </h3>
            
            <form method="POST" action="admin?page=sales" class="space-y-4">
                <input type="hidden" name="save_direct_discount" value="1">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1"><?= __('Target Motorcycle', 'Target Motor') ?></label>
                    <select name="motorcycle_id" required class="w-full border-outline-variant bg-surface-container rounded-lg p-2.5 focus:ring-secondary">
                        <option value="all">-- <?= __('All Vehicles', 'Semua Motor') ?> --</option>
                        <?php foreach($all_m as $m): ?>
                            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['make'] . ' ' . $m['model']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex gap-4">
                    <div class="flex-1">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1"><?= __('Discount %', 'Diskon %') ?></label>
                        <input type="number" name="percentage" min="1" max="100" placeholder="12" required class="w-full border border-outline-variant bg-surface-container rounded-lg p-2 focus:ring-secondary">
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1"><?= __('Duration (Days)', 'Durasi (Hari)') ?></label>
                        <input type="number" name="duration_days" min="1" placeholder="3" required class="w-full border border-outline-variant bg-surface-container rounded-lg p-2 focus:ring-secondary">
                    </div>
                </div>
                <button type="submit" class="w-full bg-secondary text-white dark:text-slate-950 font-bold py-2.5 rounded-lg text-sm hover:bg-opacity-90 transition-all shadow-sm">
                    <?= __('Apply Direct Discount', 'Terapkan Potongan Langsung') ?>
                </button>
            </form>

            <div class="border-t border-outline-variant pt-6">
                <h4 class="font-bold text-sm text-slate-800 mb-3"><?= __('Active Price Reductions', 'Potongan Harga Aktif') ?></h4>
                <div class="space-y-3">
                    <?php if($discounts_res->num_rows == 0): ?>
                        <p class="text-xs text-slate-600 italic"><?= __('No active direct discounts currently.', 'Saat ini tidak ada potongan langsung yang aktif.') ?></p>
                    <?php else: while($d = $discounts_res->fetch_assoc()): ?>
                        <div class="bg-surface-container border border-outline-variant rounded-xl p-3 flex justify-between items-center text-xs">
                            <div>
                                <p class="font-bold text-slate-900"><?= htmlspecialchars($d['make'] . ' ' . $d['model']) ?></p>
                                <p class="text-slate-700 mt-1"><?= __('Discount: ', 'Diskon: ') ?><span class="text-red-500 font-bold"><?= $d['discount_percent'] ?>%</span> &bull; <?= __('Valid until: ', 'Berlaku hingga: ') ?><?= date('d M Y H:i', strtotime($d['discount_until'])) ?></p>
                            </div>
                            <a href="admin?page=sales&clear_direct_discount=<?= $d['id'] ?>" class="text-red-500 hover:text-red-700 font-bold p-1"><?= __('Clear', 'Hapus') ?></a>
                        </div>
                    <?php endwhile; endif; ?>
                </div>
            </div>
        </div>

        
        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-sm space-y-6">
            <h3 class="text-xl font-bold text-slate-900">
                <?= __('Promo Coupons', 'Kupon Kode') ?>
            </h3>
            
            <form method="POST" action="admin?page=sales" class="space-y-4">
                <input type="hidden" name="save_coupon" value="1">
                <div class="flex gap-4">
                    <div class="flex-1">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1"><?= __('Coupon Code', 'Kode Kupon') ?></label>
                        <input type="text" name="code" placeholder="PROMO10" required class="w-full border border-outline-variant bg-surface-container rounded-lg p-2 uppercase focus:ring-secondary">
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1"><?= __('Discount %', 'Diskon %') ?></label>
                        <input type="number" name="percentage" min="1" max="100" placeholder="10" required class="w-full border border-outline-variant bg-surface-container rounded-lg p-2 focus:ring-secondary">
                    </div>
                </div>
                <div class="flex gap-4">
                    <div class="flex-1">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1"><?= __('Usage Quota (Qty)', 'Kuota Penggunaan (Jumlah)') ?></label>
                        <input type="number" name="usage_limit" min="1" placeholder="100" required class="w-full border border-outline-variant bg-surface-container rounded-lg p-2 focus:ring-secondary">
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1"><?= __('Duration (Days)', 'Durasi (Hari)') ?></label>
                        <input type="number" name="duration_days" min="1" placeholder="7" required class="w-full border border-outline-variant bg-surface-container rounded-lg p-2 focus:ring-secondary">
                    </div>
                </div>
                <button type="submit" class="w-full bg-secondary text-white dark:text-slate-950 font-bold py-2.5 rounded-lg text-sm hover:bg-opacity-90 transition-all shadow-sm">
                    <?= __('Create Promo Coupon', 'Buat Kupon Promo') ?>
                </button>
            </form>

            <div class="border-t border-outline-variant pt-6">
                <h4 class="font-bold text-sm text-slate-800 mb-3"><?= __('Coupon Codes List', 'Daftar Kode Kupon') ?></h4>
                <div class="space-y-3">
                    <?php if($coupons_res->num_rows == 0): ?>
                        <p class="text-xs text-slate-600 italic"><?= __('No coupons created yet.', 'Belum ada kupon yang dibuat.') ?></p>
                    <?php else: while($c = $coupons_res->fetch_assoc()): 
                        $quota_reached = $c['used_count'] >= $c['usage_limit'];
                    ?>
                        <div class="bg-surface-container border border-outline-variant rounded-xl p-3 flex justify-between items-center text-xs">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold bg-secondary text-white px-2 py-0.5 rounded text-[10px]"><?= htmlspecialchars($c['code']) ?></span>
                                    <span class="text-green-600 font-bold"><?= $c['percentage'] ?><?= __('% OFF', '% POTONGAN') ?></span>
                                </div>
                                <p class="text-slate-700 mt-2"><?= __('Quota: ', 'Kuota: ') ?><span class="font-bold text-slate-900"><?= $c['used_count'] ?> / <?= $c['usage_limit'] ?></span> <?= $quota_reached ? __('(Limit Reached)', '(Batas Tercapai)') : '' ?></p>
                                <p class="text-slate-600 text-[10px] mt-0.5"><?= __('Expires: ', 'Kedaluwarsa: ') ?><?= date('d M Y H:i', strtotime($c['valid_until'])) ?></p>
                            </div>
                            <a href="admin?page=sales&delete_coupon=<?= $c['id'] ?>" onclick="return confirm('<?= __('Are you sure you want to delete this coupon?', 'Hapus kupon ini?') ?>')" class="text-red-500 hover:text-red-700 font-bold p-1"><?= __('Delete', 'Hapus') ?></a>
                        </div>
                    <?php endwhile; endif; ?>
                </div>
            </div>
        </div>

    </div>
</section>

