# Walkthrough: Sistem Dealer Motor (PHP Native)

Aplikasi Dealer Motor ini telah dirancang khusus sebagai proyek semester mahasiswa Teknik Komputer. Aplikasi ini dibuat menggunakan **PHP Native Murni** dan **MySQL**, dengan struktur file yang sangat datar (tanpa framework) sehingga sangat mudah dibaca, dijelaskan, dan dimodifikasi untuk presentasi atau sidang tugas.

## 🌟 Fitur Utama Aplikasi

Sistem ini memiliki fitur-fitur profesional namun dibalut dalam kode yang sederhana:

1. **Multi-Role System**: Memiliki 3 tingkat hak akses pengguna:
   - **User Biasa**: Hanya bisa melihat daftar motor dan melakukan pemesanan (jika akunnya sudah di-verifikasi oleh Admin).
   - **Admin**: Pengelola transaksi. Bisa melihat daftar pengguna, memverifikasi pengguna baru, mengubah status pembayaran/transaksi, menghapus transaksi, dan mengubah stok secara cepat.
   - **Owner**: Pemilik dealer. Memiliki hak yang sama dengan Admin, ditambah akses ke **Panel Owner** untuk melakukan CRUD (Tambah, Edit, Hapus) pada Master Data Motor, serta dapat melihat **Sistem Log**.
2. **Manajemen Stok Real-Time**: 
   - Setiap motor memiliki jumlah stok.
   - Saat proses checkout, sistem memvalidasi *quantity* (jumlah yang ingin dibeli). Pesanan ditolak jika melebihi stok yang ada.
   - Stok akan otomatis berkurang jika transaksi berhasil, dan akan **dikembalikan otomatis** jika transaksi tersebut dihapus oleh Admin.
3. **Jenis Transaksi & Pembayaran**: Mendukung pemilihan jenis (Booking vs Beli Langsung) serta status pembayaran (Unpaid, Paid, Refunded).
4. **Sistem Logging Ekstensif (Audit Trail)**: Segala perubahan data (Login, Logout, Register, Tambah Motor, Hapus Motor, Pemesanan, Ubah Status Pembayaran, dll) akan otomatis dicatat oleh sistem beserta waktu eksekusi dan siapa pelakunya. Fitur keamanan ini **hanya bisa dilihat oleh Owner**.

## 🚀 Pembaruan Fitur Terbaru (E-Commerce & Akun)

Berikut adalah ringkasan fitur-fitur baru yang ditambahkan pada pembaruan terakhir:

### 1. Struktur Admin Panel Terpusat
- **Sistem Routing**: Menu sidebar kini menggunakan parameter `?page=...` (misalnya `admin.php?page=users`), sehingga perpindahan menu terasa seperti aplikasi profesional (tidak ada lagi halaman yang bertumpuk panjang ke bawah).
- **Penggabungan Role**: File `owner.php` telah **dihapus**. Menu khusus Owner (seperti Katalog Motor dan Sistem Log) kini langsung terintegrasi di sidebar `admin.php`, namun secara otomatis akan **disembunyikan** jika yang login adalah Admin biasa.

### 2. Pengalaman E-Commerce Interaktif
- **Tabel Keranjang (`carts`)**: Ditambahkan dukungan tabel keranjang di database agar *user* bisa memilih banyak tipe motor sekaligus sebelum membayar.
- **Best Sellers (Homepage)**: Halaman depan kini menampilkan hingga 8 motor paling laku secara dinamis yang dihitung dari total riwayat transaksi (yang tidak berstatus *cancelled*).
- **Halaman Discover**: Halaman khusus katalog motor dengan fitur **Pagination** (membatasi 20 motor per halaman) untuk mempercepat *loading* saat data semakin banyak.
- **Riwayat Transaksi**: Pengguna biasa (User) sekarang memiliki halaman khusus `history.php` untuk melacak status pesanan mereka sendiri (Unpaid, Diproses, Selesai/Batal).

### 3. Manajemen Profil Akun (Settings)
- Tombol *Logout* di Navbar kini diganti dengan Icon **Settings (⚙️)**.
- Halaman `settings.php` memungkinkan pengguna memperbarui **Email** dan **Password** mereka.
- **Keamanan Ketat**: Saat pengguna ingin mengubah email atau password, sistem mewajibkan mereka untuk memvalidasi tindakan dengan memasukkan **Password Saat Ini** terlebih dahulu (Konfirmasi Password). Tombol *Logout* yang baru dipindahkan ke halaman ini.

