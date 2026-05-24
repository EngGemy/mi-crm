<?php

namespace App\Notifications;

use App\Models\LeadReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeadReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected LeadReminder $reminder
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $lead = $this->reminder->lead;

        return (new MailMessage)
            ->subject("تذكير متابعة: {$this->reminder->title}")
            ->greeting("مرحباً {$notifiable->name}")
            ->line("لديك تذكير متابعة لـ Lead: {$lead->name}")
            ->line("العنوان: {$this->reminder->title}")
            ->line('النوع: '.(LeadReminder::TYPES[$this->reminder->type] ?? $this->reminder->type))
            ->when($this->reminder->description, fn ($msg) => $msg->line("التفاصيل: {$this->reminder->description}"))
            ->action('عرض الـ Lead', route('filament.admin.resources.leads.edit', $lead))
            ->line('شكراً لاستخدامك نظام MI Sales CRM');
    }

    public function toDatabase(object $notifiable): array
    {
        $lead = $this->reminder->lead;

        return [
            'title' => 'تذكير متابعة Lead',
            'body' => "{$this->reminder->title} - {$lead->name}",
            'lead_id' => $lead->id,
            'reminder_id' => $this->reminder->id,
            'url' => route('filament.admin.resources.leads.edit', $lead),
        ];
    }
}
