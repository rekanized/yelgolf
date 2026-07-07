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
- applies text search across course name, location, and description.

Key behavior:

- results are ordered by course name.

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

#### `GET /sessions`

Purpose:

- lists active and ended play sessions for the current player.

Key behavior:

- includes sessions the player hosted,
- includes sessions the player joined,
- excludes invited-only sessions until the player joins,
- lists active sessions before ended sessions and marks active sessions with a visible status tag,
- ended sessions remain available as historical records with their saved roster, layouts, scores, and charts.

Source: `routes/web.php`, `resources/views/sessions/index.blade.php`

#### `GET /sessions/{playSession}`

Purpose:

- renders the play session dashboard and historical summary through Livewire.

Key behavior:

- only the host or joined participants may open the page,
- invited-but-not-joined players cannot view the session until they join,
- active sessions expose invite, Game, layout-edit, and end-session controls according to role,
- ended sessions remain viewable with player data and charts, but read-only session controls are locked.

Source: `app/Livewire/PlaySessionPage.php`

#### `GET /sessions/{playSession}/game`

Purpose:

- renders the active play session game screen through Livewire.

Key behavior:

- only joined participants in an active session may open the page,
- each screen represents one shared hole index,
- each visible player is scored against the hole at that index from their own selected layout,
- joined players without selected layouts are skipped until they choose one on the session page.

Source: `app/Livewire/PlaySessionGamePage.php`

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
- joined players can reopen active and ended sessions from the Sessions navigation page,
- joined participants can open the Game screen from the session page,
- the host can end an active session after confirming a warning modal,
- ending a session marks it `ended`, stores `ended_at`, redirects back to the course page, removes it from active-session and pending-invite flows, and keeps it in the Sessions history page.

Current constraints:

- session creation is tied to a specific course, host user, host session key, and active status,
- only the registered host can invite additional players,
- only the registered host can end a session,
- non-participants are blocked from opening the session page,
- ended session pages are read-only for participants.

### Participant layout selection

Implemented behavior:

- layouts are derived from the course's imported holes,
- each joined participant can store one selected layout for their own view,
- registered hosts use the same pivot-table layout storage as other joined players,
- legacy anonymous-host layout metadata is stored directly on the play session,
- invited players cannot view the session page or update layout settings until they join,
- ended sessions display saved layout settings but do not allow layout changes.

Validation and authorization rules:

- submitted layout identifiers must exist in the current course layout options or the request aborts with `422`,
- submitted roster keys must belong to the current joined player or the request aborts with `403`.

### Game scoring

Implemented behavior:

- the game screen uses a shared `current_hole_index` stored on the play session,
- each visible player row maps that shared index to the player's own selected course layout,
- players without selected layouts are skipped and listed in a notice,
- any joined participant may edit scores for any visible player,
- score inputs store actual strokes for the hole,
- saved strokes are keyed by shared hole index, so changing layout keeps each player's entered strokes for Hole 1, Hole 2, and so on,
- player score summaries are derived as `strokes - hole par`, so under par is negative and over par is positive,
- score summaries recalculate against the player's currently selected layout,
- the bottom of the session page renders Chart.js line charts with each visible player's cumulative score against par per hole,
- plus and minus controls start from the hole par when the score is empty,
- the next-hole action is blocked until every visible player has a saved score,
- previous-hole navigation is available after hole 1.

Validation and authorization rules:

- score values must be integers from `1` through `99`,
- score updates must target joined session participants with a selected layout and a hole at the current index,
- ended sessions cannot be opened or mutated from the game page.

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

### `PlaySessionGamePage`

Responsibilities:

- guard access to active joined session participants,
- resolve each player's selected-layout hole for the shared hole index,
- save and clear per-player hole scores,
- block next-hole navigation until all visible players have scores,
- move the shared game position backward and forward.

Source: `app/Livewire/PlaySessionGamePage.php`

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
- `current_hole_index`

Behavior:

- supports authenticated hosts and anonymous-host metadata,
- uses `status = active` for newly created sessions,
- host-ended sessions use `status = ended` with `ended_at` populated,
- game navigation stores the shared current hole index on the session row,
- legacy anonymous-host layout selection can be stored directly on the session row.

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
- participant layout selections are stored here for registered joined users, including the registered host.

Sources:

- `database/migrations/2026_07_03_000008_create_play_sessions_table.php`
- `database/migrations/2026_07_03_000009_update_play_sessions_for_session_hosts.php`
- `database/migrations/2026_07_03_000010_add_layout_settings_to_play_sessions.php`
- `database/migrations/2026_07_07_000001_add_game_scoring_to_play_sessions.php`

### `play_session_scores`

Purpose:

- store per-player strokes for concrete holes in a play session.

Relevant fields:

- `play_session_id`
- `user_id`
- `hole_id`
- `hole_index`
- `strokes`

Behavior:

- unique per session/user/hole,
- unique per session/user/hole index,
- stores actual strokes, not score relative to par,
- uses `hole_index` as the stable score identity across layout switches,
- keeps the concrete `hole_id` as a reference to the current layout hole used when the score was last saved.

Source: `app/Models/PlaySessionScore.php`

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
- play sessions can be started, joined, and ended, but there is no historical session browser yet,
- course detail pages may trigger background data refresh behavior at request time,
- importer richness depends on what UDisc exposes in structured payloads for a given course page.

## 11. Recommended maintenance reference points

When changing behavior in the future, start with these files:

- routing and page composition: `routes/web.php`
- shared auth state: `app/Services/CurrentPlayerResolver.php`
- admin gating: `app/Http/Middleware/EnsureAdminAuthenticated.php`
- preferences and view state: `app/Http/Middleware/ApplyUserPreferences.php`, `app/Providers/AppServiceProvider.php`
- session workflow: `app/Livewire/PlaySessionPage.php`, `app/Livewire/PlaySessionGamePage.php`, `app/Livewire/PlayerConsole.php`, `app/Services/PlaySessionStarter.php`
- course import pipeline: `app/Livewire/Admin/CourseManager.php`, `app/Services/UDiscCourseImporter.php`
- functional regression coverage: `tests/Feature/`
