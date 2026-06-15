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

---

## 🛠️ Cara Setup & Menjalankan Aplikasi

> [!IMPORTANT]
> Karena ada penambahan tabel baru (`carts`), Anda **Wajib** mengulangi proses pembuatan database dari awal.

### 1. Reset / Persiapan Database (Wajib)
1. Jalankan modul **Apache** dan **MySQL** di XAMPP Anda.
2. Buka `http://localhost/phpmyadmin` di browser.
3. Hapus database `dealer_db` yang lama (jika ada), lalu buat ulang.
4. Klik tab **Import**, lalu pilih file `schema.sql` dari folder proyek Anda.
5. Klik **Go** / Kirim. 
6. *Selamat! Struktur tabel baru (termasuk carts) beserta data-data dummy sudah otomatis masuk.*

### 2. Menjalankan Server
**Menggunakan Terminal PHP**
Buka terminal di dalam folder proyek Anda, ketik perintah berikut:
```bash
php -S localhost:8000
```
Lalu buka browser ke `http://localhost:8000/index.php`.

---

## 🧪 Panduan Testing (Data Dummy)

Untuk memudahkan Anda mengetes seluruh fitur tanpa harus mendaftar satu per satu, `schema.sql` sudah men-generate 3 buah akun dengan peran yang berbeda-beda. Semua akun ini memiliki password awal: **`123456`**.

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
