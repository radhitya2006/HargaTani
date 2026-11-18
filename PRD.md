# Product Requirements Document — HargaTani

| Atribut | Keterangan |
| --- | --- |
| Versi | 1.0 — Rancangan MVP UAS |
| Tanggal | 14 November 2026 |
| Nama produk | HargaTani |
| Konteks | Proyek UAS fullstack mahasiswa Teknologi Informasi semester 3 |
| Wilayah | Bali |
| Status | Spesifikasi untuk implementasi; aplikasi belum dibangun |
| Asumsi jadwal | Enam minggu sejak pengerjaan dimulai; bukan tenggat yang sudah dikonfirmasi |

## 1. Gambaran Umum

### 1.1 Masalah

Petani membutuhkan akses informasi harga yang mudah dipahami, cara menghitung harga jual berdasarkan biaya, serta saluran untuk menerima pesanan langsung dari pembeli. Informasi harga yang tersebar belum otomatis membantu petani menghubungkan kondisi pasar dengan penjualan hasil panennya.

### 1.2 Solusi dan proposisi nilai

HargaTani menggabungkan dashboard harga pangan Bali, estimasi harga berbasis perhitungan backend, kalkulator harga jual, etalase hasil panen, dan pemesanan sederhana. AI membantu menulis deskripsi produk, menjelaskan tren, dan menjelaskan pilihan harga jual.

Alur utama: **pantau harga → hitung harga jual → terbitkan produk → terima pesanan**.

Tujuan MVP adalah meningkatkan akses informasi dan menyediakan alternatif pemasaran langsung. Peningkatan pendapatan atau berkurangnya ketergantungan pada tengkulak belum menjadi klaim keberhasilan tanpa penelitian lapangan.

### 1.3 Batas produk awal

- Tiga peran: petani, pembeli, dan admin. Satu akun memiliki satu peran pada MVP.
- Komoditas awal: cabai rawit merah, cabai merah besar, dan bawang merah ukuran sedang, dengan pemeriksaan ketersediaan data Bali pada milestone pertama.
- Referensi harga: seri PIHPS pasar tradisional tingkat Provinsi Bali, dipisahkan berdasarkan jenis komoditas; satuan Rp/kg.
- Lokasi produk menggunakan kabupaten/kota di Bali; harga referensi tetap tingkat provinsi.
- Satu pesanan memuat satu produk dari satu petani. Kuantitas menggunakan kg dengan maksimal dua angka desimal.
- Pembayaran dan pengambilan barang disepakati langsung; aplikasi mencatat pesanan dan status pemenuhannya.
- Antarmuka berbahasa Indonesia dan mengutamakan penggunaan melalui HP.

### 1.4 Stack yang digunakan

| Bagian | Pilihan |
| --- | --- |
| Backend | Laravel 13, PHP 8.3 atau lebih baru yang kompatibel |
| Frontend | Blade dan Livewire dari starter kit resmi |
| Styling | Tailwind CSS dan komponen bawaan starter kit |
| Database | MySQL 8, Eloquent ORM, migrations, dan seeders |
| Autentikasi | Autentikasi bawaan starter kit, session, middleware, dan policies |
| Grafik | Chart.js |
| AI | Satu penyedia Gemini API melalui HTTP client Laravel |
| Build tools | Composer, npm, dan Vite |
| Pengembangan lokal | Laragon pada Windows |
| Pengujian | PHPUnit/Laravel feature tests dan pengujian manual di browser |
| Versi kode | Git; GitHub bila digunakan untuk kolaborasi |

Arsitektur berupa satu aplikasi Laravel yang menyajikan antarmuka, mengakses MySQL, dan memanggil Gemini dari backend. Perhitungan estimasi dan kalkulator dilakukan dalam PHP. Laragon merupakan lingkungan lokal; hosting publik adalah kebutuhan terpisah.

## 2. Target Pengguna dan Persona

Persona berikut merupakan hipotesis desain, bukan hasil wawancara pengguna.

| Persona | Kebutuhan dan tujuan | Hambatan | Dukungan produk |
| --- | --- | --- | --- |
| Wayan, petani hortikultura di Bali | Memahami harga cabai dan menjual hasil panen dengan mempertimbangkan biaya | Lebih sering memakai HP, waktu mengisi formulir terbatas, belum terbiasa menulis promosi | Angka utama jelas, kalkulator sederhana, deskripsi berbantuan AI, pengelolaan pesanan |
| Ayu, pembeli rumah tangga atau pemilik warung di Bali | Menemukan produk, harga, stok, dan lokasi petani yang sesuai | Sulit memastikan ketersediaan dan kesepakatan pengambilan | Filter produk, informasi petani, pesanan dengan status yang jelas |
| Admin pengelola proyek | Menjaga kualitas data dan kelayakan etalase | Data sumber dapat kosong, format impor keliru, produk dapat bermasalah | Pratinjau impor, laporan validasi, moderasi akun dan produk |

