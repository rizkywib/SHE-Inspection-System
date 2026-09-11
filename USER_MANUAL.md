# USER MANUAL — SHE Inspection System

Sistem Inspeksi Safety, Health, and Environment (SHE)

Versi: 1.0.0
Terakhir diperbarui: September 2026

---

## Daftar Isi

1. Pendahuluan
2. Persyaratan Sistem
3. Login dan Logout
4. Dashboard
5. Menu dan Navigasi
6. Mode Offline dan Sinkronisasi Otomatis
7. Inspeksi Fire Hydrant
8. Inspeksi Fire Extinguisher (APAR)
9. Inspeksi Fire Alarm
10. Inspeksi ES/EW (Emergency Shower / Eye Wash)
11. Checklist Umum
12. Permit Matrix
13. Safety Talk / Training
14. Inspection / Incident
15. Scanner QR Code
16. Check-in GPS dan Tanda Tangan Digital
17. Master Data (Administrator)
18. Troubleshooting dan FAQ
19. Dukungan Teknis

---

## 1. Pendahuluan

SHE Inspection System adalah aplikasi Android untuk melakukan inspeksi
keselamatan, kesehatan, dan lingkungan (Safety, Health, and Environment).
Aplikasi digunakan oleh inspector untuk:

- Melakukan inspeksi peralatan pemadam kebakaran (hydrant, APAR, fire alarm).
- Melakukan inspeksi Emergency Shower / Eye Wash (ES/EW).
- Melakukan inspeksi checklist umum berdasarkan kategori.
- Mencatat kegiatan Safety Talk / Training di lapangan.
- Mengisi form Permit Matrix (Safe Work Permit).
- Melaporkan inspection/incident dengan foto.
- Scan QR code asset untuk membuka form inspeksi secara cepat.
- Check-in GPS dan tanda tangan digital.
- Bekerja secara offline dengan sinkronisasi otomatis.

Aplikasi terhubung ke server backend melalui API. Semua data disimpan di
server dan dapat diakses kembali kapan pun.

## 2. Persyaratan Sistem

| Kebutuhan | Spesifikasi |
|---|---|
| Perangkat | Android (minimal API level 21 / Android 5.0) |
| Koneksi Internet | Dibutuhkan untuk sinkronisasi; inspeksi tetap bisa dibuat offline |
| GPS | Dibutuhkan untuk check-in lokasi dan scan QR |
| Kamera | Dibutuhkan untuk foto temuan dan scan QR |
| Aplikasi | File APK `SHE-Inspection-System.apk` |

Akun pengguna (username dan password) disediakan oleh administrator.
Login menggunakan **username**, bukan email.

## 3. Login dan Logout

### 3.1 Login

1. Buka aplikasi SHE Inspection.
2. Masukkan **Username** pada kolom Username.
3. Masukkan **Password** pada kolom Password.
4. Tekan tombol **Sign In**.
5. Jika berhasil, aplikasi menampilkan Dashboard.

Catatan:
- Jika username atau password salah, muncul pesan peringatan dan Anda tetap
  berada di halaman login.
- Jika kolom kosong, validasi form akan muncul dan permintaan tidak dikirim.

### 3.2 Restore Sesi

Saat aplikasi ditutup dan dibuka kembali, jika Anda masih login, aplikasi
langsung masuk ke Dashboard tanpa perlu login ulang.

### 3.3 Logout

1. Buka menu samping (Drawer) dengan menekan ikon menu di kiri atas.
2. Pilih menu **Logout** (berwarna merah).
3. Aplikasi kembali ke halaman login dan token dihapus.

## 4. Dashboard

Dashboard adalah halaman utama setelah login. Isinya:

- **Kartu selamat datang** — sapaan berdasarkan waktu dan nama pengguna.
- **Ringkasan statistik** — Total Data, Tindak Lanjut, dan Selesai.
- **Akses Cepat** — tombol pintasan ke modul inspeksi (Hydrant, APAR,
  Fire Alarm, ES/EW, Permit, Safety Talk, Inspection).
- **Aktivitas Terbaru** — daftar inspeksi dari seluruh modul.

### 4.1 Pencarian dan Filter

- Gunakan kolom pencarian (ikon kaca pembesar) untuk mencari berdasarkan
  nama inspector atau remark.
- Gunakan chip filter status: Semua, Baru, Selesai, Signed, Open, Close.
- Tekan ikon **Refresh** di kanan atas atau tarik layar ke bawah
  (pull-to-refresh) untuk memuat ulang data.

### 4.2 Melihat Detail Aktivitas

Tekan salah satu item pada daftar Aktivitas Terbaru untuk melihat detail
inspeksi, termasuk item inspeksi dan foto.

## 5. Menu dan Navigasi

Menu utama dapat diakses melalui tombol menu (Drawer) di kiri atas:

| Menu | Keterangan |
|---|---|
| Dashboard | Kembali ke halaman utama |
| Hydrant | Inspeksi fire hydrant |
| Fire Extinguisher | Inspeksi APAR |
| Fire Alarm | Inspeksi fire alarm |
| ES/EW | Inspeksi Emergency Shower / Eye Wash |
| Permit Matrix | Inspeksi Safe Work Permit |
| Safety Talk/Training | Kegiatan safety talk / training |
| Inspection | Daftar dan pelaporan inspection/incident |
| Master Data (admin) | Hydrant Locations, Incident Types, ES&EW Areas, Users |
| Logout | Keluar dari aplikasi |

Menu **Master Data** hanya tampil untuk pengguna dengan peran admin atau
super admin.

## 6. Mode Offline dan Sinkronisasi Otomatis

Aplikasi mendukung bekerja tanpa koneksi internet (offline).

### 6.1 Menyimpan Data Saat Offline

1. Pastikan perangkat dalam mode pesawat / tanpa koneksi.
2. Buat inspeksi (Hydrant, APAR, ES/EW, atau Inspection) lengkap dengan foto.
3. Tekan Simpan.
4. Muncul pesan **"Offline: disimpan sebagai draft"** dan Anda kembali ke
   Dashboard.

### 6.2 Menandai Draft (Badge)

Item yang tersimpan offline ditandai dengan badge oranye **"Menunggu
sinkronisasi"**. Banner Mode Offline menampilkan jumlah draft yang tertunda.

### 6.3 Sinkronisasi Otomatis

Draft akan otomatis terkirim ke server saat:

- Koneksi internet kembali (mode pesawat dimatikan).
- Aplikasi dibuka kembali (app start).
- Dashboard di-refresh.

Jika pengiriman gagal (misalnya server error), data tidak hilang — data
otomatis disimpan kembali sebagai draft.

### 6.4 Tombol Kirim pada Banner

Saat offline dan ada draft, tekan **Kirim** pada banner. Jika masih offline,
draft tetap tersimpan; setelah online, draft akan terkirim.

## 7. Inspeksi Fire Hydrant

### 7.1 Membuat Inspeksi Baru

1. Buka menu **Hydrant** dari Dashboard (Akses Cepat) atau Drawer.
2. Tekan tombol untuk membuat inspeksi baru (**New Fire Hydrant Inspection**).
3. Pilih **Location**.
4. Pilih cara pengisian data:
   - **Scan QR**: scan QR hydrant agar detail terisi otomatis.
   - **Manual**: isi form lengkap (tanggal, lokasi, dan item hydrant).
5. Isi seluruh field yang wajib.
6. Tambahkan foto jika diperlukan.
7. Tekan **Simpan**.

Jika berhasil, muncul pesan sukses dan data tersimpan.

### 7.2 Melihat Detail

- Pilih inspeksi dari daftar untuk melihat detail, item, dan foto.
- Khusus pembuat data atau admin/super admin, tersedia tombol **Edit**.

### 7.3 Hak Akses Edit/Hapus

Hanya pembuat inspeksi (creator) atau pengguna dengan peran admin/super admin
yang dapat mengedit atau menghapus data. Pengguna lain hanya dapat melihat.

## 8. Inspeksi Fire Extinguisher (APAR)

1. Buka menu **Fire Extinguisher** (Akses Cepat APAR) atau Drawer.
2. Buat inspeksi baru.
3. Pilih cara pengisian:
   - **Scan QR**: scan QR APAR agar detail terisi otomatis.
   - **Manual**: isi form (tekanan, segel, nozzle, dan lainnya).
4. Tambahkan foto before/after jika diperlukan.
5. Tekan **Simpan**.

Data yang disimpan akan tampil di daftar APAR dan Dashboard.

## 9. Inspeksi Fire Alarm

1. Buka menu **Fire Alarm**.
2. Buat inspeksi baru atau buka inspeksi yang sudah ada.
3. Isi form sesuai kondisi alarm kebakaran.
4. Tekan **Simpan**.

## 10. Inspeksi ES/EW (Emergency Shower / Eye Wash)

1. Buka menu **ES/EW**.
2. Buat inspeksi baru.
3. Pilih cara pengisian:
   - **Scan QR**: scan QR point ES/EW agar point terisi otomatis.
   - **Manual**: isi area, point, kondisi, dan foto.
4. Tekan **Simpan**.

## 11. Checklist Umum

1. Buka menu **Checklist**.
2. Pilih **kategori** checklist.
3. Isi item checklist sesuai kondisi di lapangan.
4. Lakukan **check-in** bila diperlukan.
5. Tekan **Simpan**.

Catatan: menu Checklist saat ini tersedia di sisi backend; pada versi mobile
tertentu halaman ini mungkin menampilkan "Checklist belum tersedia".

## 12. Permit Matrix

Permit Matrix adalah inspeksi Safe Work Permit. Kolom **Job Performance**
berupa dropdown yang isiannya dikelola dari website (backend).

### 12.1 Membuat Permit Matrix

