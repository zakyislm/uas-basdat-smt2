# Pemenuhan Syarat Akademik UAS Basis Data - MotoInfy

Dokumen ini mencantumkan seluruh persyaratan akademik basis data yang telah dipenuhi dalam proyek **MotoInfy**. Semua objek database ini didefinisikan dalam file [ddl.sql](file:///c:/Users/user/Documents/uassmt2/basdat/ddl.sql), [dml.sql](file:///c:/Users/user/Documents/uassmt2/basdat/dml.sql), dan [functions.sql](file:///c:/Users/user/Documents/uassmt2/basdat/functions.sql).

---

## 1. Tabel Basis Data (Total: 20 Tabel)
Proyek ini memenuhi syarat jumlah tabel dengan total **20 tabel** (6 tabel utama + 14 tabel ekstensi akademik):

### Tabel Utama:
1. `users` - Menyimpan informasi pengguna (User, Admin, Owner)
2. `motorcycles` - Menyimpan katalog sepeda motor
3. `transactions` - Menyimpan transaksi pembelian/booking
4. `logs` - Menyimpan riwayat aktivitas aplikasi
5. `notifications` - Menyimpan notifikasi sistem
6. `carts` - Menyimpan keranjang belanja user

### Tabel Ekstensi Akademik (14 Tabel Baru):
7. `brands` - Merek sepeda motor
8. `categories` - Kategori motor (Sport, Matic, dsb.)
9. `colors` - Pilihan warna motor
10. `engine_types` - Kapasitas/tipe mesin
11. `payment_methods` - Metode pembayaran (Transfer, Leasing)
12. `provinces` - Data provinsi untuk pajak/pengiriman
13. `cities` - Data kota yang terhubung ke provinsi
14. `dealers` - Daftar dealer motor
15. `discounts` - Kode promo/diskon
16. `wishlists` - Daftar keinginan user
17. `reviews` - Rating dan komentar dari pembeli
18. `shipping_methods` - Metode pengiriman motor
19. `tax_rates` - Tarif pajak per provinsi
20. `audit_logs` - Log otomatis dari trigger database

---

## 2. Triggers (Minimal 3 Trigger)
Tiga trigger telah diimplementasikan untuk otomatisasi keamanan dan pencatatan audit:

*   **`trg_after_insert_transaction` (AFTER INSERT on `transactions`)**
    *   *Fungsi:* Secara otomatis mencatat log audit ke tabel `audit_logs` setiap kali ada transaksi baru yang dibuat.
*   **`trg_before_update_motorcycle` (BEFORE UPDATE on `motorcycles`)**
    *   *Fungsi:* Validasi integritas data. Mencegah perubahan data jika stok bernilai kurang dari 0 (`stock < 0`) dengan melempar error (`SIGNAL SQLSTATE '45000'`).
*   **`trg_after_insert_review` (AFTER INSERT on `reviews`)**
    *   *Fungsi:* Secara otomatis mencatat log audit ke tabel `audit_logs` setiap kali ada user yang menambahkan rating/review untuk motor tertentu.

---

## 3. User Defined Functions / UDF (Minimal 2 Function)
Dua fungsi kustom dibuat untuk membantu perhitungan kalkulasi nilai secara modular:

*   **`calculate_final_price(price, discount_pct, tax_pct)`**
    *   *Fungsi:* Menghitung harga akhir setelah dikurangi persentase diskon dan ditambah persentase pajak PPn.
    *   *Sifat:* `DETERMINISTIC`
*   **`get_total_spent(user_id)`**
    *   *Fungsi:* Mengambil total akumulasi uang yang telah dibelanjakan oleh seorang pengguna berdasarkan riwayat transaksi pembelian motor.
    *   *Sifat:* `READS SQL DATA`

---

## 4. Stored Procedures (Minimal 3 Procedure)
Tiga procedure dibuat untuk memproses logika bisnis yang kompleks langsung di sisi database:

*   **`sp_add_motorcycle(...)`**
    *   *Fungsi:* Menyederhanakan proses penambahan motor baru ke dalam tabel `motorcycles`.
*   **`sp_process_checkout(user_id, motorcycle_id, quantity)`**
    *   *Fungsi:* Memproses pembelian motor dengan aman. Memeriksa ketersediaan stok, mengurangi stok, mencatat transaksi, dan mengontrol transaksi menggunakan TCL (`COMMIT`/`ROLLBACK`).
*   **`sp_update_expired_discounts()`**
    *   *Fungsi:* Memperbarui status diskon yang sudah melewati tanggal kedaluwarsa secara berkala menggunakan Cursor.

---

## 5. Cursor
*   **Implementasi di `sp_update_expired_discounts`**
    *   *Fungsi:* Cursor digunakan untuk melakukan iterasi (`LOOP`) pada daftar diskon yang sudah kedaluwarsa (`valid_until < NOW()`) dan masih aktif, kemudian menonaktifkannya satu per satu secara sekuensial.

---

## 6. Transaction Control Language (TCL) & Table Locking
Kedua fitur ini disimulasikan secara interaktif pada halaman demo akademik:

*   **TCL (`COMMIT` & `ROLLBACK`)**
    *   *Lokasi Demo:* Halaman Akademik ([_academic_demo.php](file:///c:/Users/user/Documents/uassmt2/basdat/_academic_demo.php#L152-L179))
    *   *Cara Kerja:* Menyediakan tombol simulasi untuk menjalankan query insert transaksi dan update stok. Dosen penguji dapat memilih untuk melakukan `COMMIT` (menyimpan permanen) atau `ROLLBACK` (membatalkan semua perubahan).
*   **Table Locking (`LOCK TABLES` & `UNLOCK TABLES`)**
    *   *Lokasi Demo:* Halaman Akademik ([_academic_demo.php](file:///c:/Users/user/Documents/uassmt2/basdat/_academic_demo.php#L76-L150) & [ajax_lock.php](file:///c:/Users/user/Documents/uassmt2/basdat/ajax_lock.php))
    *   *Cara Kerja:* Mengunci tabel `motorcycles` menggunakan kunci `WRITE` selama 10 detik untuk mendemonstrasikan antrean akses konkuren (race conditions). Selama 10 detik terkunci, sesi browser lain tidak akan bisa membaca/menulis ke katalog motor sampai kunci dilepaskan (`UNLOCK TABLES`).
