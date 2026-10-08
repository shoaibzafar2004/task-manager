<?php

namespace App\Services;

use Illuminate\Support\Str;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;

/**
 * Renders task details written in GitHub-flavoured Markdown.
 *
 * Raw HTML is escaped and unsafe link schemes (javascript:, data:, …) are dropped, so the
 * output is safe to print unescaped. The live preview uses the same renderer as the page.
 */
class MarkdownRenderer
{
    public function toHtml(?string $markdown): string
    {
        return Str::markdown((string) $markdown, [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
            'external_link' => [
                'internal_hosts' => array_filter([parse_url((string) config('app.url'), PHP_URL_HOST)]),
                'open_in_new_window' => true,
                'noopener' => 'external',
                'noreferrer' => 'external',
            ],
        ], [new ExternalLinkExtension]);
    }

    /**
     * A single line of plain text for previews, without Markdown symbols.
     */
    public function toPlainText(?string $markdown): string
    {
        // Close block elements with a space first so words from separate lines don't run together.
        $html = preg_replace('#</(p|li|h[1-6]|pre|blockquote|td|th)>#', '$0 ', $this->toHtml($markdown));

        return Str::squish(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5));
    }
}
