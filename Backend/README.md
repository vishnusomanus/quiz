# Quiz API (Laravel 13)

REST API for the Quiz Management Platform. MySQL, Laravel Passport, L5-Swagger.

Companion app: [`../Frontend/README.md`](../Frontend/README.md). Product overview: [`../README.md`](../README.md).

## Stack

- PHP 8.2+ / Laravel 13
- MySQL
- Laravel Passport (personal access tokens)
- L5-Swagger (OpenAPI 3)
- Docker Compose: `app`, `nginx`, `mysql`, `phpmyadmin`

## 1. Docker setup

Run commands from **this folder** (`Backend/`), where `docker-compose.yml` lives.

### 1.1 Environment

```bash
cp .env.example .env
```

Dummy values are already in `.env.example`. Docker Compose overrides `DB_*` so the `app` container talks to the `mysql` service (`cs_mysql`).

### 1.2 Start services

```bash
docker compose up -d --build
```

Wait until MySQL is healthy (`docker compose ps`).

### 1.3 App key, migrate, seed

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
docker compose exec app php artisan storage:link --force
```

`storage:link` exposes uploaded option images at `http://localhost:8000/storage/...`. Without it, image answers will 404.

### 1.4 Passport

```bash
docker compose exec app php artisan passport:client --personal
```

If you prefer to skip the prompt, set `PASSPORT_PERSONAL_ACCESS_CLIENT_ID` and `PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET` in `.env` first.

### 1.5 URLs

| Service | URL |
| --- | --- |
| API | `http://localhost:8000/api/v1` |
| Swagger | `http://localhost:8000/api/documentation` |
| phpMyAdmin | `http://localhost:8081` (user `spip` / password `secret`) |

### 1.6 Without Docker

Use PHP 8.2+, Composer, and a local MySQL matching `.env`. Then:

```bash
composer install
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link --force
php artisan passport:client --personal
php artisan serve
```

## 2. Seed data

`DatabaseSeeder` runs `UserSeeder` then `QuizSeeder`.

### Users (password `password`)

| Email | Role | Country |
| --- | --- | --- |
| `admin@example.com` | admin | none |
| `customer@example.com` | customer | IN |
| `anita@example.com` | customer | IN |
| `us.customer@example.com` | customer | US |
| `james@example.com` | customer | US |

### Quizzes

| Title | Countries | Published | Contents |
| --- | --- | --- | --- |
| India General Knowledge | IN | yes | Text, single-choice |
| United States Civics | US | yes | Text, single-choice |
| World Capitals | none (worldwide) | yes | Text, single-choice |
| Science Basics | none | yes | Mixed single- and multiple-choice |
| World Flags | none | yes | Image options (`image_url` from flagcdn) |
| Ocean Trivia | none | no | Draft — admin catalog only |

