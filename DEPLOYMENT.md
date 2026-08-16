# Deploy ke Exabytes cPanel (AI Pro)

Sistem ni Laravel + MySQL, dibina untuk jalan kat shared hosting cPanel. Guna
domain/subdomain sedia ada (contoh `pos.kretivco.com` atau domain baru khas
Sajian Baginda).

## 1. Cipta database MySQL

Dalam cPanel → **MySQL Database Wizard**:
1. Cipta database (contoh `cpaneluser_terralestari`)
2. Cipta user database + password kuat
3. Assign user tu ke database dengan **All Privileges**

Simpan nama database, username, password — akan diperlukan dalam `.env`.

## 2. Upload kod

Pilihan A — **Git Version Control** (disyorkan, dalam cPanel):
1. cPanel → **Git Version Control** → **Create**
2. Repository URL: `https://github.com/afiqazlan17/terra_lestari.git`
3. Branch: `main` (atau branch yang dah di-merge)
4. Deploy ke folder di luar `public_html` root (contoh `/home/cpaneluser/sajian-baginda`)

Pilihan B — Upload ZIP:
1. Zip seluruh repo (kecuali `node_modules`, `.git`, `vendor`)
2. Upload & extract guna **File Manager**

## 3. Set Document Root ke folder `public/`

**PENTING**: Laravel punya entry point ialah folder `public/`, bukan root
projek. Jangan point domain terus ke root repo — nanti semua fail source
boleh diakses terus dari browser (risiko keselamatan).

