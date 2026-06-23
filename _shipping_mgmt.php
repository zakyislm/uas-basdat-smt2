<?php
if (!defined('DB_HOST') && !isset($conn)) {
    exit('Direct access not allowed.');
}


$shipping_res = $conn->query("SELECT * FROM shipping_methods ORDER BY base_cost ASC");
?>
<section id="shipping_management" class="space-y-8">
    <div class="mb-6">
        <h2 class="font-headline-lg text-primary text-3xl font-bold"><?= __('Shipping & Logistics', 'Pengiriman & Logistik') ?></h2>
        <p class="text-on-surface-variant"><?= __('Configure courier options and towing delivery fees for customer checkouts.', 'Konfigurasi opsi kurir dan tarif pengiriman derek untuk checkout pelanggan.') ?></p>
    </div>

    <div class="flex flex-col lg:flex-row gap-8 items-start">
        
        
        <div class="w-full lg:w-1/3 bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-sm lg:sticky lg:top-24">
            <h3 id="shipping-form-title" class="text-xl font-bold text-slate-900 mb-6">
                <?= __('Add Shipping Method', 'Tambah Metode Pengiriman') ?>
            </h3>
            
            <form method="POST" action="admin?page=shipping" class="space-y-4">
                <input type="hidden" name="save_shipping" value="1">
                <input type="hidden" name="shipping_id" id="shipping_id" value="">
                
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1"><?= __('Courier Service Name', 'Nama Layanan Kurir') ?></label>
                    <input type="text" name="method_name" id="method_name" required placeholder="Towing Express" class="w-full border border-outline-variant bg-surface-container rounded-lg p-2.5 focus:ring-secondary">
                </div>
                
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1"><?= __('Base Cost (Rp)', 'Tarif Dasar (Rp)') ?></label>
                    <input type="number" name="base_cost" id="base_cost" required min="0" placeholder="500000" class="w-full border border-outline-variant bg-surface-container rounded-lg p-2.5 font-mono focus:ring-secondary">
                </div>
                
                <div class="pt-4 flex gap-2">
                    <button type="submit" class="flex-grow bg-secondary text-white dark:text-slate-950 font-bold py-2.5 rounded-lg text-sm hover:bg-opacity-90 transition-all shadow-sm">
                        <?= __('Save Method', 'Simpan Metode') ?>
                    </button>
                    <button type="button" id="cancel-edit-btn" onclick="cancelEditShipping()" class="hidden px-4 py-2.5 bg-surface-container-high text-on-surface rounded-lg font-bold text-sm hover:bg-opacity-95">
                        <?= __('Cancel', 'Batal') ?>
                    </button>
                </div>
            </form>
        </div>

        
        <div class="w-full lg:w-2/3">
            <h3 class="text-xl font-bold text-slate-900 mb-6">
                <?= __('Active Shipping Methods', 'Metode Pengiriman Aktif') ?>
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php while($sm = $shipping_res->fetch_assoc()): ?>
                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 flex flex-col gap-4 relative shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-[10px] font-bold text-on-surface-variant tracking-wider uppercase mb-1">ID: #SHP-<?= $sm['id'] ?></p>
                            <h4 class="font-bold text-lg text-primary leading-tight text-slate-900"><?= htmlspecialchars($sm['method_name']) ?></h4>
                        </div>
                        <?php if ($sm['base_cost'] == 0): ?>
                            <span class="bg-emerald-100 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 text-[10px] font-bold px-2 py-1 rounded border border-emerald-200 dark:border-emerald-800"><?= __('Free / Pickup', 'Gratis / Ambil Sendiri') ?></span>
                        <?php else: ?>
                            <span class="bg-blue-100 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 text-[10px] font-bold px-2 py-1 rounded border border-blue-200 dark:border-blue-800"><?= __('Paid Towing', 'Towing Berbayar') ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="mt-2 text-emerald-600 font-bold font-mono text-base">
                        Rp <?= number_format($sm['base_cost'], 0, ',', '.') ?>
                    </div>
                    <div class="border-t border-outline-variant pt-3 flex justify-between items-center mt-auto">
                        <button onclick="editShipping(<?= $sm['id'] ?>, '<?= htmlspecialchars(addslashes($sm['method_name'])) ?>', <?= $sm['base_cost'] ?>)" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 font-bold text-xs flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">edit</span> <?= __('Edit', 'Ubah') ?>
                        </button>
                        
                        <?php if ($sm['base_cost'] > 0): ?>
                            <a href="admin?page=shipping&delete_shipping=<?= $sm['id'] ?>" onclick="return confirm('<?= __('Are you sure you want to delete this shipping method?', 'Apakah Anda yakin ingin menghapus kurir pengiriman ini?') ?>')" class="text-red-600 hover:text-red-800 font-bold text-xs flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">delete</span> <?= __('Delete', 'Hapus') ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            
            <p class="text-xs text-slate-400 dark:text-slate-400 mt-6 leading-relaxed">
                * <?= __('Note: The system enforces that at least 1 paid courier method (cost > Rp 0) must remain active to prevent checkout failures. "Ambil di Toko / Self-Pickup" is a default option and cannot be deleted.', 'Catatan: Sistem mewajibkan minimal 1 kurir berbayar (tarif > Rp 0) tetap aktif untuk mencegah kegagalan checkout. "Ambil di Toko / Self-Pickup" adalah opsi bawaan dan tidak dapat dihapus.') ?>
            </p>
        </div>

    </div>
</section>

<script>
function editShipping(id, name, cost) {
    document.getElementById('shipping_id').value = id;
    document.getElementById('method_name').value = name;
    document.getElementById('base_cost').value = cost;
    document.getElementById('shipping-form-title').innerHTML = '<?= __('Edit Shipping Method', 'Ubah Metode Pengiriman') ?>';
    document.getElementById('cancel-edit-btn').classList.remove('hidden');
}

function cancelEditShipping() {
    document.getElementById('shipping_id').value = '';
    document.getElementById('method_name').value = '';
    document.getElementById('base_cost').value = '';
    document.getElementById('shipping-form-title').innerHTML = '<?= __('Add Shipping Method', 'Tambah Metode Pengiriman') ?>';
    document.getElementById('cancel-edit-btn').classList.add('hidden');
}
</script>

