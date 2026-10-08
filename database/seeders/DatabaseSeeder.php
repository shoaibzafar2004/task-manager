<?php

namespace Database\Seeders;

use App\Models\Label;
use App\Models\Project;
use App\Models\Task;
use App\Services\TaskPriorityService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with a few demo projects, labels and tasks.
     */
    public function run(TaskPriorityService $priorities): void
    {
        $website = Project::create(['name' => 'Website redesign', 'color' => '#6366f1']);
        $mobile = Project::create(['name' => 'Mobile app', 'color' => '#10b981']);
        $personal = Project::create(['name' => 'Personal', 'color' => '#f59e0b']);

        $labels = collect([
            'bug' => '#ef4444',
            'feature' => '#8b5cf6',
            'quick win' => '#10b981',
            'waiting' => '#64748b',
        ])->map(fn (string $color, string $name) => Label::create(['name' => $name, 'color' => $color]));

        $labelsByTitle = [
            'Fix login crash on Android 15' => ['bug'],
            'Implement push notifications' => ['feature'],
            'Add dark mode' => ['feature', 'quick win'],
            'Book dentist appointment' => ['quick win'],
            'Write copy for the About page' => ['waiting'],
            'Optimise hero images' => ['quick win'],
        ];

        $tasks = [
            [$website, 'Finalize homepage wireframes', 'Share with the team for feedback before Friday.', 2],
            [$mobile, 'Fix login crash on Android 15', 'Reproducible on Pixel devices after token refresh.', -1],
            [$personal, 'Book dentist appointment', null, 0],
            [$website, 'Set up staging environment', 'Mirror production config, seed with anonymised data.', 7],
            [$mobile, 'Implement push notifications', "Use FCM for Android and APNs for iOS.\nAdd opt-in screen.", 14],
            [$website, 'Write copy for the About page', null, null],
            [$personal, 'Renew gym membership', null, 1],
            [$mobile, 'Add dark mode', 'Follow the system setting by default.', null],
            [$website, 'Optimise hero images', 'Convert to AVIF/WebP and lazy-load below the fold.', null],
            [$personal, 'Plan weekend trip', 'Check train times and book a place to stay.', 5],
        ];

        foreach ($tasks as [$project, $title, $info, $dueInDays]) {
            $task = $priorities->create([
                'project_id' => $project->id,
                'title' => $title,
                'info' => $info,
                'due_date' => $dueInDays === null ? null : today()->addDays($dueInDays),
            ]);

            $task->labels()->attach($labels->only($labelsByTitle[$title] ?? [])->pluck('id'));
        }

        Task::factory()->completed()->create(['project_id' => $website->id, 'title' => 'Pick a colour palette']);
        Task::factory()->completed()->create(['project_id' => $mobile->id, 'title' => 'Set up CI pipeline']);
    }
}
