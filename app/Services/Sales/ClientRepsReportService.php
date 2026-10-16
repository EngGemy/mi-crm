<?php

namespace App\Services\Sales;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\PoultryQuotation;
use App\Models\User;
use App\Services\Poultry\SavedClientCatalog;

class ClientRepsReportService
{
    /**
     * @return list<array{name: string, phone: string, company: string, quotes: int, reps: list<array{id: int, name: string, roles: string}>}>
     */
    public function rows(?string $search = null, ?int $repId = null): array
    {
        /** @var array<string, array{name: string, phone: string, company: string, quotes: int, reps: array<int, array{name: string, roles: array<string, true>}>}> $clients */
        $clients = [];

        Lead::query()
            ->with(['creator:id,name', 'assignedUser:id,name'])
            ->orderBy('id')
            ->get(['id', 'name', 'phone', 'whatsapp', 'company', 'created_by', 'assigned_to'])
            ->each(function (Lead $lead) use (&$clients): void {
                $key = $this->key($lead->phone ?: $lead->whatsapp, 'lead', $lead->id);
                $this->client($clients, $key, $lead->name, $lead->phone ?: $lead->whatsapp, $lead->company);
                $this->touch($clients[$key], $lead->created_by, $lead->creator?->name, 'سجّل العميل');
                if ($lead->assigned_to && (int) $lead->assigned_to !== (int) $lead->created_by) {
                    $this->touch($clients[$key], $lead->assigned_to, $lead->assignedUser?->name, 'مسؤول المتابعة');
                }
            });

        PoultryQuotation::query()
            ->with(['creator:id,name', 'clientAdder:id,name'])
            ->orderBy('id')
            ->get(['id', 'client_name', 'client_phone', 'client_company', 'created_by', 'client_added_by', 'contributors'])
            ->each(function (PoultryQuotation $quote) use (&$clients): void {
                $key = $this->key($quote->client_phone, 'quote', $quote->id);
                $this->client($clients, $key, $quote->client_name, $quote->client_phone, $quote->client_company);
                $clients[$key]['quotes']++;
                $this->touch($clients[$key], $quote->client_added_by, $quote->clientAdder?->name, 'سجّل العميل');
                $this->touch($clients[$key], $quote->created_by, $quote->creator?->name, 'عرض السعر');
                foreach ($quote->contributors ?? [] as $row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    $this->touch(
                        $clients[$key],
                        isset($row['user_id']) ? (int) $row['user_id'] : null,
                        $row['name'] ?? null,
                        'تعديل'
                    );
                }
            });

        LeadActivity::query()
            ->with(['lead:id,name,phone,whatsapp,company', 'user:id,name'])
            ->orderBy('id')
            ->get(['id', 'lead_id', 'user_id', 'type'])
            ->each(function (LeadActivity $activity) use (&$clients): void {
                $lead = $activity->lead;
                if ($lead === null) {
                    return;
                }
                $key = $this->key($lead->phone ?: $lead->whatsapp, 'lead', $lead->id);
                $this->client($clients, $key, $lead->name, $lead->phone ?: $lead->whatsapp, $lead->company);
                $role = LeadActivity::TYPES[$activity->type] ?? 'متابعة';
                $this->touch($clients[$key], $activity->user_id, $activity->user?->name, $role);
            });

        $this->fillNames($clients);

        $rows = [];
        $search = trim((string) $search);
        foreach ($clients as $client) {
            if ($client['reps'] === []) {
                continue;
            }
            if ($repId && ! isset($client['reps'][$repId])) {
                continue;
            }
            if ($search !== '' && ! $this->matches($client, $search)) {
                continue;
            }

            $reps = [];
            foreach ($client['reps'] as $id => $rep) {
                $reps[] = [
                    'id' => (int) $id,
                    'name' => $rep['name'] !== '' ? $rep['name'] : 'مستخدم',
                    'roles' => implode('، ', array_keys($rep['roles'])),
                ];
            }
            usort($reps, fn (array $a, array $b) => strcmp($a['name'], $b['name']));

            $rows[] = [
                'name' => $client['name'] !== '' ? $client['name'] : 'بدون اسم',
                'phone' => $client['phone'],
                'company' => $client['company'],
                'quotes' => $client['quotes'],
                'reps' => $reps,
            ];
        }

        usort($rows, fn (array $a, array $b) => strcmp($a['name'], $b['name']));

        return $rows;
    }

