<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ThemeManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ThemeController extends Controller
{
    public function index(): View
    {
        return view('admin.themes.index', [
            'themes' => ThemeManager::all(),
            'currentTheme' => ThemeManager::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $keys = implode(',', array_keys(ThemeManager::all()));

        $validated = $request->validate([
            'theme' => ['required', 'string', "in:{$keys}"],
        ]);

        ThemeManager::set($validated['theme']);

        return redirect()
            ->route('admin.themes.index')
            ->with('success', 'Frontend theme updated successfully.');
    }
}
