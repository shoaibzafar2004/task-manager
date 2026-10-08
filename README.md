# Task Manager

A task manager built with **Laravel 13** (PHP 8.3+), **MySQL 8.4**, Blade, **Alpine.js**, **Tailwind CSS 4** and **SortableJS**. Tasks are kept in priority order, can be reordered by drag and drop, and can be grouped by project, tagged with labels and given Markdown details with checklists.

## Features

### Tasks

- Create, edit and delete tasks. Each task has a title, details, priority, an optional project, labels and an optional due date, plus created, updated and completed times.
- Tasks are always **sorted by priority** (1 = highest). **Drag and drop** to reorder them, and the priorities are saved automatically.
- Tick a task's checkbox to **complete** it. The title is struck through and the card slides out of the list.
- **Due-date badges** show when a task is overdue (red), due today (amber), due within 2 days (blue) or due later.
- Deleting asks for confirmation in a themed dialog. Deleted tasks can be restored.

### Organising

- **Projects:** each task can belong to one project. Use the dropdown to show all tasks or one project's tasks.
- **Labels** such as _bug_ or _waiting_: a task can have several, each with its own colour.
- **Search** by title or details.
- Projects, labels and search combine, for example "Mobile app" + _bug_ + "login". They work on the Tasks and History pages and through "load more".

### Viewing and details

- Click a task's title to open it in a **popup** over the list, with open and close animations. The address bar changes to `/tasks/{id}`, so the browser's Back button closes it. Opening that link directly, or in a new tab, shows the task as a full page.
- From the popup you can complete, edit, delete or restore the task, and the list behind it updates in place.
- **Details are written in Markdown:** headings, bold and italic, lists, links, code and tables. The editor has a formatting toolbar, Write / Preview tabs and a character counter (up to 20,000 characters), and it grows as you type.
- **Checklists:** write `- [ ] step` in the details, then tick the boxes directly in the popup or on the task page. A progress bar shows how many are done, and cards show a `2/5` chip.

### History and interface

- The **History** page has a _Completed_ tab (reopen or delete) and a _Deleted_ tab (restore).
- **Long lists load in batches as you scroll:** 100 open tasks at a time, and 20 History entries at a time.
- **Dark mode:** it follows your system setting until you use the toggle in the header, then remembers your choice.
- Responsive layout and keyboard support. Popups and dialogs keep focus inside, close with Escape and return focus to where you were.

## Tech stack

| Area       | Tools                                                                             |
| ---------- | --------------------------------------------------------------------------------- |
| Backend    | Laravel 13, PHP 8.3+ (developed on 8.4), MySQL 8.4                                |
| Frontend   | Blade components, Alpine.js (+ Collapse plugin), Tailwind CSS 4, SortableJS, Vite |
| Markdown   | `league/commonmark`, through Laravel's `Str::markdown` (GitHub-flavoured)         |
| Quality    | PHPUnit, Laravel Pint, ESLint, Prettier                                           |
| Automation | GitHub Actions, Dependabot                                                        |

## Getting started

There are two ways to run it:

