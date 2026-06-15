<?php

namespace App\Support;

use App\Models\Post;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class GeminiBlogWriter
{
    public function isEnabled(): bool
    {
        return (bool) config('ai.gemini.enabled')
            && filled(config('ai.gemini.api_key'));
    }

    /**
     * @param  array{
     *     store_name: string,
     *     category_name: ?string,
     *     store_slug: string,
     *     offers: array<int, array{code: ?string, title: string, description: ?string, type: string}>,
     *     merchant: array<string, mixed>
     * }  $context
     * @return array{title: string, excerpt: string, meta_title: string, meta_description: string, content: string, source: string}|null
     */
    public function generate(array $context): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $apiKey = (string) config('ai.gemini.api_key');
        $model = (string) config('ai.gemini.model', 'gemini-2.0-flash');
        $timeout = (int) config('ai.gemini.timeout', 90);
        $prompt = $this->buildPrompt($context);

        try {
            $response = Http::timeout($timeout)
                ->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                    [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt],
                                ],
                            ],
                        ],
                        'generationConfig' => [
                            'responseMimeType' => 'application/json',
                            'temperature' => 0.55,
                            'maxOutputTokens' => 8192,
                        ],
                    ]
                );

            if (! $response->successful()) {
                Log::warning('Gemini blog generation failed', [
                    'status' => $response->status(),
                    'body' => Str::limit($response->body(), 500),
                ]);

                return null;
            }

            $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

            if (! is_string($text) || trim($text) === '') {
                return null;
            }

            $parsed = json_decode($text, true);

            if (! is_array($parsed)) {
                return null;
            }

            $blog = $this->normalizeOutput($parsed);

            if ($blog === null) {
                return null;
            }

            $blog['source'] = 'gemini';

            return $blog;
        } catch (\Throwable $exception) {
            Log::warning('Gemini blog generation exception', [
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array{
     *     store_name: string,
     *     category_name: ?string,
     *     store_slug: string,
     *     offers: array<int, array{code: ?string, title: string, description: ?string, type: string}>,
     *     merchant: array<string, mixed>
     * }  $context
     */
    private function buildPrompt(array $context): string
    {
        $siteName = (string) config('site.name');
        $monthYear = now()->format('F Y');
        $storeName = $context['store_name'];
        $category = $context['category_name'] ?? 'online retail';
        $storeUrl = url('/stores/' . $context['store_slug']);
        $merchant = $context['merchant'];
        $products = is_array($merchant['products'] ?? null) ? $merchant['products'] : [];
        $faqs = is_array($merchant['faqs'] ?? null) ? $merchant['faqs'] : [];
        $offers = $context['offers'];

        $payload = [
            'site_name' => $siteName,
            'month_year' => $monthYear,
            'store_name' => $storeName,
            'store_url' => $storeUrl,
            'category' => $category,
            'domain' => $merchant['domain'] ?? null,
            'meta_description' => $merchant['meta_description'] ?? null,
            'page_title' => $merchant['page_title'] ?? null,
            'products' => $products,
            'faqs' => $faqs,
            'offers' => collect($offers)->map(fn (array $offer) => [
                'title' => $offer['title'],
                'code' => $offer['code'] ?? null,
                'type' => $offer['type'] ?? (filled($offer['code'] ?? null) ? 'coupon' : 'discount'),
                'description' => $offer['description'] ?? null,
            ])->values()->all(),
        ];

        $factsJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return <<<PROMPT
You are an expert U.S. e-commerce SEO copywriter for {$siteName}.

Write ONE long-form English blog article using ONLY the facts in the JSON below. Do not invent product specs, prices, reviews, or coupon codes that are not in the data.

JSON facts:
{$factsJson}

Article rules:
- Audience: U.S. online shoppers looking for deals and buying advice.
- Tone: helpful, specific, trustworthy — not hype or fake testimonials.
- Length: 1,400–2,200 words in HTML.
- If 2+ products: comparison article with a <table class="comparison-table"> (Product, Price, Best for).
- If 1 product: product spotlight/review style.
- If 0 products: store/brand review with category context.
- Include sections: intro, product comparison or highlights, pros/cons, how to save with coupons, FAQ, final verdict.
- Mention {$siteName} naturally and link to the store deals page: {$storeUrl}
- Use merchant FAQs when provided; add 2–3 generic coupon-shopping FAQs if needed.
- List every offer from the JSON with codes in <code> tags when type is coupon.
- HTML only in content: <h2>, <h3>, <p>, <ul>, <li>, <ol>, <table>, <strong>, <em>, <a>, <code>. No <h1>, no markdown.
- Do not claim star ratings or verified customer reviews unless explicitly in the JSON.

Return valid JSON with exactly these keys:
{
  "title": "string, ONE line only, 45-72 characters, short SEO headline that fits in 2-3 lines on a blog card. No line breaks. No long product lists.",
  "excerpt": "string, 140-220 chars",
  "meta_title": "string, max 70 chars",
  "meta_description": "string, max 160 chars",
  "content": "string, full HTML article body"
}
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @return array{title: string, excerpt: string, meta_title: string, meta_description: string, content: string}|null
     */
    private function normalizeOutput(array $parsed): ?array
    {
        $title = trim((string) ($parsed['title'] ?? ''));
        $excerpt = trim((string) ($parsed['excerpt'] ?? ''));
        $metaTitle = trim((string) ($parsed['meta_title'] ?? ''));
        $metaDescription = trim((string) ($parsed['meta_description'] ?? ''));
        $content = trim((string) ($parsed['content'] ?? ''));

        if ($title === '' || $excerpt === '' || $content === '') {
            return null;
        }

        if ($metaTitle === '') {
            $metaTitle = Str::limit($title, 70, '');
        }

        if ($metaDescription === '') {
            $metaDescription = Str::limit($excerpt, 160, '');
        }

        return [
            'title' => Post::normalizeTitle($title),
            'excerpt' => Str::limit($excerpt, 500, ''),
            'meta_title' => Str::limit($metaTitle, 70, ''),
            'meta_description' => Str::limit($metaDescription, 320, ''),
            'content' => $content,
        ];
    }
}
