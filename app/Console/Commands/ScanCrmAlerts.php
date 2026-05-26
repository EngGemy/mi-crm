<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\LeadOccasion;
use App\Models\LeadReminder;
use App\Models\Quotation;
use App\Models\User;
use App\Services\NotificationRouter;
use Illuminate\Console\Command;

class ScanCrmAlerts extends Command
{
    protected $signature = 'crm:scan-alerts';

    protected $description = 'فحص التنبيهات الذكية اليومية: المتابعات المتأخرة، انتهاء العروض، التذكيرات المستحقة، المناسبات';

    public function handle(NotificationRouter $router): int
    {
        $this->info('بدء فحص التنبيهات ...');

        $this->scanOverdueFollowups($router);
        $this->scanExpiringQuotations($router);
        $this->scanDueReminders($router);
        $this->scanOccasions($router);

        $this->info('اكتمل الفحص.');

        return self::SUCCESS;
    }

    protected function scanOverdueFollowups(NotificationRouter $router): void
    {
        $days = (int) settings('crm.alerts.no_contact_days', 7);

        $leads = Lead::active()
            ->whereNotNull('assigned_to')
            ->where(function ($q) use ($days) {
                $q->whereNull('last_contact_at')
                    ->orWhere('last_contact_at', '<=', now()->subDays($days));
            })
            ->with('assignedUser')
            ->get();

        foreach ($leads as $lead) {
            $user = $lead->assignedUser;
            if (! $user) {
                continue;
            }

            $daysSince = $lead->days_since_last_contact;
            $router->notify(
                $user,
                "عميل يحتاج متابعة: {$lead->name}",
                "لم يتم التواصل مع {$lead->name} منذ {$daysSince} يوم.",
                'alert'
            );
        }

        $this->line("↳ متابعات متأخرة: {$leads->count()}");
    }

    protected function scanExpiringQuotations(NotificationRouter $router): void
    {
        $days = (int) settings('crm.alerts.quotation_expiry_days', 3);

        $quotations = Quotation::whereNotNull('valid_until')
            ->whereBetween('valid_until', [now()->toDateString(), now()->addDays($days)->toDateString()])
            ->whereIn('status', ['draft', 'sent'])
            ->get();

        foreach ($quotations as $quotation) {
            $users = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['sales_manager', 'admin', 'super_admin']))->get();
            foreach ($users as $user) {
                $router->notify(
                    $user,
                    'عرض سعر يقترب من انتهاء الصلاحية',
                    "العرض #{$quotation->quotation_number} ينتهي في {$quotation->valid_until->format('Y-m-d')}.",
                    'alert'
                );
            }
        }

        $this->line("↳ عروض تنتهي قريباً: {$quotations->count()}");
    }

    protected function scanDueReminders(NotificationRouter $router): void
    {
        $reminders = LeadReminder::due()
            ->where('notified', false)
            ->with(['user', 'lead'])
            ->get();

        foreach ($reminders as $reminder) {
            $user = $reminder->user;
            if (! $user) {
                continue;
            }

            $router->notify(
                $user,
                "تذكير مستحق: {$reminder->title}",
                "تذكير للعميل المحتمل: {$reminder->lead?->name}\n{$reminder->description}",
                'reminder',
                $reminder->lead?->whatsapp
            );

            $reminder->update(['notified' => true, 'notified_at' => now()]);
        }

        $this->line("↳ تذكيرات مستحقة: {$reminders->count()}");
    }

    protected function scanOccasions(NotificationRouter $router): void
    {
        $today = now()->format('m-d');
        $tomorrow = now()->addDay()->format('m-d');

        $occasions = LeadOccasion::with(['lead.assignedUser'])
            ->where(function ($q) use ($today, $tomorrow) {
                // مناسبات اليوم أو الغد (بغض النظر عن السنة للمناسبات المتكررة)
                $q->where(function ($sub) use ($today, $tomorrow) {
                    $sub->whereRaw("DATE_FORMAT(occasion_date, '%m-%d') IN (?, ?)", [$today, $tomorrow]);
                })->where('is_recurring', true)
                    ->orWhere(function ($sub) use ($today, $tomorrow) {
                        $sub->whereRaw("DATE_FORMAT(occasion_date, '%m-%d') IN (?, ?)", [$today, $tomorrow]);
                        $sub->where('is_recurring', false)
                            ->whereYear('occasion_date', now()->year);
                    });
            })
            ->get();

        foreach ($occasions as $occasion) {
            $user = $occasion->lead?->assignedUser;
            if (! $user) {
                continue;
            }

            $whenLabel = $occasion->isToday() ? 'اليوم' : 'غداً';
            $router->notify(
                $user,
                "مناسبة {$whenLabel}: {$occasion->title}",
                "مناسبة العميل {$occasion->lead->name}: {$occasion->title}. تواصل للتهنئة!",
                'reminder',
                $occasion->lead->whatsapp
            );
        }

        $this->line("↳ مناسبات اليوم/الغد: {$occasions->count()}");
    }
}
