# PRD – Saya Akan Kerja

*Aplikasi web tracking lowongan kerja · Versi 1.3 (MVP) · 26 Oktober 2026*

**Catatan revisi v1.3:** verifikasi email dihapus dari alur register. Setelah mendaftar, pengguna langsung login dan masuk ke Dashboard. Verifikasi email hanya terjadi di alur **Lupa Password** (link reset yang dikirim ke email). Halaman Verifikasi Email dihapus (halaman berkurang dari 8 menjadi 7), kolom `users.email_verified_at` tidak dipakai, dan pembersihan otomatis akun belum terverifikasi dihapus.

## 1. Ringkasan Proyek

| Item | Deskripsi |
|---|---|
| **Nama** | Saya Akan Kerja |
| **Tujuan** | Membantu pencari kerja mencatat, memantau, dan mengelola seluruh lamaran kerja dalam satu tempat yang rapi, mulai dari loker ditemukan, dilamar, sampai hasil akhir, lengkap dengan jadwal, kontak recruiter, dan langkah selanjutnya. |
| **Latar belakang** | Pencari kerja biasanya melamar ke banyak perusahaan sekaligus dan mencatatnya di spreadsheet. Spreadsheet mudah berantakan: deadline terlewat, lupa follow-up, lupa dokumen apa yang sudah dikirim, dan tidak ada gambaran statistik progres lamaran. Aplikasi ini mempertahankan kenyamanan input ala spreadsheet atau tabel Notion, tetapi ditambah akun pribadi, validasi data, pengingat visual (deadline dan jadwal), dan dashboard analisa. |
| **Prinsip desain** | Satu halaman kerja. Semua input dan pengelolaan data loker dilakukan langsung di tabel (edit di sel, tambah lewat baris +, tambah opsi dari dalam dropdown). Tidak ada form, drawer, atau halaman tambahan. |
| **Target pengguna** | Fresh graduate dan profesional yang sedang aktif melamar kerja. Multi-user skala kecil: dipakai sendiri dan beberapa teman. Pendaftaran terbuka untuk siapa saja (tanpa undangan), setiap akun punya data terpisah. |
| **Platform** | Web responsive (desktop first, tetap bisa dipakai di mobile). |
| **Target pengerjaan** | MVP sekitar 3 sampai 4 hari kerja (sudah termasuk tabel edit langsung, import spreadsheet, dan opsi dropdown dinamis). |

## 2. Ruang Lingkup

**Masuk MVP (in scope)**

- Autentikasi: register (hanya email dan password, tanpa verifikasi email), login, logout, lupa dan reset password via SMTP. Verifikasi email hanya terjadi lewat link reset password.
- Multi-user: setiap pengguna hanya melihat dan mengubah data miliknya sendiri.
- Daftar loker dalam bentuk tabel yang bisa diedit langsung di sel, dengan baris + di bagian bawah untuk menambah data (cari, filter, urutkan, hapus baris).
- Opsi dropdown dinamis: setiap dropdown punya pilihan + di bagian bawah untuk menambah opsi baru, per akun.
- Import data awal dari spreadsheet (.xlsx atau .csv) yang sudah ada, berupa dialog di halaman List Loker.
- Halaman dashboard (kerangka dan layout saja, isi statistik menyusul).
- Halaman profil (lihat data akun, edit username dan foto).

**Di luar MVP (kandidat versi berikutnya)**

- Form tambah/edit loker dan panel detail loker (drawer).
- Halaman atau fitur kelola opsi: ubah nama, urutkan, arsipkan, dan hapus opsi dropdown.
- Statistik dashboard yang terisi (funnel status, lamaran per minggu, dsb).
- Export data ke Excel atau CSV.
- Notifikasi email pengingat deadline dan jadwal.
- Login sosial (Google), ubah password dari halaman profil, ubah email.
- Hapus akun.
- Dark mode, multi bahasa, upload dokumen CV.

## 3. Tech Stack

### 3.1 Backend

