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

    public array $columns = [];

    public ?int $filterUser = null;

    public ?string $filterSource = null;

    public ?string $filterPriority = null;

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

    public function updatedFilterUser(): void
    {
        $this->loadColumns();
    }

    public function updatedFilterSource(): void
    {
        $this->loadColumns();
    }

    public function updatedFilterPriority(): void
    {
        $this->loadColumns();
    }

    public function loadColumns(): void
    {
        $statuses = array_keys(Lead::STATUSES);
        $this->columns = [];

        foreach ($statuses as $status) {
            $query = Lead::byStatus($status)
                ->with('assignedUser');

            if ($this->filterUser) {
                $query->where('assigned_to', $this->filterUser);
            }
            if ($this->filterSource) {
                $query->where('source', $this->filterSource);
            }
            if ($this->filterPriority) {
                $query->where('priority', $this->filterPriority);
            }

            // sales_rep يرى عملاءه فقط
            if (Auth::user()?->hasRole('sales_rep')) {
                $query->where('assigned_to', Auth::id());
            }

            $leads = $query->orderByDesc('score')->get();

            $this->columns[$status] = [
                'label' => Lead::STATUSES[$status],
                'color' => Lead::STATUS_COLORS[$status] ?? 'gray',
                'count' => $leads->count(),
                'total_budget' => $leads->sum('estimated_budget'),
                'leads' => $leads->map(fn ($l) => [
                    'id' => $l->id,
                    'name' => $l->name,
                    'company' => $l->company,
                    'score' => $l->score,
                    'priority' => $l->priority,
                    'estimated_budget' => $l->estimated_budget,
                    'assignee' => $l->assignedUser?->name,
                    'days_since_contact' => $l->days_since_last_contact,
                    'whatsapp' => $l->whatsapp,
                    'edit_url' => route('filament.admin.resources.leads.edit', $l),
                ])->toArray(),
            ];
        }
    }

    public function moveCard(int $leadId, string $newStatus): void
    {
        if (! array_key_exists($newStatus, Lead::STATUSES)) {
            return;
        }

        $lead = Lead::findOrFail($leadId);
        $oldStatus = $lead->status;

        if ($oldStatus === $newStatus) {
            return;
        }

        $lead->update(['status' => $newStatus]);

        // تسجيل تغيير الحالة كنشاط تلقائي
        $lead->activities()->create([
            'user_id' => Auth::id(),
            'type' => 'status_change',
            'subject' => 'تغيير حالة Pipeline',
            'description' => sprintf(
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
            ->body("{$lead->name} → ".Lead::STATUSES[$newStatus])
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
