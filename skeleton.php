<style>
body.skeleton-active {
    pointer-events: none !important;
    user-select: none !important;
}
body.skeleton-active h1, body.skeleton-active h2, body.skeleton-active h3, body.skeleton-active h4, body.skeleton-active p, body.skeleton-active span, body.skeleton-active img, body.skeleton-active button, body.skeleton-active input, body.skeleton-active select, body.skeleton-active td, body.skeleton-active th {
    color: transparent !important;
    border-color: transparent !important;
    background-color: #e2e8f0 !important;
    background-image: linear-gradient(90deg, rgba(255, 255, 255, 0) 0, rgba(255, 255, 255, 0.2) 20%, rgba(255, 255, 255, 0.5) 60%, rgba(255, 255, 255, 0)) !important;
    background-size: 200% 100% !important;
    animation: skeleton-shimmer 1.5s infinite linear !important;
    box-shadow: none !important;
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
document.addEventListener('DOMContentLoaded', () => {
    const applySkeleton = () => {
        document.body.classList.add('skeleton-active');
        const loader = document.createElement('div');
        loader.style.cssText = 'position:fixed;top:0;left:0;height:3px;background:#e02928;z-index:99999;transition:width 0.4s ease;width:10%';
        document.body.appendChild(loader);
        setTimeout(() => loader.style.width = '70%', 50);
    };

    document.addEventListener('click', (e) => {
        const link = e.target.closest('a');
        if (link && link.href && !link.href.includes('#') && !link.href.startsWith('javascript:') && link.target !== '_blank' && !e.ctrlKey && !e.metaKey && !e.shiftKey) {
            e.preventDefault();
            applySkeleton();
            window.location.href = link.href;
        }
    });

    document.addEventListener('submit', (e) => {
        applySkeleton();
        const btn = e.target.querySelector('button[type="submit"]');
        if(btn && !btn.innerHTML.includes('animate-spin')) {
            btn.innerHTML = '<span class="material-symbols-outlined animate-spin" style="font-size:16px;vertical-align:-3px;opacity:1 !important;">sync</span>';
        }
    });
});
</script>