Pengunjung tanpa login dapat melihat dashboard dan produk aktif. Login diperlukan untuk memesan, mengelola produk, atau memakai fitur AI. Kontak untuk koordinasi transaksi hanya dibagikan kepada pihak terkait setelah pesanan diterima.

## 3. Metrik Keberhasilan

Target berikut merupakan kriteria penerimaan MVP, bukan hasil yang sudah tercapai.

| KPI | Target MVP | Cara ukur |
| --- | --- | --- |
| Keberhasilan tugas petani | Minimal 80% percobaan selesai tanpa bantuan penguji | Uji minimal lima calon pengguna/perwakilan petani; tugas melihat harga, menerbitkan produk, dan menerima pesanan; keberhasilan dibagi seluruh percobaan tugas |
| Keberhasilan tugas pembeli | Minimal 80% percobaan selesai tanpa bantuan penguji | Uji minimal lima calon pengguna/perwakilan pembeli; tugas mencari produk dan membuat pesanan |
| Waktu penerbitan produk | Median maksimal lima menit setelah login | Ukur dari membuka formulir sampai produk tersimpan; foto uji disediakan |
| Integritas impor | 100% baris valid tersimpan sesuai nilai sumber; 100% duplikat terdeteksi | Cocokkan fixture impor dengan database dan hasil pratinjau |
| Integritas stok | Nol stok negatif dan nol penerimaan melebihi stok | Skenario penerimaan bersamaan terhadap stok terakhir |
| Ketahanan terhadap kegagalan AI | 100% skenario gagal uji tetap menyediakan pengisian manual dan kalkulator | Simulasikan timeout, respons tidak valid, serta penolakan/kuota penyedia |
| Transparansi AI | 100% keluaran AI berlabel dan tidak disimpan sebagai konten final tanpa tindakan pengguna | Pemeriksaan ketiga tugas AI dan hasil yang disunting |
| Performa halaman inti | p95 waktu navigasi sampai konten utama siap maksimal tiga detik | Minimal 30 pengukuran per halaman setelah build produksi pada lingkungan uji yang dicatat, tanpa waktu API AI |
| Evaluasi estimasi | MAE dan pembanding harga terakhir dilaporkan pada 20 pengamatan uji terakhir | Evaluasi berurutan per komoditas menggunakan sedikitnya 60 pengamatan historis valid |

Tidak ada target akurasi estimasi yang diklaim sebelum evaluasi. Jika data 60 pengamatan tidak tersedia, tampilkan status evaluasi belum cukup data dan batasi kesimpulan. Perwakilan pengguna seperti mahasiswa diberi label sebagai penguji proksi; hasilnya tidak dianggap validasi langsung terhadap petani.

## 4. Alur Pengguna

### 4.1 Petani: dari informasi harga ke produk

1. Mendaftar sebagai petani, login, lalu melengkapi nama tampilan, kabupaten/kota, dan kontak transaksi.
2. Memilih komoditas pada dashboard; membaca harga terakhir, tanggal pengamatan, grafik, dan sumber.
3. Melihat estimasi pengamatan berikutnya bila data memenuhi syarat; meminta ringkasan tren AI bila diperlukan.
4. Mengisi total biaya satu panen, jumlah kg layak jual, dan persentase tambahan keuntungan.
5. Meninjau biaya per kg serta harga usulan yang dihitung backend dan membandingkannya dengan referensi eceran pasar.
6. Meminta penjelasan AI secara opsional, lalu menentukan sendiri harga akhir.
7. Membuat produk: memilih komoditas, memasukkan nama, foto, stok, harga, tanggal panen, lokasi, dan deskripsi.
8. Mengisi deskripsi sendiri atau meminta draf AI dari fakta dan kata kunci yang diberikan.
9. Meninjau/mengedit draf dan menekan tombol terbitkan. Penerbitan tidak terjadi otomatis setelah AI merespons.

### 4.2 Pembeli: mencari dan memesan