    /**
     * @param  array<string, array{name: string, phone: string, company: string, quotes: int, reps: array<int, array{name: string, roles: array<string, true>}>}>  $clients
     */
    private function client(array &$clients, string $key, ?string $name, ?string $phone, ?string $company): void
    {
        if (! isset($clients[$key])) {
            $clients[$key] = [
                'name' => trim((string) $name),
                'phone' => trim((string) $phone),
                'company' => trim((string) $company),
                'quotes' => 0,
                'reps' => [],
            ];

            return;
        }

        if ($clients[$key]['name'] === '' && trim((string) $name) !== '') {
            $clients[$key]['name'] = trim((string) $name);
        }
        if ($clients[$key]['phone'] === '' && trim((string) $phone) !== '') {
            $clients[$key]['phone'] = trim((string) $phone);
        }
        if ($clients[$key]['company'] === '' && trim((string) $company) !== '') {
            $clients[$key]['company'] = trim((string) $company);
        }
    }

    /**
     * @param  array{name: string, phone: string, company: string, quotes: int, reps: array<int, array{name: string, roles: array<string, true>}>}  $client
     */
    private function touch(array &$client, ?int $userId, ?string $name, string $role): void
    {
        if (! $userId) {
            return;
        }

        $name = trim((string) $name);
        if (! isset($client['reps'][$userId])) {
            $client['reps'][$userId] = ['name' => $name, 'roles' => []];
        } elseif ($client['reps'][$userId]['name'] === '' && $name !== '') {
            $client['reps'][$userId]['name'] = $name;
        }

        $client['reps'][$userId]['roles'][$role] = true;
    }

    /**
     * @param  array<string, array{name: string, phone: string, company: string, quotes: int, reps: array<int, array{name: string, roles: array<string, true>}>}>  $clients
     */
    private function fillNames(array &$clients): void
    {
        $ids = [];
        foreach ($clients as $client) {
            foreach ($client['reps'] as $id => $rep) {
                if ($rep['name'] === '') {
                    $ids[] = $id;
                }
            }
        }

        if ($ids === []) {
            return;
        }

        $names = User::query()->whereIn('id', array_unique($ids))->pluck('name', 'id');
        foreach ($clients as &$client) {
            foreach ($client['reps'] as $id => &$rep) {
                if ($rep['name'] === '' && isset($names[$id])) {
                    $rep['name'] = (string) $names[$id];
                }
            }
        }
    }

    /**
     * @param  array{name: string, phone: string, company: string, quotes: int, reps: array<int, array{name: string, roles: array<string, true>}>}  $client
     */
    private function matches(array $client, string $search): bool
    {
        $folded = SavedClientCatalog::fold($search);
        $haystack = SavedClientCatalog::fold($client['name'].' '.$client['company']);
        if ($folded !== '' && str_contains($haystack, $folded)) {
            return true;
        }

        foreach (SavedClientCatalog::phoneFragments($search) as $fragment) {
            $phone = SavedClientCatalog::phoneKey($client['phone']) ?? '';
            if ($phone !== '' && str_contains($phone, $fragment)) {
                return true;
            }
        }

        return false;
    }

    private function key(?string $phone, string $prefix, int $id): string
    {
        $phoneKey = SavedClientCatalog::phoneKey($phone);

        return ($phoneKey && strlen($phoneKey) >= 7) ? 'phone:'.$phoneKey : $prefix.':'.$id;
    }
}