### 4. Quick Stats Card Icon Watermarks
- Repositioned the icons in all five cards in the Quick Stats row.
- The icons are positioned absolutely in the top-right corner (`absolute right-2 top-2`).
- Styled as large (`80px`), semi-transparent (`opacity-[0.08]`) background watermarks.
- Rotated counter-clockwise (`-rotate-12`) so they tilt from top-left to bottom-right (`\`), as requested.
- Card texts and values are wrapped in a container with `relative z-10` to ensure they sit above the watermark icons and remain perfectly readable.

### 5. Sidebar Scrolling & Control Panel Branding
- Changed the header text "Owner Panel" to **"Control Panel"** to make it feel like a unified admin hub.
- Added `overflow-y-auto` and customized scrollbar styling to the `<aside>` sidebar container to gracefully support smaller screens or zoomed-in displays (>90% zoom) without cutting off bottom profile cards or links.

### 6. Header Spacing Fix
- Moved the `<script>` and `<style>` blocks in `_reports.php` inside the `<section id="reports">` container.
- This prevents Tailwind's `space-y-12` on the parent container from treating the tags as sibling layout blocks, aligning the top padding of the dashboard content page perfectly with other admin pages.

### 7. 6-Month Booking Expiration and Refund Logic
- Appended a background check in `config.php` that runs seamlessly on page load:
  - Selects `booking` transactions that are `pending` and have `paid` their DP but have a transaction date older than 6 months.
  - Automatically cancels the booking (`status = 'cancelled'`) and refunds the DP (`payment_status = 'refunded'`).
  - Restores the reserved quantity of motorcycles back to the stock inventory.
  - Records the action in system logs and triggers a notification to the user about their cancelled booking and DP refund.

### 8. Injeksi Ulasan Dummy & Penyesuaian Badge Brand
- **Injeksi Ulasan Acak Bermutu**: Ditambahkan logika otomatis di [inject_dummy.php](file:///c:/Users/user/Documents/uassmt2/basdat/inject_dummy.php) untuk membaca transaksi pembelian terkonfirmasi (`status = 'confirmed'` atau `payment_status = 'paid'`) dan secara acak menginjeksi ulasan pelanggan dengan rating (1-5), komentar Indonesia realistik yang variatif sesuai rating, serta status anonim acak (30% anonim).
- **Badge Brand Gelap Premium**: Mengubah kelas css badge brand di pojok kiri atas [detail.php](file:///c:/Users/user/Documents/uassmt2/basdat/detail.php) menjadi `bg-black/50 backdrop-blur-sm text-[#ffffff] shadow-md` tanpa border agar sama dengan desain premium di halaman discover.

### 9. Sistem Penerjemahan & Lokalisasi Global (Global Localization System)
- **Dukungan Bahasa Inggris Penuh (Full English i18n)**: Memastikan seluruh bagian antarmuka pengguna (UI), formulir, tombol, label input, dialog konfirmasi JavaScript, pesan kesalahan (error), dan flash alert admin diterjemahkan secara dinamis menggunakan helper `__($en, $id)` berdasarkan bahasa yang sedang aktif.
- **Log & Notifikasi Dwibahasa Dinamis (Bilingual ' || ' Parser)**: Memperbarui cara penyimpanan log aktivitas sistem (`logs`) dan notifikasi pengguna (`notifications`) di database dengan format dwibahasa terpisah `English || Indonesian`. Di sisi tampilan, pesan ini otomatis diurai dan ditampilkan dalam satu bahasa yang sesuai dengan preferensi pengguna.
- **Perbaikan Judul Ganda (Single Translation Titles)**: Menyelesaikan masalah teks ganda seperti "Direct Discounts (Potongan Langsung)" dan "Promo Coupons (Kupon Kode)" di panel admin sehingga hanya menampilkan satu versi bahasa secara bersih.

## File Changes

### [_sales_mgmt.php](file:///c:/Users/user/Documents/uassmt2/basdat/_sales_mgmt.php)
- Menerjemahkan semua label, formulir, detail kupon, opsi dropdown, dan judul kartu. Menghapus teks bahasa ganda (bilingual) gabungan.

### [_shipping_mgmt.php](file:///c:/Users/user/Documents/uassmt2/basdat/_shipping_mgmt.php)
- Menerjemahkan semua formulir logistik kurir, lencana (badge) kurir gratis/berbayar, tombol aksi, serta catatan sistem pengiriman.

### [_academic_demo.php](file:///c:/Users/user/Documents/uassmt2/basdat/_academic_demo.php)
- Menerjemahkan deskripsi simulasi Table Locking, tips instruksi, label tombol lock/TCL, serta pesan hitung mundur JavaScript (countdown).

### [admin.php](file:///c:/Users/user/Documents/uassmt2/basdat/admin.php)
- Menerjemahkan label dropdown status transaksi, mengintegrasikan parser log dwibahasa ` || ` untuk riwayat aktivitas, dan menerjemahkan semua flash alert admin.

### [notifications_ui.php](file:///c:/Users/user/Documents/uassmt2/basdat/notifications_ui.php)
- Menambahkan parser dynamic bilingual ` || ` untuk membelah dan merender pesan notifikasi dalam bahasa aktif pilihan pengguna.

### [auth.php](file:///c:/Users/user/Documents/uassmt2/basdat/auth.php), [logout.php](file:///c:/Users/user/Documents/uassmt2/basdat/logout.php), [checkout.php](file:///c:/Users/user/Documents/uassmt2/basdat/checkout.php), [payment.php](file:///c:/Users/user/Documents/uassmt2/basdat/payment.php), [config.php](file:///c:/Users/user/Documents/uassmt2/basdat/config.php)
- Mengubah format insert data notifikasi dan pencatatan log tindakan sistem agar menggunakan pemisah dwibahasa ` || ` yang aman untuk diterjemahkan dinamis saat dirender.

## Verification
- Script `inject_dummy.php` berhasil dijalankan dan sukses menginjeksi ulasan dengan rating dan komentar Indonesia acak.
- Tampilan detail kendaraan menunjukkan badge brand yang elegan dengan tingkat keterbacaan yang tinggi.
- Daftar ulasan di halaman detail merender data rating, ulasan, tanggal review, dan penyamaran nama pengguna anonim secara tepat.
- Code successfully runs in PHP CLI check with no syntax errors or variables warnings.
- Dynamic data bindings and auto-expiration features are fully verified and responsive.
- Pengujian manual menunjukkan bahwa saat menu bahasa di-toggle ke Bahasa Inggris ('en'), seluruh UI, termasuk notifikasi sistem terbaru dan log aksi yang baru tercatat, beralih ke Bahasa Inggris secara penuh tanpa ada teks ganda atau teks Indonesia yang tertinggal.

---

## 🛠️ Cara Setup & Menjalankan Aplikasi

> [!IMPORTANT]
> Karena ada penambahan tabel baru (`carts`), Anda **Wajib** mengulangi proses pembuatan database dari awal.

### 1. Reset / Persiapan Database (Wajib)
1. Jalankan modul **Apache** dan **MySQL** di XAMPP Anda.
2. Buka `http://localhost/phpmyadmin` di browser.
3. Hapus database `dealer_db` yang lama (jika ada), lalu buat ulang.
4. Klik tab **Import**, lalu pilih dan jalankan file-file SQL berikut secara berurutan:
   - **`ddl.sql`** (Membuat semua struktur tabel)
   - **`dml.sql`** (Mengisi data master, katalog, dan dummy)
   - **`functions.sql`** (Mendaftarkan trigger, fungsi UDF, dan stored procedure)
5. Klik **Go** / Kirim untuk masing-masing file.
6. *Selamat! Struktur tabel baru, data dummy, dan program SQL basis data sudah otomatis terpasang.*

### 2. Menjalankan Server
**Menggunakan Terminal PHP**
Buka terminal di dalam folder proyek Anda, ketik perintah berikut:
```bash
php -S localhost:8000
```
Lalu buka browser ke `http://localhost:8000/index.php`.

---

## 🧪 Panduan Testing (Data Dummy)

Untuk memudahkan Anda mengetes seluruh fitur tanpa harus mendaftar satu per satu, `dml.sql` sudah men-generate 3 buah akun dengan peran yang berbeda-beda. Semua akun ini memiliki password awal: **`123456`**.

### 1. Mengetes Fitur E-Commerce (User)
* Email: `user@gmail.com`
* Password: `123456`
* **Cara Tes:**
  - Klik *Add to Cart* pada beberapa motor.
  - Perhatikan icon keranjang di kanan atas bertambah jumlahnya.
  - Klik icon keranjang, lalu klik *Proceed to Checkout*.
  - Setelah pesanan berhasil, Anda akan diarahkan ke halaman *Riwayat Transaksi*.

### 2. Mengetes Keamanan Akun (Settings)
* **Cara Tes:**
  - Klik icon **Settings ⚙️** di navigasi atas.
  - Coba ganti email Anda. Pada bagian "Current Password", masukkan sandi yang salah (misalnya `111`). Sistem akan menolak perubahan.
  - Kemudian coba masukkan sandi yang benar (`123456`), maka sistem akan berhasil memperbarui email.

### 3. Mengetes Sisi Owner / Admin
* Email: `owner@gmail.com` / `admin@gmail.com`
* Password: `123456`
* **Cara Tes:**
  - Masuk ke **Admin Panel**. Klik menu *Transactions* di sidebar kiri.
  - Cari pesanan yang baru saja dibuat oleh user tadi.
  - Ubah status menjadi "Confirmed" dan status bayar menjadi "Paid".
  - (Khusus Owner): Klik menu **System Logs** di sidebar, Anda akan melihat rekaman aktivitas Anda sendiri (mengubah status transaksi) terangkum dengan rapi!
