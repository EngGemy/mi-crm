<?php

namespace App\Services\Poultry;

use App\Models\Customer;
use App\Models\Lead;

/**
 * One optional picker for a saved person, whether they were entered as a
 * customer or a lead. The same phone, or a lead already linked to a customer,
 * is listed once.
 */
class SavedClientCatalog
{
    /** @return array<string, string> */
    public static function options(): array
    {
        $customers = Customer::query()->orderBy('name')->get();
        $leads = Lead::query()->orderBy('name')->get();

        $phones = [];
        $options = [];

        foreach ($customers as $customer) {
            if (self::alreadyListed($phones, [$customer->phone, $customer->phone_alt, $customer->whatsapp])) {
                continue;
            }

            self::remember($phones, [$customer->phone, $customer->phone_alt, $customer->whatsapp]);
            $options['customer:'.$customer->id] = self::label($customer->name, $customer->phone ?: $customer->whatsapp, 'عميل');
        }

        foreach ($leads as $lead) {
            if ($lead->customer_id && isset($options['customer:'.$lead->customer_id])) {
                continue;
            }

            if (self::alreadyListed($phones, [$lead->phone, $lead->whatsapp])) {
                continue;
            }

            self::remember($phones, [$lead->phone, $lead->whatsapp]);
            $options['lead:'.$lead->id] = self::label($lead->name, $lead->phone ?: $lead->whatsapp, 'محتمل');
        }

        return $options;
    }

    /** @return array<string, mixed>|null */
    public static function fieldsFor(?string $key): ?array
    {
        if ($key === null || $key === '' || ! str_contains($key, ':')) {
            return null;
        }

        [$kind, $id] = explode(':', $key, 2);
        $id = (int) $id;
        if ($id < 1) {
            return null;
        }

        if ($kind === 'customer') {
            $customer = Customer::query()->find($id);
            if (! $customer) {
                return null;
            }

            return [
                'customer_id' => $customer->id,
                'client_name' => $customer->name,
                'client_phone' => $customer->phone ?: $customer->whatsapp,
                'client_email' => $customer->email,
                'client_address' => $customer->address,
                'client_company' => $customer->name_en,
                'client_country' => $customer->country,
                'client_location' => $customer->city,
                'client_notes' => $customer->notes,
            ];
        }

        if ($kind === 'lead') {
            $lead = Lead::query()->find($id);
            if (! $lead) {
                return null;
            }

            return [
                'customer_id' => $lead->customer_id,
                'client_name' => $lead->name,
                'client_phone' => $lead->phone ?: $lead->whatsapp,
                'client_email' => $lead->email,
                'client_address' => $lead->address,
                'client_company' => $lead->company,
                'client_country' => $lead->country,
                'client_location' => $lead->city,
                'client_notes' => $lead->notes,
            ];
        }

        return null;
    }

    public static function phoneKey(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone) ?? '';
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (str_starts_with($digits, '0')) {
            $digits = '2'.$digits;
        }

        return $digits;
    }

    /**
     * @param  array<string, true>  $phones
     * @param  list<string|null>  $rawPhones
     */
    private static function alreadyListed(array $phones, array $rawPhones): bool
    {
        foreach ($rawPhones as $phone) {
            $key = self::phoneKey($phone);
            if ($key !== null && isset($phones[$key])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, true>  $phones
     * @param  list<string|null>  $rawPhones
     */
    private static function remember(array &$phones, array $rawPhones): void
    {
        foreach ($rawPhones as $phone) {
            $key = self::phoneKey($phone);
            if ($key !== null) {
                $phones[$key] = true;
            }
        }
    }

    private static function label(?string $name, ?string $phone, string $source): string
    {
        $name = trim((string) $name) !== '' ? trim((string) $name) : 'بدون اسم';
        $phone = trim((string) $phone);

        return $phone !== '' ? "{$name} — {$phone} ({$source})" : "{$name} ({$source})";
    }
}
