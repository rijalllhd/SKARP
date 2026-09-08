# Deployment development di Dockploy

Repository ini siap dideploy sebagai **Docker Compose**. Satu deployment membuat empat container: `web` (Nginx), `app` (Laravel/PHP-FPM), `scheduler` (jadwal 10 detik), dan `queue`. Tidak ada service database — sesi/cache pakai driver `file` dan queue pakai `sync`. Folder `storage` berada pada named volume `laravel_storage` sehingga tetap ada setelah redeploy.

## 1. Persiapan repository

Push seluruh perubahan ini ke repository Git. Jangan commit `.env` atau `firebase-credentials.json`; keduanya berisi rahasia dan sudah ada di `.gitignore`/`.dockerignore`.

## 2. Buat project di Dockploy

1. Buat **Project** lalu **Service → Compose**.
2. Hubungkan repository dan branch development Anda.
3. Pilih file compose `docker-compose.yml` dan aktifkan build saat deploy.
4. Tambahkan domain ke service **web**, port internal **80**, lalu aktifkan HTTPS/Let's Encrypt.
5. Jangan publikasikan port host untuk `database`, `app`, `scheduler`, atau `queue`. Dokploy akan merutekan domain ke service `web` melalui jaringan internalnya.

## 3. Isi environment variables di Dockploy

Masukkan seluruh nilai di bawah pada halaman Environment Dockploy. Compose meneruskannya ke semua service Laravel; file `.env` di server tidak diperlukan. Aplikasi tidak memakai database, jadi tidak ada variabel `DB_*`.

| Variabel | Nilai/panduan |
| --- | --- |
| `APP_KEY` | Buat lokal dengan `php artisan key:generate --show`; hasilnya diawali `base64:`. |
| `APP_URL` | URL HTTPS final, mis. `https://dev-dashboard.example.com`. |
| `APP_ENV` | `development` atau `production`; untuk server publik disarankan `production`. |
| `APP_DEBUG` | `false` pada server yang dapat diakses publik. |
| `FIREBASE_*` | Salin dari Firebase Project Settings. |
| `FONNTE_TOKEN`, `WA_TARGET_NUMBER` | Token Fonnte dan nomor format `628…`. |
| `RECAP_SECRET` | String acak panjang khusus endpoint rekap. |
| `ALERT_AMONIA_THRESHOLD`, `ALERT_THI_THRESHOLD`, `ALERT_COOLDOWN_MINUTES` | Opsional; default `25`, `83`, `30`. |

Gunakan nilai dari `.env.example` sebagai daftar lengkap. Aplikasi berjalan tanpa database (cache/sesi `file`, queue `sync`), jadi tidak ada variabel `DB_*` yang perlu diisi.

## 4. Firebase Admin credentials

Unduh service-account JSON dari Firebase Console. Compose sudah memasang named volume `laravel_storage` yang sama pada service `app`, `scheduler`, dan `queue`. Upload file JSON sekali ke path berikut di volume tersebut:

`/var/www/html/storage/app/firebase-credentials.json`

Pastikan user container dapat membacanya. Setelah deployment pertama, gunakan file manager/terminal Dockploy untuk upload JSON ke path tersebut. Jangan masukkan isi JSON ke Git atau environment variable biasa.

`FIREBASE_CREDENTIALS` sudah menunjuk pada path container tersebut. Jika memakai mount path lain, ubah variabel itu ke path absolut yang sama di ketiga service.

## 5. Deploy dan verifikasi

Deploy. Saat container `app` pertama kali hidup, ia otomatis membuat symbolic link `public/storage` dan menyimpan cache konfigurasi. Cek berikut setelah deployment:

1. Buka `https://domain-anda/up`; harus mengembalikan status sehat Laravel.
2. Buka dashboard dan pastikan data Firebase tampil.
3. Lihat log `scheduler`; proses `php artisan schedule:work` harus aktif, karena alert berjalan setiap 10 detik.
4. Jalankan `php artisan whatsapp:test` dari terminal container `app` untuk menguji Fonnte.

## Operasional

- Redeploy aman dan tidak menghapus volume `laravel_storage`.
- Backup berkala JSON Firebase (di volume `laravel_storage`, path `storage/app/firebase-credentials.json`).
- Log diarahkan ke stderr agar terlihat di Dockploy. Untuk menelusuri masalah, cek log service `app`, `scheduler`, dan `queue`.
