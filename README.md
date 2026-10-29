# SIMPEG PN Yogyakarta

**SIMPEG PN Yogyakarta** adalah Sistem Informasi Manajemen Kepegawaian untuk mengelola data hakim dan pegawai Pengadilan Negeri Yogyakarta. Aplikasi menggunakan PHP native dan MySQL/MariaDB melalui PDO.

Kebutuhan: PHP 7.4+ dengan ekstensi `pdo_mysql`, `mbstring`, dan `fileinfo`, serta MySQL/MariaDB.

## Fitur

- Daftar umum hakim dan pegawai, pencarian nama/NIP, filter berdasarkan bulan TMT, pangkat/golongan, pendidikan, jabatan, serta kelompok, pengurutan kolom, pengeditan data pegawai, dan pengaturan jumlah data yang ditampilkan (25, 50, 100, atau semua).
- Input data pegawai, pencatatan promosi, serta pencatatan mutasi/pindah dengan beberapa lampiran foto atau dokumen. Mutasi hanya mencatat pegawai masuk dan dapat mengaktifkan kembali pegawai Non-Aktif atau sekaligus membuat data pegawai baru dengan TMT di PN Yogyakarta. Promosi dapat mencatat naik jabatan atau pegawai keluar; pegawai yang keluar dihapus dari data pegawai sementara riwayat dan lampiran tetap disimpan.
- Pegawai keluar melalui pilihan pada menu promosi mencatat tanggal keluar secara terpisah dari tanggal SK.
- Pembuatan akun pengguna tambahan dengan role Pengelola Kepegawaian yang sama.
- Menu Presentasi Canva untuk mengatur tautan dan menampilkan presentasi data pegawai yang dibuat di Canva. Isi presentasi dikelola di Canva dan tidak tersinkron otomatis dengan database SIMPEG.
- Rekap promosi dan mutasi dengan filter bulan, triwulan, dan tahun berdasarkan tanggal SK; tanggal SK ditampilkan bersama nama harinya. Promosi naik jabatan memperbarui jabatan/pangkat; pilihan keluar menghapus data pegawai tetapi mempertahankan identitas pada riwayat dan lampiran. Riwayat dapat diedit; transaksi biasa dapat dihapus dengan penyesuaian data pegawai jika tidak ada transaksi berikutnya. Riwayat keluar tidak dapat dihapus setelah data pegawai dihapus.
- Login dengan satu role tetap, **Pengelola Kepegawaian**. Tidak ada role Admin atau Penilai.

## Instalasi lokal (XAMPP)

1. Salin folder aplikasi ke `htdocs`.
2. Impor dump database yang Anda sediakan (`pn_yogya.sql`) ke database `pn_yogya`.
3. Pilih database `pn_yogya` di phpMyAdmin, buka tab **Import**, lalu jalankan file `database.sql` dari folder aplikasi **satu kali**. File ini adalah migrasi tambahan untuk menghubungkan login NIP, menyimpan TMT, dan merekam snapshot jabatan/pangkat pada riwayat; file ini tidak menghapus data.
4. Untuk menyimpan nilai baru dari pilihan Pendidikan **Lainnya**, impor `database_pendidikan_custom.sql` satu kali pada database yang sama. Migrasi ini mempertahankan data dan mengubah kolom pendidikan agar menerima nilai khusus.
5. Untuk mengaktifkan beberapa lampiran promosi/mutasi pada database dari dump `pn_yogya (1).sql`, impor `database_lampiran.sql` satu kali agar indeks unik lama di tabel lampiran menjadi indeks biasa. Pastikan PHP dapat menulis ke folder `simpeg-pn-yogya-storage/lampiran-transaksi` di atas folder `htdocs`; file disimpan di luar document root agar tidak dapat diakses langsung.
6. Untuk mempertahankan tanggal keluar pada riwayat mutasi lama, impor `database_tanggal_keluar.sql` satu kali pada database yang sama.
7. Untuk pilihan pegawai keluar pada promosi dan snapshot identitas pada riwayat, impor `database_promosi_keluar.sql` satu kali pada database yang sama.
8. Pastikan konfigurasi PHP mengizinkan unggahan beberapa file (misalnya `upload_max_filesize=5M`, `post_max_size=55M`, dan `max_file_uploads=10`).
9. Jika pengaturan MySQL berbeda dari XAMPP default, sesuaikan host, nama database, username, dan password di `includes/config.php`.
10. Buka aplikasi melalui `http://localhost/sistem-pn-yogya/`.
11. Pada kunjungan pertama, buka aplikasi melalui browser di mesin server untuk mengakses halaman setup dan membuat akun pengelola menggunakan NIP 18 digit serta kata sandi pilihan Anda. Halaman setup menolak koneksi selain localhost. Akun bawaan `admin` dalam dump lama tidak dihapus; login aplikasi menggunakan akun yang memiliki NIP.

Lampiran menerima JPG, PNG, WEBP, PDF, DOC, DOCX, XLS, dan XLSX; maksimal 10 file dengan ukuran 5 MB per file untuk setiap transaksi.

Tautan presentasi diatur melalui menu **Presentasi Canva** setelah login. Gunakan tautan tampilan desain Canva (`https://www.canva.com/design/.../view`) dan pastikan izin berbagi Canva mengizinkan pengguna SIMPEG melihat presentasi. Aplikasi menyimpan pengaturan ini di folder `simpeg-pn-yogya-storage/pengaturan` di atas folder `htdocs`; pastikan folder penyimpanan tersebut dapat ditulis oleh PHP.

Tidak ada username atau kata sandi bawaan. Kata sandi akun disimpan sebagai hash.
