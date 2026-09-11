# User Testing — SHE Inspection System

Dokumen uji terima pengguna (User Acceptance Testing / UAT) untuk aplikasi SHE
Inspection System (mobile + backend API).

## 1. Informasi Umum

| Item | Keterangan |
|---|---|
| Nama Penguji | |
| Tanggal Pengujian | |
| Perangkat / OS | |
| Versi Aplikasi (mobile) | |
| URL API | `http://172.16.16.51:83/api` |
| Akun Penguji | username: ____________ / password: ____________ |
| Role Akun | ☐ super_admin ☐ admin ☐ inspector ☐ viewer |

## 2. Lingkungan Pengujian

- Perangkat: Android (min. API 21), disarankan perangkat fisik.
- Aplikasi: build debug/release dari `mobile/`.
- Backend: Laravel (`backend/she-api`), deployment Docker di `http://172.16.16.51:83`.
- Database: MySQL `she_inspection`.

## 3. Cara Mengisi

1. Jalankan tiap kasus uji sesuai langkah pada kolom **Langkah Pengujian**.
2. Isi kolom **Hasil Aktual** dengan perilaku yang benar-benar terlihat.
3. Tandai **Status**: ✅ Pass (sesuai harapan) / ❌ Fail (tidak sesuai) / ⏭ Skip (tidak diuji).
4. Tulis kendala pada kolom **Catatan**.

---

## 4. Kasus Uji

### A. Autentikasi & Profil

| No | Skenario | Langkah Pengujian | Hasil yang Diharapkan | Hasil Aktual | Status | Catatan |
|---|---|---|---|---|---|---|
| A1 | Login berhasil | Buka aplikasi → isi username & password benar → tekan Login | Masuk ke Dashboard dan data user tampil | | | |
| A2 | Login gagal (password salah) | Isi password salah → tekan Login | Muncul pesan "credentials incorrect", tetap di halaman login | | | |
| A3 | Login tanpa input | Tekan Login dengan field kosong | Validasi form tampil, tidak ada request terkirim | | | |
| A4 | Logout | Buka menu → Logout | Kembali ke halaman login, token dihapus | | | |
| A5 | Profil | Menu → Profil | Data user (nama, username, role) tampil benar | | | |
| A6 | Restore session | Tutup & buka kembali aplikasi (masih login) | Langsung masuk tanpa login ulang | | | |

### B. Dashboard

| No | Skenario | Langkah Pengujian | Hasil yang Diharapkan | Hasil Aktual | Status | Catatan |
|---|---|---|---|---|---|---|
| B1 | Statistik | Buka Dashboard saat online | Kartu Total Data, Tindak Lanjut, dst. terisi benar | | | |
| B2 | List aktivitas terbaru | Perhatikan daftar inspeksi di Dashboard | Data terbaru muncul terurut | | | |
| B3 | Tombol Refresh | Tekan ikon refresh | Data dimuat ulang; bila ada draft pending & online, draft ikut dikirim | | | |
| B4 | Pull-to-refresh | Tarik layar ke bawah | Sama seperti B3 | | | |
| B5 | Filter status | Gunakan filter status | List tersaring sesuai status | | | |
| B6 | Pencarian | Ketik kata kunci di kolom cari | Hasil terfilter berdasarkan kata kunci | | | |

### C. Mode Offline & Sinkronisasi

| No | Skenario | Langkah Pengujian | Hasil yang Diharapkan | Hasil Aktual | Status | Catatan |
|---|---|---|---|---|---|---|
| C1 | Simpan saat offline | Aktifkan mode pesawat → buat inspeksi Hydrant/APAR/ES/EW/Inspection lengkap dengan foto → Simpan | Muncul snackbar "Offline: disimpan sebagai draft", kembali ke Dashboard | | | |
| C2 | Badge draft | Lihat item yang baru disimpan di Dashboard | Item tampil dengan badge oranye "Menunggu sinkronisasi" & banner Mode Offline menampilkan jumlah draft | | | |
| C3 | Sinkronisasi otomatis saat online | Matikan mode pesawat (koneksi pulih) | Draft otomatis terkirim; badge berubah menjadi tersimpan (cloud done), banner offline hilang | | | |
| C4 | Tombol Kirim di banner | Saat offline & ada draft, tekan "Kirim" | (Selama masih offline) tetap draft; setelah online, draft terkirim | | | |
| C5 | Gagal kirim saat online | Saat online, buat inspeksi lalu matikan server / buat error di backend | Data tidak hilang; otomatis tersimpan sebagai draft dengan pesan "disimpan sebagai draft, akan disinkronkan otomatis" | | | |
| C6 | Buka app saat online | Tutup aplikasi (masih ada draft pending) → buka kembali saat online | Draft tersisa otomatis terkirim saat app start | | | |
| C7 | Sync via refresh | Dengan draft pending & online, tekan refresh Dashboard | Draft terkirim, status berubah jadi tersimpan | | | |

