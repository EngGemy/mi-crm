<?php

namespace App\Services\Poultry;

use App\Enums\PoultryProjectType;
use App\Models\PoultryQuotation;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * One day's exhibition book: each salesperson, their clients, and each quote.
 */
class ExhibitionDailyReport
{
    /** @return array<string, mixed> */
    public function forDate(Carbon $date, User $viewer): array
    {
        return $this->forRange($date, $date, $viewer);
    }

    /** @return array<string, mixed> */
    public function forRange(Carbon $from, Carbon $to, User $viewer): array
    {
        $start = $from->copy()->startOfDay();
        $end = $to->copy()->endOfDay();
        $ownOnly = PoultryQuoteAccess::seesOwnQuotesOnly($viewer);

        $quotes = PoultryQuotation::query()
            ->with('creator:id,name')
            ->whereBetween('created_at', [$start, $end])
            ->when($ownOnly, fn ($query) => $query->where('created_by', $viewer->id))
            ->orderBy('created_at')
            ->get();

        $repIds = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('name', 'sales_rep'))
            ->when($ownOnly, fn ($query) => $query->whereKey($viewer->id))
            ->pluck('id');

        $userIds = $repIds
            ->merge($quotes->pluck('created_by')->filter())
            ->unique()
            ->values();

        $users = User::query()
            ->whereIn('id', $userIds)
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        $grouped = $quotes->groupBy(fn (PoultryQuotation $quote) => (string) ($quote->created_by ?? '0'));

        $reps = [];
        foreach ($users as $user) {
            $reps[] = $this->repRow($user->id, $user->name, $grouped->get((string) $user->id, collect()));
        }

        if (! $ownOnly && $grouped->has('0')) {
            $reps[] = $this->repRow(0, 'غير منسوب', $grouped->get('0'));
        }

        return [
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'quotes_count' => $quotes->count(),
            'clients_count' => $quotes->pluck('client_name')->filter()->unique()->count(),
            'total' => (float) $quotes->sum(fn (PoultryQuotation $quote) => (float) $quote->total),
            'reps' => $reps,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, PoultryQuotation>  $quotes
     * @return array<string, mixed>
     */
    private function repRow(int $id, string $name, $quotes): array
    {
        $clients = $quotes->map(function (PoultryQuotation $quote) {
            return [
                'name' => $quote->client_name,
                'phone' => $quote->client_phone ?: '—',
                'quote_number' => $quote->quote_number,
                'project' => PoultryProjectType::tryFrom((string) $quote->project_type)?->labelAr() ?? ($quote->project_type ?: '—'),
                'status' => PoultryQuotation::STATUSES[$quote->status] ?? $quote->status,
                'total' => (float) $quote->total,
                'at' => optional($quote->created_at)->format('H:i') ?: '—',
            ];
        })->values()->all();

        return [
            'id' => $id,
            'name' => $name,
            'quotes_count' => count($clients),
            'clients_count' => collect($clients)->pluck('name')->unique()->count(),
            'total' => array_sum(array_column($clients, 'total')),
            'clients' => $clients,
        ];
    }
}
