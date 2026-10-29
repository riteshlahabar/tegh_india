<?php

namespace App\Services\Busy;

use Carbon\Carbon;
use InvalidArgumentException;

final class BusyPayload
{
    /**
     * @param  array<string, mixed>|list<mixed>  $payload
     * @return list<array<string, mixed>>
     */
    public static function rows(array $payload): array
    {
        if ($payload === []) {
            return [];
        }

        if (array_is_list($payload)) {
            if (! isset($payload[0])) {
                return [];
            }
            if (is_array($payload[0])) {
                return array_values(array_filter($payload, 'is_array'));
            }

            return [];
        }

        foreach (['data', 'Data', 'result', 'Result', 'records', 'Records', 'accounts', 'Accounts', 'outstanding', 'Outstanding', 'vouchers', 'Vouchers', 'items', 'Items', 'list', 'List', 'Table', 'ledgerdetails', 'voucherdetails', 'CancelledVouchers'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                return self::rows($payload[$key]);
            }
        }

        if (self::looksLikeRecord($payload)) {
            return [$payload];
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public static function pick(array $row, array $keys, mixed $default = null): mixed
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalized[self::normalizeKey((string) $key)] = $value;
        }

        foreach ($keys as $key) {
            $lookup = self::normalizeKey($key);
            if (array_key_exists($lookup, $normalized) && $normalized[$lookup] !== null && $normalized[$lookup] !== '') {
                return $normalized[$lookup];
            }
        }

        return $default;
    }