### D. Inspeksi Fire Hydrant

| No | Skenario | Langkah Pengujian | Hasil yang Diharapkan | Hasil Aktual | Status | Catatan |
|---|---|---|---|---|---|---|
| D1 | Create via QR | Menu Fire Hydrant → pilih location → scan QR hydrant | Detail hydrant terisi otomatis dari QR | | | |
| D2 | Create manual | Isi form lengkap (tanggal, location, item hydrant) + foto | Data tersimpan, muncul snackbar sukses | | | |
| D3 | Validasi wajib | Simpan tanpa mengisi item hydrant yang wajib | Validasi muncul, data tidak terkirim | | | |
| D4 | Edit | Buka detail → Edit (creator/admin) | Perubahan tersimpan (online) atau jadi draft (offline) | | | |
| D5 | Detail | Buka item yang sudah ada | Seluruh field & foto tampil benar | | | |
| D6 | Check-in GPS | Lakukan check-in di lokasi | Koordinat & waktu check-in tersimpan | | | |
| D7 | Tanda tangan | Lakukan sign digital | Data signature tersimpan | | | |
| D8 | Hak akses edit/hapus | Login sebagai user lain (bukan creator, bukan admin) | Tombol edit/hapus tidak tersedia / ditolak | | | |

### E. Inspeksi Fire Extinguisher (APAR)

| No | Skenario | Langkah Pengujian | Hasil yang Diharapkan | Hasil Aktual | Status | Catatan |
|---|---|---|---|---|---|---|
| E1 | Create via QR | Scan QR APAR | Detail APAR terisi otomatis | | | |
| E2 | Create manual | Isi form (pressure, seal, nozzle) + foto before/after | Tersimpan sukses | | | |
| E3 | Edit | Buka detail → Edit | Perubahan tersimpan | | | |
| E4 | List & detail | Buka menu APAR | List & detail tampil benar | | | |
| E5 | Offline draft | Simpan APAR saat offline | Menjadi draft (lihat C1-C3) | | | |

### F. Inspeksi Fire Alarm

| No | Skenario | Langkah Pengujian | Hasil yang Diharapkan | Hasil Aktual | Status | Catatan |
|---|---|---|---|---|---|---|
| F1 | List | Buka menu Fire Alarm | List tampil | | | |
| F2 | Detail/create | Buka detail atau buat baru | Form & data tampil benar | | | |

### G. Inspeksi ES/EW (Emergency Shower / Eye Wash)

| No | Skenario | Langkah Pengujian | Hasil yang Diharapkan | Hasil Aktual | Status | Catatan |
|---|---|---|---|---|---|---|
| G1 | Create via QR | Scan QR point ES/EW | Point terisi otomatis | | | |
| G2 | Create manual | Isi area, point, kondisi + foto | Tersimpan sukses | | | |
| G3 | Edit | Buka detail → Edit | Perubahan tersimpan / jadi draft saat offline | | | |
| G4 | Detail & list | Buka list & detail | Data & foto tampil benar | | | |

### H. Checklist Umum

| No | Skenario | Langkah Pengujian | Hasil yang Diharapkan | Hasil Aktual | Status | Catatan |
|---|---|---|---|---|---|---|
| H1 | Create checklist | Pilih kategori → isi item | Tersimpan sukses | | | |
| H2 | Check-in | Lakukan check-in | Waktu/koordinat tersimpan | | | |
| H3 | List & detail | Buka menu checklist | Data tampil benar | | | |

### I. Permit Matrix

| No | Skenario | Langkah Pengujian | Hasil yang Diharapkan | Hasil Aktual | Status | Catatan |
|---|---|---|---|---|---|---|
| I1 | List | Buka menu Permit Matrix | List tampil dengan data | | | |
| I2 | Create | Isi form (main area, sub area, job performance) | Tersimpan sukses; Job Performance berupa dropdown | | | |
| I3 | Detail | Buka detail | Data tampil benar | | | |
| I4 | Edit/hapus (admin) | Login admin → Edit/Delete | Berhasil | | | |
| I5 | Edit/hapus non-admin | Login inspector | Tombol edit/hapus tidak tersedia | | | |
| I6 | Export Excel | Tekan tombol Export Excel | File `.xls` terunduh sesuai filter | | | |

### J. Safety Talk / Training

| No | Skenario | Langkah Pengujian | Hasil yang Diharapkan | Hasil Aktual | Status | Catatan |
|---|---|---|---|---|---|---|
| J1 | List | Buka menu Safety Talk | List tampil | | | |
| J2 | Create | Isi form + hitung peserta + upload foto | Tersimpan sukses | | | |
| J3 | Detail | Buka detail | Data & foto tampil benar | | | |
| J4 | Edit/hapus (admin) | Login admin → Edit/Delete | Berhasil | | | |
| J5 | Export Excel | Tekan Export Excel | File `.xls` terunduh | | | |

