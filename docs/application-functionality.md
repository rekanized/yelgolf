# Yelgolf Application Functionality Reference

This document is a durable reference for how the current application works. It is based on the implemented Laravel and Livewire code, the database schema, and the passing test suite.

Last verified: 2026-07-03
Validation command: `php artisan test`

## 1. Product summary

Yelgolf is a Laravel 13 application for:

- browsing imported disc golf courses,
- signing in as a player or admin through one shared login flow,
- importing and refreshing course data from public UDisc course pages,
- starting multiplayer play sessions for a course,
- inviting players into those sessions,
- letting session participants choose a course layout for their own view,
- storing admin-only display preferences for locale and theme.

The frontend is intentionally simple: Blade templates, Livewire components, and CSS served without a JavaScript build pipeline.

## 2. Roles and access model

There are two effective roles in the app:

- `player`
- `admin`

Role checks are handled through the current signed-in user stored in session state.

### Shared login model

- `/login` is the only login page.
- Users can log in with either their `name` or `email`.
- Passwords are checked against the `users` table.
- If multiple users match the login string, admin users are preferred first.

### Admin-only areas

Admins can access:

- `/admin`
- `/settings`
- `/preferences` (POST)

Non-admin behavior:

- unauthenticated visitors are redirected to `/login` for admin-only pages,
- authenticated non-admin users receive `403` on admin-only pages.

### Guest behavior

Guests can:

- browse the home page,
- search courses,
- open course detail pages.

Guests cannot:

- start play sessions,
- access play session pages,
- use admin tools,
- save locale or theme preferences.

## 3. Route map

### Public and shared routes

#### `GET /`

Purpose:

- renders the course directory,
- applies text search across course name, location, and description,
- shows active sessions only when the current signed-in player is the host or a joined participant.

Key behavior:

- guests see no active-session list items,
- results are ordered by course name,
- sessions are ordered by newest `started_at` first.

Source: `routes/web.php`

#### `GET /courses/{course:slug}`

Purpose:

- renders a course detail page with course profile, facts, layouts, and gallery.

Key behavior:

- if a course is missing photos, target type, or holes, the app attempts an on-demand re-import from its stored UDisc URL,
- importer failures are reported but do not block page rendering,
- course holes are always loaded before rendering.

Source: `routes/web.php`

#### `POST /courses/{course:slug}/sessions`

Purpose:

- starts or reuses an active play session for the signed-in host on that course.

Key behavior:

- requires a signed-in current player,
- guests receive `403`,
- redirects to the play session page after creation or reuse.

Source: `routes/web.php`, `app/Services/PlaySessionStarter.php`

#### `GET /sessions/{playSession}`

Purpose:

- renders the play session dashboard through Livewire.

Key behavior:

- only the host or joined participants may open the page,
- invited-but-not-joined players cannot view the session until they join.

Source: `app/Livewire/PlaySessionPage.php`

#### `GET /login`

Purpose:

- renders the shared login form.

Source: `app/Livewire/UserLoginForm.php`

#### `POST /logout`

Purpose:

- signs out the current user from the session-backed player/admin context.

Key behavior:

- removes `current_player_id`,
- removes `admin_authenticated`,
- regenerates the session.

Source: `routes/web.php`

### Admin-only routes

#### `GET /admin`

Purpose:

- renders the course management panel for importing and refreshing UDisc courses.

Source: `app/Livewire/Admin/CourseManager.php`

#### `GET /settings`

Purpose:

- renders the locale/theme settings page.

Source: `resources/views/settings/edit.blade.php`

#### `POST /preferences`

Purpose:

- persists admin locale/theme preferences into session and cookies.

Key behavior:

- only accepts configured locale and theme keys,
- accepts an optional `redirect_to` URL,
- only redirects back to URLs rooted under the application base URL,
- stores values in both session and long-lived cookies.

Source: `routes/web.php`, `config/yelgolf.php`

## 4. Main user-facing workflows

### Course discovery

Implemented behavior:

- the home page lists all imported courses,
- the search box filters on name, location, and description,
- the course page shows descriptive course data, imported facts, holes grouped by layout, and photos,
- if mapping coordinates are present, the page exposes a Google Maps link.

Important note:

- the course page is not read-only from a data perspective; opening it may trigger a best-effort refresh from UDisc when key course fields are missing.

### Player login and logout

Implemented behavior:

- the login form accepts either username or email,
- incorrect credentials keep the user on the form and show a translated validation error,
- successful login stores the current player in session and redirects to `/#course-list`.

Important note:

- there is no separate admin login form; admin status depends entirely on the matched user record.

### Admin course import and refresh

Implemented behavior:

- admins can submit a UDisc course URL through the admin panel,
- the app validates that the input is a URL before invoking the importer,
- importer-specific URL errors are shown back to the admin,
- unexpected importer failures are reported and surfaced as a generic translated failure message,
- imported courses are listed in the same admin screen and can be refreshed individually.

### Play session lifecycle

Implemented behavior:

- a signed-in player can start a session from a course page,
- the current host is auto-attached to the session as `joined`,
- the host can invite other users,
- invited players see pending invites in the Livewire player console,
- invited players can join active sessions from that console,
- once joined, those players can reopen the session from the home page active-sessions list.

Current constraints:

- session creation is tied to a specific course, host user, host session key, and active status,
- there is no implemented flow for ending or archiving a session,
- non-participants are blocked from opening the session page.

### Participant layout selection

Implemented behavior:

- layouts are derived from the course's imported holes,
- each participant can store one selected layout,
- the host selection is stored directly on the play session,
- each invited/joined player selection is stored on the pivot table.

Validation rule:

- submitted layout identifiers must exist in the current course layout options or the request aborts with `422`.

### Settings and preferences

Implemented behavior:

- the settings page is admin-only,
- admins can switch locale between English and Swedish,
- admins can switch theme between light and dark,
- chosen values are written to session and cookies.

Important constraint:

- guest users do not get a customizable locale or theme experience through this flow.

## 5. Livewire surfaces

### `UserLoginForm`

Responsibilities:

- validate credentials,
- resolve a user by name or email,
- prioritize admins when duplicate login strings exist,
- set the current player in session,
- redirect to the course list.

Source: `app/Livewire/UserLoginForm.php`

### `PlaySessionPage`

Responsibilities:

- guard access to session participants,
- load the course, host, players, and layout data,
- manage invite picker state,
- invite eligible players,
- save participant layout selections,
- render the main session dashboard.

Source: `app/Livewire/PlaySessionPage.php`

### `PlayerConsole`

Responsibilities:

- collect the current player's pending active invites,
- poll periodically while rendered,
- allow an invited player to join a still-active session,
- redirect the player into the session on success.

Current placements:

- the player console is rendered on the home page,
- the course detail page,
- the play session page.

Source: `app/Livewire/PlayerConsole.php`

### `Admin\CourseManager`

Responsibilities:

- import a new UDisc course,
- refresh an existing imported course,
- render the list of stored courses for admins.

Source: `app/Livewire/Admin/CourseManager.php`

## 6. Core services and shared infrastructure

### `CurrentPlayerResolver`

Responsibilities:

- read `current_player_id` from the current session,
- resolve it to a `User` model when possible,
- set or clear the current player in the request and application session layers,
- expose an ordered list of available players when needed.

Implementation detail:

- it defensively checks whether the `users` table exists before resolving users.

Source: `app/Services/CurrentPlayerResolver.php`

### `PlaySessionStarter`

Responsibilities:

- create or reuse an active session for a given course/host/session-key combination,
- attach the host as a joined participant.

Important detail:

- authenticated hosts still get a `host_session_key` value based on the current Laravel session ID.

Source: `app/Services/PlaySessionStarter.php`

### `UDiscCourseImporter`

Responsibilities:

- validate supported UDisc course URLs,
- fetch remote course pages,
- extract course data,
- persist course rows,
- sync hole/layout data when richer structured payloads are available.

Observed behavior from code and tests:

- the importer prefers structured React Router payloads when present,
- it falls back to HTML parsing when structured data is unavailable,
- photos are normalized and deduplicated,
- the admin UI uses the same importer for both initial import and updates.

Source: `app/Services/UDiscCourseImporter.php`

### Middleware and view composition

`EnsureAdminAuthenticated`:

- redirects guests to login for admin-only pages,
- aborts with `403` for signed-in non-admin users.

`ApplyUserPreferences`:

- applies locale from session/cookie for admins,
- forces the default application locale for non-admin users.

`AppServiceProvider` view composer:

- shares locale/theme options, current theme, admin-auth state, and current player with all views,
- resolves theme from session/cookie only for admins,
- falls back to the configured default theme for everyone else.

Sources:

- `app/Http/Middleware/EnsureAdminAuthenticated.php`
- `app/Http/Middleware/ApplyUserPreferences.php`
- `app/Providers/AppServiceProvider.php`

## 7. Data model summary

### `users`

Relevant fields:

- `name`
- `email`
- `password`
- `role`

Behavior:

- `role` controls admin access,
- users may host sessions and participate in many sessions.

Source: `app/Models/User.php`

### `courses`

Relevant stored data:

- identity: `name`, `slug`, `udisc_url`, `udisc_id`
- summary: `location_name`, `description`, `holes_count`, `rating`, `ratings_count`, `established_year`
- map and media: `latitude`, `longitude`, `photos`
- structured facts: `target_type`, `tee_types`, `land_types`, `property_type`, `difficulty_levels`, `accessibility`, `accessibility_description`
- amenities: bathroom, drinking water, cart friendly, dog friendly, stroller friendly
- import timestamp: `imported_at`

Relationships:

- one course has many holes,
- one course has many play sessions.

Source: `app/Models/Course.php`

### `holes`

Purpose:

- store per-layout hole data imported from UDisc.

Relevant fields:

- `layout_id`
- `layout_name`
- `layout_caddie_book_url`
- `layout_difficulty`
- `layout_order`
- `sort_order`
- `number`
- `hole_label`
- `par`
- `distance_meters`
- `distance_feet`

Behavior:

- holes are ordered by layout order and sort order on the course relationship,
- layouts in the play-session UI are derived from grouped holes.

Source: `app/Models/Hole.php`

### `play_sessions`

Purpose:

- represent active or historical play sessions tied to a course.

Relevant fields:

- `course_id`
- `host_id` (nullable)
- `host_session_key`
- `host_name`
- `host_layout_id`
- `status`
- `started_at`
- `ended_at`

Behavior:

- supports authenticated hosts and anonymous-host metadata,
- currently uses `status = active` for all newly created sessions,
- host layout selection is stored directly on the session row.

Source: `app/Models/PlaySession.php`

### `play_session_user`

Purpose:

- track invitations and joined participants.

Relevant fields:

- `play_session_id`
- `user_id`
- `status`
- `invited_at`
- `joined_at`
- `selected_layout_id`

Behavior:

- unique per session/user pair,
- `status` is used for invite and join state,
- participant layout selections are stored here for non-host users.

Sources:

- `database/migrations/2026_07_03_000008_create_play_sessions_table.php`
- `database/migrations/2026_07_03_000009_update_play_sessions_for_session_hosts.php`
- `database/migrations/2026_07_03_000010_add_layout_settings_to_play_sessions.php`

## 8. Current UX and configuration rules

### Locale

- configured locales are `en` and `sv`,
- admins may change locale through settings,
- guests and non-admin browsing use the default app locale.

### Theme

- configured themes are `light` and `dark`,
- the configured default theme is `light`,
- admins may change theme through settings,
- guest browsing falls back to the configured default theme.

Source: `config/yelgolf.php`, `app/Providers/AppServiceProvider.php`

### Navigation and settings visibility

- settings links are wired into the shared navigation partials,
- the settings page is designed to be reachable only for admin-authenticated sessions.

Source: `resources/views/partials/settings-menu.blade.php`, `resources/views/partials/sports-nav.blade.php`

## 9. Verified behavior from tests

The current suite verifies, among other things:

- admin authentication and authorization rules,
- shared user authentication,
- course import behavior,
- course update behavior,
- course search,
- course show rendering,
- play session start, invite, join, and layout flows,
- preference persistence behavior.

Relevant test files:

- `tests/Feature/AdminAuthenticationTest.php`
- `tests/Feature/AdminCourseImportInteractionTest.php`
- `tests/Feature/AdminCourseUpdateTest.php`
- `tests/Feature/CourseImportTest.php`
- `tests/Feature/CourseSearchTest.php`
- `tests/Feature/CourseShowTest.php`
- `tests/Feature/PlaySessionTest.php`
- `tests/Feature/PreferencesTest.php`
- `tests/Feature/UserAuthenticationTest.php`

## 10. Important current limitations

These are not necessarily bugs, but they are important to understand when extending the app:

- there is no dedicated Laravel auth guard or password reset flow; authentication is a lightweight session-backed current-player pattern,
- admin tools are guarded by user role, not a separate admin session type,
- guests cannot personalize locale/theme,
- play sessions have start and join flows, but no implemented end-session lifecycle yet,
- course detail pages may trigger background data refresh behavior at request time,
- importer richness depends on what UDisc exposes in structured payloads for a given course page.

## 11. Recommended maintenance reference points

When changing behavior in the future, start with these files:

- routing and page composition: `routes/web.php`
- shared auth state: `app/Services/CurrentPlayerResolver.php`
- admin gating: `app/Http/Middleware/EnsureAdminAuthenticated.php`
- preferences and view state: `app/Http/Middleware/ApplyUserPreferences.php`, `app/Providers/AppServiceProvider.php`
- session workflow: `app/Livewire/PlaySessionPage.php`, `app/Livewire/PlayerConsole.php`, `app/Services/PlaySessionStarter.php`
- course import pipeline: `app/Livewire/Admin/CourseManager.php`, `app/Services/UDiscCourseImporter.php`
- functional regression coverage: `tests/Feature/`