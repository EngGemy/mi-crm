<?php

namespace App\Console\Commands;

use App\Models\LeadReminder;
use App\Notifications\LeadReminderNotification;
use Illuminate\Console\Command;

class SendLeadReminders extends Command
{
    protected $signature = 'leads:send-reminders';

    protected $description = 'إرسال تذكيرات Follow-up للـ Leads';

    public function handle(): int
    {
        $dueReminders = LeadReminder::due()
            ->where('notified', false)
            ->with(['lead', 'user'])
            ->get();

        $count = 0;
        foreach ($dueReminders as $reminder) {
            try {
                $reminder->user->notify(new LeadReminderNotification($reminder));
                $reminder->update([
                    'notified' => true,
                    'notified_at' => now(),
                ]);
                $count++;
            } catch (\Exception $e) {
                \Log::error("Failed to send reminder #{$reminder->id}: {$e->getMessage()}");
            }
        }

        $this->info("تم إرسال {$count} تذكير");

        return Command::SUCCESS;
    }
}
