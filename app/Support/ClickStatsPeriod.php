<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ClickStatsPeriod
{
    public function __construct(
        public readonly CarbonInterface $date,
        public readonly string $day,
        public readonly string $monthStart,
        public readonly string $monthEnd,
        public readonly string $yearStart,
        public readonly string $yearEnd,
    ) {
    }

    public static function fromRequest(Request $request, string $key = 'date'): self
    {
        $raw = trim((string) $request->query($key, ''));

        try {
            $date = $raw !== ''
                ? Carbon::createFromFormat('Y-m-d', $raw)->startOfDay()
                : now()->startOfDay();
        } catch (\Throwable) {
            $date = now()->startOfDay();
        }

        return new self(
            date: $date,
            day: $date->toDateString(),
            monthStart: $date->copy()->startOfMonth()->toDateString(),
            monthEnd: $date->copy()->endOfMonth()->toDateString(),
            yearStart: $date->copy()->startOfYear()->toDateString(),
            yearEnd: $date->copy()->endOfYear()->toDateString(),
        );
    }

    public function applyDayMonthYearSums(Builder $query, string $relation = 'clickDailies'): Builder
    {
        return $query
            ->withSum([
                "{$relation} as day_clicks" => fn (Builder $q) => $q->whereDate('stat_date', $this->day),
            ], 'clicks')
            ->withSum([
                "{$relation} as month_clicks" => fn (Builder $q) => $q
                    ->whereDate('stat_date', '>=', $this->monthStart)
                    ->whereDate('stat_date', '<=', $this->monthEnd),
            ], 'clicks')
            ->withSum([
                "{$relation} as year_clicks" => fn (Builder $q) => $q
                    ->whereDate('stat_date', '>=', $this->yearStart)
                    ->whereDate('stat_date', '<=', $this->yearEnd),
            ], 'clicks');
    }
}