Cara (pilih salah satu, ikut apa cPanel benarkan):
- Dalam **Domains**/**Subdomains**, set "Document Root" terus ke
  `/home/cpaneluser/sajian-baginda/public`
- ATAU kalau tak boleh ubah document root, symlink: masuk `public_html`
  (atau subdomain punya root folder), padam kandungan lama, buat symlink
  fail-fail dalam `public/` ke situ, dan edit `index.php` untuk point
  `require` ke lokasi projek sebenar.

## 4. Composer install

Kalau cPanel ada **Terminal** / SSH access:
```bash
cd ~/sajian-baginda
composer install --no-dev --optimize-autoloader
```

Kalau tiada SSH, run `composer install --no-dev` kat local machine, kemudian
upload folder `vendor/` sekali (guna .zip, sebab banyak fail kecil lambat
kalau upload one-by-one).

## 5. Setup `.env`

Copy `.env.example` ke `.env`, isi:
```
APP_NAME="Sajian Baginda"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://pos.kretivco.com   # tukar ikut domain sebenar

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cpaneluser_terralestari
DB_USERNAME=cpaneluser_dbuser
DB_PASSWORD=<password database>
```

Generate app key (guna Terminal cPanel, atau generate locally dan copy nilai
`APP_KEY` ke `.env` server):
```bash
php artisan key:generate
```

## 6. Migrate & seed database

```bash
php artisan migrate --force
php artisan db:seed --force
```

Ni akan cipta:
- Company **Terra Lestari** + Project **Sajian Baginda**
- Akaun owner: `ben@sajianbaginda.com` / password `password`
  (**tukar password ni serta-merta lepas first login**, guna halaman Profile)
- Contoh menu asas (boleh edit/padam kat halaman Menu)

## 7. Storage link (untuk gambar resit)

```bash
php artisan storage:link
```

Kalau symlink tak jalan sebab batasan hosting, boleh set
`FILESYSTEM_DISK=public` terus dan pastikan `public/storage` accessible, atau
guna cPanel File Manager untuk buat symlink manual.

## 8. Permission fail

Pastikan folder ni writable oleh web server (biasanya `750` atau `775`):
```bash
chmod -R 775 storage bootstrap/cache
```

## 9. Optimize (production)

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Nota**: Lepas ni, sebarang perubahan `.env` kena run `php artisan
config:clear` dulu sebelum `config:cache` semula, kalau tak perubahan tak
akan reflect.

## Bila ada update kod

1. Pull/deploy kod terbaru (Git Version Control → Deploy, atau upload semula)
2. `composer install --no-dev --optimize-autoloader` (kalau ada dependency baru)
3. `php artisan migrate --force` (kalau ada perubahan database)
4. `php artisan config:cache && php artisan route:cache && php artisan view:cache`

## Akaun default lepas seed

| Role | Email | Password |
|---|---|---|
| Owner (Ben) | ben@sajianbaginda.com | password |

Staff/cashier accounts kena dicipta oleh owner sendiri kat halaman **Staff**
lepas login — takde public registration untuk elak orang luar daftar akaun.

---

## Realiti deployment di Exabytes (cPanel AI Pro, tiada Terminal/SSH)

Plan hosting ni **takde Terminal/SSH access**. Semua command CLI (composer,
artisan) kena jalan melalui **Cron Jobs** (run sekali, dengan jadual masa
akan datang, lepas tu padam job tu). Berikut nota penting hasil deployment
sebenar:

### Setup yang confirmed berfungsi

- **Repo location**: `/home/kretivco/repositories/terra_lestari` (di luar
  `public_html`, guna Git Version Control cPanel, branch `main`)
- **Document root subdomain**: `sajianbaginda.kretiv.co` →
  `/home/kretivco/repositories/terra_lestari/public`
- **PHP binary untuk cron**: `/usr/local/bin/ea-php84` (bukan `php` biasa —
  cron punya `PATH` default terhad)
- **Composer binary**: wujud di `/usr/local/bin/composer`, tapi **tak boleh
  dipanggil dari cron** — network outbound dari proses cron nampak disekat
  (composer install hang tanpa habis, tanpa error). Jangan cuba run
  `composer install` via cron.
- **DNS**: domain `kretiv.co` guna Cloudflare (bukan DNS Exabytes) — rekod
  DNS baru kena ditambah di **Cloudflare dashboard** (proxy status: **DNS
  only**), BUKAN cPanel Zone Editor.
- **`config:cache`**: jangan guna. OPcache PHP-FPM server ni nampak simpan
  bytecode lama fail `bootstrap/cache/config.php` walaupun fail tu berubah
  (`opcache.validate_timestamps` kemungkinan `0`). Biarkan config uncached
  (`.env` dibaca terus setiap request) — cukup untuk trafik kedai, elak
  masalah cache basi yang sangat mengelirukan nak debug.

### Cara update kod (bila ada perubahan)

1. **Aku (Claude) push kod baru ke branch `main`** di GitHub.
2. Buka cPanel → **Git Version Control** → **Manage** repo "Terra Lestari
   System" → tab **Pull or Deploy** → **Update from Remote** (git pull).
3. **Kalau composer.json berubah** (dependency PHP baru) — vendor/ kena
   dibina semula secara **local** (bukan di server) sebab composer tak boleh
   run di server ni:
   - Aku run `composer install --no-dev --optimize-autoloader` di local,
     zip folder `vendor/`, hantar fail zip.
   - Upload ke `repositories/terra_lestari/` (root, bukan dalam `public/`)
     via File Manager, extract, replace folder `vendor/` lama.
4. **Kalau ada migration database baru** — run sekali via Cron Jobs:
   ```
   cd /home/kretivco/repositories/terra_lestari && /usr/local/bin/ea-php84 artisan migrate --force > /home/kretivco/repositories/terra_lestari/deploy-update.log 2>&1
   ```
   (Set jadual 2-3 minit akan datang, check log, **padam cron job lepas
   siap** — jangan biar cron ni kekal berulang.)
5. **Kalau CSS/JS (Tailwind/Blade) berubah** — fail compiled
   (`public/build/`) kena dibina semula secara local (`npm run build`) dan
   upload/extract macam vendor/ di atas (bukan sekadar edit Blade — kelas
   Tailwind baru takkan muncul kalau tak rebuild).
6. **Blade view (.blade.php) yang berubah tanpa kelas Tailwind baru** —
   biasanya auto-refresh sendiri (Laravel compile semula bila fail source
   berubah), tak perlu apa-apa command tambahan.

### Nota keselamatan

- Padam semua fail `deploy*.log` / `diag.log` di root repo lepas setiap
  sesi update — fail ni boleh dedah struktur server.
- Jangan biarkan cron job "one-off" kekal aktif lepas siap digunakan —
  selalu padam di **Cron Jobs** lepas confirm hasil.
