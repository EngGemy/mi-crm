<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractMilestone;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * إنشاء جدول الدفعات والـ Milestones تلقائياً عند توقيع العقد
 */
class PaymentScheduler
{
    /**
     * إنشاء Milestones + Payments بناءً على نوع العقد
     */
    public function generateForContract(Contract $contract): void
    {
        $contract->loadMissing('contractType');

        DB::transaction(function () use ($contract) {
            // 1. إنشاء Milestones
            $milestones = $this->createMilestones($contract);

            // 2. إنشاء Payments مرتبطة
            $this->createPayments($contract, $milestones);
        });
    }

    /**
     * إنشاء Milestones من نوع العقد أو الافتراضي
     */
    protected function createMilestones(Contract $contract): array
    {
        // حذف القديم
        $contract->milestones()->delete();

        $template = $contract->contractType?->default_milestones
            ?? $this->defaultMilestones();

        $contractDate = Carbon::parse($contract->contract_date);
        $manufacturingDays = (int) ($contract->manufacturing_days ?? 105);

        $milestones = [];
        foreach ($template as $idx => $m) {
            $offsetDays = (int) ($m['offset_days'] ?? 0);
            $offsetType = $m['offset_type'] ?? 'absolute'; // absolute|percentage_of_manufacturing

            if ($offsetType === 'percentage_of_manufacturing') {
                $offsetDays = (int) ($manufacturingDays * ($m['offset_percentage'] / 100));
            }

            $expectedDate = $contractDate->copy()->addDays($offsetDays);

            $milestone = ContractMilestone::create([
                'contract_id' => $contract->id,
                'code' => $m['code'],
                'title' => $m['title'],
                'description' => $m['description'] ?? null,
                'expected_date' => $expectedDate,
                'status' => 'pending',
                'sort_order' => $idx,
                'triggers_payment' => $m['triggers_payment'] ?? false,
            ]);

            $milestones[$m['code']] = $milestone;
        }

        return $milestones;
    }

    /**
     * إنشاء جدول الدفعات
     */
    protected function createPayments(Contract $contract, array $milestones): void
    {
        $contract->payments()->delete();

        $template = $contract->contractType?->payment_schedule_default
            ?? $this->defaultPaymentSchedule();

        $totalValue = (float) $contract->total_value;

        foreach ($template as $idx => $p) {
            $milestoneCode = $p['milestone_code'] ?? null;
            $milestone = $milestoneCode ? ($milestones[$milestoneCode] ?? null) : null;

            $percentage = (float) $p['percentage'];
            $amount = $totalValue * ($percentage / 100);

            // التاريخ: من Milestone أو إزاحة من تاريخ العقد
            $dueDate = $milestone?->expected_date
                ?? Carbon::parse($contract->contract_date)->addDays((int) ($p['offset_days'] ?? 0));

            Payment::create([
                'contract_id' => $contract->id,
                'milestone_id' => $milestone?->id,
                'description' => $p['description'],
                'percentage' => $percentage,
                'expected_amount' => $amount,
                'paid_amount' => 0,
                'currency' => $contract->currency,
                'due_date' => $dueDate,
                'status' => 'pending',
                'sort_order' => $idx,
            ]);
        }
    }

    /**
     * Milestones افتراضية لو نوع العقد ما عرّفش
     */
    public function defaultMilestones(): array
    {
        return [
            [
                'code' => 'CONTRACT_SIGN',
                'title' => 'توقيع العقد',
                'description' => 'إتمام التوقيع وسداد الدفعة المقدمة',
                'offset_days' => 0,
                'offset_type' => 'absolute',
                'triggers_payment' => true,
            ],
            [
                'code' => 'MANUFACTURING_START',
                'title' => 'بدء التصنيع',
                'description' => 'بدء التصنيع بعد استلام الدفعة المقدمة',
                'offset_days' => 3,
                'offset_type' => 'absolute',
                'triggers_payment' => false,
            ],
            [
                'code' => 'SHIPPING_START',
                'title' => 'بدء الشحن للموقع',
                'description' => 'الشحن من المصنع إلى موقع التركيب',
                'offset_type' => 'percentage_of_manufacturing',
                'offset_percentage' => 67,
                'triggers_payment' => true,
            ],
            [
                'code' => 'INSTALLATION_START',
                'title' => 'بدء التركيب',
                'description' => 'بدء أعمال التركيب في الموقع',
                'offset_type' => 'percentage_of_manufacturing',
                'offset_percentage' => 75,
                'triggers_payment' => false,
            ],
            [
                'code' => 'TESTING',
                'title' => 'التشغيل التجريبي',
                'description' => 'تشغيل تجريبي 10-20 يوم',
                'offset_type' => 'percentage_of_manufacturing',
                'offset_percentage' => 95,
                'triggers_payment' => false,
            ],
            [
                'code' => 'FINAL_DELIVERY',
                'title' => 'التسليم النهائي',
                'description' => 'اعتماد محضر التسليم',
                'offset_type' => 'percentage_of_manufacturing',
                'offset_percentage' => 100,
                'triggers_payment' => true,
            ],
        ];
    }

    /**
     * جدول الدفعات الافتراضي — يُقرأ من الإعدادات أولاً (finance.payment_schedule)
     */
    public function defaultPaymentSchedule(): array
    {
        $raw = settings('finance.payment_schedule');
        if ($raw) {
            $schedule = is_array($raw) ? $raw : json_decode($raw, true);
            if (is_array($schedule) && count($schedule) > 0) {
                return $schedule;
            }
        }

        return [
            [
                'description' => 'الدفعة المقدمة (70%) - عند التوقيع',
                'percentage' => 70,
                'milestone_code' => 'CONTRACT_SIGN',
            ],
            [
                'description' => 'الدفعة الثانية (25%) - عند بدء الشحن',
                'percentage' => 25,
                'milestone_code' => 'SHIPPING_START',
            ],
            [
                'description' => 'الدفعة الأخيرة (5%) - عند التسليم',
                'percentage' => 5,
                'milestone_code' => 'FINAL_DELIVERY',
            ],
        ];
    }
}
