# Yelgolf

Yelgolf is a small Laravel and Livewire golf directory with a no-build frontend stack. It uses plain Blade templates, hand-written CSS, and SQLite. There is no Bootstrap, Tailwind, npm, or Node-based asset pipeline in the application runtime.

## Frontend policy

- Everything in this project should be designed mobile first.
- Base Blade and CSS layouts should target narrow screens first, then expand with `min-width` breakpoints for tablet and desktop.
- Visual decisions should be judged on the mobile experience first, not as a desktop layout that later collapses.

## Current scope

- Public welcome page that lists imported courses.
- Admin login using temporary credentials from the environment.
- Admin importer that accepts a public UDisc course URL and stores scraped course details in SQLite.

## Admin login

The default admin credentials are:

- Username: `test`
- Password: `test`

These can be changed with `ADMIN_USERNAME` and `ADMIN_PASSWORD` in the environment.

## Local setup

```bash
composer install
cp .env.example .env
touch database/database.sqlite
php artisan key:generate
php artisan migrate
php artisan serve
```

Then open `http://localhost:8000`.

## Notes

- The importer currently targets public UDisc course pages like `https://udisc.com/courses/haesthagen-M8Wu`.
- Parsed fields include course name, location, description, hole count, rating summary, coordinates, and established year when available.
- Google authentication can be added later without changing the public course directory structure.
- The live test URL is `https://yelgolf.rekanized.com` and should be used for real browser verification.
