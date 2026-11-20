<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NavLink;
use App\Support\HeaderSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NavLinkController extends Controller
{
    public function index(): View
    {
        $links = NavLink::query()->where('type', 'header')->ordered()->get();

        return view('admin.nav-links.index', compact('links'));
    }

    public function create(): View
    {
        return view('admin.nav-links.form', [
            'link' => new NavLink([
                'type' => 'header',
                'is_active' => true,
                'sort_order' => (int) NavLink::query()->where('type', 'header')->max('sort_order') + 10,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        NavLink::create($this->validated($request));

        return redirect()
            ->route('admin.nav-links.index')
            ->with('success', 'Menu item created.');
    }

    public function edit(NavLink $nav_link): View
    {
        return view('admin.nav-links.form', [
            'link' => $nav_link,
        ]);
    }

    public function update(Request $request, NavLink $nav_link): RedirectResponse
    {
        $nav_link->update($this->validated($request));

        return redirect()
            ->route('admin.nav-links.index')
            ->with('success', 'Menu item updated.');
    }

    public function destroy(NavLink $nav_link): RedirectResponse
    {
        $nav_link->delete();

        return redirect()
            ->route('admin.nav-links.index')
            ->with('success', 'Menu item deleted.');
    }

    public function toggleActive(NavLink $nav_link): RedirectResponse
    {
        $nav_link->update([
            'is_active' => ! $nav_link->is_active,
        ]);

        $state = $nav_link->is_active ? 'active' : 'hidden';

        return back()->with('success', "{$nav_link->label} is now {$state} on the header.");
    }

    public function toggleSearch(): RedirectResponse
    {
        $visible = HeaderSearch::toggle();

        return back()->with('success', $visible
            ? 'Header search is now visible.'
            : 'Header search is now hidden.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'url' => ['required', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'open_in_new_tab' => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['open_in_new_tab'] = $request->boolean('open_in_new_tab');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['url'] = trim($data['url']);
        $data['type'] = 'header';

        return $data;
    }
}
