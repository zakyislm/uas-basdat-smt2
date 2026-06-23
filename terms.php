<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - MotoInfy</title>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <?php include 'theme_config.php'; ?>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
</head>
<body class="bg-background text-on-surface flex flex-col min-h-screen pb-16 md:pb-0">
    <header class="bg-surface-container-lowest border-b border-outline-variant py-4 px-8 sticky top-0 z-50">
        <div class="max-w-[1280px] mx-auto flex items-center justify-between">
            <a href="/" class="font-bold text-secondary text-2xl">MotoInfy</a>
            <a href="/" class="text-sm font-medium text-slate-500 hover:text-secondary">Back to Home</a>
        </div>
    </header>
    <main class="flex-grow max-w-[800px] mx-auto w-full px-8 py-12">
        <h1 class="text-4xl font-bold mb-8 text-slate-900">Terms of Service</h1>
        <div class="space-y-8 text-slate-600 leading-relaxed">
            <section>
                <h2 class="text-2xl font-bold text-on-surface mb-4">1. Acceptance of Terms</h2>
                <p>By accessing and using MotoInfy, you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to abide by these terms, please do not use this service.</p>
            </section>
            <section>
                <h2 class="text-2xl font-bold text-on-surface mb-4">2. Description of Service</h2>
                <p>MotoInfy provides an online platform for browsing and purchasing motorcycles. We reserve the right to modify, suspend or discontinue the service with or without notice at any time and without any liability to you.</p>
            </section>
            <section>
                <h2 class="text-2xl font-bold text-on-surface mb-4">3. Payment Terms</h2>
                <p>All prices are listed in Indonesian Rupiah (IDR). Payments must be made within 10 minutes of creating an order. If payment is not received within this timeframe, the order will be automatically cancelled.</p>
            </section>
            <section>
                <h2 class="text-2xl font-bold text-on-surface mb-4">4. Delivery and Fulfillment</h2>
                <p>Upon successful payment verification by our admin team, your order will be processed. Delivery timelines vary depending on your location and vehicle availability.</p>
            </section>
            <section>
                <h2 class="text-2xl font-bold text-on-surface mb-4">5. Modifications to Terms</h2>
                <p>MotoInfy reserves the right to change or modify any of the terms and conditions contained in this Terms of Service at any time and in its sole discretion.</p>
            </section>
        </div>
    </main>
    <?php include 'footer.php'; ?>
    <nav class="md:hidden fixed bottom-0 left-0 right-0 w-full bg-surface-container-lowest border-t border-outline-variant z-[999]" style="padding-bottom: env(safe-area-inset-bottom);">
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