1. Membuka etalase, mencari produk, dan memfilter komoditas atau kabupaten/kota.
2. Melihat harga per kg, stok, foto, tanggal panen, profil petani, dan lokasi pengambilan.
3. Login sebagai pembeli, memasukkan jumlah kg dan catatan, lalu memeriksa total.
4. Mengirim pesanan; sistem menyimpan harga satuan saat pemesanan dan memberikan status `menunggu`.
5. Menunggu keputusan petani. Pesanan menunggu belum mengalokasikan stok.
6. Setelah diterima, melihat kontak transaksi untuk menyepakati pembayaran dan pengambilan di luar aplikasi.
7. Setelah barang diterima, mengonfirmasi selesai. Status ini menunjukkan penyelesaian pesanan, bukan verifikasi pembayaran oleh platform.

### 4.3 Petani: memproses pesanan

1. Membuka daftar pesanan miliknya dan melihat kuantitas, total, serta catatan.
2. Menerima atau menolak pesanan menunggu. Penerimaan memeriksa stok terbaru secara atomik.
3. Jika stok cukup, sistem mengalokasikan stok, mengubah status menjadi `diterima`, dan membuka kontak bagi kedua pihak.
4. Jika stok tidak cukup, penerimaan gagal dengan pesan jelas; petani dapat menolak pesanan atau memperbarui stok fisik terlebih dahulu.
5. Mengatur pemenuhan langsung dengan pembeli. Petani dapat membatalkan pesanan diterima dengan alasan; alokasi stok dikembalikan tepat sekali.
6. Melihat status `selesai` setelah pembeli mengonfirmasi penerimaan.

### 4.4 Admin: memperbarui data

1. Login sebagai admin; akun admin dibuat melalui seeder, bukan pendaftaran publik.
2. Operator menjalankan pratinjau Artisan untuk tiga seri PIHPS Bali dan memeriksa cakupan tanggal masing-masing.
3. Mengisi riwayat awal hingga 90 hari kalender ke MySQL. Laravel menjalankan pembaruan sembilan hari terakhir pada hari kerja pukul 15.00 WITA.
4. Memeriksa hasil sinkronisasi. Kesalahan format atau kegagalan satu permintaan membatalkan seluruh pembaruan harga. Seri tanpa pengamatan memunculkan peringatan cakupan.
5. Memeriksa grafik, tanggal pengamatan terakhir, dan riwayat koreksi saat PIHPS merevisi harga.
6. Memoderasi akun atau menonaktifkan produk bila diperlukan. Penonaktifan produk menghentikan pesanan baru; pesanan yang sudah ada tetap dapat ditangani.

## 5. Kebutuhan Fungsional

Seluruh kebutuhan di bawah berprioritas wajib untuk MVP, kecuali yang dinyatakan opsional.

### 5.1 Akun dan otorisasi

- **FR-01:** Registrasi, login, logout, dan profil melalui autentikasi bawaan Laravel. Pendaftaran hanya menawarkan peran petani atau pembeli.
- **FR-02:** Backend memeriksa peran dan kepemilikan pada setiap operasi. Petani hanya mengelola produk serta pesanan masuk miliknya; pembeli hanya mengakses pesanan miliknya.
- **FR-03:** Admin dapat menonaktifkan akun dan mencabut akses sesi akun tersebut. Pesanan aktifnya diselesaikan melalui peninjauan admin bersama pihak terkait; perubahan status harus tercatat.
- **FR-04:** Kontak privat, biaya produksi, dan target keuntungan tidak ditampilkan dalam katalog publik atau dikirim kepada AI untuk tugas yang tidak membutuhkannya.

### 5.2 Data dan dashboard harga

- **FR-05** — Ambil data JSON tabel PIHPS pasar tradisional Bali secara terjadwal atau melalui Artisan. Satu permintaan mencakup maksimal tujuh hari. Satu impor mencakup maksimal 90 hari.
- **FR-06** — Cocokkan baris rincian dengan nama sumber ketiga komoditas. Validasi tanggal dan rupiah per kg, lewati pengamatan kosong, serta simpan URL sumber, waktu impor, hasil proses, dan peringatan cakupan.
- **FR-07** — Kunci unik mencakup tanggal, komoditas, wilayah, dan jenis pasar dengan satuan tetap Rp/kg. Impor ulang data identik mempertahankan nilai. Koreksi sumber memperbarui nilai dan mencatat nilai lama, nilai baru, waktu koreksi, dan proses impor dalam satu transaksi.
- **FR-08:** Dashboard menampilkan harga pengamatan terakhir, perubahan terhadap pengamatan sebelumnya, grafik dengan rentang 14/30/90 hari kalender, sumber, dan tanggal pembaruan. Perubahan belum tersedia bila hanya ada satu pengamatan.
- **FR-09:** Seri berbagai komoditas, wilayah, atau jenis pasar tidak dicampur. Harga referensi diberi nama “harga eceran pasar tradisional Bali”, bukan “harga jual petani”.
- **FR-10:** Data yang lebih tua dari tujuh hari kalender diberi indikator data lama. Data kosong menampilkan keadaan kosong dan tidak digantikan data wilayah lain.

