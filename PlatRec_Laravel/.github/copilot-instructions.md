# Copilot Instructions for PlatRec

This is a **Laravel 12** application with email verification, user authentication (via Laravel Breeze), and a full modern PHP+JavaScript stack.

## Architecture Overview

**Core Stack:**
- **Backend:** Laravel 12 (PHP 8.2+), Eloquent ORM, Service Container
- **Frontend:** Vite 7, Tailwind CSS 3, Alpine.js 3, Laravel Blade templates
- **Database:** SQLite (default), MySQL supported
- **Testing:** PHPUnit 11 with Feature and Unit test suites
- **Development:** Laravel Sail (Docker), Artisan CLI

**Key Entry Points:**
- `public/index.php` - HTTP kernel entry point
- `routes/web.php` - Web routes (public + authenticated)
- `routes/auth.php` - Authentication routes (Laravel Breeze)
- `app/Http/Controllers/` - Route handlers
- `resources/views/` - Blade templates
- `app/Models/User.php` - Primary data model

## Project Setup & Development

**Installation:**
```bash
composer install && npm install
php artisan key:generate
php artisan migrate
```

**Development Server** (runs concurrently: PHP server + queue listener + log tail + Vite dev):
```bash
composer run dev
```
Automatically rebuilds frontend assets and restarts PHP on file changes.

**Build for Production:**
```bash
npm run build  # Compiles CSS/JS to public/build/
```

**Database:**
- Default: SQLite at `database/database.sqlite`
- Switch to MySQL: Set `DB_CONNECTION=mysql` in `.env`, update credentials, then `php artisan migrate`
- Migrations in `database/migrations/` use the modern timestamp-based naming (e.g., `0001_01_01_000000_create_users_table.php`)

## Authentication & Authorization

**System:** Laravel Breeze (built-in authentication scaffolding)

**Key Controllers:**
- `Auth/RegisteredUserController` - User registration, password hashing
- `Auth/AuthenticatedSessionController` - Login/logout with session
- `Auth/EmailVerificationPromptController` & `VerifyEmailController` - Email verification flow
- `Auth/PasswordController` & `PasswordResetLinkController` - Password reset

**Middleware:**
- `auth` - Requires authenticated user
- `guest` - Only allows unauthenticated users (for login/register routes)
- `verified` - Requires authenticated + email verified (see `dashboard` route)

**User Model** (`app/Models/User.php`):
- Uses `Authenticatable`, `Notifiable` traits
- Mass-assignable: `name`, `email`, `password`
- Auto-casts: `email_verified_at` as datetime, `password` as hashed

## Convention-Based Patterns

**Routing:**
- RESTful routes in `routes/web.php`: `Route::get()`, `Route::post()`, `Route::patch()`, `Route::delete()`
- Middleware grouping: `Route::middleware('auth')->group()` wraps protected routes
- Named routes: `.name('profile.edit')` - reference in views as `route('profile.edit')`

**Controllers:**
- Base class: `app/Http/Controllers/Controller`
- Single-responsibility: `ProfileController` handles `/profile` operations (edit, update, destroy)
- Type hints on methods: `public function edit(Request $request)`

**Views:**
- Blade syntax: `{{ }}` for escaping, `{!! !!}` for raw HTML
- Main layout: `resources/views/layouts/` - extended by page views
- Component folder: `resources/views/components/` - reusable UI pieces
- Auth views: `resources/views/auth/` - login, register, reset forms

**Database:**
- Migrations use `Schema::create()` and `Blueprint` for DDL
- Foreign keys and indexing built into migrations (see `sessions` table with `user_id` foreign key)
- Seeders in `database/seeders/` for test data

## Testing

**Test Structure:**
- Feature tests: `tests/Feature/` - test HTTP endpoints, user flows
- Unit tests: `tests/Unit/` - test isolated classes
- Test DB: Uses SQLite in-memory (`:memory:`) configured in `phpunit.xml`
- Test trait: Extend `Tests\TestCase` for access to Laravel assertions

**Running Tests:**
```bash
php artisan test                    # Run all tests
php artisan test tests/Feature      # Run Feature tests only
php artisan test --filter=TestName  # Run specific test
php artisan test --coverage         # Generate coverage report
```

