# SPCC AI: Auth module (Laravel + Blade + Tailwind)

Login, register with an SPCC email, 6-digit email OTP, "Account Verified", and role-based redirect
(student / teacher / admin), built to match the UI mock.

## 1. Install

Extract this zip **into the root of your Laravel project** (the folder that has `artisan`).
Say yes to overwrite when asked. These three files are replaced:

- `routes/web.php`
- `app/Models/User.php`
- `database/seeders/DatabaseSeeder.php`

Everything else is new.

> `tests/Feature/ExampleTest.php` from the skeleton expects `/` to return 200. `/` now redirects to `/login`, so delete that test or change it to expect a 302.

## 2. Configure `.env`

```env
APP_NAME="SPCC AI"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=spcc_ai
DB_USERNAME=root
DB_PASSWORD=

# Testing: OTP codes are written to storage/logs/laravel.log instead of being emailed
MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@spcc.edu.ph"
MAIL_FROM_NAME="SPCC AI"
```

Create the empty `spcc_ai` database in MySQL first.

## 3. Run

```bash
php artisan migrate
php artisan db:seed
npm install
npm run dev          # terminal 1
php artisan serve    # terminal 2
```

Open http://localhost:8000.

## 4. Test accounts (password: `password`)

| Role    | Email                       |
|---------|-----------------------------|
| Admin   | admin_spcc@spcc.edu.ph      |
| Teacher | santos_maria@spcc.edu.ph    |
| Student | delacruz_juan@spcc.edu.ph   |

Change or remove these before deploying.

## 5. Try the register flow

1. Go to **Register**, fill in the form, press **Send OTP**.
2. Open `storage/logs/laravel.log` and copy the 6-digit code from the email text.
3. Enter it on the OTP screen, press **Verify**, then **Go to Login**.

For real emails, switch `MAIL_MAILER` to `smtp` and fill in `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`
(use your school's SMTP or a Google Workspace app password).

## 6. Your logo

Put the real seal at `public/images/spcc-logo.png`. It replaces the placeholder badge everywhere automatically.

## How it works

- **Only `@spcc.edu.ph` emails** can register (`app/Rules/SpccEmail.php`), checked in the browser and on the server.
- The account is created **only after the OTP is verified**. Until then the details wait in the session.
- Codes expire after 10 minutes, are stored only as a hash, lock after 5 wrong tries, and can be resent every 30 seconds
  (`app/Services/OtpService.php`).
- Self-registration always creates a **student**. Teacher and admin accounts are assigned by an admin
  (seeder now, the User Management screen later).
- Sign-in is rate limited (5 tries per email + IP) and deactivated accounts (`status = inactive`) are refused.
- Roles: protect any route with `->middleware(\App\Http\Middleware\EnsureRole::class . ':teacher')`.

## Not included yet

- **Forgot password?** link is in the UI but not wired up.
- The student / teacher / admin dashboards are placeholders (`resources/views/dashboard.blade.php`). They are the next step.
