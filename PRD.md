# PRD: Toko Bangunan dengan Satuan Konversi

## 1. Latar Belakang & Tujuan

Toko bangunan pada umumnya menjual barang dengan lebih dari satu satuan. Semen dijual per sak
tapi stok gudang dihitung per kg, besi beton dijual per batang tapi kadang dibeli per kg saat
rusak/potongan, pasir dijual per kubik atau per rit. Jika stok dicatat terpisah per satuan,
angka stok gudang mudah tidak sinkron: kasir yang menjual semen per sak dan admin yang mencatat
pembelian dalam kg akan punya dua angka stok yang tidak saling nyambung.

Aplikasi ini dibangun agar:
- Stok barang selalu konsisten walau dijual dalam satuan yang berbeda-beda, dengan cara
  menyimpan stok HANYA dalam satu satuan dasar per produk, dan mengonversi setiap transaksi ke
  satuan dasar itu sebelum mengurangi stok.
- Toko bisa memberi harga grosir berjenjang (semakin banyak beli, semakin murah per satuan)
  tanpa mengorbankan konsistensi stok di atas.
- Toko bisa melayani pelanggan proyek yang biasa bayar tempo (kredit), dengan piutang yang
  tercatat rapi beserta jatuh temponya, termasuk laporan umur piutang (aging).

## 2. Aktor

- **Admin**: pemilik/pengelola toko. Akses penuh: kelola produk & satuan, kelola tingkatan
  harga grosir, kelola pelanggan, kelola penjualan, kelola piutang & pembayaran, lihat laporan.
- **Kasir**: melayani transaksi harian di kasir. Bisa membuat penjualan baru, mencari
  produk/stok/harga, mencatat pembayaran piutang. Tidak bisa mengubah data master (produk,
  satuan konversi, tingkatan harga) karena kesalahan di sana berdampak ke semua transaksi
  berikutnya dan harus melalui admin.
- **Pelanggan proyek** (`proyek`): biasanya kontraktor/tukang bangunan berlangganan, boleh bayar
  tempo (kredit) dengan limit kredit tertentu.
- **Pelanggan umum** (`umum`): pembeli perorangan/eceran, transaksi tunai. Jika pelanggan umum
  datang tanpa data pelanggan tersimpan, sistem tetap mensyaratkan sebuah baris `customer`
  (memakai pelanggan generik "Umum/Walk-in" yang dibuat saat seeding) agar setiap `sale` selalu
  punya `customer_id` yang valid — ini menyederhanakan query laporan karena tidak perlu
  menangani `customer_id NULL` di semua tempat.

## 3. Lingkup Fitur

1. Manajemen produk multi-satuan dengan konversi ke satuan dasar.
2. Harga grosir berjenjang per produk+satuan (tiered pricing).
3. Transaksi penjualan (kasir) dengan satuan campuran, validasi & pengurangan stok konsisten.
4. Manajemen pelanggan (umum/proyek) dan piutang tempo dengan jatuh tempo, pembayaran parsial,
   dan laporan aging piutang.
5. Otentikasi & otorisasi berbasis role (admin/kasir).

Di luar lingkup (sengaja tidak dibangun agar sesuai kebutuhan nyata & waktu pengerjaan):
pembelian/purchase order ke supplier, multi-gudang, retur barang, cetak struk thermal, integrasi
pembayaran digital.

## 4. Entitas Data Utama

- **User**: `name`, `email`, `password`, `role` (`admin`/`kasir`).
- **Product**: `name`, `sku`, `base_unit_name`, `base_stock` (satu-satunya tempat stok
  disimpan, selalu dalam `base_unit_name`), `min_stock`.
- **ProductUnit**: `product_id`, `unit_name`, `conversion_to_base` (berapa `base_unit_name`
  setara 1 `unit_name`). Baris untuk satuan dasar sendiri juga ada dengan
  `conversion_to_base = 1`, agar semua lookup harga & konversi seragam melalui tabel ini.
- **PriceTier**: `product_id`, `product_unit_id`, `min_qty` (qty minimum DALAM satuan itu untuk
  masuk tingkatan ini), `price_per_unit`.
- **Customer**: `name`, `phone`, `address`, `type` (`umum`/`proyek`), `credit_limit`
  (relevan hanya untuk `proyek`).
