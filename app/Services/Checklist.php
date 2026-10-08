<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Reads and ticks the "- [ ] item" checklist entries inside a Markdown document.
 *
 * Entries are counted in document order, the same order the renderer outputs their checkboxes,
 * so the Nth checkbox on the page is the Nth entry here. Lines inside fenced code blocks are
 * skipped because the renderer shows them as plain text.
 */
class Checklist
{
    /**
     * A list marker (optionally inside blockquotes) followed by "[ ]", "[x]" or "[X]" and some content
     * on the same line. A box with nothing after it stays plain text, matching the renderer.
     */
    private const ITEM = '/^(?:[ \t]*>[ \t]?)*[ \t]*(?:[-*+]|\d{1,9}[.)])[ \t]+\[([ xX])\](?=[ \t]*\S)/';

    private const FENCE = '/^[ \t]{0,3}(`{3,}|~{3,})/';

    /**
     * @return array<int, array{offset: int, checked: bool}> Byte offset of each box's state character.
     */
    public function items(?string $markdown): array
    {
        $items = [];
        $fence = null;
        $offset = 0;

        foreach (preg_split('/(?<=\n)/', (string) $markdown) as $line) {
            if (preg_match(self::FENCE, $line, $match)) {
                // A fence closes only with the same character and at least the same length.
                if ($fence === null) {
                    $fence = $match[1];
                } elseif ($match[1][0] === $fence[0] && strlen($match[1]) >= strlen($fence)) {
                    $fence = null;
                }
            } elseif ($fence === null && preg_match(self::ITEM, $line, $match, PREG_OFFSET_CAPTURE)) {
                $items[] = ['offset' => $offset + $match[1][1], 'checked' => $match[1][0] !== ' '];
            }

            $offset += strlen($line);
        }

        return $items;
    }

    /**
     * Set the state of the entry at $index (0-based) and return the updated Markdown.
     *
     * @throws InvalidArgumentException when there is no entry at $index
     */
    public function set(string $markdown, int $index, bool $checked): string
    {
        $item = $this->items($markdown)[$index] ?? throw new InvalidArgumentException("No checklist item at index {$index}.");

        return substr_replace($markdown, $checked ? 'x' : ' ', $item['offset'], 1);
    }

    /**
     * @return array{done: int, total: int}
     */
    public function progress(?string $markdown): array
    {
        $items = $this->items($markdown);

        return ['done' => count(array_filter($items, fn (array $item) => $item['checked'])), 'total' => count($items)];
    }
}
