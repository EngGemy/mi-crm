<?php

namespace App\Filament\Pages;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadReminder;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class MyTasks extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $navigationGroup = 'المبيعات';

    protected static ?string $navigationLabel = 'مهامي اليوم';

    protected static ?string $title = 'مهامي اليوم والمواعيد';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.my-tasks';

    public array $todayTasks      = [];
    public array $dueReminders    = [];
    public array $upcomingMeetings = [];
    public array $calendarDays    = [];
    public array $leadsForSelect  = [];

    // Add-task form state
    public bool    $showAddTask     = false;
    public string  $newType         = 'call';
    public string  $newSubject      = '';
    public ?int    $newLeadId       = null;
    public ?string $newScheduledAt  = null;

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public function mount(): void
    {
        $this->newScheduledAt = now()->format('Y-m-d\TH:i');
        $this->loadData();
    }

    public function loadData(): void
    {
        $userId = Auth::id();

        $this->todayTasks = LeadActivity::with('lead')
            ->where('user_id', $userId)
            ->where('is_completed', false)
            ->whereNotNull('scheduled_at')
            ->whereDate('scheduled_at', '<=', now()->toDateString())
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn ($a) => [
                'id'             => $a->id,
                'type'           => $a->type,
                'type_label'     => LeadActivity::TYPES[$a->type] ?? $a->type,
                'subject'        => $a->subject,
                'lead_name'      => $a->lead?->name,
                'lead_id'        => $a->lead_id,
                'scheduled_at'   => $a->scheduled_at?->format('H:i'),
                'scheduled_date' => $a->scheduled_at?->format('Y-m-d'),
                'is_overdue'     => $a->scheduled_at?->isPast(),
                'edit_url'       => route('filament.admin.resources.leads.edit', $a->lead_id),
            ])
            ->toArray();

        $this->dueReminders = LeadReminder::with('lead')
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'snoozed'])
            ->whereDate('remind_at', '<=', now()->toDateString())
            ->orderBy('remind_at')
            ->get()
            ->map(fn ($r) => [
                'id'         => $r->id,
                'title'      => $r->title,
                'lead_name'  => $r->lead?->name,
                'remind_at'  => $r->remind_at?->format('Y-m-d H:i'),
                'type_label' => LeadReminder::TYPES[$r->type] ?? $r->type,
                'is_overdue' => $r->remind_at?->isPast(),
            ])
            ->toArray();

        $this->upcomingMeetings = LeadActivity::with('lead')
            ->where('user_id', $userId)
            ->where('is_completed', false)
            ->whereIn('type', ['meeting', 'visit', 'call'])
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [now()->startOfDay(), now()->addDays(7)])
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn ($a) => [
                'id'           => $a->id,
                'type'         => $a->type,
                'type_label'   => LeadActivity::TYPES[$a->type] ?? $a->type,
                'subject'      => $a->subject,
                'lead_name'    => $a->lead?->name,
                'scheduled_at' => $a->scheduled_at?->format('Y-m-d H:i'),
                'day_label'    => $a->scheduled_at?->isToday() ? 'اليوم'
                    : ($a->scheduled_at?->isTomorrow() ? 'غداً'
                    : $a->scheduled_at?->format('D d/m')),
                'edit_url'     => route('filament.admin.resources.leads.edit', $a->lead_id),
            ])
            ->toArray();

        $this->leadsForSelect = Lead::whereNotIn('status', ['lost'])
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $this->buildCalendar($userId);
    }

    protected function buildCalendar(int $userId): void
    {
        $start = now()->startOfMonth();
        $end   = now()->endOfMonth();

        $activityDays = LeadActivity::where('user_id', $userId)
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [$start, $end])
            ->selectRaw('DATE(scheduled_at) as day, COUNT(*) as cnt')
            ->groupBy('day')
            ->pluck('cnt', 'day')
            ->toArray();

        $days    = [];
        $blanks  = $start->dayOfWeek;
        for ($i = 0; $i < $blanks; $i++) {
            $days[] = null;
        }
        for ($d = 1; $d <= $end->day; $d++) {
            $dateKey = now()->format('Y-m') . '-' . str_pad($d, 2, '0', STR_PAD_LEFT);
            $days[]  = [
                'day'      => $d,
                'is_today' => $d === now()->day,
                'count'    => $activityDays[$dateKey] ?? 0,
            ];
        }

        $this->calendarDays = $days;
    }

    public function saveNewTask(): void
    {
        $this->validate(
            [
                'newSubject' => 'required|min:2',
                'newLeadId'  => 'required|exists:leads,id',
            ],
            [
                'newSubject.required' => 'الموضوع مطلوب',
                'newLeadId.required'  => 'اختر عميلاً محتملاً',
            ]
        );

        LeadActivity::create([
            'lead_id'      => $this->newLeadId,
            'user_id'      => Auth::id(),
            'type'         => $this->newType,
            'subject'      => $this->newSubject,
            'scheduled_at' => $this->newScheduledAt ?: now(),
            'is_completed' => false,
        ]);

        $this->newSubject    = '';
        $this->newLeadId     = null;
        $this->newScheduledAt = now()->format('Y-m-d\TH:i');
        $this->showAddTask   = false;
        $this->loadData();

        Notification::make()->title('تمت إضافة المهمة بنجاح')->success()->send();
    }

    public function completeTask(int $activityId): void
    {
        $activity = LeadActivity::findOrFail($activityId);
        $activity->update(['is_completed' => true, 'completed_at' => now()]);

        $this->loadData();

        Notification::make()->title('تم إتمام المهمة')->success()->send();
    }

    public function snoozeReminder(int $reminderId): void
    {
        $reminder = LeadReminder::findOrFail($reminderId);
        $reminder->update([
            'status'       => 'snoozed',
            'snoozed_until' => now()->addHour(),
            'snooze_count' => $reminder->snooze_count + 1,
        ]);

        $this->loadData();

        Notification::make()->title('تم تأجيل التذكير ساعة')->warning()->send();
    }
}