| Komponen | Pilihan | Alasan |
|---|---|---|
| Bahasa | PHP 8.3 (atau 8.4) | Sesuai permintaan, didukung penuh Laravel terbaru. |
| Framework | Laravel (versi stabil terbaru, 12.x ke atas) | Auth, validasi, mail, storage, ORM sudah bawaan. |
| Auth scaffolding | Laravel Breeze (varian Livewire) atau starter kit Livewire bawaan Laravel | Sudah mencakup login, register, lupa dan reset password. Fitur verifikasi email bawaannya tidak diaktifkan. Hemat waktu paling besar. |
| Verifikasi email (khusus lupa password) | Password broker bawaan Laravel (`password_reset_tokens`), tanpa interface `MustVerifyEmail` dan tanpa middleware `verified` | Kepemilikan email dibuktikan lewat link reset berisi token sekali pakai yang berlaku 60 menit. Register tidak memerlukan verifikasi. |
| Email (SMTP) | Dev: Mailpit (sudah ada di Laragon) atau Mailtrap. Produksi (wajib karena fitur lupa password bergantung pada email yang terkirim): Brevo atau Resend; SMTP Gmail dengan App Password hanya untuk uji coba | Mailpit menangkap email lokal tanpa mengirim sungguhan. |
| ORM | Eloquent | Bawaan Laravel. |
| Endpoint data tabel | Route JSON di dalam aplikasi (bukan halaman): ambil daftar loker, tambah baris, ubah satu sel, hapus baris, tambah opsi | Tabel edit langsung menyimpan per sel ke server. Dilindungi session login, policy, dan validasi Laravel. |
| Penyimpanan foto | Laravel Filesystem (disk `public`) + `storage:link` | Sederhana, bisa pindah ke S3/R2 nanti. |
| Olah gambar | Intervention Image (opsional) | Resize dan crop foto profil ke 400×400. |
| Import spreadsheet | Laravel Excel (maatwebsite/excel) | Membaca .xlsx dan .csv, validasi per baris, bisa diproses lewat queue bila datanya besar. |
| Queue | Driver database (`php artisan queue:work`) | Import besar dan pengiriman email diproses di latar belakang agar halaman tidak menggantung. |
| Scheduler | Laravel Scheduler (cron) | Menjalankan backup terjadwal. |
| Anti-spam registrasi | Cloudflare Turnstile (gratis) | Pendaftaran terbuka untuk umum dan tanpa verifikasi email, jadi Turnstile dan throttle menjadi penjaga utama dari bot. |
| Testing | Pest | Ringan dan enak dibaca, cukup untuk feature test auth, endpoint tabel, dan import. |

### 3.2 Frontend

| Komponen | Pilihan | Alasan |
|---|---|---|
| Templating | Blade | Native Laravel, tanpa build terpisah. |
| Halaman non-tabel | Livewire 3 + Alpine.js | Auth, profil, dan dashboard dibuat cepat tanpa banyak JavaScript. |
| **Tabel loker (inti aplikasi)** | **Tabulator.js 6 (MIT)** | Sudah menyediakan edit sel langsung, kolom beku (sticky), grup header kolom, tampilkan/sembunyikan kolom, navigasi keyboard ala spreadsheet, dan editor tanggal, angka, serta centang. Membangun semua itu dari nol di Livewire memakan waktu jauh lebih lama untuk 27 kolom. |
| Editor dropdown | Editor kustom Tabulator: daftar opsi + kolom cari + item + di bagian bawah | Dibuat sendiri karena perilaku + Tambah tidak ada bawaannya. |
| CSS | Tailwind CSS 4 (tema Tabulator ditimpa agar monokrom) | Standar industri saat ini, cocok untuk desain monokrom minimalis. |
| Build tool | Vite | Bawaan Laravel. |
| Chart (dashboard) | Chart.js atau ApexCharts | Ringan, mudah dibuat bar, donut, dan radar seperti di referensi. |
| Ikon | Lucide atau Heroicons | Gaya garis tipis, cocok dengan referensi. |
| Alternatif tabel | Tabel Livewire buatan sendiri dengan sel Alpine | Lebih menyatu dengan Laravel, tetapi waktu pengerjaan tabel bisa dua kali lipat. |

### 3.3 UI/UX

| Komponen | Pilihan |
|---|---|
| Tool desain | Figma (wireframe dan high fidelity) |
| Gaya visual (tren saat ini) | Monokrom minimalis, kartu rounded besar, bento grid, whitespace lega, tipografi tegas |
| Font | Inter, Plus Jakarta Sans, atau DM Sans (pilih satu, Google Fonts) |
| Pola layout | Sidebar kiri + topbar + konten dalam kartu, sesuai dua gambar referensi |
| Pola tabel | Gaya list Notion: baris tipis, garis pemisah halus, sel terfokus diberi border hitam, baris + abu di bawah |
| Komponen kunci | KPI card (satu kartu aktif berwarna hitam), tabel dengan badge status, chart, popover dropdown |

### 3.4 Database dan Infrastruktur

| Komponen | Pilihan |
|---|---|
| Database | MySQL 8 (via Laragon) |
| Environment lokal | Laragon (PHP, MySQL, Mailpit, Composer, Node) |
| Version control | Git + GitHub |
| Package manager | Composer dan npm |
| Deployment (nanti) | VPS kecil + Nginx, atau shared hosting yang mendukung Laravel |
| Backup | mysqldump terjadwal harian, disimpan di luar server aplikasi |

## 4. Panduan Desain

Referensi: dua dashboard monokrom (sidebar kiri bergaya pill, KPI card, chart, dan tabel).

**Palet warna**

