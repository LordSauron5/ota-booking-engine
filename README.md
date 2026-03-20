# OTA Booking Engine

A multi-step online booking engine built as a technical assessment. The application allows guests to browse room availability, configure their stay, authenticate, and submit a booking — all within a single fluid interface.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12, PHP 8.4.13 |
| Frontend | Vue 3 (Laravel Vue Starter Kit) |
| Routing / SSR bridge | Inertia.js |
| Authentication | Laravel Fortify |
| UI Components | shadcn/ui (Vue port) |
| Styling | Tailwind CSS |
| State Management | Pinia + pinia-plugin-persistedstate |
| Database | SQLite |
| Queue Driver | Database |

---

## Architectural Decisions

### Multi-step form with Pinia

The booking flow is split into five steps: dates, rooms, summary, confirm, and status. All step state lives in a single Pinia store persisted to `sessionStorage`. This means the user can refresh the page at any point without losing their progress, and the state survives the page reload that follows authentication.

### Inertia over a traditional SPA

Rather than building a decoupled API, Inertia serves Vue components directly from Laravel controllers with data passed as props. This eliminates the need for a separate API layer for most of the application. The only true API route is the booking status polling endpoint, which requires a background fetch after the page has loaded.

### property.json as the room data source

Room and pricing data is read from a static `property.json` file at the project root. A dedicated `RoomService` reads this file and caches the result in Laravel's cache layer for one hour, avoiding repeated file reads on every request. Controllers never touch the file directly.

### Server-side price calculation

The frontend displays prices optimistically using Pinia computed values, but the server recalculates every price independently on both the draft and confirm endpoints. Client-submitted prices are ignored entirely. This prevents any price manipulation.

### Draft → pending → confirmed/failed booking lifecycle

Bookings are saved to the database at the summary step as a `draft` before the user authenticates. When the user confirms, the booking transitions to `pending` inside a database transaction with a `lockForUpdate` availability check to prevent overselling. The booking is only marked `confirmed` or `failed` once the channel manager job resolves.

### Guest-to-user claim via session token

Users can complete steps 1–3 as a guest. A `session_token` is attached to the draft booking at creation time. After authentication, a claim endpoint attaches the authenticated user to the draft. Because Fortify regenerates the session on registration, the claim endpoint validates ownership via the booking ID held in the client's Pinia store rather than relying on session token matching.

### Async channel manager via queued job

`ChannelManagerService::book()` is slow and unreliable. Rather than blocking the user's request, the confirm endpoint dispatches a `BookWithChannelManager` job to the database queue and returns immediately. The frontend then polls `/bookings/{id}/status` every three seconds until it receives a terminal state. The job uses `$this->release()` for transient failures rather than rethrowing, which prevents Laravel from logging expected retries as errors. After three attempts the booking is marked as failed and the user is offered a retry option.

### Form Requests for auth and ownership checks

Auth checks, ownership validation, and booking state guards are handled in dedicated Form Request classes (`ClaimBookingRequest`, `ConfirmBookingRequest`, `StoreDraftBookingRequest`) rather than in the controller. Controllers only orchestrate — receive a validated request, call a service, return a response.

### Shared AuthModal component

The login/register modal is extracted into a standalone `AuthModal.vue` component used in both the step 4 confirm flow and the persistent navigation bar. This means the user can authenticate from any step without losing their place.

---

## Setup Instructions

### Prerequisites

- PHP 8.4.13
- Composer
- Node.js 18+
- npm

### Steps

```bash
# 1. Clone the repository
git clone https://github.com/LordSauron5/ota-booking-engine.git
cd ota-booking-engine

# 2. Install PHP dependencies
composer install

# 3. Install Node dependencies
npm install

# 4. Copy the environment file and generate an app key
cp .env.example .env
php artisan key:generate

# 5. Run database migrations
php artisan migrate

# 6. Start the development servers
composer run dev
```

`composer run dev` starts Laravel, Vite, and the queue worker concurrently. The application will be available at `http://localhost:8000`.

### Processing the queue manually (alternative)

If you prefer to control the queue worker separately:

```bash
# Terminal 1
php artisan serve

# Terminal 2
npm run dev

# Terminal 3 — run after each booking submission to process jobs
php artisan queue:work --stop-when-empty

# Or keep it running continuously
php artisan queue:work
```

### Notes

- SQLite is preconfigured. The database file is created at `database/database.sqlite` on first migration.
- `property.json` must be present at the project root. It is the source of truth for room and pricing data.
- Room data is cached for one hour. To clear it during development run `php artisan cache:clear`.