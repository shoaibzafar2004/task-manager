<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLabelRequest;
use App\Models\Label;
use Illuminate\Http\RedirectResponse;

class LabelController extends Controller
{
    public function store(StoreLabelRequest $request): RedirectResponse
    {
        $label = Label::create($request->validated());

        return back()->with('status', "Label \"{$label->name}\" created.");
    }

    public function destroy(Label $label): RedirectResponse
    {
        $label->delete();

        return redirect()->route('tasks.index')->with('status', 'Label deleted. It was removed from its tasks.');
    }
}
