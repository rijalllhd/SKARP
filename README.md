# SKARP IoT Dashboard — Laravel

Dashboard monitoring sensor IoT realtime berbasis Laravel + Blade + Firebase JS SDK.

## Tech Stack
- **Framework:** Laravel 11
- **Styling:** Tailwind CSS (CDN)
- **Chart:** Chart.js (CDN)
- **Realtime:** Firebase JS SDK v10 (CDN) — `onValue` listener
- **WA Bot:** Fonnte API via Laravel HTTP Client
- **Scheduler:** Laravel Task Scheduler (cron)

---

## Instalasi

### 1. Install dependencies
```bash
composer install
```

### 2. Setup environment
```bash
cp .env.example .env
php artisan key:generate
```

Isi variabel berikut di `.env`:

| Variabel | Cara dapat |
|---|---|
| `FIREBASE_API_KEY` | Firebase Console → Project Settings → General |
| `FIREBASE_DATABASE_URL` | Firebase Console → Realtime Database → Data tab |
| `FIREBASE_MESSAGING_SENDER_ID` | Project Settings → General → Your apps |
| `FIREBASE_APP_ID` | Project Settings → General → Your apps |
| `FIREBASE_CREDENTIALS` | Project Settings → Service Accounts → Generate new private key → simpan ke `storage/app/firebase-credentials.json` |
| `FONNTE_TOKEN` | [fonnte.com](https://fonnte.com) → Dashboard → Token |
| `WA_TARGET_NUMBER` | Nomor tujuan, format: `628xxxxxxxxxx` |
| `ALERT_AMONIA_THRESHOLD` | Ambang amonia untuk peringatan tinggi, default: `25` ppm |
| `ALERT_THI_THRESHOLD` | Ambang THI untuk peringatan tinggi, default: `83` |
| `ALERT_COOLDOWN_MINUTES` | Jeda pengiriman ulang peringatan, default: `30` menit |
| `RECAP_SECRET` | String acak bebas, contoh: `skarp-iot-2025-secret` |

### 3. Jalankan
```bash
php artisan serve
```
Buka [http://localhost:8000](http://localhost:8000)

---

## WhatsApp Recap

### Test manual
```bash
php artisan whatsapp:test

# Atau dengan isi pesan sendiri
php artisan whatsapp:test "Tes WhatsApp dari SKARP"
```

Command tersebut mengirim langsung ke `WA_TARGET_NUMBER` dan tidak membutuhkan koneksi Firebase. Untuk menguji rekap lengkap yang membaca Firebase:
```bash
php artisan sensor:send-daily-recap
```

### Jadwal otomatis
Tambahkan ke crontab server:
```
* * * * * php /path/ke/project/artisan schedule:run >> /dev/null 2>&1
```

Jadwal default:

- Rekap setiap hari pukul **20:00 WIB**.
- Pengecekan nilai THI/amonia setiap 10 detik. Jika nilainya melewati ambang tinggi, WhatsApp dikirim dengan jeda ulang sesuai `ALERT_COOLDOWN_MINUTES`.

Pada Windows untuk pengembangan, jalankan proses scheduler terpisah:
```bash
php artisan schedule:work
```

Jadwal aplikasi didefinisikan di `routes/console.php`.

---

## Struktur Firebase
Data dari Arduino dikirim ke path:
```
SKARP/SensorData/
  ├── Amonia_PPM
  ├── Amonia_Raw
  ├── Kelembapan
  ├── Suhu
  └── THI
```

---

## Deploy ke Shared Hosting / VPS

1. Upload semua file (kecuali `node_modules`, `.git`)
2. Jalankan `composer install --no-dev`
3. Set `.env` di server
4. `php artisan key:generate`
5. Pastikan `storage/` dan `bootstrap/cache/` writable
6. Setup crontab untuk scheduler
7. Custom domain: arahkan domain ke folder `public/`
