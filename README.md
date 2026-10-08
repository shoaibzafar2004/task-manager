# Task Manager

A small task manager built with Laravel 13 (PHP 8.3+), MySQL 8.4, Blade, Alpine.js, Tailwind CSS 4 and SortableJS.

## Features
- Create, edit and delete tasks. Each task has a title, info, priority, timestamps, an optional project and an optional due date.
- Due-date badges show when a task is overdue, due today or due soon.
- Search tasks by title or details, on both the Tasks and History pages.
- Dark mode toggle. It follows your system setting until you choose, then remembers your choice.
- Drag and drop to reorder tasks. Priorities (1 = highest) are saved automatically.
- Tasks are always sorted by priority. Long lists load 100 at a time as you scroll (History loads 20 at a time).
- Tick a task to complete it. It animates out of the list.
- Click a task's title to open its own page with every detail, including for completed and deleted tasks.
- Write details in Markdown (headings, lists, links, code), with a formatting toolbar and live preview.
- Checklists (`- [ ] step`) can be ticked right on the task page. Cards show progress such as `2/5`.
- **History** page with tabs for Completed tasks (reopen or delete) and Deleted tasks (restore).
- Project dropdown to show all tasks or one project's tasks. You can add and delete projects.
- Coloured labels (e.g. *bug*, *waiting*). A task can have several. Filter by label on its own or together with a project and search.

## Setup
```bash
composer install
npm install
cp .env.example .env && php artisan key:generate   # skip if .env already exists

docker compose up -d          # MySQL 8.4 on 127.0.0.1:3306 (task / secret)
php artisan migrate --seed    # tables plus demo data

composer run dev              # app at http://127.0.0.1:8000, with Vite hot reload
```

## Tests
```bash
php artisan test   # uses in-memory SQLite (see phpunit.xml)
```

## How priority works
Open tasks always hold a gapless sequence `1..n`. The logic lives in `app/Services/TaskPriorityService.php`:
- **New task:** goes to the bottom, or to the position you enter, in which case the tasks below it shift down.
- **Drag and drop:** the dragged tasks swap their existing priority slots. Reordering inside a filtered project never moves tasks from other projects.
- **Completing or deleting a task:** the tasks below it move up to close the gap.
- **Reopening or restoring a task:** it goes back to the bottom of the list.
