<?php

namespace App\Filament\Pages;

use App\Models\ExhibitionAccess;
use App\Models\User;
use App\Services\Poultry\PoultryWelcomeWhatsApp;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ExhibitionPermissions extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'المبيعات';

    protected static ?string $navigationLabel = 'صلاحيات المعرض';

    protected static ?string $title = 'صلاحيات المعرض';

    protected static ?int $navigationSort = 7;

    protected static string $view = 'filament.pages.exhibition-permissions';

    /** @var list<array<string, mixed>> */
    public array $reps = [];

    public string $welcomeMessage = '';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'sales_manager']) ?? false;
    }

    public function mount(): void
    {
        $saved = settings('poultry.whatsapp_welcome', PoultryWelcomeWhatsApp::DEFAULT_TEMPLATE);
        $this->welcomeMessage = trim((string) $saved) !== ''
            ? (string) $saved
            : PoultryWelcomeWhatsApp::DEFAULT_TEMPLATE;
        $this->loadReps();
    }

    public function saveWelcome(): void
    {
        abort_unless(static::canAccess(), 403);

        app(PoultryWelcomeWhatsApp::class)->saveTemplate($this->welcomeMessage, auth()->id());

        Notification::make()
            ->title('تم حفظ نص رسالة الواتساب')
            ->success()
            ->send();
    }

    public function toggle(int $userId, string $field): void
    {
        abort_unless(in_array($field, ['report_daily', 'report_weekly', 'view_team'], true), 404);
        abort_unless(static::canAccess(), 403);

        $rep = User::query()
            ->whereKey($userId)
            ->whereHas('roles', fn ($query) => $query->where('name', 'sales_rep'))
            ->firstOrFail();

        $access = ExhibitionAccess::firstOrCreate(['user_id' => $rep->id]);
        $access->{$field} = ! $access->{$field};
        $access->save();

        $this->loadReps();
    }

    public function loadReps(): void
    {
        $this->reps = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('name', 'sales_rep'))
            ->with('exhibitionAccess')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'report_daily' => $user->exhibitionAccess?->report_daily ?? true,
                'report_weekly' => $user->exhibitionAccess?->report_weekly ?? true,
                'view_team' => $user->exhibitionAccess?->view_team ?? false,
            ])
            ->all();
    }
}