| Peran | Warna | Hex (usulan) |
|---|---|---|
| Hitam utama (tombol, menu aktif, kartu aktif, sel terfokus) | Hitam | `#111111` |
| Teks utama | Hitam lembut | `#1A1A1A` |
| Teks sekunder | Abu tua | `#6B6B6B` |
| Aksen chart / badge sekunder | Abu sedang | `#8A8A8A` |
| Border dan divider | Abu muda | `#E5E5E5` |
| Background halaman | Putih keabuan | `#F7F7F7` |
| Permukaan kartu | Putih | `#FFFFFF` |

**Prinsip**

- Hanya hitam, putih, dan abu. Perbedaan status dibedakan lewat isi (solid hitam, abu, outline) dan ikon, bukan warna.
- Radius kartu 16 sampai 24 px, bayangan sangat halus.
- Menu aktif di sidebar berbentuk pill hitam dengan teks putih.
- Badge status: solid hitam untuk status positif atau final (Diterima, Offering), abu untuk proses berjalan, outline untuk status netral atau tidak aktif. Karena status bisa ditambah pengguna, gaya badge mengikuti **kategori status** (lihat 6.7).
- Error validasi di sel ditandai border hitam putus-putus dan ikon peringatan dengan tooltip pesan, tanpa warna merah.
- Deadline kurang dari 3 hari ditandai teks tebal dan ikon jam.

## 5. Daftar Halaman

Aplikasi hanya terdiri dari 7 halaman. Pekerjaan utama berlangsung di satu halaman, yaitu List Loker.

| No | Halaman | Route | Akses |
|---|---|---|---|
| 1 | Register | `/register` | Tamu |
| 2 | Login | `/login` | Tamu |
| 3 | Lupa Password | `/forgot-password` | Tamu |
| 4 | Reset Password | `/reset-password/{token}` | Tamu (via link email) |
| 5 | Dashboard | `/dashboard` | Login |
| 6 | List Loker | `/loker` | Login |
| 7 | Profil | `/profil` | Login |

**Bukan halaman:** Dialog Import berada di dalam halaman List Loker (tanpa route sendiri). Endpoint JSON untuk tabel hanya dipakai oleh tabel dan tidak bisa dibuka sebagai halaman.

## 6. Detail Halaman

### 6.1 Register

- **Tujuan:** membuat akun baru. Pendaftaran terbuka untuk umum, tanpa undangan.
- **Field:** email, password, konfirmasi password. Username tidak diisi saat register. Sistem membuatnya otomatis dari bagian depan email (sebelum tanda @), ditambah angka bila sudah dipakai, dan pengguna bisa mengubahnya nanti di Profil.
- **Validasi:** email format valid dan unik; password minimal 8 karakter, disarankan kombinasi huruf dan angka; konfirmasi harus sama.
- **Anti penyalahgunaan:** Cloudflare Turnstile pada form dan throttle pendaftaran per IP (misalnya 5 per jam). Tidak ada verifikasi email pada register.
- **Alur:** submit → akun dibuat (opsi dropdown bawaan disalin ke akun) → pengguna langsung login → diarahkan ke Dashboard.
- **Elemen lain:** tautan ke Login, tampilan password bisa di-show/hide, dan teks bantuan di bawah field email: 'Gunakan email aktif milikmu. Email ini dipakai untuk reset password.'

### 6.2 Login

- **Field:** email, password, checkbox Ingat Saya.
- **Aturan:** pembatasan percobaan (maksimal 5 kali per menit per kombinasi email dan IP); pesan error generik (tidak membocorkan apakah email terdaftar).
- **Elemen lain:** tautan Lupa Password dan Daftar.
- **Setelah berhasil:** ke Dashboard.

### 6.3 Lupa Password

- **Tujuan:** memulihkan akses akun. Ini satu-satunya alur yang memverifikasi email: kepemilikan email dibuktikan lewat link reset yang dikirim ke alamat tersebut.
- **Field:** email.
- **Alur:** submit → jika email terdaftar, kirim link reset via SMTP → tampilkan pesan netral: 'Jika email terdaftar, kami telah mengirim link reset password'.
- **Aturan:** link berlaku 60 menit; pengiriman dibatasi per email agar tidak disalahgunakan.

### 6.4 Reset Password

- **Field:** email (terisi otomatis dan read-only), password baru, konfirmasi.
- **Aturan:** password baru minimal 8 karakter; token sekali pakai.
- **Setelah berhasil:** kolom `password_changed_at` diperbarui, sesi lain di-logout, pengguna diarahkan ke Login dengan pesan sukses.

### 6.5 Layout Aplikasi (berlaku untuk halaman 6.6 sampai 6.8)

