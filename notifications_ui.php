<!-- Notification Dropdown UI (Requires Alpine.js or Custom JS, but we will use simple CSS/JS toggle) -->
<div class="relative inline-block text-left" id="notif-dropdown-container">
    <button type="button" class="text-slate-600 hover:text-secondary p-2 transition-colors flex items-center justify-center rounded-full hover:bg-slate-50 relative" title="Notifications" onclick="toggleNotifDropdown()">
        <span class="material-symbols-outlined text-[24px]">notifications</span>
        <?php if ($notif_count > 0): ?>
            <span class="absolute top-0 right-0 bg-secondary text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full min-w-[18px] text-center border-2 border-white">
                <?= $notif_count ?>
            </span>
        <?php endif; ?>
    </button>

    <div id="notif-dropdown-menu" class="hidden absolute right-0 mt-2 w-80 origin-top-right rounded-xl bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-50 overflow-hidden border border-slate-100">
        <div class="bg-slate-50 px-4 py-3 border-b border-slate-100 flex justify-between items-center">
            <h3 class="text-sm font-bold text-slate-900">Notifications</h3>
            <?php if ($notif_count > 0): ?>
                <span class="bg-slate-200 text-slate-700 text-[10px] font-bold px-2 py-0.5 rounded-full"><?= $notif_count ?> New</span>
            <?php endif; ?>
        </div>
        <div class="max-h-80 overflow-y-auto">
            <?php if ($notif_count > 0): ?>
                <?php foreach ($notifications as $n): ?>
                    <a href="<?= htmlspecialchars($n['link']) ?>" class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50 transition-colors border-b border-slate-50 last:border-0">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full <?= $n['bg'] ?> flex items-center justify-center">
                            <span class="material-symbols-outlined text-[16px] <?= $n['color'] ?>"><?= $n['icon'] ?></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-900 line-clamp-2"><?= htmlspecialchars($n['message']) ?></p>
                            <p class="text-[10px] text-slate-400 mt-0.5 uppercase tracking-wider font-bold">Just Now</p>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="px-4 py-8 text-center">
                    <span class="material-symbols-outlined text-slate-300 text-4xl mb-2 block">notifications_paused</span>
                    <p class="text-sm text-slate-500 font-medium">No new notifications</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function toggleNotifDropdown() {
        const menu = document.getElementById('notif-dropdown-menu');
        menu.classList.toggle('hidden');
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const container = document.getElementById('notif-dropdown-container');
        const menu = document.getElementById('notif-dropdown-menu');
        if (container && !container.contains(event.target)) {
            menu.classList.add('hidden');
        }
    });
</script>