### K. Inspection / Incident

| No | Skenario | Langkah Pengujian | Hasil yang Diharapkan | Hasil Aktual | Status | Catatan |
|---|---|---|---|---|---|---|
| K1 | Create dengan foto wajib | Isi form + pilih **Foto Temuan Awal** (wajib) → Simpan | Tersimpan sukses | | | |
| K2 | Create tanpa foto | Simpan tanpa Foto Temuan Awal | Validasi "Foto temuan awal wajib dipilih" | | | |
| K3 | Perbaikan opsional | Tambah Foto Perbaikan | Foto tersimpan (opsional) | | | |
| K4 | Edit (creator/admin) | Buka detail → Edit | Perubahan tersimpan / jadi draft saat offline | | | |
| K5 | List | Buka menu Inspection | List tampil benar | | | |
| K6 | Offline draft | Simpan saat offline | Menjadi draft (lihat C1-C3) | | | |

### L. QR Code Scanner

| No | Skenario | Langkah Pengujian | Hasil yang Diharapkan | Hasil Aktual | Status | Catatan |
|---|---|---|---|---|---|---|
| L1 | Scan asset hydrant | Scan QR hydrant | Terbuka form create hydrant dengan detail terisi | | | |
| L2 | Scan asset APAR | Scan QR APAR | Form create APAR terisi | | | |
| L3 | Scan asset ES/EW | Scan QR ES/EW | Form create ES/EW terisi | | | |
| L4 | QR tidak dikenal | Scan QR asing | Muncul pesan data tidak ditemukan / tidak crash | | | |

### M. Master Data

| No | Skenario | Langkah Pengujian | Hasil yang Diharapkan | Hasil Aktual | Status | Catatan |
|---|---|---|---|---|---|---|
| M1 | CRUD Point | Tambah/edit/hapus Point | Data berubah & tampil benar | | | |
| M2 | CRUD Location | Tambah/edit/hapus Location | Berhasil | | | |
| M3 | CRUD Incident Type | Tambah/edit/hapus | Berhasil | | | |
| M4 | CRUD ES/EW Area | Tambah/edit/hapus | Berhasil | | | |
| M5 | CRUD User | Tambah user baru (admin) | User baru bisa login | | | |

### N. Registrasi Awal & Referensi Nomor

| No | Skenario | Langkah Pengujian | Hasil yang Diharapkan | Hasil Aktual | Status | Catatan |
|---|---|---|---|---|---|---|
| N1 | Reference no. terisi otomatis | Buat inspeksi Hydrant baru (online) | Data tersimpan & `reference_no` (FH-...) terisi otomatis, tidak error SQL | | | |
| N2 | Referensi unik | Buat 2+ inspeksi hydrant | Reference no berbeda-beda, tidak ada duplikat | | | |

---

## 5. Ringkasan Hasil

| Modul | Total Kasus | ✅ Pass | ❌ Fail | ⏭ Skip | Catatan |
|---|---|---|---|---|---|
| A. Autentikasi & Profil | 6 | | | | |
| B. Dashboard | 6 | | | | |
| C. Offline & Sinkronisasi | 7 | | | | |
| D. Fire Hydrant | 8 | | | | |
| E. Fire Extinguisher | 5 | | | | |
| F. Fire Alarm | 2 | | | | |
| G. ES/EW | 4 | | | | |
| H. Checklist | 3 | | | | |
| I. Permit Matrix | 6 | | | | |
| J. Safety Talk | 5 | | | | |
| K. Inspection / Incident | 6 | | | | |
| L. QR Code | 4 | | | | |
| M. Master Data | 5 | | | | |
| N. Referensi Nomor | 2 | | | | |
| **Total** | **69** | | | | |

## 6. Kendala / Temuan

| No | Modul | Deskripsi Temuan | Tingkat Keparahan | Saran Perbaikan |
|---|---|---|---|---|
| 1 | | | ☐ Kecil ☐ Sedang ☐ Kritis | |
| 2 | | | ☐ Kecil ☐ Sedang ☐ Kritis | |
| 3 | | | ☐ Kecil ☐ Sedang ☐ Kritis | |

## 7. Keputusan

- ☐ **Layak Rilis** — semua kasus penting (Severity Kritis/Sedang) sudah Pass.
- ☐ **Layak Rilis dengan Catatan** — ada temuan kecil yang akan diperbaiki setelah rilis.
- ☐ **Tidak Layak Rilis** — ada temuan kritis yang belum diperbaiki.

Tanda tangan Penguji: ____________&nbsp;&nbsp;&nbsp;&nbsp;Tanggal: ____________