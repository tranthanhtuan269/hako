<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

final class AffiliateExcelParser
{
    /** Headers we never import into public content. */
    private const SENSITIVE_HEADERS = ['user', 'pass', 'password', 'mật khẩu', 'mat khau'];

    /**
     * Parse an Excel signup workbook into grouped store records.
     *
     * @return list<array{
     *     sheet_name: string,
     *     source_row: int,
     *     stt: ?string,
     *     category_name: ?string,
     *     store_name: string,
     *     website: ?string,
     *     affiliate_url: string,
     *     offers: list<array{code: ?string, title: string, description: ?string, type: string}>
     * }>
     */
    public function parseFile(string $absolutePath): array
    {
        $reader = IOFactory::createReaderForFile($absolutePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($absolutePath);

        $stores = [];

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $title = trim((string) $sheet->getTitle());
            $rows = $sheet->toArray(null, true, true, false);

            if ($rows === []) {
                continue;
            }

            $headerRowIndex = $this->findHeaderRow($rows);

            if ($headerRowIndex === null) {
                continue;
            }

            $map = $this->mapHeaders($rows[$headerRowIndex]);

            if (! isset($map['store_name']) && ! isset($map['affiliate_url'])) {
                continue;
            }

            $current = null;

            for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $excelRow = $i + 1;

                if ($this->rowIsEmpty($row)) {
                    continue;
                }

                $storeName = $this->sanitizeStoreName($this->cell($row, $map['store_name'] ?? null));
                $affiliateUrl = $this->normalizeUrl($this->cell($row, $map['affiliate_url'] ?? null));
                $stt = $this->normalizeStt($this->cell($row, $map['stt'] ?? null));
                $isStoreStart = $this->isStoreStartRow($storeName, $stt, $affiliateUrl, $current);

                if ($isStoreStart) {
                    if ($current !== null && $this->isValidStore($current)) {
                        $stores[] = $current;
                    }

                    $website = $this->normalizeUrl($this->cell($row, $map['website'] ?? null));
                    $resolvedName = $storeName !== ''
                        ? $storeName
                        : $this->fallbackStoreName($website, $affiliateUrl);

                    $current = [
                        'sheet_name' => $title,
                        'source_row' => $excelRow,
                        'stt' => $stt,
                        'category_name' => $this->nullable($this->cell($row, $map['category'] ?? null)),
                        'store_name' => $resolvedName,
                        'website' => $website,
                        'affiliate_url' => $affiliateUrl ?? '',
                        'offers' => [],
                    ];
                } elseif ($current === null) {
                    continue;
                } else {
                    // Continuation / fill-down: keep existing affiliate if blank.
                    $maybeAff = $affiliateUrl;
                    if ($maybeAff) {
                        $current['affiliate_url'] = $maybeAff;
                    }
                    if ($storeName !== '' && $this->isBadStoreName((string) ($current['store_name'] ?? ''))) {
                        $current['store_name'] = $storeName;
                    }
                    if ($storeName !== '' && $current['store_name'] === '') {
                        $current['store_name'] = $storeName;
                    }
                    $maybeWeb = $this->normalizeUrl($this->cell($row, $map['website'] ?? null));
                    if ($maybeWeb && empty($current['website'])) {
                        $current['website'] = $maybeWeb;
                    }
                    $maybeCat = $this->nullable($this->cell($row, $map['category'] ?? null));
                    if ($maybeCat && empty($current['category_name'])) {
                        $current['category_name'] = $maybeCat;
                    }
                }

                $offer = $this->extractOffer($row, $map);

                if ($offer !== null) {
                    $current['offers'][] = $offer;
                }
            }

            if ($current !== null && $this->isValidStore($current)) {
                $stores[] = $current;
            }
        }

