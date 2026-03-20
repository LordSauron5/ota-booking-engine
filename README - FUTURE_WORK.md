# Future Work

This document outlines features and improvements that were intentionally deferred given the MVP time constraint. These would be prioritised in a production version of the application.

---

## Features

### Email confirmation
Confirmed bookings should trigger a transactional email to the guest containing their booking reference, dates, room details, and total. Laravel's `Mail` and `Notification` system would handle this cleanly, dispatched from within the `BookWithChannelManager` job after a successful confirmation. A separate notification would be sent if the booking ultimately fails after all retries.

### User booking history
Authenticated users should be able to view their past and upcoming bookings. This would be a simple query against the `bookings` table filtered by `user_id`, rendered as a dedicated page accessible from the navigation.

### Admin and property manager portal
A back-office interface for managing properties, rooms, bookings, and users. This would include:
- Viewing and filtering all bookings by status, date range, or property
- Manually overriding booking statuses
- Managing room inventory and pricing without editing `property.json` directly
- User account management

### Multiple properties
Currently the application is hardcoded to a single property loaded from `property.json`. A proper multi-property setup would move property and room data into dedicated database tables, allowing multiple properties to be managed independently. The booking flow would need a property selection step or separate URLs per property.

---

## Technical Improvements

### Room availability conflict checking at draft stage
The current soft availability check at the room selection step only looks at `available_count` from the cached JSON. A proper check would query the database for overlapping confirmed and pending bookings for the selected dates before allowing the user to proceed to the summary, giving earlier feedback rather than only failing at the confirm step.

### Retry UI polish for failed channel manager jobs
The current failed state shows a basic "Try again" button. A production implementation would show the specific failure reason where safe to display, indicate how many retry attempts remain, and potentially offer a "notify me when resolved" option for cases where the channel manager is experiencing extended downtime.

### Full mobile responsive polish
The current layout uses Tailwind's responsive utilities but has not been thoroughly tested across a range of mobile devices and screen sizes. The two-column room selection layout, the stepper navigation, and the auth modal all need dedicated mobile review and adjustment.

### Stale draft cleanup
Draft bookings created by guests who abandon the flow accumulate in the database over time. A scheduled artisan command should periodically purge drafts where `session_token_expires_at` is in the past and the booking has no associated `user_id`. This keeps the database clean and ensures stale drafts do not affect availability counts if the soft check is ever extended to include them.

---

## Code Quality

### Remove unused starter kit boilerplate
The Laravel Vue starter kit ships with several pages and components that are unused in this project — the dashboard, settings pages, profile management, and password confirmation flows. These should be removed to keep the codebase clean and reduce confusion for future contributors.

### Test coverage
No automated tests were written during the MVP phase. A production-grade test suite would include:
- Feature tests for each booking controller endpoint covering happy paths, validation failures, auth failures, and concurrency scenarios
- Unit tests for `BookingService` pricing calculations, availability checks, and status transitions
- Unit tests for `RoomService` cache behaviour
- Browser tests (Dusk or Playwright) covering the full end-to-end booking flow including the auth modal and status polling

### Extract `useHttp` and `useFormatters` usage audit
The composables `useHttp` and `useFormatters` were introduced to eliminate duplication across components, but some components may still contain inline versions of these utilities from earlier iterations. A full audit should confirm all components are importing from the shared composables.

### TypeScript strictness
The current TypeScript configuration is permissive in places. Tightening the config and resolving any implicit `any` types — particularly around Inertia's `page.props.auth` — would improve type safety across the frontend.