### 5.3 Estimasi dan kalkulator harga

- **FR-11:** Estimasi dihitung backend sebagai rata-rata tujuh pengamatan valid terakhir untuk satu komoditas. Label: “estimasi harga pasar pada pengamatan berikutnya”; tampilkan metode serta tanggal acuannya. Ini bukan prediksi tujuh hari atau estimasi harga produk individual.
- **FR-12:** Syarat estimasi adalah tujuh pengamatan dalam 30 hari kalender terakhir dan pengamatan terbaru tidak lebih tua dari tujuh hari. Jika gagal, tampilkan alasan data belum cukup atau belum mutakhir.
- **FR-13:** Kalkulator memakai `biaya per kg = total biaya satu panen / kg layak jual` dan `harga usulan = biaya per kg × (1 + tambahan keuntungan/100)`. Kg harus positif; biaya dan persentase tidak boleh negatif. Persentase merupakan tambahan terhadap biaya, bukan margin terhadap pendapatan.
- **FR-14:** Tampilkan rincian biaya, harga usulan, serta selisih terhadap referensi pasar bila tersedia. Petani dapat mengubah harga akhir tanpa sistem memaksakan diskon atau menjanjikan keuntungan.
- **FR-15:** Simpan nilai uang transaksi sebagai rupiah bulat dan kuantitas sebagai desimal dua digit. Pembulatan uang dilakukan sekali ke rupiah terdekat pada hasil akhir; jangan memakai float untuk penyimpanan uang.

### 5.4 Produk dan pesanan

- **FR-16:** Petani dapat membuat, melihat, mengedit, mengarsipkan, dan menandai produk habis. Produk memiliki nama, komoditas, deskripsi, satu foto wajib, harga/kg positif, stok tersedia, tanggal panen tidak di masa depan, kabupaten/kota, dan lokasi pengambilan.
- **FR-17:** Produk berstatus draf tidak muncul publik. Produk aktif tanpa stok menampilkan habis dan tidak dapat dipesan. Foto hanya JPEG, PNG, atau WebP, maksimal 2 MB; SVG dan berkas executable ditolak.
- **FR-18:** Pembeli dapat mencari nama produk dan memfilter komoditas/lokasi. Daftar produk dan pesanan memakai pagination.
- **FR-19:** Pesanan menyimpan produk, petani, pembeli, kuantitas, harga satuan saat pemesanan, total, catatan, dan status. Harga snapshot tetap berlaku untuk pesanan tersebut meskipun produk berubah; petani dapat menolak sebelum menerima.
- **FR-20:** Transisi normal adalah `menunggu → diterima → selesai`, `menunggu → ditolak`, `menunggu → dibatalkan`, atau `diterima → dibatalkan`. Pembeli dapat membatalkan saat menunggu; petani dapat membatalkan saat diterima dengan alasan; pembeli mengonfirmasi selesai. Tidak ada pembatalan atau penyelesaian otomatis.
- **FR-21:** Gunakan transaksi database dan penguncian baris saat menerima, mengubah stok, atau membatalkan pesanan diterima. Penerimaan berulang tidak boleh mengurangi stok lagi; pembatalan berulang tidak boleh mengembalikan stok lagi. Semua perubahan status dicatat dengan pelaku dan waktu.
- **FR-22:** Stok yang diedit petani berarti stok tersedia di luar alokasi pesanan diterima. Produk yang memiliki riwayat pesanan diarsipkan, bukan dihapus permanen.

### 5.5 Asisten AI