    public static function city(array $row, ?string $default = null): ?string
    {
        $explicit = self::pick($row, ['City', 'city', 'station', 'Station']);
        if (! empty($explicit)) {
            return trim((string) $explicit);
        }

        $addr = ($row['Address1'] ?? '').' '.($row['Address2'] ?? '').' '.($row['Address3'] ?? '').' '.($row['Address4'] ?? '');

        static $cities = [
            'Navi Mumbai', 'Mumbai', 'Pune', 'Nagpur', 'Thane', 'Nashik', 'Kalyan', 'Dombivli', 'Dombivali', 'Aurangabad', 'Solapur', 'Kolhapur',
            'Ahmedabad', 'Surat', 'Vadodara', 'Vadodra', 'Baroda', 'Rajkot', 'Bhavnagar', 'Jamnagar', 'Gandhinagar', 'Ankleshwar', 'Bharuch', 'Vapi', 'Valsad', 'Dahej', 'Sanand', 'Halol', 'Nadiad', 'Morbi',
            'New Delhi', 'Delhi', 'Noida', 'Greater Noida', 'Ghaziabad', 'Gurugram', 'Gurgaon', 'Faridabad',
            'Bengaluru', 'Bangalore', 'Mysore', 'Hubli', 'Belgaum', 'Mangalore',
            'Hyderabad', 'Secunderabad', 'Warangal', 'Visakhapatnam', 'Vijayawada',
            'Chennai', 'Coimbatore', 'Madurai', 'Trichy', 'Salem', 'Hosur',
            'Kolkata', 'Howrah', 'Durgapur', 'Siliguri',
            'Jaipur', 'Jodhpur', 'Udaipur', 'Kota', 'Bhiwadi', 'Alwar',
            'Indore', 'Bhopal', 'Gwalior', 'Jabalpur', 'Pithampur', 'Dewas', 'Dhar', 'Ujjain',
            'Lucknow', 'Kanpur', 'Agra', 'Varanasi', 'Prayagraj', 'Meerut', 'Aligarh', 'Moradabad',
            'Chandigarh', 'Ludhiana', 'Amritsar', 'Jalandhar', 'Mohali', 'Panchkula', 'Baddi',
            'Dehradun', 'Haridwar', 'Roorkee', 'Rudrapur', 'Pantnagar',
            'Raipur', 'Bhilai', 'Ranchi', 'Jamshedpur', 'Bhubaneswar', 'Cuttack', 'Patna', 'Guwahati', 'Panaji', 'Margao', 'Verna', 'Goa',
            'Palghar', 'Boisar', 'Vasai', 'Virar', 'Ambernath', 'Badlapur', 'Bhiwandi', 'Raigad', 'Panvel', 'Taloja', 'Mahad', 'Rohad', 'Roha', 'Chiplun', 'Ratnagiri', 'Satara', 'Sangli', 'Ahmednagar', 'Jalgaon', 'Dhule', 'Nanded', 'Latur', 'Amravati', 'Chandrapur', 'Wardha',
            'Silvassa', 'Daman', 'Diu', 'Gujarat'
        ];

        foreach ($cities as $c) {
            if (stripos($addr, $c) !== false) {
                if (in_array($c, ['Dombivali', 'Dombivli'])) return 'Dombivli';
                if (in_array($c, ['Vadodra', 'Baroda'])) return 'Vadodara';
                if ($c === 'Bengaluru') return 'Bangalore';
                if ($c === 'Gurugram') return 'Gurgaon';
                return $c;
            }
        }

        $pin = trim((string) ($row['PinCode'] ?? $row['Pincode'] ?? $row['pin'] ?? ''));
        if (! empty($pin)) {
            $prefix = substr($pin, 0, 3);
            static $pinMap = [
                '400' => 'Mumbai', '401' => 'Palghar', '410' => 'Pune', '411' => 'Pune', '412' => 'Pune',
                '413' => 'Solapur', '414' => 'Ahmednagar', '415' => 'Satara', '416' => 'Kolhapur',
                '421' => 'Thane', '422' => 'Nashik', '423' => 'Nashik', '424' => 'Dhule', '425' => 'Jalgaon',
                '431' => 'Aurangabad', '440' => 'Nagpur', '444' => 'Amravati',
                '380' => 'Ahmedabad', '382' => 'Ahmedabad', '390' => 'Vadodara', '391' => 'Vadodara',
                '392' => 'Bharuch', '393' => 'Ankleshwar', '395' => 'Surat', '396' => 'Vapi',
                '110' => 'Delhi', '122' => 'Gurgaon', '201' => 'Noida',
                '452' => 'Indore', '453' => 'Indore', '454' => 'Pithampur', '455' => 'Dewas',
                '247' => 'Roorkee', '249' => 'Haridwar', '248' => 'Dehradun', '263' => 'Rudrapur',
                '226' => 'Lucknow', '208' => 'Kanpur', '302' => 'Jaipur', '301' => 'Bhiwadi',
                '560' => 'Bangalore', '500' => 'Hyderabad', '600' => 'Chennai', '700' => 'Kolkata',
                '403' => 'Goa',
            ];
            if (isset($pinMap[$prefix])) {
                return $pinMap[$prefix];
            }
        }

        $state = trim((string) ($row['State_Name'] ?? $row['State'] ?? ''));
        if (! empty($state) && $state !== '---Others---') {
            return $state;
        }

        return $default;
    }

    public static function money(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        if (is_numeric($value)) {
            return round((float) $value, 2);
        }

        $text = trim((string) $value);
        $negative = str_contains(strtolower($text), '(dr)') || str_starts_with($text, '-');
        $digits = preg_replace('/[^\d.]/', '', $text) ?? '0';
        $amount = round((float) $digits, 2);

        return $negative ? -abs($amount) : $amount;
    }

    public static function date(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $text = trim((string) $value);
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'Y/m/d', 'd-M-Y'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $text);
                if ($parsed !== false) {
                    return $parsed->toDateString();
                }
            } catch (InvalidArgumentException) {
                // try next format
            }
        }

        try {
            return Carbon::parse($text)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function looksLikeRecord(array $row): bool
    {
        foreach (['Name', 'name', 'Alias', 'ERP_code', 'ErpCode', 'ERPCode', 'BillNo', 'VchCode', 'invoice_no', 'PartyName', 'Account_Name', 'Inv_No'] as $key) {
            if (array_key_exists($key, $row)) {
                return true;
            }
        }

        return false;
    }

    private static function normalizeKey(string $key): string
    {
        return strtolower(preg_replace('/[^a-z0-9]/i', '', $key) ?? $key);
    }
}