- **Sale**: `customer_id`, `sale_date`, `payment_type` (`tunai`/`tempo`), `due_date`
  (wajib jika `tempo`), `status` (`lunas`/`belum_lunas`, dihitung dari total dibayar vs total
  tagihan penjualan tunai, atau dari status piutangnya jika tempo), `created_by` (user kasir).
- **SaleItem**: `sale_id`, `product_id`, `product_unit_id`, `qty`, `unit_price` (snapshot harga
  saat transaksi, tidak berubah walau tingkatan harga berubah kemudian), `subtotal`.
- **Receivable** (piutang): `sale_id`, `customer_id`, `amount`, `due_date`, `status`
  (`belum_lunas`/`lunas`/`jatuh_tempo`). Dibuat otomatis untuk setiap penjualan `tempo`.
- **ReceivablePayment**: `receivable_id`, `amount`, `paid_at`. Mendukung pembayaran parsial;
  sisa tagihan = `amount - SUM(pembayaran)`; status `lunas` begitu sisa <= 0.

## 5. Alur Transaksi End-to-End

1. Kasir membuka form penjualan baru, memilih pelanggan (atau memilih "Umum/Walk-in").
2. Kasir menambahkan item: pilih produk, pilih satuan jual (misalnya "sak" untuk semen), input
   qty. Sistem otomatis mencari tingkatan harga (price tier) yang berlaku untuk produk+satuan+qty
   tersebut (tier dengan `min_qty` terbesar yang tetap `<= qty`), lalu menetapkan `unit_price`.
3. Sistem menghitung `qty * conversion_to_base` dari `product_unit` yang dipilih untuk
   memperkirakan kebutuhan stok dalam satuan dasar, dan menampilkan stok yang tersedia dalam
   satuan itu (misalnya "Stok: 320 kg (setara 8 sak)") agar kasir yakin stok cukup sebelum
   menyimpan.
4. Saat penjualan disimpan: untuk setiap item, sistem MEMVALIDASI `base_stock >= kebutuhan_base`
   sebelum menyentuh data apapun; jika salah satu item tidak cukup, seluruh transaksi ditolak
   (tidak ada penyimpanan sebagian) dengan pesan error yang jelas (nama produk & stok
   tersedia). Jika valid, sistem mengurangi `base_stock` produk sebesar kebutuhan_base dan
   menyimpan `sale_item` dengan `unit_price` & `subtotal` hasil hitungan di atas.
5. Jika `payment_type = tempo`: sistem membuat satu baris `Receivable` senilai total penjualan
   dengan `due_date` sesuai input kasir, status awal `belum_lunas`.
6. Jika `payment_type = tunai`: `sale.status` langsung `lunas`, tidak ada `Receivable` dibuat.
7. Admin dapat melihat halaman "Piutang", mencatat pembayaran (parsial/lunas) terhadap sebuah
   `Receivable`; sisa tagihan dihitung ulang dan status di-refresh (`lunas` jika sisa <= 0).
8. Status `jatuh_tempo` dihitung on-the-fly saat halaman piutang/report diakses (bukan lewat job
   terjadwal) dengan membandingkan `due_date` terhadap tanggal hari ini untuk piutang yang masih
   `belum_lunas` — lihat catatan keputusan desain di bagian 7.

## 6. Kriteria Penerimaan per Fitur Inti

### Fitur 1: Penjualan multi-satuan dengan stok konsisten
- [ ] Admin dapat menambahkan beberapa `product_unit` untuk satu produk, masing-masing dengan
      `conversion_to_base` sendiri, termasuk baris untuk satuan dasarnya sendiri
      (`conversion_to_base = 1`).
- [ ] Form penjualan menampilkan stok tersedia dalam satuan dasar DAN dikonversi ke satuan yang
      sedang dipilih kasir.
- [ ] Menyimpan penjualan mengurangi `base_stock` sejumlah `qty * conversion_to_base`, bukan
      `qty` mentah, untuk setiap satuan yang dipakai.
- [ ] Menjual gabungan beberapa satuan berbeda dari produk yang sama pada transaksi yang sama
      mengurangi `base_stock` secara akumulatif dan konsisten (contoh: jual 2 sak + 10 kg semen
      dalam satu transaksi mengurangi 90 kg total).