- **FR-23:** Gunakan satu endpoint internal, `POST /api/ai/assist`, dengan tugas `description`, `trend_summary`, atau `price_advice`. Endpoint memakai autentikasi sesi, proteksi CSRF, validasi, dan batas sepuluh permintaan per menit per akun. Satu layanan backend memilih prompt sesuai tugas.
- **FR-24:** Untuk deskripsi, kirim fakta produk dan kata kunci. AI tidak boleh menambahkan klaim organik, bebas pestisida, sertifikasi, kualitas, atau asal yang tidak diberikan.
- **FR-25:** Untuk tren, backend mengambil data dalam 14 hari kalender terakhir dan menghitung perubahan, rata-rata, minimum, serta maksimum. AI menerima tanggal dan jumlah pengamatan sebenarnya. Minimal dua pengamatan diperlukan; jika kurang, tampilkan statistik tersedia dan informasi data belum cukup.
- **FR-26:** Untuk saran harga, kirim biaya per kg, tambahan keuntungan, harga usulan hasil kalkulator, dan referensi pasar beserta tanggal bila tersedia. AI hanya menjelaskan angka dan pilihan tersebut; jangan menerima angka harga baru hasil karangan model sebagai harga usulan.
- **FR-27:** AI tidak mengarang penyebab tren atau menjanjikan harga masa depan. Semua angka tampilan utama berasal dari backend. Tolak keluaran yang kosong, formatnya tidak sesuai, atau memuat angka yang bertentangan dengan data backend; arahkan ke hasil manual/statistik.
- **FR-28:** Tampilkan label “dibuat oleh AI”. Deskripsi, ringkasan, dan penjelasan berupa draf yang dapat diedit. Deskripsi disimpan ke produk hanya setelah petani menekan simpan/terbitkan; ringkasan dan penjelasan dapat disimpan sebagai catatan privat pengguna, tanpa mengubah dashboard global. Simpan penanda asal AI meskipun teks disunting.
- **FR-29:** Batasi waktu tunggu backend ke penyedia maksimal 20 detik tanpa retry otomatis. Saat gagal, tampilkan pesan singkat, pertahankan isian, dan aktifkan pengisian manual. Kalkulator serta statistik tetap tersedia selama backend dapat diakses.
- **FR-30:** Saat internet putus, tampilkan status koneksi dan kegagalan penyimpanan dengan jelas. Pertahankan isian di halaman yang sedang terbuka; jangan mengklaim data tersimpan di server. Tidak ada jaminan isian bertahan setelah tab ditutup atau dimuat ulang.

### 5.6 Model data minimum dan antarmuka

Entitas minimum: pengguna/profil, komoditas, harga pasar, batch impor, riwayat koreksi harga, produk, pesanan, riwayat status pesanan, dan catatan AI privat. Model pesanan tetap satu produk; tabel keranjang dan pembayaran belum dibutuhkan.

Backend mengatur akses dashboard harga, katalog, CRUD produk, impor admin, transisi pesanan, kalkulator/estimasi, dan satu endpoint AI. Respons AI minimum memuat jenis tugas, teks draf, penanda AI, serta referensi periode/sumber bila relevan. Error memiliki pesan aman yang dapat ditampilkan pengguna; detail penyedia tidak diteruskan mentah.

## 6. Kebutuhan Non-Fungsional

### 6.1 Performa dan keandalan

- Target performa mengikuti KPI bagian 3, diuji dengan 1.000 produk, 10.000 baris harga, dan 20 sesi pengguna aktif pada lingkungan uji yang spesifikasinya dicatat.
- Gunakan indeks pada kunci seri harga, status/pemilik pesanan, serta filter produk. Hindari query berulang per item; muat grafik sesuai rentang yang diminta.
- Panggilan AI dijalankan hanya atas tindakan pengguna. Tampilkan loading, cegah klik ganda, dan sediakan pesan sukses/gagal yang jelas.
- Catat kegagalan impor, perubahan stok/status, serta kegagalan dan durasi AI. Log tidak menyimpan API key, kata sandi, biaya produksi, kontak pribadi, atau prompt lengkap.
- Sediakan prosedur ekspor database dan salinan foto sebelum demo/migrasi serta uji pemulihannya. Snapshot data lokal dipakai untuk demo jika sumber PIHPS tidak dapat diakses, dengan tanggal asli tetap terlihat.

### 6.2 Keamanan dan privasi

- Gunakan hashing kata sandi bawaan Laravel, proteksi CSRF, escaping keluaran HTML, query ORM berparameter, dan pembatasan login.
- Validasi seluruh input di server; jangan percaya peran, harga hasil hitung, total pesanan, atau kepemilikan yang dikirim browser.
- Terapkan policies pada objek, bukan hanya menyembunyikan tombol. Pengujian harus mencakup percobaan mengakses ID milik pengguna lain.
- Simpan kredensial database dan Gemini pada `.env`; `.env` tidak masuk Git. Browser tidak pernah menerima API key.
- Validasi MIME dan isi gambar; gunakan nama penyimpanan yang dibuat server. Batasi input AI, perlakukan teks pengguna sebagai data, dan render respons sebagai teks aman.
- Untuk hosting publik: HTTPS wajib, debug nonaktif, cookie sesi aman, dan document root mengarah ke direktori `public` Laravel.

