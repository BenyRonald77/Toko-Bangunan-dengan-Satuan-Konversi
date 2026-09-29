# Toko Bangunan dengan Satuan Konversi

Aplikasi kasir/back-office internal untuk toko bangunan: penjualan multi-satuan dengan stok
yang selalu konsisten, harga grosir berjenjang, dan piutang pelanggan proyek beserta laporan
umur jatuh temponya (aging). Lihat `PRD.md` untuk spesifikasi lengkap dan `DESIGN.md` untuk
arah desain UI.

## Setup

Kebutuhan: PHP 8.4+, Composer 2.x, Node.js/npm.

```bash
composer install
npm install

cp .env.example .env      # sudah diset DB_CONNECTION=sqlite untuk sandbox ini
php artisan key:generate

touch database/database.sqlite
php artisan migrate --seed

npm run build              # atau: npm run dev, saat mengembangkan
php artisan serve
```

Buka `http://127.0.0.1:8000`. Untuk deployment sungguhan, ganti blok database di `.env` ke
blok MySQL yang sudah tersedia (dikomentari) di `.env.example`.

Menjalankan test:

```bash
php artisan test
```

## Kredensial Demo

Dibuat oleh `UserSeeder` (`php artisan migrate --seed`):

| Peran  | Email                      | Password   |
|--------|-----------------------------|------------|
| Admin  | admin@tokobangunan.test     | `password` |
| Kasir  | kasir@tokobangunan.test     | `password` |

Data lain yang ikut di-seed: 3 pelanggan (Umum/Walk-in, satu pelanggan umum, satu pelanggan
proyek dengan limit kredit), dan 7 produk bahan bangunan (semen, besi beton, cat tembok,
keramik, pasir, paku, triplek) lengkap dengan satuan jual dan tingkatan harga grosirnya.

## Fitur

- **Manajemen produk & satuan konversi** (admin): tiap produk punya satuan dasar (tempat stok
  benar-benar disimpan) dan boleh punya beberapa satuan jual lain dengan faktor konversinya
  masing-masing ke satuan dasar (mis. 1 sak semen = 40 kg).
- **Harga grosir berjenjang** (admin): beberapa tingkatan harga per kombinasi produk+satuan,
  dipilih otomatis berdasarkan qty yang dibeli saat transaksi.
- **Penjualan multi-satuan** (admin & kasir): kasir memilih produk, satuan, dan qty; harga
  dan pratinjau stok (termasuk konversinya) tampil langsung sebelum disimpan. Stok divalidasi
  dan dikurangi dalam satuan dasar, dan satu transaksi yang mengandung item dengan stok tidak
  cukup ditolak seluruhnya (tidak ada penyimpanan sebagian).
- **Piutang pelanggan (tempo)** (admin & kasir): penjualan dengan pembayaran tempo otomatis
  membuat piutang dengan jatuh tempo; pembayaran parsial maupun lunas bisa dicatat dan sisa
  tagihan/status ter-update otomatis.
- **Laporan aging piutang**: piutang yang belum lunas dikelompokkan ke belum jatuh tempo,
  terlambat 1-30 hari, 31-60 hari, dan >60 hari, masing-masing dengan jumlah dan total nyata
  (bukan angka rekaan), dan bisa dipakai sebagai filter daftar piutang.
- **Dashboard** ringkas: transaksi hari ini, jumlah piutang jatuh tempo, dan (khusus admin)
  daftar produk yang stoknya sudah di bawah ambang minimum.
- Login & manajemen profil bawaan Laravel Breeze (stack Livewire).

Batasan peran: kasir bisa membuat penjualan dan mencatat pembayaran piutang, tapi tidak bisa
mengubah data master (produk, satuan, tingkatan harga, pelanggan) — itu wewenang admin. Lihat
PRD bagian 7 untuk alasannya.

## Bagaimana Model Konversi Satuan & Stok Bekerja

Ini bagian paling penting untuk dipercaya angkanya, jadi dijelaskan di sini, bukan hanya di kode:

1. **Stok sebuah produk hanya disimpan di satu tempat**: kolom `products.base_stock`, selalu
   dalam satuan dasar produk itu (`products.base_unit_name`, misal "kg"). Tidak ada stok
   terpisah per satuan jual — itu sumber ketidaksinkronan yang ingin dihindari aplikasi ini.
2. **Setiap satuan jual punya faktor konversi ke satuan dasar**, disimpan di
   `product_units.conversion_to_base`. Satuan dasar sendiri juga punya baris di sini dengan
   `conversion_to_base = 1`, supaya semua satuan (termasuk satuan dasar) diperlakukan sama saat
   mencari harga.
3. **Saat sebuah item penjualan disimpan**, sistem menghitung
   `qty_yang_dibeli * conversion_to_base` satuan itu untuk mendapatkan kebutuhan dalam satuan
   dasar, lalu mengurangi `base_stock` sejumlah itu. Membeli 2 sak + 10 kg semen dalam satu
   transaksi mengurangi `base_stock` sejumlah `2*40 + 10*1 = 90 kg` — bukan "2" dan "10" yang
   dikurangi dari dua tempat berbeda.
4. **Validasi terjadi sebelum penyimpanan apa pun**: seluruh kebutuhan stok satu transaksi
   (bisa lebih dari satu produk/item) dihitung dan dicek terhadap `base_stock` yang ada,
   dan HANYA jika semuanya cukup, transaksi baru benar-benar disimpan dan stok dikurangi,
   dalam satu database transaction. Kalau satu item saja kurang stoknya, seluruh transaksi
   dibatalkan — tidak ada penjualan yang "separuh tersimpan".
5. **Aturan ini hanya ada di satu tempat**: `App\Services\SaleService::createSale()`. Baik
   form penjualan (Livewire) maupun seeder demo memanggil service yang sama, sehingga tidak ada
   jalur lain yang bisa menyimpan `sale_item` tanpa melewati validasi dan pengurangan stok ini.
6. **Harga yang dipakai juga dihitung di tempat yang sama** (`App\Services\PricingService`):
   tingkatan harga (`price_tiers`) dipilih berdasarkan qty yang dibeli untuk kombinasi
   produk+satuan itu — tier dengan `min_qty` terbesar yang tetap `<= qty` dipakai (batas
   `min_qty` itu sendiri termasuk dalam tier itu, bukan tier di bawahnya). Harga yang berlaku
   disimpan sebagai snapshot di `sale_items.unit_price`, jadi mengubah tier di kemudian hari
   tidak mengubah riwayat transaksi yang sudah terjadi.

Karena dua aturan di atas (konversi+stok, dan pemilihan harga) hanya hidup di satu service
masing-masing, siapa pun yang menambah jalur baru untuk membuat penjualan (API, import, dll)
di masa depan otomatis ikut aturan yang sama selama memanggil `SaleService`/`PricingService`,
bukan menulis ulang logikanya.

## Status Piutang "Jatuh Tempo"

Kolom `receivables.status` di database hanya menyimpan `belum_lunas`/`lunas` (yang memang perlu
ditulis saat ada pembayaran). Status "jatuh tempo" dihitung saat dibaca
(`Receivable::displayStatus()`), dengan membandingkan `due_date` terhadap tanggal hari ini untuk
piutang yang `belum_lunas` — bukan lewat command terjadwal yang menulis ulang status. Ini lebih
sederhana dan tidak bisa "basi" karena tidak menunggu job jalan; laporan aging memakai turunan
yang sama (`Receivable::agingBucket()`).
