<?php

namespace App\Filament\Pages;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class TaskCalendar extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'المبيعات';
    protected static ?string $navigationLabel = 'تقويم المهام';
    protected static ?string $title           = 'تقويم المهام والمواعيد';
    protected static ?int    $navigationSort  = 3;
    protected static string  $view            = 'filament.pages.task-calendar';

    // Calendar display
    public string $calendarView  = 'dayGridMonth';
    public string $currentMonth  = '';
    public array  $events        = [];
    public ?string $filterUser   = null;

    // Task form modal
    public bool    $showTaskModal    = false;
    public string  $taskType         = 'call';
    public string  $taskSubject      = '';
    public ?int    $taskLeadId       = null;
    public ?int    $taskAssignedTo   = null;
    public string  $taskScheduledAt  = '';
    public string  $taskDescription  = '';
    public string  $taskColor        = '#3b82f6';

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public function mount(): void
    {
        $this->currentMonth   = now()->format('Y-m');
        $this->taskScheduledAt = now()->format('Y-m-d\TH:i');
        $this->filterUser     = Auth::user()?->hasRole('sales_rep') ? (string) Auth::id() : null;
        $this->loadEvents();
    }

    public function updatedFilterUser(): void  { $this->loadEvents(); }
    public function updatedCurrentMonth(): void { $this->loadEvents(); }

    public function loadEvents(): void
    {
        $start = \Carbon\Carbon::parse($this->currentMonth . '-01')->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $query = LeadActivity::with(['lead', 'user'])
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [$start, $end]);

        if (Auth::user()?->hasRole('sales_rep')) {
            $query->where('user_id', Auth::id());
        } elseif ($this->filterUser) {
            $query->where('user_id', $this->filterUser);
        }

        $this->events = $query->get()->map(function (LeadActivity $a) {
            $color = $a->color ?: (LeadActivity::TYPE_COLORS[$a->type] ?? '#6b7280');
            return [
                'id'              => $a->id,
                'title'           => ($a->subject ?: (LeadActivity::TYPES[$a->type] ?? $a->type))
                                   . ($a->lead ? ' — ' . $a->lead->name : ''),
                'start'           => $a->scheduled_at->toIso8601String(),
                'end'             => $a->scheduled_at->copy()->addHour()->toIso8601String(),
                'backgroundColor' => $a->is_completed ? '#9ca3af' : $color,
                'borderColor'     => $a->is_completed ? '#9ca3af' : $color,
                'textColor'       => '#ffffff',
                'extendedProps'   => [
                    'type'         => $a->type,
                    'type_label'   => LeadActivity::TYPES[$a->type] ?? $a->type,
                    'lead_name'    => $a->lead?->name,
                    'assignee'     => $a->user?->name,
                    'is_completed' => $a->is_completed,
                    'description'  => $a->description,
                ],
            ];
        })->values()->toArray();
    }

    // ── Navigation ──────────────────────────────────────────

    public function prevMonth(): void
    {
        $this->currentMonth = \Carbon\Carbon::parse($this->currentMonth . '-01')
            ->subMonth()->format('Y-m');
        $this->loadEvents();
    }

    public function nextMonth(): void
    {
        $this->currentMonth = \Carbon\Carbon::parse($this->currentMonth . '-01')
            ->addMonth()->format('Y-m');
        $this->loadEvents();
    }

    public function goToday(): void
    {
        $this->currentMonth = now()->format('Y-m');
        $this->loadEvents();
    }

    // ── Task Modal ───────────────────────────────────────────

    public function openTaskModal(?string $date = null): void
    {
        $this->taskType        = 'call';
        $this->taskSubject     = '';
        $this->taskLeadId      = null;
        $this->taskAssignedTo  = Auth::id();
        $this->taskScheduledAt = $date ? $date . 'T09:00' : now()->format('Y-m-d\TH:i');
        $this->taskDescription = '';
        $this->taskColor       = '#3b82f6';
        $this->showTaskModal   = true;
        $this->resetErrorBag();
    }

    public function closeTaskModal(): void
    {
        $this->showTaskModal = false;
    }

    public function saveTask(): void
    {
        $this->validate([
            'taskSubject'    => 'required|min:2',
            'taskLeadId'     => 'required|exists:leads,id',
            'taskAssignedTo' => 'required|exists:users,id',
            'taskScheduledAt'=> 'required|date',
        ], [
            'taskSubject.required'    => 'الموضوع مطلوب',
            'taskLeadId.required'     => 'اختر عميلاً محتملاً',
            'taskAssignedTo.required' => 'اختر المندوب',
            'taskScheduledAt.required'=> 'حدد الوقت',
        ]);

        LeadActivity::create([
            'lead_id'      => $this->taskLeadId,
            'user_id'      => $this->taskAssignedTo,
            'assigned_by'  => Auth::id(),
            'type'         => $this->taskType,
            'subject'      => $this->taskSubject,
            'description'  => $this->taskDescription ?: null,
            'scheduled_at' => $this->taskScheduledAt,
            'is_completed' => false,
            'color'        => $this->taskColor !== LeadActivity::TYPE_COLORS[$this->taskType] ?? null
                              ? $this->taskColor : null,
        ]);

        $this->closeTaskModal();
        $this->loadEvents();

        Notification::make()->title('تم إضافة المهمة')->success()->send();
    }

    public function completeTask(int $id): void
    {
        $activity = LeadActivity::findOrFail($id);

        if (! Auth::user()?->hasAnyRole(['super_admin', 'admin', 'sales_manager'])
            && $activity->user_id !== Auth::id()) {
            Notification::make()->title('غير مصرح لك')->danger()->send();
            return;
        }

        $activity->update(['is_completed' => true, 'completed_at' => now()]);
        $this->loadEvents();
        Notification::make()->title('تم إتمام المهمة')->success()->send();
    }

    public function getSalesReps(): array
    {
        return User::whereHas('roles', fn ($q) =>
            $q->whereIn('name', ['sales_rep', 'sales_manager', 'admin', 'super_admin'])
        )->pluck('name', 'id')->toArray();
    }

    public function getLeadsForSelect(): array
    {
        return Lead::whereNotIn('status', ['lost'])
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }
}
