
<div class="relative inline-block text-left" id="notif-dropdown-container">
    <button type="button" class="text-slate-600 hover:text-secondary p-2 transition-colors flex items-center justify-center rounded-full hover:bg-surface-container-low relative" title="Notifications" onclick="toggleNotifDropdown()">
        <span class="material-symbols-outlined text-[24px]">notifications</span>
        <?php if (isset($notif_count) && $notif_count > 0): ?>
            <span class="absolute top-0 right-0 bg-secondary text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full min-w-[18px] text-center border-2 border-white">
                <?= $notif_count ?>
            </span>
        <?php endif; ?>
    </button>
    <div id="notif-mobile-backdrop" class="hidden fixed inset-0 bg-slate-900/50 z-[90] md:hidden backdrop-blur-sm" onclick="toggleNotifDropdown()"></div>
    <div id="notif-dropdown-menu" class="hidden fixed inset-x-4 top-1/2 -translate-y-1/2 z-[100] md:absolute md:inset-auto md:right-0 md:mt-2 md:translate-y-0 w-auto md:w-96 origin-top-right rounded-xl bg-surface-container-lowest shadow-2xl ring-1 ring-black ring-opacity-5 focus:outline-none flex flex-col border border-slate-100 max-h-[80vh]">
        <div class="bg-surface-container-low px-4 py-3 border-b border-slate-100 flex justify-between items-center rounded-t-xl shrink-0">
            <h3 class="text-sm font-bold text-slate-900"><?= __('Notifications', 'Notifikasi') ?></h3>
            <div class="flex items-center gap-2">
                <?php if (isset($notif_count) && $notif_count > 0): ?>
                    <span class="bg-slate-200 text-on-surface text-[10px] font-bold px-2 py-0.5 rounded-full"><?= $notif_count ?> <?= __('New', 'Baru') ?></span>
                <?php endif; ?>
                <button type="button" class="md:hidden text-slate-400 hover:text-on-surface transition-colors bg-surface-container-lowest rounded-full p-0.5 border border-outline-variant" onclick="toggleNotifDropdown()">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>
        </div>
        <?php if (!empty($notifications)): ?>
            <form id="notif-form" action="notif_action" method="POST" class="flex-grow flex flex-col overflow-hidden">
                <div class="bg-surface-container-lowest px-3 py-2 border-b border-slate-100 flex gap-2 justify-between items-center sticky top-0 z-10 shadow-sm">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="notif-select-all" class="rounded text-secondary focus:ring-secondary/50 border-slate-300 w-3.5 h-3.5" onclick="toggleAllNotifs(this)">
                        <label for="notif-select-all" class="text-[10px] font-bold text-slate-500 tracking-wider cursor-pointer"><?= __('All', 'Semua') ?></label>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" name="action" value="read_selected" class="text-[10px] font-bold text-slate-500 hover:text-blue-600 dark:hover:text-blue-400 uppercase tracking-wider transition-colors" title="<?= __('Mark selected as read', 'Tandai terpilih dibaca') ?>"><?= __('Read Sel', 'Baca Terpilih') ?></button>
                        <button type="submit" name="action" value="delete_selected" class="text-[10px] font-bold text-slate-500 hover:text-red-600 dark:hover:text-red-400 uppercase tracking-wider transition-colors" title="<?= __('Delete selected', 'Hapus terpilih') ?>"><?= __('Del Sel', 'Hapus Terpilih') ?></button>
                    </div>
                </div>
                <div class="overflow-y-auto flex-grow" style="max-height: 50vh;">
                    <?php foreach ($notifications as $n): ?>
                        <div class="flex items-start gap-3 px-4 py-3 hover:bg-surface-container-low transition-colors border-b border-slate-50 last:border-0 relative <?= $n['is_read'] ? 'opacity-60' : 'bg-slate-50/50' ?>">
                            <div class="pt-1 flex-shrink-0">
                                <input type="checkbox" name="notif_ids[]" value="<?= $n['id'] ?>" class="notif-checkbox rounded text-secondary focus:ring-secondary/50 border-slate-300 w-3.5 h-3.5">
                            </div>
                            <div class="flex-shrink-0 w-8 h-8 rounded-full <?= $n['bg'] ?> flex items-center justify-center">
                                <span class="material-symbols-outlined text-[16px] <?= $n['color'] ?>"><?= $n['icon'] ?></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <a href="<?= htmlspecialchars($n['link']) ?>" class="block">
                                    <?php
                                    $msgText = $n['message'];
                                    if (strpos($msgText, ' || ') !== false) {
                                        $parts = explode(' || ', $msgText);
                                        $msgText = __($parts[0], $parts[1]);
                                    }
                                    ?>
                                    <p class="text-sm text-slate-900 line-clamp-2 <?= $n['is_read'] ? '' : 'font-bold' ?>"><?= htmlspecialchars($msgText) ?></p>
                                    <p class="text-[10px] text-slate-400 mt-0.5 uppercase tracking-wider font-bold"><?= time_elapsed_string($n['created_at']) ?></p>
                                </a>
                            </div>
                            <?php if (!$n['is_read']): ?>
                                <div class="w-2 h-2 rounded-full bg-secondary absolute right-4 top-1/2 -translate-y-1/2"></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="bg-surface-container-low px-3 py-2 border-t border-slate-100 flex justify-between items-center rounded-b-xl sticky bottom-0 z-10">
                    <button type="submit" name="action" value="read_all" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors"><?= __('Mark All Read', 'Tandai Semua Dibaca') ?></button>
                    <button type="submit" name="action" value="delete_all" class="text-xs font-bold text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 transition-colors"><?= __('Delete All', 'Hapus Semua') ?></button>
                </div>
            </form>
        <?php else: ?>
            <div class="px-4 py-8 text-center bg-surface-container-lowest rounded-b-xl">
                <span class="material-symbols-outlined text-slate-300 text-4xl mb-2 block">notifications_paused</span>
                <p class="text-sm text-slate-500 font-medium"><?= __('No notifications', 'Tidak ada notifikasi') ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>
