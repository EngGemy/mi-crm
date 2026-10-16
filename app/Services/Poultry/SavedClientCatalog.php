<?php

namespace App\Services\Poultry;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\PoultryQuotation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
        $leads = Lead::query()->with('creator:id,name')->orderBy('name')->get();

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

            if (self::alreadyListed($phones, [$lead->phone])) {
                continue;
            }

            self::remember($phones, [$lead->phone]);
            $addedBy = trim((string) ($lead->creator?->name ?? ''));
            $source = $addedBy !== '' ? 'محتمل · أضافه '.$addedBy : 'محتمل';
            $options['lead:'.$lead->id] = self::label($lead->name, $lead->phone ?: $lead->whatsapp, $source);
        }

        return $options;
    }

    /**
     * Who first saved this person. A later quote or login must not replace them.
     */
    public static function registrarId(?string $key, ?string $phone): ?int
    {
        if (is_string($key) && str_contains($key, ':')) {
            [$kind, $id] = explode(':', $key, 2);
            $id = (int) $id;
            if ($id > 0 && $kind === 'lead') {
                $createdBy = Lead::query()->whereKey($id)->value('created_by');

                return $createdBy ? (int) $createdBy : null;
            }
            if ($id > 0 && $kind === 'customer') {
                $createdBy = Lead::query()
                    ->where('customer_id', $id)
                    ->whereNotNull('created_by')
                    ->orderBy('id')
                    ->value('created_by');
                if ($createdBy) {
                    return (int) $createdBy;
                }
            }
        }

        $phoneKey = self::phoneKey($phone);
        if ($phoneKey === null || strlen($phoneKey) < 7) {
            return null;
        }

        $createdBy = Lead::query()
            ->whereNotNull('created_by')
            ->where(function (Builder $query) use ($phoneKey): void {
                $query->where('phone_key', $phoneKey)->orWhere('alt_phone_key', $phoneKey);
            })
            ->orderBy('id')
            ->value('created_by');

        return $createdBy ? (int) $createdBy : null;
    }

    /** @return array<string, string> */
    public static function search(string $term, int $limit = 15): array
    {
        $term = trim($term);
        if (mb_strlen($term) < 2) {
            return [];
        }

        $leads = Lead::query()->with('creator:id,name')->limit($limit);
        self::whereMatches($leads, $term, 'lead');
        self::orderMatches($leads, $term);

        $customers = Customer::query()->limit($limit);
        self::whereMatches($customers, $term, 'customer');
        self::orderMatches($customers, $term);

        $options = [];
        $phones = [];

        foreach ($leads->get() as $lead) {
            self::remember($phones, [$lead->phone]);
            $addedBy = trim((string) ($lead->creator?->name ?? ''));
            $source = $addedBy !== '' ? 'محتمل · أضافه '.$addedBy : 'محتمل';
            $options['lead:'.$lead->id] = self::label($lead->name, $lead->phone ?: $lead->whatsapp, $source);
        }

        foreach ($customers->get() as $customer) {
            if (self::alreadyListed($phones, [$customer->phone])) {
                continue;
            }

            $options['customer:'.$customer->id] = self::label($customer->name, $customer->phone ?: $customer->whatsapp, 'عميل');
            if (count($options) >= $limit) {
                break;
            }
        }

        return $options;
    }

    public static function optionLabel(?string $key): ?string
    {
        if ($key === null || $key === '' || ! str_contains($key, ':')) {
            return null;
        }

        [$kind, $id] = explode(':', $key, 2);
        $id = (int) $id;
        if ($id < 1) {
            return null;
        }

        if ($kind === 'lead') {
            $lead = Lead::query()->with('creator:id,name')->find($id);
            if (! $lead) {
                return null;
            }
            $addedBy = trim((string) ($lead->creator?->name ?? ''));
            $source = $addedBy !== '' ? 'محتمل · أضافه '.$addedBy : 'محتمل';

            return self::label($lead->name, $lead->phone ?: $lead->whatsapp, $source);
        }

        if ($kind === 'customer') {
            $customer = Customer::query()->find($id);

            return $customer
                ? self::label($customer->name, $customer->phone ?: $customer->whatsapp, 'عميل')
                : null;
        }

        return null;
    }

    public static function whereMatches(Builder $query, string $term, string $kind): void
    {
        $folded = self::fold($term);
        $like = '%'.self::escapeLike($folded).'%';
        $rawLike = '%'.self::escapeLike(trim($term)).'%';
        $phoneFragments = self::phoneFragments($term);

        $query->where(function (Builder $inner) use ($kind, $like, $rawLike, $phoneFragments): void {
            $inner->where('search_name', 'like', $like)
                ->orWhere('name', 'like', $rawLike);

            if ($kind === 'lead') {
                $inner->orWhere('company', 'like', $rawLike)
                    ->orWhere('city', 'like', $rawLike)
                    ->orWhere('phone', 'like', $rawLike)
                    ->orWhere('whatsapp', 'like', $rawLike);
            } else {
                $inner->orWhere('city', 'like', $rawLike)
                    ->orWhere('phone', 'like', $rawLike)
                    ->orWhere('phone_alt', 'like', $rawLike)
                    ->orWhere('whatsapp', 'like', $rawLike);
            }

            foreach ($phoneFragments as $fragment) {
                $inner->orWhere('phone_key', 'like', '%'.$fragment.'%')
                    ->orWhere('alt_phone_key', 'like', '%'.$fragment.'%');
            }
        });
    }

    public static function ensureLeadFromQuote(PoultryQuotation $quote): void
    {
        $name = trim((string) $quote->client_name);
        $phone = trim((string) $quote->client_phone);
        if ($name === '' || self::phoneKey($phone) === null) {
            return;
        }

        $key = self::phoneKey($phone);
        $lead = Lead::query()
            ->where(function (Builder $query) use ($key): void {
                $query->where('phone_key', $key)->orWhere('alt_phone_key', $key);
            })
            ->orderBy('id')
            ->first();

        if ($lead) {
            foreach ([
                'company' => $quote->client_company,
                'city' => $quote->client_location,
                'email' => $quote->client_email,
                'address' => $quote->client_address,
            ] as $field => $value) {
                $value = trim((string) $value);
                if ($value !== '' && blank($lead->{$field})) {
                    $lead->{$field} = $value;
                }
            }
            if ($lead->isDirty()) {
                $lead->save();
            }
        } else {
            $owner = $quote->created_by ?: auth()->id();
            $lead = Lead::create([
                'name' => $name,
                'phone' => $phone,
                'email' => $quote->client_email,
                'company' => $quote->client_company,
                'country' => $quote->client_country ?: 'Egypt',
                'city' => $quote->client_location,
                'address' => $quote->client_address,
                'notes' => $quote->client_notes,
                'source' => 'walk_in',
                'source_details' => 'حاسبة الأسعار',
                'status' => 'new',
                'priority' => 'medium',
                'created_by' => $owner,
                'assigned_to' => $owner,
            ]);
        }

        if (empty($quote->client_added_by) && $lead->created_by) {
            $quote->client_added_by = $lead->created_by;
            $quote->saveQuietly();
        }
    }

    public static function syncSearchColumns(Model $person): void
    {
        $person->setAttribute('search_name', self::fold(implode(' ', array_filter([
            $person->getAttribute('name'),
            $person->getAttribute('company') ?: $person->getAttribute('name_en'),
            $person->getAttribute('city'),
        ]))));
        $person->setAttribute('phone_key', self::phoneKey($person->getAttribute('phone')));
        $person->setAttribute('alt_phone_key', self::phoneKey(
            $person->getAttribute('whatsapp') ?: $person->getAttribute('phone_alt')
        ));
    }

    public static function registrarName(?string $key, ?string $phone): ?string
    {
        $id = self::registrarId($key, $phone);
        if (! $id) {
            return null;
        }

        $name = User::query()->whereKey($id)->value('name');
        $name = trim((string) $name);

        return $name !== '' ? $name : null;
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
        if (str_starts_with($digits, '20') && strlen($digits) > 10) {
            $digits = substr($digits, 2);
        }
        $digits = ltrim($digits, '0');

        return $digits !== '' ? $digits : null;
    }

    /** @return list<string> */
    public static function phoneFragments(string $term): array
    {
        $digits = preg_replace('/\D/', '', $term) ?? '';
        if ($digits === '') {
            return [];
        }

        $fragments = [$digits, self::phoneKey($digits)];
        if (str_starts_with($digits, '20') && strlen($digits) > 3) {
            $fragments[] = ltrim(substr($digits, 2), '0');
        }
        if (str_starts_with($digits, '0')) {
            $fragments[] = ltrim($digits, '0');
        }

        return array_values(array_unique(array_filter(
            $fragments,
            fn ($fragment) => is_string($fragment) && strlen($fragment) >= 3
        )));
    }

    public static function fold(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));

        return strtr($value, [
            'أ' => 'ا',
            'إ' => 'ا',
            'آ' => 'ا',
            'ى' => 'ي',
            'ة' => 'ه',
            'ؤ' => 'و',
            'ئ' => 'ي',
            'ـ' => '',
        ]);
    }

    public static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private static function orderMatches(Builder $query, string $term): void
    {
        $prefix = self::escapeLike(self::fold($term)).'%';
        $phone = self::phoneFragments($term)[0] ?? null;
        if (is_string($phone)) {
            $query->orderByRaw(
                'CASE WHEN phone_key LIKE ? OR alt_phone_key LIKE ? THEN 0 WHEN search_name LIKE ? THEN 1 ELSE 2 END',
                ['%'.$phone.'%', '%'.$phone.'%', $prefix]
            );

            return;
        }

        $query->orderByRaw('CASE WHEN search_name LIKE ? THEN 0 ELSE 1 END', [$prefix]);
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
