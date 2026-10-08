<?php

namespace App\Http\Controllers;

use App\Http\Requests\PreviewMarkdownRequest;
use App\Services\MarkdownRenderer;
use Illuminate\Http\JsonResponse;

class MarkdownPreviewController extends Controller
{
    /**
     * Render the editor's "Preview" tab with the same renderer the task page uses.
     */
    public function __invoke(PreviewMarkdownRequest $request, MarkdownRenderer $markdown): JsonResponse
    {
        return response()->json(['html' => $markdown->toHtml($request->validated('text'))]);
    }
}