### 6.3 Kompatibilitas, aksesibilitas, dan kegunaan

- Tata letak dapat dipakai pada lebar 360 px hingga desktop 1440 px. Tabel lebar memakai scroll di dalam komponen tanpa membuat seluruh halaman melebar.
- Uji Chrome Android, Chrome/Edge Windows, dan Safari iOS versi stabil saat pengujian. Jika perangkat iOS tidak tersedia, catat bahwa pengujian emulasi tidak menggantikan validasi perangkat nyata.
- Form memiliki label, error dekat kolom terkait, fokus keyboard terlihat, tombol berukuran sentuh memadai, dan kontras teks yang terbaca.
- Status naik/turun tidak bergantung pada warna; grafik disertai ringkasan angka. Gunakan format rupiah Indonesia, tanggal jelas, dan zona waktu tampilan Asia/Makassar.
- Gunakan migration, seeder, service terpisah untuk harga/AI/pesanan, `.env.example`, dan petunjuk setup supaya proyek dapat dijalankan ulang di komputer penguji.

## 7. Out of Scope

- Payment gateway, escrow, rekening bersama, verifikasi transfer, dan pencairan dana.
- Pengiriman otomatis, integrasi kurir, perhitungan ongkir, serta pelacakan kendaraan.
- Keranjang lintas produk/petani, lelang, negosiasi harga dalam aplikasi, dan pemesanan panen masa depan.
- Chat internal, notifikasi WhatsApp otomatis, email transaksional wajib, dan push notification.
- Rating, ulasan, program loyalitas, iklan, dan komisi platform.
- Verifikasi identitas resmi petani, jaminan kualitas produk, dan penyelesaian sengketa komersial.
- Unggah CSV lewat panel web dan integrasi penyedia harga tambahan.
- Prediksi musiman, prediksi jangka panjang, model machine learning kompleks, serta AI diagnosis tanaman.
- Multiwilayah, multibahasa, banyak peran dalam satu akun, dan aplikasi Android/iOS native.
- Mode offline penuh, sinkronisasi otomatis setelah offline, dan autosave lintas perangkat.

## 8. Asumsi, Risiko, dan Ketergantungan

### 8.1 Asumsi

- Nama produk yang dipakai adalah HargaTani, mengikuti permintaan terbaru dan nama workspace; “Harga Panen” merupakan nama pada pembahasan awal.
- Durasi enam minggu merupakan baseline perencanaan. Ukuran tim, jadwal kuliah, dan tenggat aktual belum dikonfirmasi; pembagian tugas mengikuti kapasitas pelaksana tanpa menambah scope.
- Tersedia akses legal terhadap laporan PIHPS untuk keperluan proyek akademik; penggunaan ulang dan atribusi diperiksa sebelum publikasi.
- Komputer Laragon aktif, pengguna Windows login, dan MySQL berjalan saat jadwal pembaruan. Operator dapat menjalankan ulang sinkronisasi untuk jadwal yang terlewat.
- Pengguna memiliki koneksi internet untuk login, penyimpanan, pemesanan, dan AI. Pembayaran serta pengambilan dapat dikoordinasikan langsung.
- Biaya per panen dan jumlah kg layak jual diisi jujur oleh petani; aplikasi tidak memverifikasi pembukuan tersebut.

### 8.2 Risiko dan mitigasi

