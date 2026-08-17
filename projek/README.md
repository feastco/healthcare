# PKU Healthcare Operations Management System

> **Portfolio / evaluation simulation** — not production software, not an EMR/SIMRS replacement.
> All data is synthetic. See the [Medical Domain Disclaimer](#medical-domain-disclaimer).

## Overview

A healthcare operations management system built as a modular monolith with a layered
architecture (ADR-001). It covers registration, scheduling, billing, payment, and audit
for a hospital operations scenario.

- **API**: RESTful JSON under `/api/v1` (ADR-010), token authentication via Laravel Sanctum.
- **Web UI**: server-rendered Blade application (dashboard, master data, operations, monitoring,
  administration) styled with the TailAdmin shell (Tailwind CSS 4 + Alpine.js 3 + Vite 8).
- **Database**: PostgreSQL 18 with a `btree_gist` exclusion constraint that prevents doctor
  schedule overlap (ADR-003, ADR-007).
- **Authorization**: role + permission based via `spatie/laravel-permission`, enforced
  server-side with middleware and Policy/Gate on every operation (ADR-005).
- **Financial integrity**: invoice/payment amounts are decimal (`numeric`) and computed
  server-side with BCMath inside `DB::transaction()` boundaries (ADR-006, RULES).
- **Audit**: synchronous, sanitized audit trail written inside the same transaction as the
  target mutation (ADR-009).

## Tech Stack

| Component      | Version (locked in)                  |
|----------------|--------------------------------------|
| PHP            | 8.3+ (verified on 8.4.24)            |
| Laravel        | 13.x (`laravel/framework: ^13.17`)   |
| PostgreSQL     | 18                                   |
| Laravel Sanctum| `^4.3`                              |
| spatie/permission | `^8.3`                           |
| Node.js        | 20.19+ / 22.12+ (verified on 24.14.0)|
| Vite / Tailwind| Vite `^8.0` / Tailwind CSS `^4.0`    |

## Modules

- **Master data**: patients, doctors, departments, doctor schedules.
- **Scheduling**: appointments with a state machine
  (`WAITING → IN_PROGRESS → COMPLETED`, plus `CANCELLED`); doctor-owner transition
  authorization; PostgreSQL exclusion constraint guarantees no overlapping appointment for
  the same doctor.
- **Billing & payment**: invoice generation, payment processing with overpayment protection
  and outstanding balance computed via BCMath.
- **Audit logs**: read-only monitoring for the `IT/Admin` role.
- **Administration**: users, roles, and role-permission assignment (`Super Admin` only).
- **My Queue**: doctor daily queue with start/complete service actions.

### Roles

`Super Admin`, `Registration Staff`, `Doctor`, `Cashier`, `IT/Admin`.

## Requirements

- **PHP** 8.3+ with extensions: `pdo_pgsql`, `pgsql`, `bcmath`, `mbstring`, `openssl`,
  `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `curl`, `intl`.
- **Composer** 2.x.
- **PostgreSQL** 18 (the `btree_gist` extension is created automatically by the
  `2026_08_14_110001_add_appointment_exclusion_constraint` migration).
- **Node.js** 20.19+ (Vite 8 requirement; 22.12+ recommended) and **npm**.

## Local Setup (ADR-012 — Local Development / Evaluation)

1. **Install PHP dependencies**

   ```bash
   composer install
   ```

2. **Prepare PostgreSQL databases**

   Create two databases: `pku_healthcare` (application) and `pku_healthcare_test` (tests).
   The connection is owned by the `postgres` role by default.

3. **Configure the environment**

   ```bash
   cp .env.example .env
   ```

   Edit `.env`:

   - `DB_CONNECTION=pgsql`, `DB_DATABASE=pku_healthcare`, `DB_USERNAME`, `DB_PASSWORD`.
   - `DEMO_ADMIN_PASSWORD` — the demo account password (see [Demo accounts](#demo-accounts)).
   - `APP_URL=http://localhost:8000` matches the value used by `php artisan serve`.

4. **Generate the application key**

   ```bash
   php artisan key:generate
   ```

5. **Install and build frontend assets**

   ```bash
   npm install
   npm run build
   ```

   During development you can run `npm run dev` instead (Vite dev server).

6. **Migrate and seed**

   ```bash
   php artisan migrate --seed
   ```

7. **Start the application**

   ```bash
   php artisan serve
   ```

   Open `http://localhost:8000`.

## Demo Accounts

- `superadmin@example.com` — role **Super Admin**.
- `test@example.com` — no special role.

Both accounts use the password you set in the `DEMO_ADMIN_PASSWORD` environment variable.

- The password is **read from the environment only** — it is never hard-coded in the
  seeder or source (ADR-012).
- The demo users are **created only when `DEMO_ADMIN_PASSWORD` is set**. If it is empty or
  unset, `php artisan db:seed` still seeds roles and permissions but **skips** the demo
  accounts.
- The stored password is a **bcrypt hash** — never stored in plaintext.
- No other role accounts (Registration Staff, Doctor, Cashier, IT/Admin) are seeded.

## Tests

```bash
php artisan test
```

- The test suite runs against `.env.testing`, which uses **PostgreSQL** (`pku_healthcare_test`)
  with `RefreshDatabase` — there is no SQLite fallback.
- Test-only values (including a test `DEMO_ADMIN_PASSWORD`) live in `.env.testing`, which is
  git-ignored.
- Code style:

  ```bash
  vendor/bin/pint --test
  ```

- Current verified baseline: **489/489 tests, 1421 assertions** (see `CHANGELOG`).

## Frontend Build

```bash
npm run build    # production build (vite build)
npm run dev      # development server (hot reload)
```

## Security

- `.env` and all `.env.*` files are git-ignored; only `.env.example` (placeholders, no
  secrets) is tracked.
- No credentials are committed; seed data is entirely synthetic (fake names/emails/NIKs,
  no real healthcare data, no external healthcare integration).
- Authorization is enforced server-side (route middleware + Policy/Gate) on every mutation —
  hiding a UI button is never the authorization boundary.

## Destructive Commands Warning

`php artisan migrate:fresh` and any `DROP DATABASE` / `DROP TABLE` operation destroy data
irreversibly. Only run them against disposable local evaluation databases.

## Medical Domain Disclaimer

This project is a **simulation for portfolio and evaluation purposes**. It is not a
production hospital information system, not an EMR/SIMRS replacement, and not a substitute
for certified clinical, billing, or administrative systems. Any data it processes is
synthetic and must not be used for real patient care or record keeping.