- **[Use it](#use-it-with-docker):** everything runs in Docker and starts with your computer. Only Docker is needed.
- **[Develop it](#develop-it):** PHP and Vite run on your machine with hot reload, against the same MySQL container.

### Use it with Docker

Requires Docker with Compose.

```bash
git clone git@github.com:shoaibzafar2004/task-manager.git
cd task-manager
cp .env.example .env

docker compose build app
key=$(docker compose run --rm --no-deps --entrypoint php app artisan key:generate --show)
sed -i "s|^APP_KEY=.*|APP_KEY=$key|" .env      # on macOS: sed -i ''

docker compose up -d
```

Open **http://localhost:8800**. Three containers start, and they come back automatically after a reboot:

| Container             | What it does                                                                                           |
| --------------------- | ------------------------------------------------------------------------------------------------------ |
| `task-manager-app`    | The app, served by FrankenPHP. Runs database migrations and caches config/routes/views on every start. |
| `task-manager-mysql`  | MySQL 8.4, with its data in the `mysql-data` Docker volume. Host port 3307.                            |
| `task-manager-backup` | Dumps the database to `./backups` once a day and keeps the last 14 days.                               |

**After pulling new code**, rebuild the app so the running copy picks it up:

```bash
docker compose up -d --build app
```

**Ports:** the app uses `APP_PORT` (8800) and MySQL `FORWARD_DB_PORT` (3307), both set in `.env`. They're chosen to stay clear of other projects, which usually use 8000 and 3306. Change them there if something else needs them.

**Backups and restoring:**

```bash
ls backups/                                                   # task_manager-YYYY-MM-DD.sql.gz
gunzip < backups/task_manager-2026-10-08.sql.gz | docker exec -i task-manager-mysql mysql -utask -psecret task_manager
```

The files in `backups/` are created by the container, so they're owned by `root`. Use `sudo` to delete them by hand.

**Your data is protected:** `migrate:fresh`, `migrate:refresh`, `migrate:reset` and `db:wipe` are blocked outside the test suite (see `AppServiceProvider`). Don't run `db:seed` against your real data either, because it adds the demo tasks.

### Develop it

Requires:

- PHP 8.3 or newer, with the `pdo_mysql` and `pdo_sqlite` extensions
- Composer 2
- Node.js 20.19+ or 22.12+ (developed on 24) and npm
- Docker, for MySQL. Any MySQL 8 server works too.

```bash
composer install
npm install

cp .env.example .env               # skip if you set up the Docker app above
php artisan key:generate

docker compose up -d mysql         # MySQL 8.4 on 127.0.0.1:3307 (database task_manager, user task, password secret)
php artisan migrate                # creates the tables

composer run dev                   # web server, queue, logs and Vite → http://127.0.0.1:8000
```

The development server and the Docker app share the same database. To try the app with demo data, use a separate database: set `DB_DATABASE` in `.env` to a new database, then run `php artisan migrate --seed`. The seeder adds 3 projects, 4 labels (_bug_, _feature_, _quick win_, _waiting_), 10 open tasks with due dates and 2 completed tasks.

To use your own MySQL server instead of Docker, set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` in `.env`.

Restart `composer run dev` after changing `.env`. Its long-running processes keep the values they started with.

## Using the app

| To…                       | Do this                                                                                                   |
| ------------------------- | --------------------------------------------------------------------------------------------------------- |
| Add a task                | **New task**. Leave _Priority_ empty to add it at the bottom, or enter a number to insert it there.       |
| Reorder                   | Drag a task by the handle (⋮⋮) on its left.                                                               |
| Complete                  | Tick the round checkbox, or **Mark complete** in the popup.                                               |
| See every detail          | Click the task's title.                                                                                   |
| Add a project or label    | **+ Project** / **+ Label** in the header, then pick a colour.                                            |
| Delete a project or label | Select it in the filter, then use the link at the bottom of the list. Its tasks are kept.                 |
| Format details            | Use the toolbar, or <kbd>Ctrl</kbd>/<kbd>Cmd</kbd> + <kbd>B</kbd> for bold and + <kbd>I</kbd> for italic. |
| Add a checklist           | The checklist toolbar button, or type `- [ ] item`.                                                       |
| Find old tasks            | **History**, with the same search and filters.                                                            |

## Development

### Checks

Run these before opening a pull request. CI runs the same ones.

| Check                    | Command                | Fix automatically   |
| ------------------------ | ---------------------- | ------------------- |
| Tests (in-memory SQLite) | `php artisan test`     | —                   |
| PHP code style (Pint)    | `composer lint`        | `composer lint:fix` |
| JavaScript (ESLint)      | `npm run lint`         | `npm run lint:fix`  |
| Formatting (Prettier)    | `npm run format:check` | `npm run format`    |

Prettier covers JS, CSS, JSON, YAML and Markdown with 4 spaces, single quotes and 120 columns. PHP and Blade files are left to Pint.

To run the tests against MySQL instead of SQLite, create a test database once and point the tests at it:

```bash
docker exec task-manager-mysql mysql -uroot -proot \
  -e "CREATE DATABASE IF NOT EXISTS task_manager_test; GRANT ALL ON task_manager_test.* TO 'task'@'%';"

DB_CONNECTION=mysql DB_DATABASE=task_manager_test php artisan test
```

### Contributing workflow

1. Create a branch from `main`, e.g. `feature/due-date-reminders`.
2. Make the change with tests, and run the checks above.
3. Push the branch and open a pull request into `main`.
4. GitHub Actions runs **Lint**, **Prettier** and **Tests**. The pull request can be merged once all three pass.

### Continuous integration

`.github/workflows/ci.yml` runs on every pull request into `main` and every push to `main`:

- **Lint:** Laravel Pint (`pint --test`) and ESLint
- **Prettier:** `prettier --check .`
- **Tests:** builds the assets with Vite, then runs the full PHPUnit suite

### Dependency updates

`.github/dependabot.yml` keeps the dependencies current:

- Composer and npm are checked daily, GitHub Actions weekly.
- Each package gets **its own pull request**, with at most one open per ecosystem at a time.
- **Patch and minor updates are merged automatically** once Lint, Prettier and Tests pass. **Major updates** wait for a manual review.

### Protecting `main` (one-time GitHub setup)

To block merges until the checks pass, in the GitHub repository go to **Settings → Rules → Rulesets → New branch ruleset**:

1. Name it e.g. _Protect main_, set enforcement to **Active** and target the **default branch**.
2. Enable **Require a pull request before merging**.
3. Enable **Require status checks to pass** and add **Lint**, **Prettier** and **Tests**. They can be found once they've run at least once.
4. Enable **Block force pushes**, then save.

Add yourself to the **bypass list** if you need to push directly in an emergency.

## How it works

### Priorities

Open tasks always hold a gapless sequence `1..n`. The rules live in [`app/Services/TaskPriorityService.php`](app/Services/TaskPriorityService.php), and every change runs in a database transaction:

- **New task:** goes to the bottom, or to the position entered; the tasks below shift down.
- **Drag and drop:** the moved tasks swap the priority slots they already held. Reordering a filtered view (by project, label or search) never moves tasks that aren't shown.
- **Editing the priority:** the task moves to the new position and the tasks in between shift by one.
- **Completing or deleting:** the tasks below move up to close the gap.
- **Reopening or restoring:** the task goes back to the bottom.

### Loading long lists

- **Open tasks load by priority** (`?after=N`) rather than by page number. Completing or reordering tasks while you scroll therefore never skips or repeats one. The browser sends the last priority it currently shows and renumbers it as tasks are completed. Completions and batch loads run one at a time, so the two can't overlap.
- **History uses cursor pagination,** so reopening an entry doesn't shift the next batch.

### Markdown and checklists

- Task details are rendered by [`MarkdownRenderer`](app/Services/MarkdownRenderer.php). Raw HTML is escaped and unsafe links (`javascript:`, `data:`) lose their `href`, so user content can't inject scripts. External links open in a new tab with `noopener noreferrer`. The editor's preview is rendered by the same class on the server, so it always matches the page.
- [`Checklist`](app/Services/Checklist.php) finds `- [ ]` entries in the same order as the rendered checkboxes, skipping fenced code blocks. A test checks the two always agree.
- Each tick sends a version of the details it was rendered from. If the details changed elsewhere in the meantime, the server answers `409 Conflict` and the page reloads, instead of ticking the wrong line.

### Task popup

The list and History request a task as JSON and show it in a popup, using `history.pushState`. Loading the same URL directly renders the full page from the same Blade partial ([`tasks/partials/detail.blade.php`](resources/views/tasks/partials/detail.blade.php)), so the two can't drift apart.

## Project structure

```
app/
├── Http/Controllers/     TaskController, HistoryController, ProjectController, LabelController,
│                         TaskChecklistController, MarkdownPreviewController
├── Http/Requests/        Form Request validation for every write
├── Models/               Task, Project, Label
└── Services/             TaskPriorityService, MarkdownRenderer, Checklist
database/
├── migrations/           projects, tasks, labels, label_task
└── seeders/              demo data
resources/
├── css/app.css           Tailwind setup, dark mode and component styles
├── js/app.js             drag and drop, completion, load more, popup, editor, checklists
└── views/
    ├── components/       layout, dialogs, filters, badges, Markdown editor, …
    ├── tasks/            list, task page, edit form and partials
    └── history/          History page and partials
tests/Feature/            PHPUnit feature tests
.github/                  CI workflow and Dependabot config
Dockerfile, docker/       app image (Vite build + FrankenPHP) and its startup script
docker-compose.yml        app, MySQL and daily backup containers
```

### Database

| Table        | Purpose                                                                                       |
| ------------ | --------------------------------------------------------------------------------------------- |
| `tasks`      | title, details (Markdown), priority, due date, `completed_at`, soft deletes, optional project |
| `projects`   | name and colour; deleting one keeps its tasks without a project                               |
| `labels`     | name and colour                                                                               |
| `label_task` | which labels each task has; deleting a label removes it from its tasks                        |
