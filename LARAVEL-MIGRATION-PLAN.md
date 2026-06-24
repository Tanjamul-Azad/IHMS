# IHMS → Laravel + Inertia + React — Migration Plan

Target stack: **Laravel 11 + Inertia.js + React (Vite) + Tailwind**, MySQL/MariaDB (existing
`healthcare_db` schema), built incrementally so the old PHP app keeps working until each
module is replaced.

---

## 0. Toolchain prerequisites (BLOCKER — install before scaffolding)

| Tool | Have | Need |
|------|------|------|
| PHP | 8.0.30 ❌ | **8.2+** |
| Composer | ❌ | required |
| Node / npm | 22 / 10 ✅ | ✅ |
| Git | ✅ | ✅ |

**Recommended:** install **[Laravel Herd](https://herd.laravel.com)** (Windows) — bundles PHP 8.3 +
Composer + a local dev server in one installer. Alternative: upgrade XAMPP to a build with PHP 8.2
and install Composer manually.

Verify after install:
```
php -v          # 8.2+
composer -V
node -v && npm -v
```

---

## 1. Scaffold (once toolchain is ready)

```bash
cd f:/Xamp/htdocs
composer create-project laravel/laravel ihms
cd ihms
composer require laravel/breeze --dev
php artisan breeze:install react        # Inertia + React + Vite + Tailwind + auth scaffolding
npm install
```
Configure `.env` → `DB_DATABASE=healthcare_db` (or a fresh `ihms` DB), then `php artisan migrate`.
App lives at `f:/Xamp/htdocs/ihms`, served via Herd (`ihms.test`) or `php artisan serve` + `npm run dev`.

---

## 2. Schema → Eloquent models (16 tables)

Existing data is trivial (1 test user, 1 patient) → build **clean Laravel migrations** rather than
importing the old dump, fixing the issues noted below. Re-seed test data afterward.

**Core / auth**
- `users` — extend Laravel's default: `first_name`, `last_name`, `email`, `password` (bcrypt — compatible),
  `role` enum(`admin`,`doctor`,`patient`,`blood_bank_admin`), `status`, `last_login_at`.
  Use Laravel's native `email_verified_at` instead of the `email_verified` tinyint.
- `admin_users` — 1:1 User (`department`, `access_level`).
- `doctors` — 1:1 User (`specialization`, `qualification`, `license_number` UNIQUE, `experience_years`,
  `consultation_fee`, `available_days`, `available_hours` JSON cast, `about`, `rating`, `total_ratings`).
- `patients` — 1:1 User (address block, `date_of_birth`, `gender`, `blood_group`, vitals, emergency
  contact, `medical_conditions`, `allergies`, `current_medications`).

**Clinical**
- `appointments` — Patient ↔ Doctor (`appointment_date`, `status`, `reason`, `notes`).
- `doctor_availability` — Doctor hasMany (`day_of_week`, `start_time`, `end_time`).
- `medical_history` — Patient + Doctor (`diagnosis`, `treatment`, `visit_date`, `next_visit_date`).
- `prescriptions` — Patient + Doctor (`medication`, `dosage`, `instructions`, `prescription_date`, `status`).
- `health_logs` — Patient hasMany (vitals: weight, BP, glucose, temp, HR, mood, symptoms).
- `payments` — Patient + nullable Appointment (`amount`, `payment_method`, `transaction_id`, `status`).

**Ambulance**
- `ambulances` (`vehicle_number` UNIQUE, type, driver, location, status, price_per_km).
- `ambulance_bookings` — Ambulance + Patient (pickup, destination, date, time, status).

**Blood bank**
- `blood_banks` (name, address, contacts, `license_number` UNIQUE, `admin_user_id`→User, `operating_hours` JSON).
- `blood_donations` — donor(User) + BloodBank (blood_group, quantity, hemoglobin, BP, pulse).
- `blood_inventory` — BloodBank (blood_group, quantity, expiry, status).
- `blood_requests` — Patient + BloodBank (blood_group, quantity, urgency, dates, status, purpose).

### Issues to fix during migration
- `patients` has duplicate `dob` **and** `date_of_birth` → keep one (`date_of_birth`).
- Roles: 4 roles incl. `blood_bank_admin` → use **spatie/laravel-permission** (or a `role` enum +
  `RoleMiddleware` + Policies) for real access control. **Remove the hardcoded admin credentials** that
  live in the old `index.php`/`login.php`; seed a real admin instead.
- Tables without `updated_at` (appointments, availability, etc.) → set `const UPDATED_AT = null` or add the column.
- Add proper FK `onDelete` behavior and validation (FormRequests) everywhere.

---

## 3. UI: reuse the design system already built
The palette, fonts, and components from `assets/css/theme.css` carry over:
- Port tokens into `tailwind.config.js` (colors `ink/brand/accent/soft/mist`, fonts Plus Jakarta Sans + Inter).
- Rebuild the **landing page** and **auth** (already designed in this repo) as React/Inertia pages first —
  fastest visible win and validates the whole pipeline.
- Lucide → `lucide-react`. AOS → `framer-motion` (now usable, since this is React) for reveal/animation.

---

## 4. Incremental port order (each = working slice, verified before next)
1. Landing + auth (login/register/verify) — proves stack end-to-end.
2. Role-aware dashboards (patient / doctor / admin / blood-bank).
3. Appointments (browse doctors → availability → book → manage) — the interactive centerpiece.
4. Medical records + prescriptions.
5. Health logs, payments.
6. Blood bank (banks, inventory, donations, requests).
7. Ambulance booking.
8. Chatbot.
9. Hardening pass: policies, validation, CSRF (built-in), audit logging, rate limiting, tests, deploy.

---

## 5. Deployment (real product)
VPS + Nginx + PHP-FPM + MySQL (or Laravel Forge / Ploi), HTTPS via Let's Encrypt, `.env` secrets out of
git, `npm run build` for production assets, queue worker + scheduler. Containerize with Docker optionally.
