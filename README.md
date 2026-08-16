# Terra Lestari — Sajian Baginda

Sistem dalaman untuk Terra Lestari, bermula dengan projek pertama **Sajian
Baginda** (F&B, masakan Kelantan). Dibina Laravel + MySQL, sedia untuk deploy
ke shared hosting cPanel (Exabytes).

## Ciri-ciri

- **POS** — cashier key-in order, checkout, cetak/papar resit (thermal-printer
  friendly layout).
- **Belian barang** — Ben (owner) rekod belian barang basah harian + upload
  gambar resit.
- **Buka/Tutup Hari** — tandakan mula & akhir hari niaga dengan modal awal &
  cash akhir.
- **Dashboard** — jualan harian/mingguan, untung kasar, trend 7 hari.
- **Role-based access** — owner (Ben) full access semua modul; cashier hanya
  akses POS.

## Struktur data

`Company` (Terra Lestari) → `Project` (Sajian Baginda, dan projek lain akan
datang) → `Category` / `Product` / `Order` / `Purchase` / `DailySession`.
Struktur ni sedia untuk tambah projek/outlet lain di bawah Terra Lestari pada
masa hadapan.

## Setup local development

```bash
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # atau setup MySQL dalam .env
php artisan migrate --seed
php artisan serve
```

Login default lepas seed: `ben@sajianbaginda.com` / `password`.

## Deploy production

Lihat [DEPLOYMENT.md](DEPLOYMENT.md) untuk panduan deploy ke cPanel Exabytes.
