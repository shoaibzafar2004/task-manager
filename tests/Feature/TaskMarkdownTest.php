<?php

namespace Tests\Feature;

use App\Http\Requests\StoreTaskRequest;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskMarkdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_page_renders_markdown(): void
    {
        $task = Task::factory()->create(['info' => "## Steps\n\n- **Draft** the post\n- [x] Pick a title\n- [ ] Publish"]);

        $this->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSee('<h2>Steps</h2>', false)
            ->assertSee('<strong>Draft</strong>', false)
            ->assertSee('<input checked="" disabled="" type="checkbox"> Pick a title', false);
    }

    public function test_raw_html_is_escaped(): void
    {
        $task = Task::factory()->create(['info' => 'Hi <img src=x onerror=alert(1)> there']);

        $this->get(route('tasks.show', $task))
            ->assertOk()
            ->assertDontSee('<img src=x onerror=alert(1)>', false)
            ->assertSee('&lt;img src=x onerror=alert(1)&gt;', false);
    }

    public function test_unsafe_links_lose_their_href(): void
    {
        $task = Task::factory()->create(['info' => '[click me](javascript:alert(1))']);

        $this->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSee('click me')
            ->assertDontSee('javascript:alert', false);
    }

    public function test_cards_show_plain_text_without_markdown_symbols(): void
    {
        Task::factory()->create(['info' => "## Plan\n\n- **Buy** milk\n- Call _bank_"]);

        $this->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('Plan Buy milk Call bank')
            ->assertDontSee('**Buy**');
    }

    public function test_preview_renders_with_the_same_rules(): void
    {
        $this->postJson(route('markdown.preview'), ['text' => '**bold** <script>x</script>'])
            ->assertOk()
            ->assertJsonPath('html', "<p><strong>bold</strong> &lt;script&gt;x&lt;/script&gt;</p>\n");
    }

    public function test_preview_and_details_share_the_length_limit(): void
    {
        $tooLong = str_repeat('a', StoreTaskRequest::INFO_MAX_LENGTH + 1);

        $this->postJson(route('markdown.preview'), ['text' => $tooLong])->assertJsonValidationErrors('text');
        $this->post(route('tasks.store'), ['title' => 'Long', 'info' => $tooLong])->assertSessionHasErrors('info');
        $this->post(route('tasks.store'), ['title' => 'Long enough', 'info' => substr($tooLong, 1)])->assertSessionHasNoErrors();
    }
}
