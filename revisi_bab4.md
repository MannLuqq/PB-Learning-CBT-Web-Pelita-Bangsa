# DRAFT MATERI REVISI BAB 4: IMPLEMENTASI DAN PEMBAHASAN

## 4.X. Pembaruan dan Revisi Sistem Pengembangan (PB-Learning)
Berdasarkan hasil evaluasi, kebutuhan pengguna, dan batasan infrastruktur server hosting produksi, telah dilakukan serangkaian revisi dan pembaruan fungsionalitas pada sistem **PB-Learning**. Pembaruan ini mencakup aspek keamanan ujian (*CBT Anti-Cheat*), manajemen data pengguna (*Soft Delete* & *Multi-Admin*), integrasi database, penyesuaian penamaan identitas pendidik, serta optimasi performa *client-side*.

Berikut adalah rincian pembaruan sistem yang diimplementasikan pada Bab 4 ini:

### 1. Implementasi Multi-Role & Pembatasan Hak Akses Khusus (Superadmin)
Pada arsitektur sistem sebelumnya, peran Administrator (*Admin*) memiliki kendali penuh atas manipulasi data (termasuk penghapusan data guru dan siswa) secara langsung di sistem. Untuk meningkatkan keamanan data akademik, dilakukan pemisahan peran dengan memperkenalkan aktor baru yaitu **Superadmin** selaku pemegang otoritas tertinggi sistem:
* **Pembatasan Registrasi Admin:** Pendaftaran akun Admin baru kini didelegasikan secara eksklusif kepada Superadmin. Menu registrasi publik hanya melayani peran Siswa dan Guru. Pembuatan akun Admin baru hanya dapat diproses melalui submenu khusus "Kelola Akun Admin" di Dashboard Superadmin dengan enkripsi password berbasis *BCRYPT*.
* **Otoritas Penghapusan Akun:** Aktor Admin biasa tidak lagi memiliki wewenang untuk menghapus akun guru atau siswa. Tombol aksi hapus pada panel Admin dinonaktifkan secara permanen, dan seluruh fungsi penghapusan akun dipindahkan secara terpusat ke panel kontrol Superadmin.

### 2. Mekanisme Soft Delete dan Tempat Sampah (Recycle Bin)
Untuk mengantisipasi kehilangan data akibat ketidaksengajaan atau kesalahan operasional, aksi penghapusan akun diubah dari mekanisme penghapusan permanen (*Hard Delete*) menjadi penghapusan logis (*Soft Delete*):
* **Perubahan Skema Database:** Menambahkan atribut `deleted_at` dengan tipe data `DATETIME` pada tabel `users`. 
* **Logika Operasional:** Ketika Superadmin melakukan penghapusan akun siswa atau guru, sistem tidak akan menjalankan kueri `DELETE FROM`, melainkan melakukan pembaruan status `UPDATE users SET deleted_at = NOW() WHERE id = ?`. Akun yang memiliki nilai pada kolom `deleted_at` secara otomatis dinonaktifkan dari sistem dan tidak dapat digunakan untuk masuk (*login*).
* **Menu Tempat Sampah (Recycle Bin):** Menyediakan halaman khusus bagi Superadmin untuk meninjau data yang terhapus secara sementara. Pada halaman ini, Superadmin diberikan opsi untuk memulihkan akun (*Restore*) sehingga kembali aktif, atau melakukan penghapusan secara permanen (*Permanent Delete*) dari database.

### 3. Sistem Sinkronisasi Sesi Pengguna Real-Time (Auto-Logout)
Untuk menjaga konsistensi hak akses apabila terjadi penghapusan atau penonaktifkan akun oleh Superadmin saat pengguna yang bersangkutan sedang aktif menggunakan sistem, dikembangkan modul *real-time session tracking*:
* **Background Polling:** Pada sisi klien (*client-side*), modul JavaScript menggunakan interval waktu (`setInterval`) setiap 7 detik untuk mengirimkan permintaan asinkron (AJAX fetch) ke API server (`api/check_user_status.php`).
* **Sanksi Otomatis:** Jika API server mendeteksi status pengguna telah berubah menjadi dinonaktifkan (`is_active = 0`) atau telah masuk dalam daftar soft-deleted (`deleted_at IS NOT NULL`), sistem klien akan memicu fungsi `logout()` secara otomatis. Sistem akan menghapus seluruh data sesi (`sessionStorage` dan `localStorage`), menampilkan kotak dialog notifikasi status akun, dan mengalihkan pengguna kembali ke halaman utama login.