| Risiko | Dampak | Mitigasi |
| --- | --- | --- |
| Endpoint tabel PIHPS berubah atau gagal diakses | Sinkronisasi terhenti | Validasi respons, catat kegagalan, pertahankan data terakhir beserta tanggal, lalu periksa kembali format sumber |
| Riwayat salah satu komoditas Bali tidak tersedia | Dashboard/evaluasi tidak lengkap | Periksa data di minggu pertama; rilis hanya komoditas yang memiliki seri valid dan catat pengurangan cakupan; jangan membuat data sintetis seolah data resmi |
| Harga eceran disamakan dengan harga petani | Saran dianggap harga wajib atau jaminan keuntungan | Label eceran, tanggal dan sumber terlihat; harga usulan berasal dari biaya; keputusan akhir tetap pada petani |
| Data jarang atau terlambat | Estimasi tidak representatif | Tampilkan data lama/kosong, syarat estimasi, jumlah pengamatan, dan periode sebenarnya |
| Estimasi kalah dari tebakan harga terakhir | Manfaat prediksi rendah | Laporkan MAE keduanya, sebut metode sebagai estimasi eksperimental, dan hindari klaim akurasi |
| AI mengarang angka, fakta, atau penyebab | Konten menyesatkan | Backend memiliki semua angka utama, prompt terbatas, pemeriksaan keluaran, label dan tinjauan pengguna |
| Kuota AI habis, internet putus, atau penyedia gagal | Fitur teks AI berhenti | Timeout, rate limit, pesan jelas, formulir manual, dan demo skenario gagal |
| Dua pesanan mengambil stok terakhir | Overselling | Transaksi database, penguncian baris, dan pengujian konkurensi |
| Pembeli tidak melanjutkan setelah pesanan diterima | Stok tertahan | Petani membatalkan dengan alasan setelah koordinasi; stok dikembalikan tepat sekali |
| Data kontak atau biaya bocor | Kerugian privasi | Otorisasi objek, kontak terbatas pihak transaksi, biaya privat, dan log tanpa data sensitif |
| Fitur bertambah menjelang UAS | Alur inti tidak selesai | Bekukan scope MVP; dahulukan alur tanpa AI, baru integrasi AI dan penyempurnaan |

### 8.3 Dependencies

| Ketergantungan | Kebutuhan dan tindakan |
| --- | --- |
| PIHPS Nasional Bank Indonesia | Laporan historis Bali, identitas komoditas, metodologi, atribusi, dan pemeriksaan ketentuan penggunaan |
| Gemini API | Akun/API key, model yang tersedia, akses jaringan, kuota, dan anggaran yang diverifikasi saat setup; tidak mengasumsikan layanan selalu gratis |
| Laragon dan runtime | PHP kompatibel, ekstensi Laravel/PDO MySQL, MySQL aktif, dan web server lokal |
| Composer dan npm | Akses unduh dependensi saat instalasi; commit lockfile agar versi dapat direproduksi |
| Penguji pengguna | Minimal lima petani/perwakilan petani dan lima pembeli/perwakilan pembeli, dengan status proksi dicatat |
| Hosting bila diperlukan | PHP/MySQL kompatibel, HTTPS, penyimpanan persisten, serta konfigurasi lingkungan; diputuskan sesuai kebutuhan demo |

Referensi resmi:

