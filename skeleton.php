<style>
body.skeleton-active {
    pointer-events: none !important;
    user-select: none !important;
}
body.skeleton-active h1, body.skeleton-active h2, body.skeleton-active h3, body.skeleton-active h4, body.skeleton-active p, body.skeleton-active span, body.skeleton-active img, body.skeleton-active button, body.skeleton-active input, body.skeleton-active select, body.skeleton-active td, body.skeleton-active th {
    color: transparent !important;
    border-color: transparent !important;
    background-color: var(--color-slate-200, #e2e8f0) !important;
    background-image: linear-gradient(90deg, rgba(255, 255, 255, 0) 0, rgba(255, 255, 255, 0.4) 20%, rgba(255, 255, 255, 0.8) 60%, rgba(255, 255, 255, 0)) !important;
    background-size: 200% 100% !important;
    animation: skeleton-shimmer 1.5s infinite linear !important;
    box-shadow: none !important;
}
html.dark body.skeleton-active h1, html.dark body.skeleton-active h2, html.dark body.skeleton-active h3, html.dark body.skeleton-active h4, html.dark body.skeleton-active p, html.dark body.skeleton-active span, html.dark body.skeleton-active img, html.dark body.skeleton-active button, html.dark body.skeleton-active input, html.dark body.skeleton-active select, html.dark body.skeleton-active td, html.dark body.skeleton-active th {
    background-image: linear-gradient(90deg, rgba(255,255,255,0) 0, rgba(255,255,255,0.02) 20%, rgba(255,255,255,0.06) 60%, rgba(255,255,255,0)) !important;
}
body.skeleton-active img, body.skeleton-active svg, body.skeleton-active .material-symbols-outlined, body.skeleton-active i {
    color: transparent !important;
    opacity: 0 !important;
}
@keyframes skeleton-shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}
</style>
<script>
window.showPopup = function(message) {
    const existing = document.getElementById('manual-popup');
    if (existing) existing.remove();
    
    const popup = document.createElement('div');
    popup.id = 'manual-popup';
    popup.className = 'fixed top-24 right-4 md:right-8 bg-slate-900 text-white px-6 py-4 rounded-xl shadow-2xl z-[100] flex items-center gap-4 transition-all duration-300 transform translate-y-0 opacity-100';
    popup.innerHTML = `
        <span class="material-symbols-outlined text-[#bb0112]">info</span>
        <span class="font-medium text-sm">${message}</span>
        <button onclick="this.parentElement.style.opacity='0'; setTimeout(()=>this.parentElement.remove(), 300)" class="text-slate-400 hover:text-white ml-2">
            <span class="material-symbols-outlined text-[18px]">close</span>
        </button>
    `;
    document.body.appendChild(popup);
    
    setTimeout(() => {
        const el = document.getElementById('manual-popup');
        if(el) {
            el.style.opacity = '0';
            setTimeout(() => { if (el) el.remove(); }, 300);
        }
    }, 3000);
};

document.addEventListener('DOMContentLoaded', () => {
    const applySkeleton = () => {
        document.body.classList.add('skeleton-active');
        const loader = document.createElement('div');
        loader.id = 'skeleton-loader-bar';
        loader.style.cssText = 'position:fixed;top:0;left:0;height:3px;background:#e02928;z-index:99999;transition:width 0.4s ease;width:10%';
        document.body.appendChild(loader);
        setTimeout(() => { if (document.getElementById('skeleton-loader-bar')) loader.style.width = '70%'; }, 50);
    };
    document.addEventListener('click', (e) => {
        const link = e.target.closest('a');
        if (link && link.href && !link.href.includes('#') && !link.href.startsWith('javascript:') && link.target !== '_blank' && !e.ctrlKey && !e.metaKey && !e.shiftKey) {
            if (e.defaultPrevented) return;
            e.preventDefault();
            
            window.scrollTo({ top: 0, behavior: 'smooth' });
            applySkeleton();
            
            setTimeout(() => {
                window.location.href = link.href;
            }, 350);
        }
    });
    document.addEventListener('submit', (e) => {
        if (e.defaultPrevented) return;
        applySkeleton();
        const btn = e.target.querySelector('button[type="submit"]');
        if(btn && !btn.innerHTML.includes('animate-spin')) {
            btn.dataset.originalHtml = btn.innerHTML;
            btn.innerHTML = '<span class="material-symbols-outlined animate-spin" style="font-size:16px;vertical-align:-3px;opacity:1 !important;">sync</span>';
        }
    });
});
window.addEventListener('pageshow', (event) => {
    document.body.classList.remove('skeleton-active');
    const loader = document.getElementById('skeleton-loader-bar');
    if (loader) loader.remove();
    document.querySelectorAll('button[type="submit"][data-original-html]').forEach(btn => {
        btn.innerHTML = btn.dataset.originalHtml;
        delete btn.dataset.originalHtml;
    });
});
</script>