- [ ] Percobaan menjual lebih dari stok yang tersedia (setelah dikonversi) DITOLAK, stok tidak
      berubah, dan tidak ada baris `sale`/`sale_item` yang tersimpan sebagian.
- [ ] Aturan konversi & pengurangan stok hanya ada di satu tempat (service/model method), dipakai
      baik dari Livewire form maupun dari seeder demo, sehingga tidak bisa dilewati.

### Fitur 2: Harga grosir berjenjang
- [ ] Admin dapat membuat beberapa `price_tier` untuk kombinasi produk+satuan yang sama dengan
      `min_qty` berbeda.
- [ ] Saat qty yang dibeli persis sama dengan `min_qty` sebuah tier, tier tersebut yang berlaku
      (boundary inklusif), bukan tier di bawahnya.
- [ ] Saat qty satu unit di bawah `min_qty` sebuah tier, tier di bawahnya yang berlaku.
- [ ] Jika qty melebihi `min_qty` tier tertinggi, tier tertinggi tetap berlaku (bukan error).
- [ ] Jika produk+satuan belum punya tier apapun, sistem menolak penjualan item itu dengan pesan
      jelas (tidak ada harga 0 diam-diam).

### Fitur 3: Piutang pelanggan dengan jatuh tempo
- [ ] Penjualan `tempo` otomatis membuat `Receivable` dengan `due_date` sesuai input dan status
      `belum_lunas`.
- [ ] Halaman Piutang menampilkan daftar piutang dengan sisa tagihan, jatuh tempo, dan status,
      bisa difilter per pelanggan dan per status.
- [ ] Mencatat pembayaran parsial mengurangi sisa tagihan tapi status tetap `belum_lunas`/
      `jatuh_tempo` selama sisa > 0.
- [ ] Mencatat pembayaran yang membuat sisa <= 0 mengubah status jadi `lunas`.
- [ ] Laporan aging mengelompokkan piutang yang belum lunas ke: "belum jatuh tempo",
      "1-30 hari terlambat", "31-60 hari terlambat", ">60 hari terlambat", dihitung dari
      `due_date` terhadap tanggal hari ini.

## 7. Catatan Keputusan Desain

- **Batas akses kasir**: kasir hanya bisa CRUD `Sale`/`SaleItem` (membuat penjualan baru) dan
  mencatat `ReceivablePayment`. Kasir tidak bisa membuat/mengubah `Product`, `ProductUnit`,
  `PriceTier`, atau `Customer` master data — cukup satu kolom `role` enum di tabel `users`,
  dicek lewat middleware/policy sederhana, tanpa tabel permission granular karena hanya dua
  peran dan kebutuhannya sederhana.
- **Tidak ada override harga manual saat transaksi**: harga selalu ditentukan oleh price tier
  yang berlaku, tidak ada input harga bebas oleh kasir maupun admin di form penjualan. Ini
  menjaga histori transaksi konsisten dengan aturan harga yang berlaku, dan mencegah kasir
  memberi diskon tanpa jejak. Jika toko butuh harga khusus, itu berarti menambah price tier baru,
  bukan mengedit di form penjualan.
- **Pelanggan walk-in**: setiap `Sale` mensyaratkan `customer_id`. Seeder membuat satu customer
  bertipe `umum` bernama "Umum / Walk-in" untuk transaksi tanpa data pelanggan spesifik.
- **Status `jatuh_tempo` dihitung saat dibaca (bukan command terjadwal)**: karena tidak ada
  kebutuhan background job di aplikasi ini dan jumlah piutang pada skala toko bangunan kecil-
  menengah tidak besar, status jatuh tempo dihitung langsung dari perbandingan `due_date` vs
  `now()` setiap kali halaman piutang/aging dibuka, bukan lewat scheduled command yang menulis
  ulang kolom `status` di database. Ini lebih sederhana, selalu akurat (tidak bisa "basi" karena
  command belum jalan), dan cukup untuk kebutuhan pelaporan. Kolom `status` di tabel tetap
  disimpan untuk status `lunas`/`belum_lunas` (yang memang perlu ditulis saat ada pembayaran),
  sedangkan `jatuh_tempo` adalah status tampilan yang diturunkan dari `belum_lunas` + `due_date`
  terlewat.
