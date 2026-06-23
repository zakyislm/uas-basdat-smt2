<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - MotoInfy</title>
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
        <h1 class="text-4xl font-bold mb-8 text-slate-900">Privacy Policy</h1>
        <div class="space-y-8 text-slate-600 leading-relaxed">
            <section>
                <h2 class="text-2xl font-bold text-on-surface mb-4">1. Information We Collect</h2>
                <p>We collect information you provide directly to us, such as when you create or modify your account, request on-demand services, contact customer support, or otherwise communicate with us. This information may include: name, email, phone number, postal address, and payment information.</p>
            </section>
            <section>
                <h2 class="text-2xl font-bold text-on-surface mb-4">2. How We Use Your Information</h2>
                <p>We may use the information we collect about you to:</p>
                <ul class="list-disc pl-6 mt-2 space-y-2">
                    <li>Provide, maintain, and improve our services</li>
                    <li>Process transactions and send related information</li>
                    <li>Send you technical notices, updates, security alerts, and support messages</li>
                    <li>Respond to your comments, questions, and requests</li>
                </ul>
            </section>
            <section>
                <h2 class="text-2xl font-bold text-on-surface mb-4">3. Information Sharing</h2>
                <p>We do not share personal information about you with third parties except as follows:</p>
                <ul class="list-disc pl-6 mt-2 space-y-2">
                    <li>With vendors, consultants, and other service providers who need access to such information to carry out work on our behalf</li>
                    <li>In response to a request for information if we believe disclosure is in accordance with any applicable law, regulation, or legal process</li>
                </ul>
            </section>
            <section>
                <h2 class="text-2xl font-bold text-on-surface mb-4">4. Security</h2>
                <p>MotoInfy takes reasonable measures to help protect information about you from loss, theft, misuse and unauthorized access, disclosure, alteration and destruction. Passwords are securely hashed using bcrypt.</p>
            </section>
            <section>
                <h2 class="text-2xl font-bold text-on-surface mb-4">5. Contact Us</h2>
                <p>If you have any questions about this Privacy Policy, please contact us at info@motoinfy.com.</p>
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
