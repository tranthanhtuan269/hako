<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffiliateExcelImport;
use App\Models\AffiliateExcelImportItem;
use App\Support\AffiliateExcelImportProcessor;
use App\Support\AffiliateExcelParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AffiliateExcelImportController extends Controller
{
    public function index(): View
    {
        $pendingItems = AffiliateExcelImportItem::query()
            ->with('import')
            ->pending()
            ->orderBy('id')
            ->paginate(30, ['*'], 'pending_page');

        $recentItems = AffiliateExcelImportItem::query()
            ->with(['import', 'store'])
            ->whereIn('status', [
                AffiliateExcelImportItem::STATUS_DONE,
                AffiliateExcelImportItem::STATUS_FAILED,
            ])
            ->orderByDesc('processed_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $pendingCount = AffiliateExcelImportItem::query()->pending()->count();

        return view('admin.affiliate-excel-import.index', [
            'pendingItems' => $pendingItems,
            'recentItems' => $recentItems,
            'pendingCount' => $pendingCount,
        ]);
    }

    public function upload(Request $request, AffiliateExcelParser $parser): RedirectResponse
    {
        $data = $request->validate([
            'excel_file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $data['excel_file'];
        $userId = (int) auth()->id();
        $storedPath = $file->store('affiliate-excel-imports/'.$userId, 'local');

        try {
            $parsed = $parser->parseFile(Storage::disk('local')->path($storedPath));
        } catch (\Throwable $e) {
            report($e);
            Storage::disk('local')->delete($storedPath);

            return redirect()
                ->route('admin.affiliate-excel-import.index')
                ->with('error', 'Could not read Excel file: '.$e->getMessage());
        }

        if ($parsed === []) {
            Storage::disk('local')->delete($storedPath);

            return redirect()
                ->route('admin.affiliate-excel-import.index')
                ->with('error', 'No valid stores found. Need columns like Tên Store + Link affiliate.');
        }

        $import = AffiliateExcelImport::create([
            'user_id' => $userId,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'status' => 'parsed',
            'total_items' => count($parsed),
            'processed_items' => 0,
            'failed_items' => 0,
        ]);

        foreach ($parsed as $row) {
            AffiliateExcelImportItem::create([
                'affiliate_excel_import_id' => $import->id,
                'user_id' => $userId,
                'sheet_name' => $row['sheet_name'] ?? null,
                'source_row' => $row['source_row'] ?? null,
                'stt' => $row['stt'] ?? null,
                'category_name' => $row['category_name'] ?? null,
                'store_name' => $row['store_name'],
                'website' => $row['website'] ?? null,
                'affiliate_url' => $row['affiliate_url'],
                'offers' => $row['offers'] ?? [],
                'status' => AffiliateExcelImportItem::STATUS_PENDING,
            ]);
        }

        $import->refreshCounters();

        return redirect()
            ->route('admin.affiliate-excel-import.index')
            ->with('success', 'Uploaded “'.$import->original_filename.'” — '.$import->total_items.' store record(s) queued as pending.');
    }

    public function processNext(Request $request, AffiliateExcelImportProcessor $processor): JsonResponse
    {
        $options = $this->couponSourceOptions($request);

        $item = AffiliateExcelImportItem::query()
            ->pending()
            ->orderBy('id')
            ->first();

        if (! $item) {
            return response()->json([
                'done' => true,
                'pending_remaining' => 0,
                'message' => 'No pending records left.',
            ]);
        }

        @set_time_limit(180);
        $result = $processor->begin($item, $options);
        $pendingRemaining = AffiliateExcelImportItem::query()->pending()->count();

        return response()->json([
            ...$result,
            'done' => false,
            'pending_remaining' => $pendingRemaining,
            'process_step_url' => route('admin.affiliate-excel-import.process-step', $item),
        ]);
    }

    public function processItem(Request $request, AffiliateExcelImportItem $item, AffiliateExcelImportProcessor $processor): JsonResponse
    {
        if ($item->status !== AffiliateExcelImportItem::STATUS_PENDING
            && $item->status !== AffiliateExcelImportItem::STATUS_FAILED
            && $item->status !== AffiliateExcelImportItem::STATUS_PROCESSING) {
            return response()->json([
                'ok' => false,
                'message' => 'Only pending/failed records can be processed.',
            ], 422);
        }

        @set_time_limit(180);
        $result = $processor->begin($item->fresh(), $this->couponSourceOptions($request));

        return response()->json([
            ...$result,
            'pending_remaining' => AffiliateExcelImportItem::query()->pending()->count(),
            'process_step_url' => route('admin.affiliate-excel-import.process-step', $item),
        ]);
    }

    /**
     * @return array{include_detected_coupons: bool}
     */
    private function couponSourceOptions(Request $request): array
    {
        $validated = $request->validate([
            'include_detected_coupons' => ['nullable', 'boolean'],
            'coupon_source' => ['nullable', 'in:excel_only,excel_and_detected'],
        ]);

        if (array_key_exists('include_detected_coupons', $validated)) {
            return ['include_detected_coupons' => (bool) $validated['include_detected_coupons']];
        }

        return [
            'include_detected_coupons' => ($validated['coupon_source'] ?? 'excel_only') === 'excel_and_detected',
        ];
    }

    public function processStep(Request $request, AffiliateExcelImportItem $item, AffiliateExcelImportProcessor $processor): JsonResponse
    {
        $data = $request->validate([
            'step' => ['required', 'string', 'max:50'],
        ]);

        @set_time_limit(180);
        $result = $processor->runStep($item->fresh(), $data['step']);
        $pendingRemaining = AffiliateExcelImportItem::query()->pending()->count();

        return response()->json([
            ...$result,
            'pending_remaining' => $pendingRemaining,
            'batch_done' => $pendingRemaining === 0 && ($result['item_done'] ?? false),
        ]);
    }

    public function destroyItem(AffiliateExcelImportItem $item): RedirectResponse
    {
        if ($item->status === AffiliateExcelImportItem::STATUS_PROCESSING) {
            return redirect()
                ->route('admin.affiliate-excel-import.index')
                ->with('error', 'Cannot delete an item that is currently processing.');
        }

        $import = $item->import;
        $item->delete();
        $import?->refreshCounters();

        return redirect()
            ->route('admin.affiliate-excel-import.index')
            ->with('success', 'Pending record removed.');
    }

    public function clearPending(): RedirectResponse
    {
        $imports = AffiliateExcelImport::query()
            ->whereHas('items', fn ($q) => $q->pending())
            ->get();

        AffiliateExcelImportItem::query()->pending()->delete();

        foreach ($imports as $import) {
            $import->refreshCounters();
        }

        return redirect()
            ->route('admin.affiliate-excel-import.index')
            ->with('success', 'All pending records cleared.');
    }

    public function template(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Kickbooster');
        $headers = [
            'STT', 'Danh Mục', 'Tên Store', 'Link Web', 'Link Login', 'User', 'Pass',
            'Link affiliate', 'Mã Coupon', 'Ofer', 'Mô Tả Coupons',
        ];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray([
            '1', '', 'example-store', 'https://example.com', '', '', '',
            'https://example.com/?ref=YOURID', 'SAVE10', '10% OFF', 'Get 10% Off Entire Order',
        ], null, 'A2');
        $sheet->fromArray([
            '1', '', 'example-store', 'https://example.com', '', '', '',
            'https://example.com/?ref=YOURID', 'No Need Code', 'Free Ship', 'Free shipping on orders over $50',
        ], null, 'A3');

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'affiliate-import-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
