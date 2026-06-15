
<div class="relative inline-block text-left" id="notif-dropdown-container">
    <button type="button" class="text-slate-600 hover:text-secondary p-2 transition-colors flex items-center justify-center rounded-full hover:bg-slate-50 relative" title="Notifications" onclick="toggleNotifDropdown()">
        <span class="material-symbols-outlined text-[24px]">notifications</span>
        <?php if (isset($notif_count) && $notif_count > 0): ?>
            <span class="absolute top-0 right-0 bg-secondary text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full min-w-[18px] text-center border-2 border-white">
                <?= $notif_count ?>
            </span>
        <?php endif; ?>
    </button>

    <div id="notif-dropdown-menu" class="hidden absolute right-0 mt-2 w-80 md:w-96 origin-top-right rounded-xl bg-white shadow-xl ring-1 ring-black ring-opacity-5 focus:outline-none z-50 flex flex-col border border-slate-100 max-h-[80vh]">
        <div class="bg-slate-50 px-4 py-3 border-b border-slate-100 flex justify-between items-center rounded-t-xl">
            <h3 class="text-sm font-bold text-slate-900">Notifications</h3>
            <?php if (isset($notif_count) && $notif_count > 0): ?>
                <span class="bg-slate-200 text-slate-700 text-[10px] font-bold px-2 py-0.5 rounded-full"><?= $notif_count ?> New</span>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($notifications)): ?>
            <form id="notif-form" action="notif_action.php" method="POST" class="flex-grow flex flex-col overflow-hidden">
                <div class="bg-white px-3 py-2 border-b border-slate-100 flex gap-2 justify-between items-center sticky top-0 z-10 shadow-sm">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="notif-select-all" class="rounded text-secondary focus:ring-secondary/50 border-slate-300 w-3.5 h-3.5" onclick="toggleAllNotifs(this)">
                        <label for="notif-select-all" class="text-[10px] font-bold text-slate-500 uppercase tracking-wider cursor-pointer">All</label>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" name="action" value="read_selected" class="text-[10px] font-bold text-slate-500 hover:text-blue-600 uppercase tracking-wider transition-colors" title="Mark selected as read">Read Sel</button>
                        <button type="submit" name="action" value="delete_selected" class="text-[10px] font-bold text-slate-500 hover:text-red-600 uppercase tracking-wider transition-colors" title="Delete selected">Del Sel</button>
                    </div>
                </div>

                <div class="overflow-y-auto flex-grow" style="max-height: 50vh;">
                    <?php foreach ($notifications as $n): ?>
                        <div class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50 transition-colors border-b border-slate-50 last:border-0 relative <?= $n['is_read'] ? 'opacity-60' : 'bg-slate-50/50' ?>">
                            <div class="pt-1 flex-shrink-0">
                                <input type="checkbox" name="notif_ids[]" value="<?= $n['id'] ?>" class="notif-checkbox rounded text-secondary focus:ring-secondary/50 border-slate-300 w-3.5 h-3.5">
                            </div>
                            <div class="flex-shrink-0 w-8 h-8 rounded-full <?= $n['bg'] ?> flex items-center justify-center">
                                <span class="material-symbols-outlined text-[16px] <?= $n['color'] ?>"><?= $n['icon'] ?></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <a href="<?= htmlspecialchars($n['link']) ?>" class="block">
                                    <p class="text-sm text-slate-900 line-clamp-2 <?= $n['is_read'] ? '' : 'font-bold' ?>"><?= htmlspecialchars($n['message']) ?></p>
                                    <p class="text-[10px] text-slate-400 mt-0.5 uppercase tracking-wider font-bold"><?= time_elapsed_string($n['created_at']) ?></p>
                                </a>
                            </div>
                            <?php if (!$n['is_read']): ?>
                                <div class="w-2 h-2 rounded-full bg-secondary absolute right-4 top-1/2 -translate-y-1/2"></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="bg-slate-50 px-3 py-2 border-t border-slate-100 flex justify-between items-center rounded-b-xl sticky bottom-0 z-10">
                    <button type="submit" name="action" value="read_all" class="text-xs font-bold text-blue-600 hover:text-blue-800 transition-colors">Mark All Read</button>
                    <button type="submit" name="action" value="delete_all" class="text-xs font-bold text-red-600 hover:text-red-800 transition-colors">Delete All</button>
                </div>
            </form>
        <?php else: ?>
            <div class="px-4 py-8 text-center bg-white rounded-b-xl">
                <span class="material-symbols-outlined text-slate-300 text-4xl mb-2 block">notifications_paused</span>
                <p class="text-sm text-slate-500 font-medium">No notifications</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function toggleNotifDropdown() {
        const menu = document.getElementById('notif-dropdown-menu');
        menu.classList.toggle('hidden');
    }
    
    function toggleAllNotifs(source) {
        checkboxes = document.querySelectorAll('.notif-checkbox');
        for(var i=0, n=checkboxes.length;i<n;i++) {
            checkboxes[i].checked = source.checked;
        }
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const container = document.getElementById('notif-dropdown-container');
        if (!container.contains(event.target)) {
            document.getElementById('notif-dropdown-menu').classList.add('hidden');
        }
    });
</script>
