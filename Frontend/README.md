# Quiz frontend (React + Vite)

SPA for the Quiz Management Platform. Learners take quizzes in the browser; admins manage the catalog.

API: [`../Backend/README.md`](../Backend/README.md). Product overview: [`../README.md`](../README.md).

## Stack

- React 19 + TypeScript + Vite
- Tailwind CSS + shadcn-style UI
- Zustand (session)
- TanStack Query (API cache)
- React Router

## Run locally

Needs the Laravel API on `http://localhost:8000` (see the backend README).

```bash
cp .env.example .env
npm install
npm run dev
```

Open `http://localhost:3000`.

`.env.example`:

```
VITE_API_BASE_URL=/api/v1
```

Vite proxies both `/api` and `/storage` to `http://localhost:8000`, so:

- API calls go to `/api/v1/...`
- Uploaded option images load from `/storage/option-images/...`

If you point `VITE_API_BASE_URL` at another host, that host must also serve `/storage` (or CORS + public URLs).

### Scripts

```bash
npm run dev      # Vite on port 3000
npm run build    # production bundle
npm run lint     # oxlint
```

## Sign-in

Use seeded users from the backend (password `password`):

| Email | Role | Country |
| --- | --- | --- |
| `admin@example.com` | admin | — |
| `customer@example.com` | learner | IN |
| `anita@example.com` | learner | IN |
| `us.customer@example.com` | learner | US |
| `james@example.com` | learner | US |

Admins cannot start a quiz. They land on **Quiz List**. Learners cannot open the admin catalog.

The session (token + user) is stored in `localStorage`. Sign out from the header.

## Routes

| Path | Who | Screen |
| --- | --- | --- |
| `/login` | public | Email / password |
| `/quizzes` | learner | Published quizzes for their country + worldwide |
| `/quizzes/:quizId` | learner | Attend (or resume / view completed result) |
| `/quizzes/:quizId/leaderboard` | learner | Leaderboard |
| `/admin/quizzes` | admin | Catalog, including unpublished |
| `/admin/quizzes/new` | admin | Create quiz |
| `/admin/quizzes/:quizId/edit` | admin | Edit quiz |
| `/admin/quizzes/:quizId/leaderboard` | admin | Leaderboard |

Unauthenticated visits redirect to `/login`. Wrong-role visits redirect to that role's home.

## Learner flow

1. Sign in → quiz list (country-filtered).
2. **Start** or **Resume** a quiz.
3. One question at a time. Single-choice uses radio buttons; multiple-choice uses checkboxes.
4. Options with `image_url` render as a selectable image (label shown underneath when present).
5. Overall timer and per-question timer come from the API. When a question timer hits zero, that question is skipped.
6. Leaving the tab once is allowed; a **second** leave skips the current question.
7. After submit (or expiry), the result screen shows score and a leaderboard link.
8. Completed quizzes cannot be retaken. The list shows **View result** instead of Start.

## Admin flow

1. Sign in → **Quiz List**.
2. Create or edit:
   - Title, description, overall duration, default per-question time
   - Countries (empty = worldwide)
   - Published toggle
   - Questions: type `single_choice` or `multiple_choice`, prompt, optional per-question time override
   - Options: text label and/or image
3. **Image upload** (no URL field):
   - Click **Upload** / **Replace** on an option
   - jpeg, png, gif, or webp
   - Max **2 MB** — larger files show an error on that option and a toast (`This image is too large (X MB). Maximum size is 2 MB.`)
   - **Remove** clears the image from the option (file stays on disk until you replace it)
4. An option needs a label, an image, or both. Single-choice questions need exactly one correct option; multiple-choice need at least one.
5. Save writes the quiz; option payloads include the uploaded `image_url`.
6. Leaderboard is available from the catalog row.

World Flags is seeded with image answers so you can attend as a learner without creating a quiz first.

## Project layout

```
src/
  api/           Auth, quiz CRUD, attempts, image upload
  auth/          Role helpers
  components/    Layout, quiz form, question card, UI primitives
  hooks/         Tab-leave guard
  pages/         Login, learner quizzes, admin catalog
  routes/        Route table + role guards
  store/         Zustand auth + toast
  types/         API TypeScript types
```

Key files:

| File | Role |
| --- | --- |
| `src/api/quizzes.ts` | Quiz CRUD, leaderboard, `uploadOptionImage` |
| `src/api/attempts.ts` | Start/resume, current question, submit answer |
| `src/components/quiz/QuizForm.tsx` | Admin create/edit, image upload + 2 MB validation |
| `src/components/quiz/QuestionCard.tsx` | Attend UI (text + image options) |
| `src/pages/QuizAttendPage.tsx` | Timers, tab leave guard, submit |
| `src/hooks/useQuizTabGuard.ts` | Second tab-leave skips the question |
