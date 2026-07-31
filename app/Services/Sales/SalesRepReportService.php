<?php

namespace App\Services\Sales;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SalesRepReportService
{
    /**
     * @return Collection<int, User>
     */
    public function salesUsers(?int $onlyUserId = null): Collection
    {
        $query = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['sales_rep', 'sales_manager']))
            ->orderBy('name');

        if ($onlyUserId) {
            $query->where('id', $onlyUserId);
        }

        return $query->get();
    }

    /**
     * @return array{
     *   from: string,
     *   to: string,
     *   mode: string,
     *   summary: array,
     *   reps: list<array>,
     *   activities: list<array>
     * }
     */
    public function build(string $mode, string $dateFrom, string $dateTo, ?int $userId = null): array
    {
        $from = Carbon::parse($dateFrom)->startOfDay();
        $to = Carbon::parse($dateTo)->endOfDay();

        $users = $this->salesUsers($userId);
        $reps = [];
        $allActivityRows = [];

        $summary = [
            'calls' => 0,
            'whatsapp' => 0,
            'visits' => 0,
            'meetings' => 0,
            'other' => 0,
            'total_activities' => 0,
            'duration_minutes' => 0,
            'completed' => 0,
            'pending' => 0,
            'new_leads' => 0,
            'won' => 0,
        ];

        foreach ($users as $user) {
            $activitiesQuery = LeadActivity::query()
                ->with(['lead:id,name,phone,lead_number', 'user:id,name'])
                ->where('user_id', $user->id)
                ->whereBetween('created_at', [$from, $to]);

            $activities = (clone $activitiesQuery)->orderByDesc('created_at')->get();

            $byType = $activities->groupBy('type')->map->count()->all();
            $calls = (int) ($byType['call'] ?? 0);
            $whatsapp = (int) ($byType['whatsapp'] ?? 0);
            $visits = (int) ($byType['visit'] ?? 0);
            $meetings = (int) ($byType['meeting'] ?? 0);
            $duration = (int) $activities->sum(fn ($a) => (int) ($a->duration_minutes ?? 0));
            $completed = $activities->where('is_completed', true)->count();

            $pending = LeadActivity::query()
                ->where('user_id', $user->id)
                ->where('is_completed', false)
                ->whereNotNull('scheduled_at')
                ->where('scheduled_at', '<=', $to)
                ->count();

            $newLeads = Lead::query()
                ->where('assigned_to', $user->id)
                ->whereBetween('created_at', [$from, $to])
                ->count();

            $won = Lead::query()
                ->where('assigned_to', $user->id)
                ->where('status', 'won')
                ->whereBetween('updated_at', [$from, $to])
                ->count();

            $other = $activities->count() - $calls - $whatsapp - $visits - $meetings;

            $reps[] = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'calls' => $calls,
                'whatsapp' => $whatsapp,
                'visits' => $visits,
                'meetings' => $meetings,
                'other' => max(0, $other),
                'total_activities' => $activities->count(),
                'duration_minutes' => $duration,
                'completed' => $completed,
                'pending' => $pending,
                'new_leads' => $newLeads,
                'won' => $won,
                'outcomes' => $activities
                    ->whereNotNull('outcome')
                    ->groupBy('outcome')
                    ->map->count()
                    ->all(),
            ];

            foreach ($activities as $activity) {
                $allActivityRows[] = [
                    'id' => $activity->id,
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'type' => $activity->type,
                    'type_label' => LeadActivity::TYPES[$activity->type] ?? $activity->type,
                    'subject' => $activity->subject,
                    'description' => $activity->description,
                    'outcome' => $activity->outcome,
                    'outcome_label' => LeadActivity::OUTCOMES[$activity->outcome] ?? ($activity->outcome ?: '—'),
                    'duration_minutes' => (int) ($activity->duration_minutes ?? 0),
                    'lead_name' => $activity->lead?->name,
                    'lead_phone' => $activity->lead?->phone,
                    'lead_number' => $activity->lead?->lead_number,
                    'is_completed' => (bool) $activity->is_completed,
                    'created_at' => optional($activity->created_at)?->format('Y-m-d H:i'),
                    'scheduled_at' => optional($activity->scheduled_at)?->format('Y-m-d H:i'),
                    'completed_at' => optional($activity->completed_at)?->format('Y-m-d H:i'),
                ];
            }

            $summary['calls'] += $calls;
            $summary['whatsapp'] += $whatsapp;
            $summary['visits'] += $visits;
            $summary['meetings'] += $meetings;
            $summary['other'] += max(0, $other);
            $summary['total_activities'] += $activities->count();
            $summary['duration_minutes'] += $duration;
            $summary['completed'] += $completed;
            $summary['pending'] += $pending;
            $summary['new_leads'] += $newLeads;
            $summary['won'] += $won;
        }

        usort($reps, fn ($a, $b) => $b['total_activities'] <=> $a['total_activities']);

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'mode' => $mode,
            'summary' => $summary,
            'reps' => $reps,
            'activities' => $allActivityRows,
        ];
    }

    public function periodForMode(string $mode, ?string $anchorDate = null): array
    {
        $anchor = $anchorDate ? Carbon::parse($anchorDate) : now();

        return match ($mode) {
            'daily' => [
                $anchor->toDateString(),
                $anchor->toDateString(),
            ],
            'yesterday' => [
                $anchor->copy()->subDay()->toDateString(),
                $anchor->copy()->subDay()->toDateString(),
            ],
            'weekly' => [
                $anchor->copy()->startOfWeek()->toDateString(),
                $anchor->copy()->endOfWeek()->toDateString(),
            ],
            default => [
                $anchor->copy()->startOfMonth()->toDateString(),
                $anchor->copy()->endOfMonth()->toDateString(),
            ],
        };
    }
}
