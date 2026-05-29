<?php

namespace App\Filament\Pages;

use App\Models\Lead;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class LeadPipeline extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-view-columns';

    protected static ?string $navigationGroup = 'المبيعات';

    protected static ?string $navigationLabel = 'Pipeline الكانبان';

    protected static ?string $title = 'Pipeline — رحلة العملاء المحتملين';

    protected static ?int $navigationSort = 0;

    protected static string $view = 'filament.pages.lead-pipeline';

    // Columns data
    public array $columns = [];

    // Filters
    public ?int    $filterUser     = null;
    public ?string $filterSource   = null;
    public ?string $filterPriority = null;

    // Create modal state
    public bool   $showModal    = false;
    public string $modalStatus  = 'new';

    // Lead form
    public string  $newName            = '';
    public string  $newPhone           = '';
    public string  $newWhatsapp        = '';
    public string  $newCompany         = '';
    public string  $newSource          = 'whatsapp';
    public string  $newPriority        = 'medium';
    public ?int    $newAssignedTo      = null;
    public ?string $newEstimatedBudget = null;
    public string  $newNotes           = '';

    // Todo list inside modal
    public array  $modalTasks    = [];
    public string $newTaskInput  = '';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('leads.view_any') || auth()->user()?->can('leads.view_own') ?? false;
    }

    public function mount(): void
    {
        if (Auth::user()?->hasRole('sales_rep')) {
            $this->filterUser = Auth::id();
        }
        $this->loadColumns();
    }

    public function updatedFilterUser(): void    { $this->loadColumns(); }
    public function updatedFilterSource(): void  { $this->loadColumns(); }
    public function updatedFilterPriority(): void{ $this->loadColumns(); }

    public function loadColumns(): void
    {
        $statuses = array_keys(Lead::STATUSES);
        $this->columns = [];

        foreach ($statuses as $status) {
            $query = Lead::byStatus($status)->with('assignedUser');

            if ($this->filterUser)     $query->where('assigned_to', $this->filterUser);
            if ($this->filterSource)   $query->where('source', $this->filterSource);
            if ($this->filterPriority) $query->where('priority', $this->filterPriority);

            if (Auth::user()?->hasRole('sales_rep')) {
                $query->where('assigned_to', Auth::id());
            }

            $leads = $query->orderByDesc('score')->get();

            $this->columns[$status] = [
                'label'        => Lead::STATUSES[$status],
                'color'        => Lead::STATUS_COLORS[$status] ?? 'gray',
                'count'        => $leads->count(),
                'total_budget' => $leads->sum('estimated_budget'),
                'leads'        => $leads->map(fn ($l) => [
                    'id'                => $l->id,
                    'name'              => $l->name,
                    'company'           => $l->company,
                    'score'             => $l->score,
                    'priority'          => $l->priority,
                    'estimated_budget'  => $l->estimated_budget,
                    'assignee'          => $l->assignedUser?->name,
                    'days_since_contact'=> $l->days_since_last_contact,
                    'whatsapp'          => $l->whatsapp,
                    'edit_url'          => route('filament.admin.resources.leads.edit', $l),
                ])->toArray(),
            ];
        }
    }

    // ── Modal ──────────────────────────────────────────────────

    public function openCreateModal(string $status): void
    {
        $this->modalStatus = $status;
        $this->resetModalForm();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetModalForm();
    }

    protected function resetModalForm(): void
    {
        $this->newName            = '';
        $this->newPhone           = '';
        $this->newWhatsapp        = '';
        $this->newCompany         = '';
        $this->newSource          = 'whatsapp';
        $this->newPriority        = 'medium';
        $this->newAssignedTo      = null;
        $this->newEstimatedBudget = null;
        $this->newNotes           = '';
        $this->modalTasks         = [];
        $this->newTaskInput       = '';
        $this->resetErrorBag();
    }

    // ── Todo list ──────────────────────────────────────────────

    public function addModalTask(): void
    {
        $text = trim($this->newTaskInput);
        if ($text !== '') {
            $this->modalTasks[] = ['text' => $text, 'done' => false];
            $this->newTaskInput = '';
        }
    }

    public function removeModalTask(int $index): void
    {
        array_splice($this->modalTasks, $index, 1);
        $this->modalTasks = array_values($this->modalTasks);
    }

    public function toggleModalTask(int $index): void
    {
        if (isset($this->modalTasks[$index])) {
            $this->modalTasks[$index]['done'] = !$this->modalTasks[$index]['done'];
        }
    }

    // ── Save ──────────────────────────────────────────────────

    public function saveLead(): void
    {
        $this->validate(
            ['newName' => 'required|min:2'],
            ['newName.required' => 'اسم العميل مطلوب']
        );

        $lead = Lead::create([
            'name'              => $this->newName,
            'phone'             => $this->newPhone  ?: null,
            'whatsapp'          => $this->newWhatsapp ?: $this->newPhone ?: null,
            'company'           => $this->newCompany  ?: null,
            'source'            => $this->newSource,
            'priority'          => $this->newPriority,
            'status'            => $this->modalStatus,
            'assigned_to'       => $this->newAssignedTo ?: Auth::id(),
            'created_by'        => Auth::id(),
            'estimated_budget'  => $this->newEstimatedBudget ?: null,
            'notes'             => $this->newNotes  ?: null,
            'score'             => 50,
            'next_followup_at'  => now()->addDays(2),
        ]);

        foreach ($this->modalTasks as $task) {
            $lead->activities()->create([
                'user_id'      => Auth::id(),
                'type'         => 'note',
                'subject'      => $task['text'],
                'is_completed' => $task['done'],
                'completed_at' => $task['done'] ? now() : null,
                'scheduled_at' => now()->addDay(),
            ]);
        }

        $this->closeModal();
        $this->loadColumns();

        Notification::make()
            ->title('تم إضافة العميل المحتمل')
            ->body($lead->name . ' → ' . Lead::STATUSES[$this->modalStatus])
            ->success()
            ->send();
    }

    // ── Drag & drop ───────────────────────────────────────────

    public function moveCard(int $leadId, string $newStatus): void
    {
        if (! array_key_exists($newStatus, Lead::STATUSES)) return;

        $lead      = Lead::findOrFail($leadId);
        $oldStatus = $lead->status;
        if ($oldStatus === $newStatus) return;

        $lead->update(['status' => $newStatus]);

        $lead->activities()->create([
            'user_id'      => Auth::id(),
            'type'         => 'status_change',
            'subject'      => 'تغيير حالة Pipeline',
            'description'  => sprintf(
                'تغيير الحالة من «%s» إلى «%s» عبر الكانبان',
                Lead::STATUSES[$oldStatus] ?? $oldStatus,
                Lead::STATUSES[$newStatus] ?? $newStatus
            ),
            'is_completed' => true,
            'completed_at' => now(),
        ]);

        $this->loadColumns();

        Notification::make()
            ->title('تم تحديث الحالة')
            ->body("{$lead->name} → " . Lead::STATUSES[$newStatus])
            ->success()
            ->send();
    }

    public function getSalesReps(): array
    {
        return User::whereHas('roles', fn ($q) => $q->whereIn('name', ['sales_rep', 'sales_manager']))
            ->pluck('name', 'id')
            ->toArray();
    }
}