1. Buka menu **Permit Matrix** (Akses Cepat Permit) atau Drawer.
2. Tekan tombol buat baru.
3. Isi form: main area, sub area, job performance, dan data lainnya.
4. Tekan **Simpan**.

### 12.2 Hak Akses

- Semua pengguna dengan permission dapat melihat dan membuat.
- Edit dan hapus khusus admin/super admin.
- Non-admin tidak melihat tombol edit/hapus.

### 12.3 Export Excel

Pada halaman index (website/backend), tersedia tombol **Export Excel** untuk
mengunduh seluruh data hasil filter ke file `.xls`.

## 13. Safety Talk / Training

1. Buka menu **Safety Talk/Training**.
2. Tekan tombol buat baru.
3. Isi form kegiatan (topik, tanggal, lokasi).
4. Hitung jumlah peserta.
5. Upload foto kegiatan.
6. Tekan **Simpan**.

Edit dan hapus khusus admin/super admin. Export Excel tersedia di halaman
index (website/backend).

## 14. Inspection / Incident

Modul ini untuk melaporkan inspection/incident.

### 14.1 Membuat Laporan Baru

1. Buka menu **Inspection**.
2. Tekan tombol buat laporan baru.
3. Isi form:
   - Tanggal dan waktu kejadian.
   - Lokasi.
   - Tipe inspeksi/insiden (dropdown).
   - Deskripsi.
4. Pilih **Foto Temuan Awal** (wajib) — ambil dari kamera atau galeri.
5. Opsional: tambahkan **Foto Perbaikan**.
6. Tekan **Simpan**.

Jika foto temuan awal tidak dipilih, muncul validasi
**"Foto temuan awal wajib dipilih"** dan data tidak terkirim.

### 14.2 Edit Laporan

- Pembuat laporan atau admin/super admin dapat mengedit.
- Perubahan tersimpan online atau menjadi draft saat offline.

## 15. Scanner QR Code

1. Pastikan GPS aktif dan izin lokasi diberikan.
2. Buka halaman **Scan QR Code**.
3. Arahkan kamera ke QR code asset (hydrant, APAR, ES/EW, dll).
4. Aplikasi membaca lokasi GPS dan memproses QR.
5. Jika asset ditemukan, form inspeksi terbuka dengan detail terisi otomatis.
6. Jika QR tidak dikenal, muncul pesan data tidak ditemukan (aplikasi tidak
   crash).

Scanner QR juga bisa bekerja offline menggunakan cache data point lokal,
selama aplikasi pernah dibuka dalam kondisi online.

## 16. Check-in GPS dan Tanda Tangan Digital

### 16.1 Check-in GPS

Pada form inspeksi, lakukan **check-in** untuk menyimpan koordinat dan waktu
kehadiran. Pastikan GPS aktif dan izin lokasi diberikan.

### 16.2 Tanda Tangan Digital

Lakukan **sign** (tanda tangan digital) pada inspeksi. Data tanda tangan
tersimpan di server.

Catatan: tanda tangan digital yang disimpan adalah tanda tangan milik user
yang melakukan sign (diambil dari profil user di server).

## 17. Master Data (Administrator)

Menu Master Data hanya tersedia untuk admin/super admin. Melalui drawer,
tersedia:

| Menu | Fungsi |
|---|---|
| Hydrant Locations | Kelola lokasi hydrant |
| Incident Types | Kelola tipe insiden |
| ES&EW Areas | Kelola area ES/EW |
| Users | Kelola pengguna aplikasi |

Untuk masing-masing data, Anda dapat menambah, mengedit, dan menghapus sesuai
izin. Data user baru dapat dibuat agar bisa login.

## 18. Troubleshooting dan FAQ

### Aplikasi tidak bisa login

- Pastikan koneksi internet aktif.
- Pastikan username dan password benar (login memakai username, bukan email).
- Hubungi administrator jika lupa password.

### Data tidak tersinkron

- Pastikan perangkat online.
- Tekan **Kirim** pada banner draft, atau refresh Dashboard.
- Sinkronisasi berjalan otomatis saat koneksi kembali.

### Scan QR gagal

- Pastikan GPS aktif dan izin lokasi diberikan.
- Pastikan QR code terdaftar di server.
- Saat offline, pastikan aplikasi pernah dibuka dalam kondisi online agar
  cache point tersedia.

### Foto tidak muncul

- Foto disimpan di server; pastikan koneksi aktif saat melihat detail.

### Saya tidak bisa edit/hapus data

- Edit/hapus hanya untuk pembuat data atau admin/super admin.
- Hubungi administrator bila diperlukan.

### Error koneksi server

- Pastikan URL API benar: `http://172.16.16.51:83/api`.
- Hubungi tim IT.

## 19. Dukungan Teknis

Untuk pertanyaan, kendala, atau permintaan akun, hubungi tim IT PT. Ecogreen
Oleochemicals Indonesia.

---

© 2026 PT. Ecogreen Oleochemicals Indonesia. Seluruh hak cipta dilindungi.