- **Sidebar kiri:** logo 'Saya Akan Kerja', menu Dashboard, List Loker, Profil, dan tombol Logout di bagian bawah. Menu aktif berbentuk pill hitam.
- **Topbar:** judul halaman, avatar dan username pengguna dengan dropdown (Profil, Logout).
- **Mobile:** sidebar berubah menjadi menu hamburger.

### 6.6 Dashboard

- **Tujuan:** pusat analisa statistik lamaran. Pada MVP hanya kerangka halaman, data dikosongkan.
- **Isi MVP:** judul 'Overview', grid kartu KPI (placeholder), area chart (placeholder), dan tabel ringkas (placeholder). Setiap blok menampilkan empty state: 'Statistik akan muncul di sini'.
- **Struktur grid (mengikuti referensi):** baris atas 5 kartu KPI, baris tengah satu chart besar (bar atau garis) + satu chart kecil (donut atau radar), baris bawah tabel aktivitas terbaru.
- **Rencana isi versi berikutnya:** total loker, jumlah sudah melamar, sedang proses, interview, diterima, ditolak; chart lamaran per minggu; funnel status (berdasarkan kategori status); distribusi sumber loker dan sistem kerja; daftar deadline terdekat; jadwal next step terdekat.
- **Catatan teknis:** siapkan route dan komponen terpisah per widget agar mudah diisi nanti, serta aturan bahwa semua query statistik dibatasi `user_id` pengguna yang login.

### 6.7 List Loker (halaman kerja utama)

Seluruh pekerjaan dengan data loker terjadi di halaman ini, seperti di spreadsheet atau list Notion. Tidak ada form, modal tambah data, atau drawer detail.

**Toolbar**

- Kolom pencarian (judul, perusahaan, lokasi).
- Filter: Status, Prioritas, Sistem Kerja, Tipe Kontrak.
- Tombol **Atur Kolom**: tampilkan atau sembunyikan kolom. Pilihan disimpan di browser.
- Tombol **Import**: membuka dialog import (lihat bagian akhir 6.7).

**Struktur tabel**

- Semua 27 kolom rancangan ada di tabel, karena tidak ada panel detail. Kolom diberi header grup agar mudah dinavigasi: Info Loker, Lamaran, Dokumen, Kontak dan Tindak Lanjut, Catatan.
- Kolom yang tampil secara default (12 kolom): No, Judul Loker, Nama Perusahaan, Lokasi, Sistem Kerja, Gaji Minimum, Gaji Maksimum, Link Loker, Deadline, Tanggal Melamar, Status, Prioritas. Kolom lain dimunculkan lewat Atur Kolom.
- Kolom No, Judul Loker, dan Nama Perusahaan beku (sticky) saat tabel di-scroll ke samping.
- Tabel bisa di-scroll horizontal dan vertikal; header tetap terlihat saat scroll ke bawah.
- Urutan default: sesuai urutan input (baris terlama di atas, baris baru di bawah). Pengguna bisa mengurutkan lewat klik header kolom.
- Pagination 50 baris per halaman.

**Menambah baris (baris +)**

- Baris paling bawah tabel berisi tombol **+ Baris Baru** (abu, seperti di Notion).
- Klik + menambah baris kosong di akhir tabel dan fokus langsung ke sel Judul Loker.
- Nilai awal: Tanggal Ditemukan = hari ini, Status = Disimpan, Prioritas = Sedang, kolom centang = tidak dicentang.
- Baris baru baru disimpan ke database setelah Judul Loker dan Nama Perusahaan terisi. Baris yang ditinggalkan kosong dibuang otomatis, jadi tidak ada baris sampah.
- Bila pengguna sedang berada di halaman lain, tampilan pindah ke halaman terakhir sebelum baris baru muncul.

**Edit langsung di sel**

- Klik sel (atau tekan Enter) untuk mulai mengedit, Esc untuk membatalkan.
- Perubahan tersimpan otomatis saat sel selesai diedit (pindah sel atau Enter). Indikator kecil 'Menyimpan…' lalu 'Tersimpan' tampil di toolbar. Bila gagal, nilai lama dikembalikan dan muncul pesan error.
- Navigasi keyboard ala spreadsheet: Tab dan Shift+Tab ke sel kanan atau kiri, panah untuk berpindah sel, Enter untuk edit atau simpan.

