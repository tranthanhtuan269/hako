<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SiteHeroSlides;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HeroSliderController extends Controller
{
    public function index(): View
    {
        return view('admin.hero-slider.index', [
            'slides' => SiteHeroSlides::all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slides' => ['nullable', 'array', 'max:12'],
            'slides.*.id' => ['nullable', 'string', 'max:64'],
            'slides.*.enabled' => ['nullable', 'boolean'],
            'slides.*.headline' => ['nullable', 'string', 'max:120'],
            'slides.*.subtitle' => ['nullable', 'string', 'max:320'],
            'slides.*.cta_label' => ['nullable', 'string', 'max:40'],
            'slides.*.cta_url' => ['nullable', 'string', 'max:500'],
            'slides.*.image_url' => ['nullable', 'url', 'max:500'],
            'slides.*.image_file' => ['nullable', 'image', 'max:5120'],
            'slides.*.remove_image' => ['nullable', 'boolean'],
        ]);

        $slides = $validated['slides'] ?? [];
        $uploads = [];
        $removeFlags = [];

        foreach ($slides as $index => $slide) {
            $uploads[$index] = $request->file("slides.{$index}.image_file");
            $removeFlags[$index] = $request->boolean("slides.{$index}.remove_image");
            $slides[$index]['enabled'] = $request->boolean("slides.{$index}.enabled");
        }

        SiteHeroSlides::saveFromRequest($slides, $uploads, $removeFlags);

        return redirect()
            ->route('admin.hero-slider.index')
            ->with('success', 'Homepage hero slider saved.');
    }
}
