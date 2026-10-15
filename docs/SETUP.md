# Local development setup

## Before generating the app
This repository currently contains documentation and DBML, not an installed Laravel application.
Record the output of:
```bash
php --version
composer --version
node --version
npm --version
mysql --version
```
Confirm HCM's Laravel and Inertia major versions with the facilitator. The official React starter kit inspected on 15 October 2026 requires PHP ^8.3, Laravel ^13.17 and Inertia Laravel ^3.0. Do not assume those are HCM's versions.

## Generate in a separate temporary directory
After selecting compatible versions and installing PHP, Composer, Node and MySQL, use Laravel's official installer:
```bash
composer global require laravel/installer
laravel new grant-platform-starter
```
Choose React, built-in Laravel authentication, MySQL and the team's testing framework. Keep TypeScript and shadcn/ui. If HCM uses an earlier major version, select a compatible official starter-kit release first; do not use a moving latest release blindly.

Clone GrantPlatform separately and create a feature branch. Copy the generated application into that checkout without copying a nested .git directory, .env, vendor, node_modules or generated build output. Preserve the repository README, docs and starter-kit license.

## Configure local environment
Copy the generated .env.example to .env if needed. Set:
```dotenv
APP_NAME=GrantPlatform
APP_ENV=local
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=grant_platform
DB_USERNAME=grant_platform
DB_PASSWORD=
SESSION_DRIVER=database
QUEUE_CONNECTION=database
MAIL_MAILER=log
```
Set DB_PASSWORD locally to the development database user's actual password. Create a dedicated empty development database and user; do not use production credentials. Keep private evidence outside the public storage disk. Do not point migrations at an existing production database.

## Install and verify
From the generated application root:
```bash
composer install
php artisan key:generate
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
npm install
npm run build
php artisan test
composer run dev
```
Match the Spatie release to the selected Laravel/PHP versions. Add HasRoles to User and explicit role/permission seeders in the authentication implementation step; installing the package alone does not enforce access.

Check the generated package.json and composer.json for available lint/type-check scripts, then run them. Commit composer.lock and package-lock.json. Use npm ci for subsequent reproducible installs.

## Completion gate
Before merging application scaffolding:
- Fresh MySQL migration succeeds.
- Frontend build, authentication tests and available lint/type checks pass.
- Public registration cannot grant Admin/Super Admin.
- Donor approval remains separate from login.
- No blanket Super Admin authorization override.
- No real data or secrets appear in the diff.
- Document tested PHP, Node, MySQL and package versions.

Current preparation environment has no PHP or Composer; apt installation failed. None of the application commands above were executed successfully here.

## Official references
- https://laravel.com/docs/starter-kits
- https://github.com/laravel/react-starter-kit
- https://spatie.be/docs/laravel-permission