| Kolom | Jenis sel | Cara edit |
|---|---|---|
| No | Nomor urut | Tidak bisa diedit |
| Tanggal Ditemukan, Deadline, Tanggal Melamar, Follow-up Terakhir | Tanggal | Klik, pilih dari date picker atau ketik |
| Jadwal Next Step | Tanggal dan jam | Klik, pilih tanggal dan jam |
| Judul Loker, Nama Perusahaan, Lokasi, Lamaran Dikirim Ke, Nama Kontak, Email/No. HP Kontak | Teks | Ketik langsung |
| Next Action, Catatan | Teks panjang | Tampil terpotong 2 baris; saat diedit sel membesar otomatis mengikuti isi |
| Gaji Minimum, Gaji Maksimum | Angka (Rp) | Ketik angka; tampil sebagai Rp 5.000.000 |
| Link Loker | URL | Tampil sebagai ikon link yang membuka tab baru; klik sel untuk mengubah URL |
| CV, Portofolio, Surat Lamaran, Sertifikat | Centang | Klik langsung untuk mencentang atau menghapus centang |
| Industri, Sistem Kerja, Tipe Kontrak, Sumber Loker, Status, Prioritas | Dropdown | Pilih dari daftar, atau tambah opsi baru (lihat di bawah) |

**Dropdown dengan opsi + (opsi dinamis)**

- Klik sel dropdown membuka popover berisi: kolom cari di atas, daftar opsi di tengah, dan item **+ Tambah opsi** di bagian paling bawah.
- Klik **+ Tambah opsi**: item berubah menjadi kolom input. Ketik nama, tekan Enter, opsi tersimpan dan langsung terpilih untuk sel tersebut.
- Bila hasil pencarian kosong, item bawah berubah menjadi **+ Tambah 'teks yang diketik'** agar bisa ditambah sekali klik.
- Khusus **Status**: setelah mengetik nama, pengguna memilih kategori status lewat pilihan kecil (Belum Melamar, Dalam Proses, Diterima, Ditolak atau Ditutup), default Dalam Proses. Kategori dipakai untuk gaya badge dan statistik dashboard.
- Berlaku untuk keenam dropdown. Opsi bersifat **per akun**: opsi buatan satu pengguna tidak muncul di akun lain.
- Aturan opsi baru: teks dirapikan (spasi di awal dan akhir dibuang), maksimal 50 karakter, tidak boleh sama dengan opsi yang sudah ada (tanpa membedakan huruf besar atau kecil).
- Nilai awal bawaan (disalin ke akun saat akun dibuat):

| Field | Nilai awal bawaan |
|---|---|
| Sistem Kerja | Onsite, Hybrid, Remote |
| Tipe Kontrak | Full-time, Part-time, Kontrak, Magang, Freelance |
| Status | Disimpan (belum melamar), Sudah Melamar, Screening CV, Tes, Interview HR, Interview User, Offering, Diterima, Ditolak, Tidak Ada Kabar, Dibatalkan |
| Prioritas | Tinggi, Sedang, Rendah |
| Industri | IT dan Teknologi, Keuangan dan Perbankan, Pendidikan, Kesehatan, Manufaktur, Retail dan FMCG, Lainnya |
| Sumber Loker | LinkedIn, Glints, JobStreet, Instagram, Referensi, Website Perusahaan, Lainnya |

**Menghapus baris**

- Saat kursor berada di sebuah baris, muncul ikon **⋯** di kolom paling kiri dengan satu aksi: Hapus.
- Hapus meminta konfirmasi kecil di tempat (bukan modal). Setelah dihapus, muncul toast dengan tombol **Batalkan** selama 5 detik. Data memakai soft delete.

**Validasi di sel**

- Wajib: Judul Loker dan Nama Perusahaan (tidak boleh dikosongkan), Tanggal Ditemukan, dan Status.
- Gaji Minimum dan Maksimum berupa angka ≥ 0; Gaji Maksimum tidak boleh lebih kecil dari Gaji Minimum (boleh kosong).
- Link Loker harus berformat URL valid.
- Tanggal Melamar tidak boleh lebih awal dari Tanggal Ditemukan.
- Sel yang tidak valid tidak disimpan, ditandai di sel, dan pesan tampil sebagai tooltip.

**Tampilan lain**

- Deadline kurang dari 3 hari ditandai teks tebal dan ikon jam; deadline yang sudah lewat tampil abu dan dicoret bila Status belum Sudah Melamar.
- Empty state: tabel kosong hanya menampilkan baris **+ Baris Baru** dan tombol Import dari Spreadsheet.
- Aturan akses: pengguna hanya bisa melihat dan mengubah loker miliknya sendiri.

**Dialog Import dari spreadsheet (di dalam halaman List Loker)**

- **Tujuan:** memasukkan data dari spreadsheet yang sudah ada sekaligus, agar pengguna tidak mengetik ulang.
- **Akses:** tombol Import di toolbar. Dialog tampil di atas halaman List Loker, bukan halaman baru.
- **Alur:**
  1. Unduh template: file .xlsx berisi header sesuai 27 kolom rancangan dan satu baris contoh.
  2. Unggah file .xlsx atau .csv (maksimal 5 MB dan 1.000 baris per file).
  3. Pratinjau dan validasi: tampil jumlah baris valid, baris bermasalah lengkap dengan nomor baris dan alasannya, serta baris yang diduga duplikat. Pengguna memilih Impor Baris Valid atau Batal.
  4. Hasil: ringkasan berhasil, dilewati, dan gagal, plus unduhan laporan error (CSV). Tabel di belakang dialog langsung menampilkan baris baru.
