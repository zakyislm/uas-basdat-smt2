    
    <header class="w-full top-0 sticky z-50 bg-surface-container-lowest border-b border-outline-variant shadow-sm">
        <div class="flex justify-between items-center px-4 md:px-8 py-2 w-full max-w-[1280px] mx-auto h-16">
            <div class="flex items-center gap-4">
                <a href="/" class="text-xl font-bold text-secondary">MotoTrack Pro</a>
            </div>
            
            <div class="flex items-center gap-2">
                
                <nav class="hidden md:flex items-center gap-2 mr-2 border-r border-outline-variant pr-4">
                    <a href="/" class="text-secondary p-2 transition-colors flex items-center justify-center rounded-full hover:bg-slate-50" title="Home">
                        <span class="material-symbols-outlined text-[24px]" style="font-variation-settings: 'FILL' 1;">home</span>
                    </a>
                    <a href="discover" class="text-slate-600 hover:text-secondary p-2 transition-colors flex items-center justify-center rounded-full hover:bg-slate-50" title="Discover">
                        <span class="material-symbols-outlined text-[24px]">travel_explore</span>
                    </a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="history" class="text-slate-600 hover:text-secondary p-2 transition-colors flex items-center justify-center rounded-full hover:bg-slate-50" title="History">
                            <span class="material-symbols-outlined text-[24px]">receipt_long</span>
                        </a>
                        <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'owner'): ?>
                            <a href="admin" class="text-slate-600 hover:text-secondary p-2 transition-colors flex items-center justify-center rounded-full hover:bg-slate-50" title="Admin Panel">
                                <span class="material-symbols-outlined text-[24px]">admin_panel_settings</span>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </nav>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php include 'notifications_ui.php'; ?>
                    <a href="cart" class="text-slate-600 hover:text-secondary p-2 transition-colors flex items-center justify-center rounded-full hover:bg-slate-50 relative" title="Cart">
                        <span class="material-symbols-outlined text-[24px]">shopping_cart</span>
                        <?php if (isset($cart_count) && $cart_count > 0): ?>
                            <span class="absolute top-0 right-0 bg-secondary text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full min-w-[18px] text-center border-2 border-white"><?= $cart_count ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <a href="settings" class="hidden md:flex text-slate-600 hover:text-secondary p-2 transition-colors items-center justify-center rounded-full hover:bg-slate-50" title="Settings">
                        <span class="material-symbols-outlined text-[24px]">settings</span>
                    </a>
                    
                    <button type="button" id="mobileMenuBtn" class="md:hidden text-slate-600 hover:text-secondary p-2 transition-colors flex items-center justify-center rounded-full hover:bg-slate-50">
                        <span class="material-symbols-outlined text-[24px]">menu</span>
                    </button>
                <?php else: ?>
                    <a href="auth" class="bg-slate-900 text-white px-5 py-2 rounded-lg font-bold text-sm hover:bg-secondary transition-colors">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <?php if (isset($_SESSION['user_id'])): ?>
    <div id="mobileSidebarOverlay" class="fixed inset-0 bg-black/50 z-[60] hidden opacity-0 transition-opacity duration-300"></div>
    <div id="mobileSidebar" class="fixed top-0 right-0 h-full w-[280px] bg-surface-container-lowest shadow-2xl z-[70] transform translate-x-full transition-transform duration-300 flex flex-col">
        <div class="px-4 py-2 h-16 border-b border-outline-variant flex justify-between items-center">
            <span class="text-xl font-bold text-secondary">Menu</span>
            <button id="closeSidebarBtn" class="p-2 text-slate-500 hover:text-secondary rounded-full hover:bg-slate-50 transition-colors flex items-center justify-center">
                <span class="material-symbols-outlined text-[24px]">close</span>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto p-4 flex flex-col gap-2">
            <a href="/" class="flex items-center gap-3 p-3 rounded-lg text-slate-700 hover:bg-slate-50 hover:text-secondary transition-colors">
                <span class="material-symbols-outlined">home</span>
                <span class="font-medium">Home</span>
            </a>
            <a href="discover" class="flex items-center gap-3 p-3 rounded-lg text-slate-700 hover:bg-slate-50 hover:text-secondary transition-colors">
                <span class="material-symbols-outlined">travel_explore</span>
                <span class="font-medium">Discover</span>
            </a>
            <a href="history" class="flex items-center gap-3 p-3 rounded-lg text-slate-700 hover:bg-slate-50 hover:text-secondary transition-colors">
                <span class="material-symbols-outlined">receipt_long</span>
                <span class="font-medium">History</span>
            </a>
            <div class="my-2 border-t border-outline-variant"></div>
            <a href="settings" class="flex items-center gap-3 p-3 rounded-lg text-slate-700 hover:bg-slate-50 hover:text-secondary transition-colors">
                <span class="material-symbols-outlined">settings</span>
                <span class="font-medium">Settings</span>
            </a>
            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'owner'): ?>
            <a href="admin" class="flex items-center gap-3 p-3 rounded-lg text-secondary bg-red-50 hover:bg-red-100 transition-colors mt-2">
                <span class="material-symbols-outlined">admin_panel_settings</span>
                <span class="font-bold">Admin Panel</span>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const btn = document.getElementById('mobileMenuBtn');
        const closeBtn = document.getElementById('closeSidebarBtn');
        const sidebar = document.getElementById('mobileSidebar');
        const overlay = document.getElementById('mobileSidebarOverlay');

        if(btn) {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                overlay.classList.remove('hidden');
                setTimeout(() => overlay.classList.remove('opacity-0'), 10);
                sidebar.classList.remove('translate-x-full');
            });
        }

        const closeSidebar = () => {
            sidebar.classList.add('translate-x-full');
            overlay.classList.add('opacity-0');
            setTimeout(() => overlay.classList.add('hidden'), 300);
        };

        if(closeBtn) closeBtn.addEventListener('click', closeSidebar);
        if(overlay) overlay.addEventListener('click', closeSidebar);
    });
    </script>
    <?php endif; ?>

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

