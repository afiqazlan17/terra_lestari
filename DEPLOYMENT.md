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
