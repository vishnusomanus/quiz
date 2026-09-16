# Quiz Management Platform

Laravel 13 REST API (MySQL) + React (Vite) app for creating, taking, and ranking quizzes.

| Folder | What it is |
| --- | --- |
| [`Backend/`](Backend/README.md) | Laravel API, Passport auth, MySQL, Swagger |
| [`Frontend/`](Frontend/README.md) | React admin catalog, in-app attend, leaderboard |

## Features

- **Roles** — admins manage the catalog; learners take quizzes inside the React app (no external redirect).
- **Question types** — single-choice (exactly one correct option) and multiple-choice (one or more correct options).
- **Image answers** — admins upload jpeg/png/gif/webp images (max 2 MB) onto options. Learners see those images while answering.
- **Countries** — assign a quiz to one or more ISO country codes, or leave none for worldwide.
- **Timers** — overall quiz timer and per-question timer, enforced on the server.
- **Attempts** — one question at a time, resume in progress, no retakes after complete/expired.
- **Leaderboard** — ranked by score, then fastest completion time.

## Prerequisites

- Docker & Docker Compose (backend)
- Node.js 20+ (frontend)
- Ports `8000` (API), `3306` (MySQL), `8081` (phpMyAdmin), and `3000` (React) free

## Quick start

### 1. Backend

```bash
cd Backend
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
docker compose exec app php artisan storage:link --force
docker compose exec app php artisan passport:client --personal
```

| Service | URL |
| --- | --- |
| API | `http://localhost:8000/api/v1` |
| Swagger | `http://localhost:8000/api/documentation` |
| phpMyAdmin | `http://localhost:8081` (user `spip` / password `secret`) |

Dummy `.env` values live in `Backend/.env.example`. Docker Compose overrides `DB_*` so the app container talks to the `mysql` service.

`storage:link` is required so uploaded option images are reachable at `/storage/...`. Re-run migrate/seed commands are in [`Backend/README.md`](Backend/README.md).

### 2. Frontend

```bash
cd Frontend
cp .env.example .env
npm install
npm run dev
```

Open `http://localhost:3000`. Vite proxies `/api` and `/storage` to `http://localhost:8000`.

### Seeded users (password `password`)

| Email | Role | Country | What they can do |
| --- | --- | --- | --- |
| `admin@example.com` | admin | none | Catalog CRUD and leaderboards only |
| `customer@example.com` | customer | IN | India + worldwide quizzes |
| `anita@example.com` | customer | IN | India + worldwide quizzes |
| `us.customer@example.com` | customer | US | US + worldwide quizzes |
| `james@example.com` | customer | US | US + worldwide quizzes |

Admins cannot start or submit quiz attempts.

### Seeded quizzes

| Quiz | Audience | Notes |
| --- | --- | --- |
| India General Knowledge | IN | Text, single-choice |
| United States Civics | US | Text, single-choice |
| World Capitals | Worldwide | Text, single-choice |
| Science Basics | Worldwide | Mixed single- and multiple-choice |
| World Flags | Worldwide | Image options (single- and multiple-choice) |
| Ocean Trivia | Unpublished draft | Visible only to admins |

## Question types and images

- `single_choice` — learner picks one option; the quiz must have exactly one correct option.
- `multiple_choice` — learner can pick several; at least one option must be marked correct.
- Each option needs a **label**, an **image**, or both.
- In the admin UI, images are **uploaded** (not pasted as URLs). Allowed types: jpeg, png, gif, webp. Maximum size: **2 MB**. Larger files show a validation error.
- Uploaded files are stored on the public disk and returned as `image_url` on the option payload.

## API overview

Authenticate:

```http
POST /api/v1/auth/login
{ "email": "customer@example.com", "password": "password" }
```

Use the returned Bearer token on protected routes.

| Method | Path | Who | Notes |
| --- | --- | --- | --- |
| POST | `/api/v1/auth/login` | public | Email + password. Returns Passport token. |
| POST | `/api/v1/auth/logout` | auth | Revoke the current token. |
| GET | `/api/v1/user` | auth | Current user. |
| GET | `/api/v1/quizzes?user_id={uuid}&country=IN` | public | Country-filtered catalog. Includes `is_completed`, `in_progress_attempt_id`, `completed_attempt_id`. |
| GET | `/api/v1/quizzes` | admin | Full catalog, including unpublished. Filter with `is_published`. |
| GET | `/api/v1/quizzes/{id}` | public | Detail. `is_correct` only for admins. Options include `image_url`. |
| POST/PUT/PATCH/DELETE | `/api/v1/quizzes` | admin | CRUD. Omit `countries` for worldwide. |
| POST | `/api/v1/uploads/option-images` | admin | Multipart `image` file. Returns `{ url, path }`. Max 2 MB. |
| POST | `/api/v1/quizzes/{id}/attempts` | learner | Start or resume. Completed/expired attempts are returned (no retakes). |
| GET | `/api/v1/attempts/{id}` | learner | One question at a time while in progress. |
| POST | `/api/v1/attempts/{id}/answers` | learner | Record the current answer. Server enforces order and timers. |
| POST | `/api/v1/quizzes/{id}/submit` | learner | Bulk submit / finalize. |
| GET | `/api/v1/quizzes/{id}/leaderboard` | public | Rank, name, score, completion time. |

Interactive schemas: `http://localhost:8000/api/documentation`.

## Assumptions

- Database engine is MySQL only (as specified). Tests use in-memory SQLite.
- `user_id` on the quiz list is the user's UUID (public identifier), not the internal integer id.
- Country filter uses ISO 3166-1 alpha-2 codes. Empty country list on a quiz means worldwide.
- Retakes are **not** allowed. A completed or expired attempt is shown again instead of starting over.
- Overall quiz timer and per-question timer are both enforced server-side; the React UI mirrors remaining time.
- While a quiz is in progress, leaving the browser tab is limited: a second leave skips the current question.

## Tests

```bash
cd Backend
./vendor/bin/phpunit
# or inside Docker:
docker compose exec app php artisan test
```
