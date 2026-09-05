# Healthcare Operations Management System (Simulation)

> Portfolio simulation of a healthcare operations management system. **This is NOT a production EMR/SIMRS system** — it's an academic/portfolio project built to demonstrate Laravel backend engineering, domain modeling, and full-stack development for a healthcare workflow.

[![Stack](https://img.shields.io/badge/Stack-PHP%208.4%20%7C%20Laravel%2013%20%7C%20PostgreSQL%2018-blue?style=flat-square)](#tech-stack)
[![GitHub](https://img.shields.io/badge/GitHub-feastco%2Fhealthcare-181717?style=for-the-badge&logo=github)](https://github.com/feastco/healthcare)
[![Project Docs](https://img.shields.io/badge/Docs-PRD%20%7C%20ARCH%20%7C%20ADR-orange)](#documentation)

## ⚠️ Disclaimer

This repository is a **simulation** of a (Penyakit Kencing Manis / Diabetes) healthcare operations management system. It is **not** a real EMR, not production-deployed, and not certified for clinical use. All data is synthetic. Built as a portfolio project to demonstrate Laravel architecture and full-stack engineering.

## 🏗 Project Structure

```
healthcare/
├── projek/                # Laravel application (PHP 8.4, Laravel 13, PostgreSQL 18)
│   ├── app/               # Domain code (controllers, models, services)
│   ├── database/          # Migrations & seeders
│   ├── docs/              # (see note)
│   ├── resources/         # Views (Blade)
│   ├── routes/            # API + web routes
│   └── README.md          # Detailed Laravel app docs
├── docs/                  # Top-level design docs (PRD, ARCHITECTURE, ADR, …)
│   ├── PRD.md
│   ├── ARCHITECTURE.md
│   ├── DATA-MODEL.md
│   ├── API.md
│   ├── DESIGN.md
│   ├── RULES.md
│   ├── TESTING.md
│   ├── IMPLEMENTATION-PLAN.md
│   ├── CHANGELOG.md
│   ├── ADR/               # Architecture Decision Records (001–012)
│   ├── tasks/             # Phase task breakdowns (00–11)
│   └── UI-UX-DESIGN.md
├── AGENTS.md              # Multi-division rules (v3.1)
└── README.md              # ← you are here
```

## 🛠 Tech Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.4 |
| Framework | Laravel 13.x (API + Blade) |
| Database | PostgreSQL 18 |
| Auth | Laravel Sanctum, spatie/laravel-permission |
| Local Env | Laragon (Windows) |
| Build | Vite |
| Testing | PHPUnit |

## ✨ Scope (per PRD v1.3)

- **Patient management** — registration, demographics, history
- **Clinical encounters** — visits, vitals, notes
- **Care plans & protocols** — diabetes-specific pathways
- **Operations** — scheduling, queueing, room assignment
- **Billing & payment** — Phase 06 (next)
- **Release readiness** — Phases 07–11

Phases 00–05 (foundation → encounter flow) are **complete and PASS** per `docs/CHANGELOG.md`. Next: **Phase 06 Billing & Payment**.

## 📚 Documentation

The full design chain lives in `docs/` (single source of truth):

- **`PRD.md`** (v1.3 FINAL) — product requirements
- **`ARCHITECTURE.md`** (FINAL, `butuh_ml: TIDAK`) — system architecture
- **`ADR/ADR-001..012`** — architecture decision records
- **`DATA-MODEL.md`** — entities, relations, indexes
- **`API.md`** — REST endpoints
- **`RULES.md`** — coding & workflow rules
- **`TESTING.md`** — test strategy
- **`IMPLEMENTATION-PLAN.md`** — phase plan (00–11)

> Any code change must be traceable to these documents. Changes go via `CR-xx` (B1).

## ⚡ Quick Start (Laravel app)

```bash
git clone https://github.com/feastco/healthcare.git
cd healthcare/projek
composer install
cp .env.example .env
php artisan key:generate

# Configure DB (PostgreSQL)
# Set DB_CONNECTION=pgsql, DB_DATABASE, DB_USERNAME, DB_PASSWORD

php artisan migrate --seed
php artisan serve
# Open http://localhost:8000
```

## 👤 Author

**Fisco Maulana Ikhwan** — Informatics Engineering (D3), Universitas Dian Nuswantoro
- GitHub: [@feastco](https://github.com/feastco)
- LinkedIn: [fiscomaulanaikhwan](https://www.linkedin.com/in/fiscomaulanaikhwan)
