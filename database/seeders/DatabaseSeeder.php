<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Services\TaskPriorityService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with a few demo projects and tasks.
     */
    public function run(TaskPriorityService $priorities): void
    {
        $website = Project::create(['name' => 'Website redesign', 'color' => '#6366f1']);
        $mobile = Project::create(['name' => 'Mobile app', 'color' => '#10b981']);
        $personal = Project::create(['name' => 'Personal', 'color' => '#f59e0b']);

        $tasks = [
            [$website, 'Finalize homepage wireframes', 'Share with the team for feedback before Friday.'],
            [$mobile, 'Fix login crash on Android 15', 'Reproducible on Pixel devices after token refresh.'],
            [$personal, 'Book dentist appointment', null],
            [$website, 'Set up staging environment', 'Mirror production config, seed with anonymised data.'],
            [$mobile, 'Implement push notifications', "Use FCM for Android and APNs for iOS.\nAdd opt-in screen."],
            [$website, 'Write copy for the About page', null],
            [$personal, 'Renew gym membership', null],
            [$mobile, 'Add dark mode', 'Follow the system setting by default.'],
            [$website, 'Optimise hero images', 'Convert to AVIF/WebP and lazy-load below the fold.'],
            [$personal, 'Plan weekend trip', 'Check train times and book a place to stay.'],
        ];

        foreach ($tasks as [$project, $title, $info]) {
            $priorities->create(['project_id' => $project->id, 'title' => $title, 'info' => $info]);
        }

        Task::factory()->completed()->create(['project_id' => $website->id, 'title' => 'Pick a colour palette']);
        Task::factory()->completed()->create(['project_id' => $mobile->id, 'title' => 'Set up CI pipeline']);
    }
}