<script>
    function toggleNotifDropdown() {
        const menu = document.getElementById('notif-dropdown-menu');
        const backdrop = document.getElementById('notif-mobile-backdrop');
        menu.classList.toggle('hidden');
        if(backdrop) backdrop.classList.toggle('hidden');
    }
    function toggleAllNotifs(source) {
        checkboxes = document.querySelectorAll('.notif-checkbox');
        for(var i=0, n=checkboxes.length;i<n;i++) {
            checkboxes[i].checked = source.checked;
        }
    }
    document.addEventListener('click', function(event) {
        const container = document.getElementById('notif-dropdown-container');
        if (container && !container.contains(event.target)) {
            const menu = document.getElementById('notif-dropdown-menu');
            if(menu && !menu.classList.contains('hidden')) {
                menu.classList.add('hidden');
                const backdrop = document.getElementById('notif-mobile-backdrop');
                if(backdrop) backdrop.classList.add('hidden');
            }
        }
    });
    function setupNotifAjax() {
        const form = document.getElementById('notif-form');
        if (form) {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                e.stopPropagation();
                const formData = new FormData(form);
                if (e.submitter && e.submitter.value) {
                    formData.append('action', e.submitter.value);
                }
                try {
                    const response = await fetch('notif_action', {
                        method: 'POST',
                        body: formData
                    });
                    if (response.ok) {
                        const text = await response.text();
                        const doc = new DOMParser().parseFromString(text, 'text/html');
                        const newContainer = doc.getElementById('notif-dropdown-container');
                        if (newContainer) {
                            document.getElementById('notif-dropdown-container').innerHTML = newContainer.innerHTML;
                            const newMenu = document.getElementById('notif-dropdown-menu');
                            if(newMenu) newMenu.classList.remove('hidden');
                            const newBackdrop = document.getElementById('notif-mobile-backdrop');
                            if(newBackdrop) newBackdrop.classList.remove('hidden');
                            setupNotifAjax(); 
                        }
                    }
                } catch (err) {
                    console.error('Error submitting notif action', err);
                }
            });
        }
    }
    setupNotifAjax();
</script>