### 4. Implementasi Sistem Keamanan Ujian CBT (Anti-Cheat System)
Keamanan pelaksanaan ujian berbasis komputer (*Computer Based Test*) menjadi salah satu fokus pembaruan utama untuk mencegah kecurangan akademik oleh siswa saat mengerjakan soal secara mandiri. Fitur ini dirancang menggunakan beberapa API web modern:
* **Mode Layar Penuh Paksa (Force Fullscreen):** Saat siswa memulai lembar ujian, sistem secara otomatis memaksa peramban (*browser*) memasuki mode layar penuh menggunakan *Fullscreen API*.
* **Deteksi Perpindahan Fokus Jendela (Blur & Visibility API):** Sistem secara aktif memantau perilaku peramban melalui pendeteksian perpindahan fokus tab (`blur`) dan status visibilitas halaman (`visibilitychange`). Jika siswa menekan `Alt + Tab`, membuka aplikasi eksternal, meminimalkan jendela peramban, atau sengaja keluar dari mode layar penuh, sistem akan mencatat tindakan tersebut sebagai pelanggaran.
* **Sanksi Reset Jawaban & Soal Nomor 1:** Untuk menghindari kendala pemblokiran paksa yang dapat mengganggu kelancaran teknis ujian, sanksi kecurangan diatur secara edukatif namun ketat. Saat kecurangan terdeteksi, seluruh jawaban yang telah diisi oleh siswa akan dikosongkan (*reset* dari memori), dan tampilan halaman soal dikembalikan secara paksa ke nomor 1.
* **Pemblokiran Fungsi Interaktif Klien:** Menyisipkan kode pencegahan *event* (`preventDefault`) untuk menonaktifkan klik kanan peramban (*context menu*), pemblokiran fungsi salin-tempel (*copy-paste*), penonaktifan seleksi teks soal, serta pemblokiran tombol pintas pengembang (*Developer Tools* seperti tombol `F12` dan kombinasi `Ctrl+Shift+I`).

### 5. Penyesuaian NIP Menjadi NUPTK pada Akun Pendidik
Guna menyelaraskan istilah administratif sekolah swasta atau non-PNS, seluruh terminologi NIP (Nomor Induk Pegawai) bagi guru diubah secara menyeluruh menjadi **NUPTK** (Nomor Unik Pendidik dan Tenaga Kependidikan). Perubahan ini diimplementasikan pada:
* Atribut tampilan (Label form input pada antarmuka pengguna).
* Validasi panjang karakter input (disesuaikan dengan standar NUPTK yang berjumlah 16 digit angka).
* Komentar dokumentasi kode pemrograman backend dan skema database relasional.

### 6. Perubahan Format Input Nilai dari Excel ke CSV (Kompatibilitas Hosting)
Pada desain awal, pengunggahan nilai siswa oleh guru dirancang menggunakan format berkas Excel (.xlsx). Namun, pustaka pemroses Excel di sisi server sering kali memerlukan spesifikasi modul tambahan yang berat (seperti Python dengan Pandas, atau alokasi memori PHP yang tinggi). 
* **Migrasi ke Format CSV:** Sistem pengolahan nilai dimigrasikan sepenuhnya menggunakan berkas dengan format *Comma-Separated Values* (CSV).
* **Efisiensi Server:** Pemrosesan berkas CSV dilakukan dengan menggunakan fungsi bawaan PHP murni (`fgetcsv`) tanpa memerlukan ketergantungan pustaka pihak ketiga. Hal ini menjamin penghematan penggunaan memori server hosting (Domainesia) dan memastikan fitur pengolahan nilai berjalan maksimal pada server tanpa dukungan lingkungan Python.

### 7. Pengamanan Prosedur Reset Password default `pbresetpass`
Untuk meminimalisir celah keamanan pada akun baru atau akun yang sedang dipulihkan oleh administrator, kata sandi bawaan (*default reset password*) diubah dari format sebelumnya (`demo1234`) menjadi format unik **`pbresetpass`**. Password dienkripsi menggunakan algoritma pengacak satu arah berstandar industri *BCRYPT* sebelum disimpan ke database.

### 8. Perbaikan Efek Getar (Jitter/Trembling) pada Antarmuka
Dilakukan perbaikan visual pada berkas gaya CSS (`css/style.css` dan `css/dashboard.css`). Efek getaran tidak stabil (*jittering*) yang sebelumnya terjadi ketika kursor pengguna diarahkan (*hover*) ke elemen tombol (*buttons*), kartu statistik (*stat-cards*), atau sidebar menu telah dihilangkan dengan menyesuaikan properti transisi transformasi CSS (`transform` dan `translateY`).

---

### Ringkasan Tabel Perbandingan Fitur Sebelum & Sesudah Revisi

| Aspek / Fitur | Sebelum Revisi | Setelah Revisi (Hasil Pembaruan) |
|---|---|---|
| **Otoritas Akun** | Admin dapat menghapus data guru/siswa; Admin baru terdaftar publik | Superadmin memegang kontrol penuh; Admin baru didaftarkan secara internal oleh Superadmin |
| **Penghapusan Akun** | *Hard Delete* (data langsung terhapus dari database) | *Soft Delete* (data ditampung ke Tempat Sampah dan bisa dipulihkan/direstore) |
| **Keamanan CBT** | Tidak ada deteksi kecurangan / proteksi browser | Proteksi Fullscreen, deteksi perpindahan tab, penonaktifan tombol F12, klik kanan, dan salin-tempel |
| **Identitas Guru** | Menggunakan istilah NIP | Menggunakan istilah NUPTK (16 digit) |
| **Format Input Nilai** | Menggunakan format Excel (.xlsx) | Menggunakan format CSV (.csv) agar kompatibel di semua server hosting |
| **Password Bawaan** | Menggunakan password default `demo1234` | Menggunakan password default `pbresetpass` yang terenkripsi BCRYPT |
