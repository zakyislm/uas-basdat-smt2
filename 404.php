<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>MotoTrack Pro | 404 Not Found</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <style>
        body { font-family: 'Hanken Grotesk', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    </style>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "secondary": "#bb0112",
                        "secondary-container": "#e02928",
                        "background": "#f7f9fb",
                        "surface-container-lowest": "#ffffff",
                        "outline-variant": "#c6c6cd"
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-background text-slate-800 flex flex-col min-h-screen pb-16 md:pb-0">
    <?php include 'header.php'; ?>
    <div class="flex-grow flex flex-col items-center justify-center p-8 text-center">
        <span class="material-symbols-outlined text-[120px] text-slate-200 mb-6" style="font-variation-settings: 'FILL' 1;">broken_image</span>
        <h1 class="text-6xl font-black text-slate-900 mb-4">404</h1>
        <h2 class="text-2xl font-bold text-slate-700 mb-2">Halaman Tidak Ditemukan</h2>
        <p class="text-slate-500 mb-8 max-w-md">Maaf, kami tidak dapat menemukan halaman yang Anda cari. Mungkin URL-nya salah atau halamannya sudah dihapus.</p>
        <a href="/" class="bg-secondary text-white px-8 py-3 rounded-xl font-bold hover:bg-secondary-container hover:shadow-lg transition-all flex items-center gap-2">
            <span class="material-symbols-outlined">home</span>
            Kembali ke Beranda
        </a>
    </div>

    <nav class="md:hidden fixed bottom-0 left-0 right-0 w-full bg-white border-t border-slate-200 z-[999]" style="padding-bottom: env(safe-area-inset-bottom);">
        <div class="flex justify-around items-center h-16">
            <a href="/" class="flex flex-col items-center justify-center w-full h-full text-slate-500 hover:text-secondary transition-colors">
                <span class="material-symbols-outlined text-[24px]">home</span>
                <span class="text-[10px] font-bold mt-1">Home</span>
            </a>
            <a href="discover" class="flex flex-col items-center justify-center w-full h-full text-slate-500 hover:text-secondary transition-colors">
                <span class="material-symbols-outlined text-[24px]" style="font-variation-settings: 'FILL' 1;">travel_explore</span>
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