- [FAQ PIHPS: metodologi, harga eceran, hari kerja, dan kemungkinan data tidak tersedia](https://www.bi.go.id/hargapangan/Informasi/FAQ).
- [Tabel harga PIHPS pasar tradisional berdasarkan komoditas](https://www.bi.go.id/hargapangan/TabelHarga/PasarTradisionalKomoditas).
- [Persyaratan deployment Laravel 13](https://laravel.com/framework/docs/13.x/deployment).
- [Starter kit Laravel dan Livewire](https://laravel.com/framework/docs/13.x/starter-kits).
- [Referensi Gemini API](https://ai.google.dev/api/generate-content).
- [Grafik garis Chart.js](https://www.chartjs.org/docs/latest/charts/line.html).

## 9. Langkah Implementasi

1. **Validasi sumber dan bekukan scope.** Periksa tiga seri komoditas Bali, unduh sampel dan riwayat, pastikan penamaan/satuan konsisten, serta konfirmasi ketentuan penggunaan. Catat komoditas yang benar-benar dapat didukung dan batas dataset evaluasi.
2. **Siapkan fondasi.** Buat proyek Laravel dengan starter kit Livewire, konfigurasi MySQL di Laragon, inisialisasi Git, `.env.example`, migrations, seeders peran/admin, serta autentikasi dan policies. Catat versi runtime dan lockfile.
3. **Rancang database dan antarmuka.** Tetapkan relasi minimum bagian 5.6, kunci unik seri harga, representasi uang/kuantitas, transisi pesanan, dan wireframe responsif dashboard, etalase, formulir produk, serta panel admin.
4. **Bangun jalur data harga.** Implementasikan sinkronisasi PIHPS terjadwal, pratinjau Artisan, validasi impor, penanganan duplikat, koreksi dengan audit, dashboard, grafik, status data lama/kosong, kalkulator, dan estimasi backend.
5. **Bangun penjualan tanpa ketergantungan AI.** Implementasikan CRUD produk/foto, katalog/filter, snapshot harga pesanan, otorisasi, alokasi stok atomik, pembatalan, penyelesaian, dan riwayat status.
6. **Tambahkan integrasi AI.** Bangun satu service dan endpoint tiga tugas, sumber konteks dari backend, prompt terpisah, label, penyuntingan draf, catatan privat, timeout/rate limit, serta fallback manual. Gunakan mock penyedia saat menguji kegagalan.
7. **Lakukan verifikasi.** Jalankan unit test kalkulator/estimasi, feature test akses dan impor, uji stok bersamaan dengan MySQL, uji kegagalan AI, pemeriksaan responsif/keamanan, pengukuran performa, dan evaluasi MAE tanpa memakai data masa depan.
8. **Uji pengguna dan siapkan serah terima.** Jalankan skenario KPI, perbaiki hambatan utama, siapkan dataset demo beserta sumber/tanggal, backup dan uji restore, README setup, akun uji, laporan evaluasi, serta naskah demo.

### Skenario pengujian penerimaan minimum

- Impor berisi baris valid, kosong, tanggal salah, duplikat identik, konflik nilai, wilayah salah, dan satuan salah.
- Harga tanpa data, hanya satu pengamatan, kurang dari tujuh pengamatan, akhir pekan, dan data lama.
- Kalkulator dengan kg nol/negatif, biaya negatif, tambahan keuntungan nol, serta pembulatan rupiah.
- Registrasi mencoba memilih admin; pengguna mengakses atau mengubah objek milik akun lain.
- Pemesanan melebihi stok, perubahan harga setelah pemesanan, dua penerimaan bersamaan, serta pengiriman ulang aksi terima/batal/selesai.
- Deskripsi AI memuat klaim yang tidak diberikan, ringkasan memuat angka bertentangan, respons kosong/rusak, timeout, kuota habis, dan penyuntingan hasil AI.
- Koneksi terputus saat AI dipanggil atau pesanan disimpan; UI tidak menampilkan sukses palsu.
- Foto dengan ukuran/format terlarang, input HTML/script, navigasi keyboard, dan penggunaan pada layar 360 px.

## 10. Milestones

Minggu dihitung relatif terhadap hari pertama implementasi. Setiap milestone selesai hanya jika bukti penerimaannya tersedia.

| Milestone | Waktu | Hasil utama | Kriteria selesai |
| --- | --- | --- | --- |
| M1 — Validasi dan fondasi | Minggu 1 | Data sampel/riwayat, scope final komoditas, wireframe, proyek Laravel/MySQL, login dan peran | Aplikasi berjalan lokal; akun tiap peran tersedia; akses lintas peran ditolak; ketersediaan dan sumber data tercatat |
| M2 — Informasi harga | Minggu 2 | Impor, grafik, kalkulator, estimasi, koreksi data | Impor tervalidasi; angka cocok fixture; kasus data kosong/lama berjalan; hasil kalkulator dan estimasi diuji |
| M3 — Etalase | Minggu 3 | Profil petani, produk/foto, katalog dan filter responsif | Petani dapat menerbitkan/mengarsipkan produk; pembeli menemukan produk; kepemilikan dan validasi foto teruji |
| M4 — Pesanan | Minggu 4 | Pemesanan, snapshot harga, penerimaan, pembatalan, penyelesaian | Alur pembeli–petani selesai; stok tidak negatif; penerimaan bersamaan dan aksi berulang teruji |
| M5 — Asisten AI | Minggu 5 | Tiga tugas AI, label, editor draf, catatan privat, fallback | Ketiga tugas dapat didemokan; AI tidak menerbitkan/mengubah harga otomatis; skenario kegagalan tetap memungkinkan alur manual |
| M6 — Validasi dan demo | Minggu 6 | Uji pengguna, evaluasi MAE, performa, dokumentasi, backup | Hasil KPI dan keterbatasan dilaporkan; tidak ada bug kritis terbuka; setup dapat diulang dan backup berhasil dipulihkan |

### Definition of Done rilis MVP

- Seluruh kebutuhan wajib berjalan untuk komoditas yang lolos validasi sumber, dengan pengurangan cakupan tercatat bila diperlukan.
- Alur harga → kalkulator → produk → pesanan dapat didemokan lengkap menggunakan data yang sumber dan tanggalnya jelas.
- Tes kritis otorisasi, uang, impor, stok, dan kegagalan AI lulus. Bug kritis mencakup akses tanpa hak, kebocoran rahasia, perhitungan uang salah, kehilangan data, dan stok negatif.
- Build produksi berhasil; tidak ada rahasia dalam repository; README, akun demo, laporan pengujian, serta prosedur pemulihan tersedia.
- Metrik yang belum mencapai target dan keterbatasan estimasi dicatat secara terbuka sebagai hasil evaluasi, bukan disembunyikan atau diganti klaim keberhasilan.