- **Pemetaan kolom:** kolom dicocokkan lewat nama header (tidak membedakan huruf besar atau kecil). Kolom yang tidak dikenal diabaikan, dan kolom No dari file tidak dipakai. Header spreadsheet yang sudah ada sama dengan 27 kolom rancangan, jadi tidak perlu pemetaan manual.
- **Kolom wajib:** Judul Loker dan Nama Perusahaan. Bila Tanggal Ditemukan kosong, diisi tanggal import. Bila Status kosong, diisi Disimpan.
- **Format nilai:** tanggal diterima dalam format DD/MM/YYYY atau YYYY-MM-DD; gaji berupa angka (tulisan Rp dan titik pemisah ribuan dibersihkan); kolom centang menerima ✔, ya, true, 1, atau x.
- **Nilai dropdown baru:** bila file berisi nilai yang belum ada (misalnya Status baru), nilai itu otomatis dibuat sebagai opsi baru milik pengguna dan ditampilkan di pratinjau. Status baru dari import diberi kategori Dalam Proses.
- **Duplikat:** baris dengan Judul Loker, Nama Perusahaan, dan Tanggal Ditemukan yang sama dengan data yang sudah ada ditandai kemungkinan duplikat dan dilewati secara default. Pengguna bisa memilih tetap mengimpornya.
- **Validasi:** aturan sama dengan validasi di sel.
- **Keamanan:** isi sel dibaca sebagai teks biasa, formula tidak pernah dijalankan, dan file yang diunggah dihapus dari server setelah proses selesai.
- **Teknis:** data disimpan per batch 200 baris dalam transaksi database. Di atas 200 baris, import berjalan lewat queue dan pengguna melihat status progresnya.

### 6.8 Profil

- **Tujuan:** melihat info akun dan mengubah data dasar.
- **Tampilan:**
  - Foto profil (jika belum ada, tampil avatar inisial).
  - Username (**bisa diedit**).
  - Email (**read-only**).
  - Password ditampilkan sebagai `••••••••` (**read-only**) dengan info 'Terakhir diganti: tanggal dan jam'. Nilai berasal dari `password_changed_at`; bila belum pernah diganti, gunakan tanggal pendaftaran.
- **Aksi:** Ubah Foto (upload, pratinjau, crop persegi), Hapus Foto, Simpan Perubahan.
- **Validasi:**
  - Username 3 sampai 30 karakter, hanya huruf, angka, titik, dan underscore, unik.
  - Foto berformat JPG, PNG, atau WebP, maksimal 2 MB; disimpan ke ukuran 400×400 px.
  - Foto lama dihapus dari storage saat diganti.
- **Catatan:** karena password tidak bisa diubah dari halaman ini, satu-satunya jalur ganti password adalah Lupa Password. Fitur hapus akun ditunda ke versi berikutnya.

## 7. Model Data

### 7.1 Tabel `users`

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint, PK | |
| username | varchar(30), unique | Otomatis dari email saat register |
| email | varchar, unique | |
| password | varchar | Hash bcrypt/argon (otomatis oleh Laravel) |
| password_changed_at | timestamp, nullable | Info terakhir ganti password |
| avatar_path | varchar, nullable | |
| remember_token | varchar | |
| created_at, updated_at | timestamp | |

Tabel bawaan Laravel lain yang ikut dipakai: `password_reset_tokens`, `sessions`.

Kolom `email_verified_at` bawaan Laravel tidak dipakai di MVP. Kolom itu bisa ditambahkan lagi bila nanti ada fitur ubah email atau verifikasi akun.

### 7.2 Tabel `job_applications`

Dinamai `job_applications` (bukan `jobs`) agar tidak bentrok dengan tabel queue bawaan Laravel. Baris baru dibuat di database saat Judul Loker dan Nama Perusahaan sudah terisi, lalu setiap sel disimpan sebagai perubahan satu kolom.

