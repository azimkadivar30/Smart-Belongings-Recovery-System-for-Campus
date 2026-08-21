# Smart Belonging System for Campus

A centralized lost & found platform for college campuses. Students report lost items, admins verify and update status, and QR tags help identify smart gadgets (phones, laptops).

## Tech Stack
- HTML5, CSS3, Bootstrap 5, JavaScript (frontend)
- PHP (backend, PDO + prepared statements)
- MySQL (database)
- PHPMailer / mail() for email notifications (to be added in `notifications.php`)

## Pages included in this pass
- `index.php` — Home page (project intro, login/register CTAs)
- `login.php` + `process/login_process.php` — Student & Admin login
- `register.php` + `process/register_process.php` — Student registration
- `dashboard.php` — User dashboard (total/found/pending stat cards + recent items)
- `logout.php`
- `includes/db.php` — PDO database connection
- `includes/auth_check.php` — Session guard for protected pages
- `database/schema.sql` — Full MySQL schema (users, items, notifications)
- `assets/css/style.css` — Design system using your brand palette

## Setup
1. Start Apache + MySQL (XAMPP / WAMP / LAMP).
2. Import the schema:
   ```
   mysql -u root -p < database/schema.sql
   ```
3. Update DB credentials in `includes/db.php` if needed.
4. A working admin account is already seeded by `database/schema.sql`:
   - Email: `smartbelongingsystemadmin@gmail.com`
   - Password: `Asmin@1898`
   To use a different admin password instead, generate a new hash in PHP:
   ```php
   <?php echo password_hash('YourNewPassword', PASSWORD_DEFAULT);
   ```
   Then update the `users` table `password` column for the admin row with that hash.
5. Place the project folder inside `htdocs` (XAMPP) and visit
   `http://localhost/sbs/index.php`.
6. **Optional -- Smart Matching (image hashing):** perceptual image
   hashing (Feature 9) needs Python 3 with Pillow + ImageHash on the
   same machine as PHP, reachable as `python3` (or `python`) on PATH:
   ```
   pip install Pillow ImageHash
   ```
   If this isn't installed, uploads still work fine -- `image_hash`
   just stays NULL for those photos and they're skipped from
   similarity matching until Python/ImageHash are available.
7. Already have a database from an earlier pass? `database/schema.sql`
   is safe to re-run as-is (every change is an idempotent `ADD COLUMN
   IF NOT EXISTS` or a guarded rename). `database/migration_feature10.sql`
   is the same set of changes on their own, if you just want the diff.

## Still to build (next pass)
- `report_item.php` — student's "report a lost item" form
- `my_items.php` — list/track all items reported by the student
- `qr_generate.php` — generate & download QR code for a gadget
- `notifications.php` — in-app notification list + email trigger
- `profile.php` — edit student profile
- `admin/dashboard.php`, `admin/manage_items.php`, `admin/manage_users.php` — full admin panel

## Design tokens
| Role       | Hex        |
|------------|-----------|
| Primary (deep navy) | `#355872` |
| Secondary (mid blue) | `#7AAACE` |
| Highlight (light blue) | `#9CD5FF` |
| Background (paper) | `#F7F8F0` |

Fonts: **Fraunces** (headings) + **Inter** (body), loaded via Google Fonts CDN.