**Key Test Setup** (in `phpunit.xml`):
- `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:` - isolated test DB
- `MAIL_MAILER=array` - testing mail without sending
- `QUEUE_CONNECTION=sync` - run jobs synchronously in tests

## Frontend Build & Styling

**Vite Pipeline** (`vite.config.js`):
- Inputs: `resources/css/app.css`, `resources/js/app.js`
- Outputs: `public/build/manifest.json` + asset files
- Hot reload: Enabled automatically in dev mode

**Tailwind CSS Configuration** (`tailwind.config.js`):
- Purges unused styles from `resources/views/**/*.blade.php`
- Uses `@tailwindcss/forms` plugin for styled form inputs
- Extended fonts: `Figtree` as default sans-serif

**Frontend JS Setup** (`resources/js/bootstrap.js`):
- Axios pre-configured with `X-Requested-With` header for CSRF detection
- Alpine.js initialized globally (`window.Alpine`)

## Project-Specific Conventions

**Configuration:**
- `.env` file manages environment (see `config/app.php`, `config/database.php`)
- Defaults: `APP_ENV=production`, `DB_CONNECTION=sqlite`
- Laravel Breeze adds authentication scaffolding on project init

**Naming Patterns:**
- Controllers: `{Feature}Controller` (e.g., `ProfileController`)
- Routes: kebab-case (e.g., `/profile`, `/verify-email`)
- Models: PascalCase singular (e.g., `User`)
- Migrations: timestamp + description (e.g., `0001_01_01_000000_create_users_table.php`)

**Code Style:**
- PSR-12 enforced via Laravel Pint (run `./vendor/bin/pint` to auto-format)
- Type hints required on method parameters and return types
- Docblocks with `@var`, `@return`, `@use` annotations

## Integration Points & External Dependencies

**Composer Dependencies (prod):**
- `laravel/framework` - Core framework
- `laravel/tinker` - Interactive shell for debugging

**Composer Dev Dependencies:**
- `laravel/breeze` - Authentication scaffolding
- `phpunit/phpunit` - Testing framework
- `mockery/mockery` - Mocking library for tests
- `fakerphp/faker` - Generates fake test data
- `laravel/pint` - Code style fixer
- `laravel/sail` - Docker development environment

**npm Dependencies:**
- `laravel-vite-plugin` - Integration between Laravel and Vite
- `tailwindcss` + `@tailwindcss/forms` - Styling
- `alpinejs` - Lightweight reactive JS
- `axios` - HTTP client for AJAX

## Key Files to Understand

| File | Purpose |
|------|---------|
| `routes/web.php` | Public & authenticated web routes |
| `routes/auth.php` | Authentication flow routes |
| `app/Http/Controllers/ProfileController.php` | Profile CRUD operations |
| `app/Models/User.php` | User model & auth trait |
| `resources/views/layouts/` | Base Blade templates |
| `resources/css/app.css` | Global Tailwind imports |
| `vite.config.js` | Asset build configuration |
| `phpunit.xml` | Test runner configuration |

## Common Development Tasks

**Add a New Feature:**
1. Create migration: `php artisan make:migration create_features_table`
2. Create model: `php artisan make:model Feature`
3. Create controller: `php artisan make:controller FeatureController`
4. Register routes in `routes/web.php`
5. Create views in `resources/views/features/`
6. Add tests in `tests/Feature/`

**Modify User Authentication:**
- Extend `App\Models\User` or use traits in `app/Models/`
- Auth logic in `routes/auth.php` and `app/Http/Controllers/Auth/`
- Email verification enforced via `verified` middleware on protected routes

**Update Styling:**
- Edit `resources/css/app.css` (imports Tailwind directives)
- Tailwind config in `tailwind.config.js` (extends theme, adds plugins)
- Run `npm run dev` or `npm run build` to compile

**Debug Issues:**
- Use `php artisan tinker` for interactive shell (Models, queries, logic)
- Check logs: `storage/logs/laravel.log`
- Laravel Pail: `php artisan pail --timeout=0` (tail logs in real-time)