| Kolom (DB) | Padanan di Excel | Tipe | Catatan |
|---|---|---|---|
| id | (No) | bigint, PK | Kolom No di UI adalah nomor urut tampilan, bukan disimpan |
| user_id | – | FK ke users, index | Wajib, cascade on delete |
| found_date | B Tanggal Ditemukan | date | Default hari ini |
| title | C Judul Loker | varchar | Wajib |
| company | D Nama Perusahaan | varchar | Wajib |
| industry_id | E Industri | FK ke options, nullable | Opsi dinamis |
| location | F Lokasi | varchar, nullable | |
| work_arrangement_id | G Sistem Kerja | FK ke options, nullable | Opsi dinamis |
| contract_type_id | H Tipe Kontrak | FK ke options, nullable | Opsi dinamis |
| salary_min | I Gaji Minimum | unsigned bigint, nullable | Rupiah, tanpa desimal |
| salary_max | J Gaji Maksimum | unsigned bigint, nullable | |
| source_id | K Sumber Loker | FK ke options, nullable | Opsi dinamis |
| job_url | L Link Loker | text, nullable | |
| deadline | M Deadline | date, nullable | |
| applied_date | N Tanggal Melamar | date, nullable | |
| applied_to | O Lamaran Dikirim Ke | varchar, nullable | |
| status_id | P Status | FK ke options | Wajib, default Disimpan, opsi dinamis |
| priority_id | Q Prioritas | FK ke options, nullable | Default Sedang, opsi dinamis |
| cv_sent | R CV | boolean | Default false |
| portfolio_sent | S Portofolio | boolean | Default false |
| cover_letter_sent | T Surat Lamaran | boolean | Default false |
| certificate_sent | U Sertifikat | boolean | Default false |
| contact_name | V Nama Kontak | varchar, nullable | |
| contact_info | W Email/No. HP Kontak | varchar, nullable | |
| next_step_at | X Jadwal Next Step | datetime, nullable | |
| last_followup_date | Y Follow-up Terakhir | date, nullable | |
| next_action | Z Next Action | text, nullable | |
| notes | AA Catatan | text, nullable | |
| created_at, updated_at, deleted_at | – | timestamp | Soft delete agar hapus bisa dibatalkan |

**Index yang disarankan:** (`user_id`, `id`), (`user_id`, `status_id`), (`user_id`, `deadline`).

**Relasi:** satu `user` memiliki banyak `job_application`.

### 7.3 Tabel `options`

Menyimpan semua nilai dropdown per pengguna, sehingga satu tabel melayani Status, Prioritas, Sistem Kerja, Tipe Kontrak, Industri, dan Sumber Loker.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint, PK | |
| user_id | FK ke users, index | Pemilik opsi |
| type | varchar | status, priority, work_arrangement, contract_type, industry, source |
| label | varchar(50) | Nama yang tampil |
| category | varchar, nullable | Hanya untuk type status: belum_melamar, dalam_proses, diterima, ditolak_ditutup |
| sort_order | unsigned int | Urutan tampil di dropdown (MVP: urutan dibuat) |
| is_archived | boolean | Default false; disiapkan untuk fitur kelola opsi di versi berikutnya |
| created_at, updated_at | timestamp | |

**Unique:** (`user_id`, `type`, `label`). **Relasi:** satu `user` memiliki banyak `option`, dan kolom `*_id` pada `job_applications` merujuk ke `options.id` dengan aturan restrict on delete.

**Nilai awal:** seeder menyimpan daftar bawaan (Bagian 6.7). Saat akun baru dibuat, daftar itu disalin ke akun tersebut.

### 7.4 Endpoint data tabel (bukan halaman)

| Aksi | Method | Route | Fungsi |
|---|---|---|---|
| Ambil daftar | GET | `/loker/data` | Daftar loker milik pengguna (halaman, urut, filter, cari) beserta opsi dropdown |
| Tambah baris | POST | `/loker/data` | Membuat baris baru dengan nilai awal |
| Ubah satu sel | PATCH | `/loker/data/{id}` | Menyimpan satu kolom dengan validasi |
| Hapus baris | DELETE | `/loker/data/{id}` | Soft delete |
| Batalkan hapus | POST | `/loker/data/{id}/restore` | Memulihkan baris yang baru dihapus |
| Tambah opsi | POST | `/opsi` | Membuat opsi dropdown baru untuk pengguna |
| Import | POST | `/loker/import` | Pratinjau dan eksekusi import dari dialog |

Semua endpoint memeriksa session login dan kepemilikan data (`user_id`).

## 8. Kebutuhan Non-Fungsional

- **Multi-user:** target sekitar 5 sampai 20 pengguna dengan ribuan baris loker; satu server kecil sudah cukup. Semua query loker dan opsi dibatasi `user_id` pengguna yang login.
- **Keamanan:** hash password bawaan Laravel, proteksi CSRF (termasuk untuk endpoint tabel), throttle pada login, register, dan lupa password, Cloudflare Turnstile pada register, policy otorisasi agar pengguna tidak bisa mengakses data pengguna lain, validasi mime dan ukuran file upload (foto dan file import).
- **Import:** isi file diperlakukan sebagai teks, formula tidak dijalankan, batas 5 MB dan 1.000 baris per file.
- **Performa tabel:** pagination 50 baris, penyimpanan per sel ringan (satu kolom per request), dan permintaan simpan dikirim berurutan agar tidak saling menimpa.
- **Konflik edit:** bila akun yang sama dibuka di dua tab, nilai terakhir yang disimpan yang berlaku (last write wins).
- **Backup:** dump database harian dan penyimpanan foto ikut dicadangkan.
- **Responsif:** halaman nyaman dipakai di layar 360 px ke atas. Tabel bisa dipakai di mobile lewat scroll horizontal, tetapi edit data paling nyaman di desktop.
- **Aksesibilitas:** kontras teks memadai (hitam di atas putih), fokus keyboard terlihat, tabel bisa dioperasikan dengan keyboard.
- **Bahasa antarmuka:** Bahasa Indonesia.
- **Zona waktu:** Asia/Jakarta (`APP_TIMEZONE`).