`QuizSeeder` updates existing rows by slug. World Flags is recreated so option images stay in sync. First-time migrate and seed are in [section 1.3](#13-app-key-migrate-seed). To re-run or wipe the database, use [section 9](#9-docker-commands).

## 3. Roles and catalog rules

- **Admin** — create/update/delete quizzes, upload option images, view any leaderboard. Admins **cannot** start or submit attempts.
- **Learner (customer)** — list published quizzes for their country (plus worldwide), start/resume, answer, submit, view their result and the leaderboard.

Country assignment:

- One or more ISO 3166-1 alpha-2 codes (for example `IN`, `US`).
- Omit `countries` (or send an empty list) for a worldwide quiz.

Retakes are not allowed. Starting or submitting after a completed or expired attempt returns that attempt instead of creating another.

## 4. Question types and option images

### Types

| `type` | Learner UI | Validation |
| --- | --- | --- |
| `single_choice` | Radio (one pick) | Exactly one option with `is_correct: true` |
| `multiple_choice` | Checkboxes (several picks) | At least one option with `is_correct: true` |

### Option payload

Each option may have a `label`, an `image_url`, or both. At least one of those is required.

```json
{
  "label": "India",
  "image_url": "http://localhost:8000/storage/option-images/abc.webp",
  "is_correct": true
}
```

`image_url` is stored on `question_options`. Learners never receive `is_correct` on quiz detail.

### Upload endpoint

Admins upload a file; the API stores it on the **public** disk and returns a URL to put on the option.

```http
POST /api/v1/uploads/option-images
Authorization: Bearer {admin token}
Content-Type: multipart/form-data

image=<file>
```

Success `200`:

```json
{
  "status": true,
  "message": "Image uploaded",
  "data": {
    "url": "http://localhost:8000/storage/option-images/....webp",
    "path": "option-images/....webp"
  }
}
```

Validation:

- Field name: `image`
- MIME: jpeg, jpg, png, gif, webp
- Max size: **2048 KB (2 MB)**
- Admin only

The React admin form is upload-only (no URL paste). Seeded World Flags questions use public CDN URLs so they work without uploading files.

## 5. API

Prefix: `/api/v1`. All mutating quiz/attempt routes need `Authorization: Bearer {token}` except login and public reads.

### Auth

| Method | Path | Auth | Description |
| --- | --- | --- | --- |
| POST | `/auth/login` | public | `{ email, password }` → user + token |
| POST | `/auth/logout` | auth | Revoke current token |
| GET | `/user` | auth | Current user |

### Users

| Method | Path | Auth | Description |
| --- | --- | --- | --- |
| GET | `/users` | auth | Paginated users |
| GET | `/users/{uuid}` | auth | User detail (UUID) |

### Quizzes

| Method | Path | Auth | Description |
| --- | --- | --- | --- |
| GET | `/quizzes` | public | Catalog. Learners pass `user_id` + `country`. Admins see unpublished; filter with `is_published`. |
| GET | `/quizzes/{id}` | public | Detail + questions/options. `is_correct` admin-only. |
| POST | `/quizzes` | admin | Create quiz + questions + options |
| PUT/PATCH | `/quizzes/{id}` | admin | Update (replaces questions/options when sent) |
| DELETE | `/quizzes/{id}` | admin | Soft delete |

List items include `is_completed`, `in_progress_attempt_id`, and `completed_attempt_id` when `user_id` is provided.

### Option image upload

| Method | Path | Auth | Description |
| --- | --- | --- | --- |
| POST | `/uploads/option-images` | admin | Multipart `image`. See [section 4](#4-question-types-and-option-images). |

### Attempts

| Method | Path | Auth | Description |
| --- | --- | --- | --- |
| POST | `/quizzes/{id}/attempts` | learner | Start or resume. Returns completed/expired attempt if one exists (no retake). |
| GET | `/attempts/{id}` | learner | Current question while in progress; full result when finished |
| POST | `/attempts/{id}/answers` | learner | Save the current answer. Enforces question order and both timers. |
| POST | `/quizzes/{id}/submit` | learner | Bulk submit / finalize |

Timers:

- Quiz `time_limit_seconds` — overall quiz clock, started when the attempt is created.
- Quiz `per_question_time_seconds` — default per-question clock.
- Question `time_limit_seconds` — optional override for that question. An unanswered question whose window expired is treated as skipped.

### Leaderboard

| Method | Path | Auth | Description |
| --- | --- | --- | --- |
| GET | `/quizzes/{id}/leaderboard` | public | Ranked by score, then fastest `completed_at - started_at` |

## 6. Logging

Custom daily channels in `config/logging.php`:

| Channel | File |
| --- | --- |
| `auth_errors` | `storage/logs/auth/auth_errors-YYYY-MM-DD.log` |
| `user_errors` | `storage/logs/user/user_errors-YYYY-MM-DD.log` |
| `quiz_errors` | `storage/logs/quiz/quiz_errors-YYYY-MM-DD.log` |

Controllers log full error details (message, trace, line). API responses include stack details only when `APP_ENV=local`.

## 7. Swagger

L5-Swagger generates OpenAPI 3 docs. Title: **Quiz API**.

| Piece | Location |
| --- | --- |
| Config | `config/l5-swagger.php` |
| Info | `app/OpenApi/BaseDocumentation.php` |
| Auth | `App\Http\Controllers\Api\V1\Swagger\AuthSwagger` |
| Users | `App\Http\Controllers\Api\V1\Swagger\UserSwagger` |
| Quizzes / attempts / leaderboard | `App\Http\Controllers\Api\V1\Swagger\QuizSwagger` |
| Option image upload | `App\Http\Controllers\Api\V1\Swagger\UploadSwagger` |

Regenerate:

```bash
docker compose exec app php artisan l5-swagger:generate
```

Browse: `http://localhost:8000/api/documentation`

## 8. Tests

Feature coverage lives in `tests/Feature/` (auth, users, quizzes, attempts, image options, upload validation). PHPUnit uses in-memory SQLite; it does not need the Docker MySQL.

```bash
# host (after composer install)
./vendor/bin/phpunit

# or Docker
docker compose exec app php artisan test
```

Quiz-only:

```bash
./vendor/bin/phpunit tests/Feature/QuizApiTest.php
```

## 9. Docker commands

From `Backend/`:

```bash
docker compose up -d
docker compose up -d --build
docker compose down
docker compose down -v          # also drops the MySQL volume
docker compose logs -f app
docker compose logs -f nginx
docker compose exec app php artisan <command>
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
docker compose exec app php artisan migrate --seed --force
docker compose exec app php artisan migrate:fresh --seed --force   # wipes the database
docker compose exec app php artisan l5-swagger:generate
docker compose exec app php artisan storage:link --force
```

Without Docker, the same artisan commands run on the host (`php artisan migrate --force`, and so on).