        return $this->mergeDuplicateGroups($stores);
    }

    /**
     * @param  UploadedFile  $file
     * @return list<array<string, mixed>>
     */
    public function parseUpload(UploadedFile $file): array
    {
        return $this->parseFile($file->getRealPath());
    }

    /**
     * @param  list<array<int, mixed>>  $rows
     */
    private function findHeaderRow(array $rows): ?int
    {
        $limit = min(10, count($rows));

        for ($i = 0; $i < $limit; $i++) {
            $map = $this->mapHeaders($rows[$i]);

            if (isset($map['store_name']) || isset($map['affiliate_url'])) {
                return $i;
            }
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $headerCells
     * @return array<string, int>
     */
    private function mapHeaders(array $headerCells): array
    {
        $map = [];

        foreach ($headerCells as $index => $raw) {
            $header = Str::lower(trim((string) $raw));
            $header = preg_replace('/\s+/', ' ', $header) ?? $header;

            if ($header === '' || in_array($header, self::SENSITIVE_HEADERS, true)) {
                continue;
            }

            $key = match (true) {
                in_array($header, ['stt', 'no', '#', 'stt.'], true) => 'stt',
                in_array($header, ['danh mục', 'danh muc', 'category', 'categories'], true) => 'category',
                in_array($header, ['tên store', 'ten store', 'store', 'store name', 'merchant'], true) => 'store_name',
                in_array($header, ['link web', 'website', 'web', 'site'], true) => 'website',
                in_array($header, ['link affiliate', 'affiliate', 'affiliate link', 'aff link', 'affiliate url'], true) => 'affiliate_url',
                in_array($header, ['mã coupon', 'ma coupon', 'coupon', 'code', 'coupon code'], true) => 'code',
                in_array($header, ['ofer', 'offer', 'offers', 'deal', 'title'], true) => 'offer',
                in_array($header, ['mô tả coupons', 'mo ta coupons', 'mô tả', 'mo ta', 'coupon value', 'description', 'desc'], true) => 'description',
                in_array($header, ['note', 'ghi chú', 'ghi chu'], true) => 'note',
                in_array($header, ['link login', 'login'], true) => 'login',
                in_array($header, ['link ads', 'ads', 'published url'], true) => 'ads',
                default => null,
            };

            if ($key !== null && ! isset($map[$key])) {
                $map[$key] = (int) $index;
            }
        }

        // Untitled description column often sits right after Ofer when header stops early.
        if (! isset($map['description']) && isset($map['offer'])) {
            $map['description'] = $map['offer'] + 1;
        }

        return $map;
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  array<string, int>  $map
     * @return array{code: ?string, title: string, description: ?string, type: string}|null
     */
    private function extractOffer(array $row, array $map): ?array
    {
        $rawCode = $this->cell($row, $map['code'] ?? null);
        $title = $this->cell($row, $map['offer'] ?? null);
        $description = $this->cell($row, $map['description'] ?? null);

        $code = $this->normalizeCode($rawCode);

        if ($title === '' && $description === '' && $code === null) {
            return null;
        }

        if ($title === '') {
            $title = $description !== ''
                ? Str::limit($description, 80, '…')
                : ($code ? "Coupon {$code}" : 'Special offer');
        }

        if ($this->isPolicyNoise($title, $description)) {
            // Keep shipping deals; drop pure returns-policy rows.
            if (preg_match('/return|exchange|refund|policy/i', $title.' '.$description)) {
                return null;
            }
        }

        return [
            'code' => $code,
            'title' => HtmlCleaner::normalizePlainText($title),
            'description' => $description !== '' ? HtmlCleaner::normalizePlainText($description) : null,
            'type' => $code ? 'coupon' : 'discount',
        ];
    }

    private function isPolicyNoise(string $title, string $description): bool
    {
        $hay = Str::lower($title.' '.$description);

        return str_contains($hay, 'return') || str_contains($hay, 'exchange') || str_contains($hay, 'refund policy');
    }

    private function sanitizeStoreName(string $value): string
    {
        $value = trim($value);

        if ($this->isBadStoreName($value)) {
            return '';
        }

        return $value;
    }

    private function isBadStoreName(string $value): bool
    {
        $value = trim($value);

        if ($value === '') {
            return true;
        }

        return (bool) preg_match('/^#(NAME\?|REF!|VALUE!|NULL!|DIV\/0!|N\/A|GETTING_DATA)$/i', $value);
    }

    private function fallbackStoreName(?string $website, ?string $affiliateUrl): string
    {
        foreach ([$website, $affiliateUrl] as $url) {
            if (! $url) {
                continue;
            }

            $host = parse_url($url, PHP_URL_HOST);

            if (is_string($host) && $host !== '') {
                $host = preg_replace('/^www\./', '', strtolower($host)) ?? $host;

                return Str::before($host, '.') ?: $host;
            }
        }

        return 'Store';
    }

    private function normalizeCode(?string $raw): ?string
    {
        $code = trim((string) $raw);

        if ($code === '') {
            return null;
        }

        $lower = Str::lower($code);

        if (in_array($lower, ['no need code', 'no code', 'none', 'n/a', 'na', '-'], true)) {
            return null;
        }

        return Str::limit($code, 100, '');
    }

    private function normalizeUrl(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (! preg_match('#^https?://#i', $value)) {
            if (preg_match('#^[\w.-]+\.[a-z]{2,}(/.*)?$#i', $value)) {
                $value = 'https://'.$value;
            } else {
                return null;
            }
        }

        if (! filter_var($value, FILTER_VALIDATE_URL)) {
            return null;
        }

        return $value;
    }

    private function normalizeStt(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (string) (int) floatval($value);
        }

        return $value;
    }

    private function isStoreStartRow(string $storeName, ?string $stt, ?string $affiliateUrl, ?array $current): bool
    {
        if ($current === null) {
            return $storeName !== '' || $affiliateUrl !== null || $stt !== null;
        }

        if ($stt !== null && $stt !== ($current['stt'] ?? null)) {
            return true;
        }

        if ($storeName !== '' && Str::lower($storeName) !== Str::lower((string) ($current['store_name'] ?? ''))) {
            return true;
        }

        if ($affiliateUrl && $current['affiliate_url'] && $this->hostKey($affiliateUrl) !== $this->hostKey($current['affiliate_url'])) {
            // Same store name but different affiliate host on a new STT-less fill — treat as continuation if name matches.
            if ($storeName === '' || Str::lower($storeName) === Str::lower((string) $current['store_name'])) {
                return false;
            }

            return true;
        }

        // Fill-down rows repeat store name + same STT → continuation.
        return false;
    }

    private function hostKey(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST) ?: $url;

        return Str::lower(preg_replace('/^www\./', '', (string) $host) ?? (string) $host);
    }

    /**
     * @param  array{affiliate_url?: string, store_name?: string, offers?: array}  $store
     */
    private function isValidStore(array $store): bool
    {
        if (! filled($store['affiliate_url'] ?? null)) {
            return false;
        }

        if (! filled($store['store_name'] ?? null)) {
            return false;
        }

        if (($store['offers'] ?? []) === []) {
            // Still importable: create store with a placeholder offer from store name.
            return true;
        }

        return true;
    }

    /**
     * @param  list<array<string, mixed>>  $stores
     * @return list<array<string, mixed>>
     */
    private function mergeDuplicateGroups(array $stores): array
    {
        $merged = [];
        $indexByKey = [];

        foreach ($stores as $store) {
            if (($store['offers'] ?? []) === []) {
                $store['offers'] = [[
                    'code' => null,
                    'title' => 'Current deal',
                    'description' => 'Check the store page for the latest offer.',
                    'type' => 'discount',
                ]];
            }

            $key = Str::lower(trim($store['sheet_name'].'|'.$store['store_name'].'|'.$store['affiliate_url']));

            if (isset($indexByKey[$key])) {
                $existing = &$merged[$indexByKey[$key]];
                foreach ($store['offers'] as $offer) {
                    $existing['offers'][] = $offer;
                }
                $existing['offers'] = $this->uniqueOffers($existing['offers']);
                unset($existing);
                continue;
            }

            $store['offers'] = $this->uniqueOffers($store['offers']);
            $indexByKey[$key] = count($merged);
            $merged[] = $store;
        }

        return $merged;
    }

    /**
     * @param  list<array{code: ?string, title: string, description: ?string, type: string}>  $offers
     * @return list<array{code: ?string, title: string, description: ?string, type: string}>
     */
    private function uniqueOffers(array $offers): array
    {
        $out = [];
        $seen = [];

        foreach ($offers as $offer) {
            $key = Str::lower(trim(($offer['code'] ?? '').'|'.$offer['title']));

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $out[] = $offer;
        }

        return $out;
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function cell(array $row, ?int $index): string
    {
        if ($index === null || ! array_key_exists($index, $row)) {
            return '';
        }

        $value = $row[$index];

        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }

    private function nullable(string $value): ?string
    {
        return $value !== '' ? $value : null;
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