## 9. Rencana Pengerjaan (estimasi)

| Tahap | Pekerjaan | Estimasi |
|---|---|---|
| 1 | Setup Laragon, project Laravel, database, Tailwind, Vite, SMTP (Mailpit) | 1 jam |
| 2 | Install Breeze/Livewire, sesuaikan tampilan auth (tanpa verifikasi email di register), lupa dan reset password, Turnstile | 2 sampai 2,5 jam |
| 3 | Layout aplikasi (sidebar, topbar) dan kerangka Dashboard | 1,5 jam |
| 4 | Migration, model, policy untuk `job_applications` dan `options`, seeder opsi bawaan, penyalinan opsi ke akun baru, endpoint data tabel | 2 jam |
| 5 | Tabel loker dengan Tabulator: kolom beku, header grup, Atur Kolom, cari, filter, urut, pagination, tema monokrom | 3 sampai 4 jam |
| 6 | Edit langsung di sel untuk semua jenis kolom, baris +, simpan otomatis, validasi di sel, hapus baris dan batalkan | 4 sampai 5 jam |
| 7 | Editor dropdown dengan pencarian dan item + Tambah opsi (termasuk kategori Status) | 2 sampai 3 jam |
| 8 | Dialog import (template, upload, validasi, pratinjau, laporan error, queue) | 4 sampai 5 jam |
| 9 | Halaman Profil (edit username, upload foto) | 1,5 jam |
| 10 | Polishing UI, responsive, testing dasar | 2 sampai 3 jam |
| | **Total** | **sekitar 23 sampai 29 jam kerja (3 sampai 4 hari)** |

Bagian paling berisiko adalah tahap 5 sampai 7 (tabel edit langsung). Kerjakan lebih dulu setelah auth selesai.

## 10. Keputusan dan Pertanyaan Tersisa

| Topik | Keputusan |
|---|---|
| Jumlah pengguna | Multi-user skala kecil: dipakai sendiri dan beberapa teman, data tiap akun terpisah. |
| Pendaftaran | Terbuka untuk siapa saja, tanpa undangan dan tanpa verifikasi email. Diamankan dengan Turnstile dan throttle. |
| Verifikasi email | Dihapus dari register. Hanya berlaku di alur Lupa Password (link reset berisi token, sekali pakai, berlaku 60 menit). |
| Struktur halaman | Satu halaman kerja (List Loker). Form tambah/edit dan drawer detail dihapus dari versi 1. Tidak ada halaman kelola opsi. |
| Input data loker | Langsung di tabel: edit di sel dan baris + di bagian bawah, seperti spreadsheet atau list Notion. |
| Opsi dropdown | Semua dropdown dinamis. Opsi ditambah lewat item + di bagian bawah daftar. Daftar opsi **per akun**. |
| Import data | Masuk MVP sebagai dialog di halaman List Loker. |
| Username | Tidak diisi saat register; otomatis dari email dan bisa diubah di Profil. |
| Hapus akun | Ditunda ke versi berikutnya. |

**Pertanyaan yang masih terbuka**

1. Tanpa halaman kelola opsi, opsi yang salah ketik belum bisa diubah nama atau dihapus. Apakah cukup ditunda ke versi berikutnya, atau perlu ikon kecil edit dan hapus yang muncul saat kursor di atas opsi di dalam dropdown (tetap tanpa halaman baru)?
2. Spreadsheet yang sudah ada baru dikirim headernya (cocok dengan 27 kolom rancangan). Contoh beberapa baris data masih dibutuhkan untuk memastikan format tanggal, gaji, dan centang sebelum aturan import dikunci.
3. Tanpa verifikasi email di register, ada dua risiko kecil: (a) email salah ketik membuat pengguna tidak bisa reset password, dan (b) seseorang bisa mendaftar dengan email orang lain, sehingga pemilik email asli melihat pesan 'email sudah terdaftar'. Pemilik asli masih bisa mengambil akun itu lewat Lupa Password, tetapi datanya sudah diisi orang lain. Untuk MVP skala kecil risikonya rendah dan dimitigasi dengan teks bantuan di form register. Apakah cukup begitu, atau perlu field konfirmasi email?
