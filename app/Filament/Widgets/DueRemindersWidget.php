<?php

namespace App\Filament\Widgets;

use App\Models\LeadReminder;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class DueRemindersWidget extends Widget
{
    protected static string $view = 'filament.widgets.due-reminders';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    public function getReminders(): Collection
    {
        return LeadReminder::with('lead')
            ->where('user_id', Auth::id())
            ->where('status', 'pending')
            ->where('remind_at', '<=', now()->addDay())
            ->orderBy('remind_at')
            ->limit(5)
            ->get();
    }

    public function snooze(int $id): void
    {
        $reminder = LeadReminder::findOrFail($id);
        $reminder->update([
            'status' => 'snoozed',
            'snoozed_until' => now()->addHour(),
            'snooze_count' => $reminder->snooze_count + 1,
        ]);

        Notification::make()->title('تم تأجيل التذكير')->warning()->send();
    }

    public function complete(int $id): void
    {
        $reminder = LeadReminder::findOrFail($id);
        $reminder->update(['status' => 'completed', 'completed_at' => now()]);

        Notification::make()->title('تم إتمام التذكير')->success()->send();
    }
}
