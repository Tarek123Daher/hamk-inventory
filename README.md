# HAMK Inventory

HAMK Inventory is a Laravel 12 application for recording the HAMK AI & Robotics Club's components, kits, stock movements, and borrowing/returns. The interface uses Blade, directly served CSS and vanilla JavaScript; Node.js and npm are not needed to run it.

## Requirements

- PHP 8.2 or newer with the extensions required by Laravel (PDO SQLite or PDO MySQL, fileinfo, Mbstring, OpenSSL, and XML).
- Composer 2.
- SQLite (included with the default project configuration) or a MySQL/MariaDB database.

## Local installation (Windows PowerShell)

From the project directory:

```powershell
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
```

On a new installation only, generate the application key. Do not regenerate it on a database that already contains encrypted application data:

```powershell
php artisan key:generate
```

The example environment uses SQLite at `database/database.sqlite`. Create that file if it does not exist, then run incremental migrations and link public image storage:

```powershell
if (-not (Test-Path database/database.sqlite)) { New-Item -ItemType File database/database.sqlite }
php artisan migrate
php artisan storage:link
```

Create the first administrator. The password is entered at a hidden prompt and is not stored in source code or command history:

```powershell
php artisan hamk:create-admin
```

Start the server:

```powershell
composer dev
```

Open <http://127.0.0.1:8000>. Stop the server with `Ctrl+C`.

If an administrator already exists but needs a new password, use the email address shown in the Users page or the database query and run:

```powershell
php artisan hamk:reset-admin-password admin@example.com
```

Replace the example email with the existing administrator's address. Both password entries are hidden, must match, and require at least 12 characters; the existing password is never read or displayed.

The `setup` Composer script installs dependencies, copies `.env.example` if needed, and runs migrations; for a first-time install, generate the app key and create the admin as shown above. No seed command creates fictitious users or inventory.

## Database configuration

The default configuration is local SQLite. To use MySQL/MariaDB, set `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in the private `.env` file, create an empty database, then run `php artisan migrate`. Never put real credentials in `.env.example` or a public archive. Do not use `migrate:fresh` on data that must be retained.

## Stock and records

- A component's `quantity` is its database-recorded total. New components begin at zero; add a receipt or a reviewed import to establish recorded units.
- Available quantity is calculated as recorded units minus outstanding borrowing, damaged units, and lost units.
- Issue and stock adjustments reduce/increase recorded units. Damaged/lost movements retain recorded quantity and separately reduce availability. Borrowing/return history does not overwrite recorded quantity.
- Stock movements, kit borrowing, and return records are transaction-protected and retain activity history. Component archiving is used instead of deleting referenced records.
- Categories and kits can be created without seeding sample inventory. Use roles `administrator`, `inventory_manager`, and `viewer`; controller routes enforce permissions.

## Workbook import status and CSV workflow

No `Arduino_Kits_Inventory.xlsx` workbook was found among the workspace or the provided attachments. No real inventory has been imported, and no quantities or kit contents have been inferred from the design mockup. The app includes a guarded CSV import workflow at **Import data**: export each reviewed worksheet as CSV, map its source headings to the canonical headers in `public/inventory-import-template.csv`, preview all rows, then explicitly confirm the import.

Imports require stable SKUs, enforce unique/repeatable records, preserve kit assignment and notes, separate `recorded_quantity` from optional `physical_quantity`, create stock movement history for quantity changes, and report inserted, updated, skipped, and erroneous rows. Re-uploading the exact same file is blocked. Unmapped columns and incomplete rows are surfaced for review rather than guessed. The original `.xlsx` workbook itself is not parsed by this app; it must be reviewed and converted worksheet-by-worksheet first.

## Backups and restore

For SQLite, stop the app before making a consistent file copy. Back up both:

- `database/database.sqlite`
- `storage/app/public/` (uploaded component images)

For example:

```powershell
Copy-Item database/database.sqlite "$env:USERPROFILE\hamk-inventory-backup.sqlite"
Copy-Item storage/app/public "$env:USERPROFILE\hamk-inventory-images" -Recurse
```

Restore by stopping the app, replacing the database and uploaded image files from the protected backup, then starting it again. For MySQL, use `mysqldump` and the corresponding `mysql` restore workflow; back up uploaded images separately. Keep backups private and encrypted when they contain member information.

## Sharing and deployment

- Local use is available only on the owner's computer.
- To share on a trusted local network, run `php artisan serve --host=0.0.0.0 --port=8000` and provide the computer's LAN address. The computer must remain on; configure the firewall and trusted network appropriately. This development server is not suitable for public production hosting.
- For a one-off remote preview, a Cloudflare Quick Tunnel can forward to a loopback-only Laravel server, for example `cloudflared tunnel --url http://127.0.0.1:8001`. Set `APP_DEBUG=false` and `SESSION_SECURE_COOKIE=true` for that server. Anyone with the temporary URL can reach the login page; only share credentials for an account with the intended role (use `viewer` for read-only access). The URL works only while both processes are running and is not production hosting.
- Public access requires deployment to a secured hosting provider with HTTPS, production environment settings, a persistent database/storage configuration, backups, and a web server/PHP process manager. Deployment has not been performed or verified.
- The Android WebView app in `android-app/` requires Android 7.0 or later, a live internet connection, and the website server. It is not an offline inventory app. Build a debug APK with JDK 17 and Android SDK 35 installed by running `.\android-app\build-and-publish.ps1` from PowerShell in the repository root. The script copies the APK to `public\downloads\HAMK-Inventory.apk`. To point the APK at another HTTPS site, pass `-SiteUrl https://your-host.example` to the same script. A Quick Tunnel URL can expire; rebuild the APK whenever the preview URL changes. Android may require allowing APK installation from the app used to open the download. Debug APKs are for private testing and are not production Play Store releases.
- A web app manifest is also provided for browser installation where supported. There is no service worker or offline inventory cache.

## Validation

Run the automated tests with:

```powershell
php artisan test
```

The tests cover authenticated dashboard access, database-driven counts, role enforcement, component creation, receipt recording, borrowing availability, and partial returns.
