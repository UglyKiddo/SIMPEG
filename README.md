# SI Kepegawaian PN Yogyakarta

**SI Kepegawaian PN Yogyakarta** adalah sistem informasi untuk mengelola data hakim dan pegawai Pengadilan Negeri Yogyakarta. Aplikasi menggunakan PHP native dan MySQL/MariaDB melalui PDO.

Kebutuhan: PHP 7.4+ dengan ekstensi `pdo_mysql`, `mbstring`, dan `fileinfo`, serta MySQL/MariaDB.

## Fitur

- Daftar umum hakim dan pegawai, pencarian nama/NIP, filter berdasarkan bulan TMT, pangkat/golongan, pendidikan, jabatan, serta kelompok, pengurutan kolom, pengeditan data pegawai, pengaturan jumlah data yang ditampilkan (25, 50, 100, atau semua), serta unduhan Word dan Excel sesuai filter aktif.
- Input data pegawai dengan pilihan jabatan yang dapat diketik; jabatan baru dapat dibuat dengan kategori Hakim, Pegawai, atau PPNPN. Pencatatan promosi serta mutasi/pindah mendukung beberapa lampiran foto atau dokumen. Mutasi Masuk mencatat pegawai baru atau mengaktifkan kembali pegawai Non-Aktif. Promosi Naik Jabatan menyimpan tanggal pelaksanaan dan memperbarui jabatan/pangkat; promosi Keluar menghapus pegawai dari data aktif dan mempertahankan identitas serta riwayatnya.
- Pegawai keluar melalui pilihan pada menu promosi mencatat tanggal keluar secara terpisah dari tanggal SK.
- Perubahan data pegawai melalui menu Edit disimpan sebagai riwayat, sehingga kondisi data sebelum perubahan dapat dilihat dengan memilih tanggal pada filter **Kondisi per tanggal**.
- Pembuatan akun pengguna tambahan dengan role Pengelola Kepegawaian yang sama.
- Menu Presentasi Canva untuk mengatur tautan dan menampilkan presentasi data pegawai yang dibuat di Canva. Isi presentasi dikelola di Canva dan tidak tersinkron otomatis dengan database SI Kepegawaian.
- Rekap promosi dan mutasi dengan filter bulan, triwulan, dan tahun berdasarkan tanggal SK, serta unduhan PDF rekap tahunan per bulan. Kondisi per tanggal memakai tanggal pelaksanaan promosi atau tanggal keluar sebagai tanggal efektif; kondisi sebelum tanggal tersebut tetap menampilkan keadaan pegawai sebelumnya.
- Login dengan satu role tetap, **Pengelola Kepegawaian**. Tidak ada role Admin atau Penilai.

## Instalasi lokal (XAMPP)

1. Salin folder aplikasi ke `htdocs`.
2. Jalankan `composer install` pada folder aplikasi untuk memasang Dompdf yang digunakan pada unduhan PDF rekap.
3. Impor dump database yang Anda sediakan (`pn_yogya.sql`) ke database `pn_yogya`.
4. Pilih database `pn_yogya` di phpMyAdmin, buka tab **Import**, lalu jalankan file `database.sql` dari folder aplikasi **satu kali**. File ini adalah migrasi tambahan untuk menghubungkan login NIP, menyimpan TMT, dan merekam snapshot jabatan/pangkat pada riwayat; file ini tidak menghapus data.
5. Untuk menyimpan nilai baru dari pilihan Pendidikan **Lainnya**, impor `database_pendidikan_custom.sql` satu kali pada database yang sama. Migrasi ini mempertahankan data dan mengubah kolom pendidikan agar menerima nilai khusus.
6. Untuk mengaktifkan beberapa lampiran promosi/mutasi pada database dari dump `pn_yogya (1).sql`, impor `database_lampiran.sql` satu kali agar indeks unik lama di tabel lampiran menjadi indeks biasa. Pastikan PHP dapat menulis ke folder `simpeg-pn-yogya-storage/lampiran-transaksi` di atas folder `htdocs`; file disimpan di luar document root agar tidak dapat diakses langsung.
7. Untuk mempertahankan tanggal keluar pada riwayat mutasi lama, impor `database_tanggal_keluar.sql` satu kali pada database yang sama.
8. Untuk pilihan pegawai keluar pada promosi dan snapshot identitas pada riwayat, impor `database_promosi_keluar.sql` satu kali pada database yang sama.
9. Untuk menyimpan versi data pegawai sebelum diedit, impor `database_riwayat_edit_pegawai.sql` satu kali pada database yang sama. Riwayat edit mulai tercatat setelah migrasi ini dipasang; perubahan sebelumnya tidak dapat dipulihkan otomatis.
10. Untuk menyimpan tanggal pelaksanaan promosi naik jabatan, impor `database_tanggal_melaksanakan_promosi.sql` satu kali pada database yang sama. Riwayat promosi lama akan memakai tanggal SK sebagai nilai awal tanggal pelaksanaan.
11. Pastikan konfigurasi PHP mengizinkan unggahan beberapa file (misalnya `upload_max_filesize=5M`, `post_max_size=55M`, dan `max_file_uploads=10`).
12. Jika pengaturan MySQL berbeda dari XAMPP default, sesuaikan host, nama database, username, dan password di `includes/config.php`.
13. Buka aplikasi melalui `http://localhost/sistem-pn-yogya/`.
14. Pada kunjungan pertama, buka aplikasi melalui browser di mesin server untuk mengakses halaman setup dan membuat akun pengelola menggunakan NIP 18 digit serta kata sandi pilihan Anda. Halaman setup menolak koneksi selain localhost. Akun bawaan `admin` dalam dump lama tidak dihapus; login aplikasi menggunakan akun yang memiliki NIP.

Lampiran menerima JPG, PNG, WEBP, PDF, DOC, DOCX, XLS, dan XLSX; maksimal 10 file dengan ukuran 5 MB per file untuk setiap transaksi.

Tautan presentasi diatur melalui menu **Presentasi Canva** setelah login. Gunakan tautan tampilan desain Canva (`https://www.canva.com/design/.../view`) dan pastikan izin berbagi Canva mengizinkan pengguna SI Kepegawaian melihat presentasi. Aplikasi menyimpan pengaturan ini di folder `simpeg-pn-yogya-storage/pengaturan` di atas folder `htdocs`; pastikan folder penyimpanan tersebut dapat ditulis oleh PHP.

Tidak ada username atau kata sandi bawaan. Kata sandi akun disimpan sebagai hash.
