<?php

namespace App\Services;

use App\Models\City;
use App\Models\Company;
use App\Models\SubCity;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CompanySheetImporter
{
    public const DEMO_FILENAME = 'customer-bulk-import-demo.xlsx';

    public const DEFAULT_STAFF_PASSWORD = '12345678';

    public const DEFAULT_STAFF_EMAIL_DOMAIN = 'staff.teghcables.local';

    /** @var list<string> */
    public const DEMO_HEADERS = [
        'Company Name',
        'Contact Person',
        'Mobile Number',
        'Alternate Mobile',
        'Email',
        'Address',
        'sub city station wise',
        'City',
        'State',
        'PIN Code',
        'Sales Person Name',
        'calling',
        'sales',
        'Brand',
        'GST Number',
    ];

    /** @var array<string, int|null> */
    private array $execByName = [];

    /** @var array<string, int|null> */
    private array $telecallerByName = [];

    /** @var list<array{name:string,email:string,role:string}> */
    private array $createdStaff = [];

    /**
     * Import customers from sheet columns:
     * Company Name, Contact Person, Mobile Number, Alternate Mobile, Email,
     * Address, sub city station wise, City, State, PIN Code,
     * Sales Person Name, calling, sales, Brand, GST Number.
     *
     * @return array{added:int,updated:int,skipped:int,total_rows:int,unmatched_staff:int,staff_created:int,staff_logins:list<array{name:string,email:string,role:string}>}
     */
    public function import(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        if (count($rows) < 2) {
            return $this->emptyResult();
        }

        $this->createdStaff = [];
        $this->warmStaffLookup();

        $headerRow = array_map(fn ($h) => $this->normalizeHeader((string) $h), $rows[0]);
        $map = $this->mapColumns($headerRow);

        $added = 0;
        $updated = 0;
        $skipped = 0;
        $unmatchedStaff = 0;
        $dataRows = 0;

        $hasAlternate = Schema::hasColumn('companies', 'alternate_mobile');
        $hasState = Schema::hasColumn('companies', 'state');
        $hasPin = Schema::hasColumn('companies', 'pin_code');
        $hasBrand = Schema::hasColumn('companies', 'brand');

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (! is_array($row) || $this->rowIsEmpty($row)) {
                continue;
            }
            $dataRows++;

            $payload = $this->rowToPayload($row, $map);
            if ($payload['name'] === '') {
                $skipped++;

                continue;
            }

            $salesName = $payload['_sales_person_name'];
            $salesAlt = $payload['_sales'];
            $callingName = $payload['_calling'];
            unset($payload['_sales_person_name'], $payload['_sales'], $payload['_calling']);

            $execId = $this->resolveOrCreateExec($salesName, $salesAlt, $payload['city'] ?: null);
            $tcId = $this->resolveOrCreateTelecaller($callingName, $payload['city'] ?: null);

            if (($salesName !== '' || $salesAlt !== '') && $execId === null) {
                $unmatchedStaff++;
            }
            if ($callingName !== '' && $tcId === null) {
                $unmatchedStaff++;
            }

            if ($payload['city'] !== '') {
                $this->ensureCityAndSubCity($payload['city'], $payload['sub_city'] ?: null);
            }

            if (! $hasAlternate) {
                unset($payload['alternate_mobile']);
            }
            if (! $hasState) {
                unset($payload['state']);
            }
            if (! $hasPin) {
                unset($payload['pin_code']);
            }
            if (! $hasBrand) {
                unset($payload['brand']);
            }

            // Drop empty optionals so update doesn't wipe existing with null unintentionally
            // for identity fields we still want updates.
            $existing = $this->findExisting($payload);

            $payload['assigned_exec_id'] = $execId;
            $payload['assigned_tc_id'] = $tcId;

            try {
                if ($existing) {
                    $update = [];
                    foreach ($payload as $key => $value) {
                        if (in_array($key, ['assigned_exec_id', 'assigned_tc_id'], true)) {
                            continue;
                        }
                        if ($value !== null && $value !== '') {
                            $update[$key] = $value;
                        }
                    }
                    if ($execId !== null) {
                        $update['assigned_exec_id'] = $execId;
                    }
                    if ($tcId !== null) {
                        $update['assigned_tc_id'] = $tcId;
                    }
                    $existing->update($update);
                    $updated++;

                    continue;
                }

                $payload['credit_limit'] = 0;
                $payload['outstanding_amount'] = 0;
                $payload['stage'] = 'New';
                $payload['created_by'] = Auth::id();
                if ($payload['city'] !== '') {
                    $this->ensureCityAndSubCity($payload['city'], $payload['sub_city'] ?: null);
                }

                Company::create($payload);
                $added++;
            } catch (\Throwable $e) {
                report($e);
                $skipped++;
            }
        }

        City::reclassifyImportedLocations();

        return [
            'added' => $added,
            'updated' => $updated,
            'skipped' => $skipped,
            'total_rows' => $dataRows,
            'unmatched_staff' => $unmatchedStaff,
            'staff_created' => count($this->createdStaff),
            'staff_logins' => $this->createdStaff,
        ];
    }

    /**
     * @return array{added:int,updated:int,skipped:int,total_rows:int,unmatched_staff:int,staff_created:int,staff_logins:list<array{name:string,email:string,role:string}>}
     */
    private function emptyResult(): array
    {
        return [
            'added' => 0,
            'updated' => 0,
            'skipped' => 0,
            'total_rows' => 0,
            'unmatched_staff' => 0,
            'staff_created' => 0,
            'staff_logins' => [],
        ];
    }

    /**
     * Sample sheet admins can download, fill, and re-upload.
     *
     * @return list<list<string>>
     */
    public function demoRows(): array
    {
        return [
            [
                'Shree Krishna Electricals',
                'Rajesh Sharma',
                '9876543210',
                '9876543211',
                'rajesh@shreekrishna.test',
                'Shop 12, Station Road, Kalyan West',
                'Kalyan',
                'Mumbai',
                'Maharashtra',
                '421301',
                'Amit Patil',
                'Priya Shah',
                'Amit Patil',
                'Polycab',
                '27AAAAA1111A1Z5',
            ],
            [
                'Patel Cable Traders',
                'Nilesh Patel',
                '9825011122',
                '9825011133',
                'nilesh@patelcables.test',
                'C.G. Road, Navrangpura',
                'Ahmedabad',
                'Gujarat',
                'Gujarat',
                '380009',
                'Sneha Kulkarni',
                'Priya Shah',
                'Sneha Kulkarni',
                'Havells',
                '24BBBBB2222B1Z8',
            ],
            [
                'Deccan Wire House',
                'Sanjay Deshmukh',
                '9890123456',
                '',
                'sanjay@deccanwire.test',
                'Pimpri Industrial Estate, Plot 8',
                'Talegaon',
                'Pune',
                'Maharashtra',
                '410507',
                'Amit Patil',
                '',
                'Amit Patil',
                'Finolex',
                '27CCCCC3333C1Z1',
            ],
            [
                'Thane Power Solutions',
                'Meera Joshi',
                '9819123456',
                '9819123457',
                'meera@thanepower.test',
                'Gokul Nagar, Thane West',
                'Thane',
                'Mumbai',
                'Maharashtra',
                '400601',
                '',
                'Priya Shah',
                '',
                'KEI',
                '27DDDDD4444D1Z2',
            ],
            [
                'Vapi Insulation Mart',
                'Kiran Rana',
                '9876540099',
                '',
                'kiran@vapiinsulation.test',
                'GIDC Phase 2, Vapi',
                'Vapi',
                'Gujarat',
                'Gujarat',
                '396195',
                'Sneha Kulkarni',
                'Priya Shah',
                'Sneha Kulkarni',
                'RR Kabel',
                '24EEEEE5555E1Z3',
            ],
        ];
    }

    public function makeDemoSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Customers');
        $sheet->fromArray(self::DEMO_HEADERS, null, 'A1');
        $sheet->fromArray($this->demoRows(), null, 'A2');
        $this->styleDemoSheet($sheet, count(self::DEMO_HEADERS));

        $help = $spreadsheet->createSheet();
        $help->setTitle('How to use');
        $help->fromArray([
            ['Bulk customer import'],
            ['1. Keep row 1 headers exactly as they are (or match the names in the upload modal).'],
            ['2. Replace the 5 demo companies with your own rows, or import this file as-is to try the flow.'],
            ['3. Company Name is required. Other columns are optional.'],
            ['4. City should be Mumbai, Pune or Gujarat. Put the station/area in “sub city station wise”.'],
            ['5. Sales Person Name / sales is assigned to that salesman. If they are new, a Staff login is created (password 12345678).'],
            ['6. calling is assigned to that telecaller. If they are new, a Staff login is created (password 12345678).'],
            ['7. Existing customers are updated when GST matches, or Company Name + mobile/city matches.'],
        ], null, 'A1');
        $help->getColumnDimension('A')->setWidth(110);
        $help->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    public function writeDemoToPath(string $path): void
    {
        IOFactory::createWriter($this->makeDemoSpreadsheet(), 'Xlsx')->save($path);
    }

    private function styleDemoSheet(Worksheet $sheet, int $columnCount): void
    {
        $lastCol = $sheet->getCell([$columnCount, 1])->getColumn();
        $headerRange = 'A1:'.$lastCol.'1';

        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1B4F72'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter($headerRange);

        for ($i = 1; $i <= $columnCount; $i++) {
            $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }
    }

    private function warmStaffLookup(): void
    {
        User::where('role', 'sales_executive')->get(['id', 'name'])->each(function (User $u) {
            $this->execByName[$this->nameKey($u->name)] = $u->id;
        });
        User::where('role', 'telecaller')->get(['id', 'name'])->each(function (User $u) {
            $this->telecallerByName[$this->nameKey($u->name)] = $u->id;
        });
    }

    private function nameKey(string $name): string
    {
        return Str::of($this->cleanStaffName($name))->lower()->replaceMatches('/\s+/', ' ')->trim()->toString();
    }

    private function cleanStaffName(string $name): string
    {
        $name = trim(preg_replace('/\s+/', ' ', $name) ?? $name);
        $name = preg_replace('/^(mr|mrs|ms|miss|dr|shri|smt)\.?\s+/i', '', $name) ?? $name;

        return trim($name);
    }

    private function isPlaceholderName(string $name): bool
    {
        $key = $this->nameKey($name);

        return $key === '' || in_array($key, ['na', 'n/a', 'nil', 'none', '-', '--', 'unassigned', 'tbd', 'null', '0'], true);
    }

    private function resolveExecId(string $name): ?int
    {
        if ($this->isPlaceholderName($name)) {
            return null;
        }

        return $this->execByName[$this->nameKey($name)] ?? null;
    }

    private function resolveTelecallerId(string $name): ?int
    {
        if ($this->isPlaceholderName($name)) {
            return null;
        }

        return $this->telecallerByName[$this->nameKey($name)] ?? null;
    }

    private function resolveOrCreateExec(string $salesName, string $salesAlt, ?string $city): ?int
    {
        $id = $this->resolveExecId($salesName) ?? $this->resolveExecId($salesAlt);
        if ($id !== null) {
            return $id;
        }

        $create = $this->cleanStaffName($salesName !== '' ? $salesName : $salesAlt);
        if ($this->isPlaceholderName($create)) {
            return null;
        }

        return $this->ensureStaff($create, 'sales_executive', $city);
    }

    private function resolveOrCreateTelecaller(string $callingName, ?string $city): ?int
    {
        $id = $this->resolveTelecallerId($callingName);
        if ($id !== null) {
            return $id;
        }

        $create = $this->cleanStaffName($callingName);
        if ($this->isPlaceholderName($create)) {
            return null;
        }

        return $this->ensureStaff($create, 'telecaller', $city);
    }

    private function staffBranch(?string $city): string
    {
        $city = trim((string) $city);
        if (City::isMain($city)) {
            return (string) City::canonicalName($city);
        }

        $parent = City::parentForSubCity($city);
        if ($parent) {
            return $parent;
        }

        return 'Mumbai';
    }

    private function uniqueStaffEmail(string $name, string $role): string
    {
        $slug = Str::of($name)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '.')->trim('.')->toString();
        if ($slug === '') {
            $slug = $role.'.'.Str::lower(Str::random(6));
        }

        $domain = self::DEFAULT_STAFF_EMAIL_DOMAIN;
        $email = $slug.'@'.$domain;
        $i = 2;
        while (User::where('email', $email)->exists()) {
            $email = $slug.$i.'@'.$domain;
            $i++;
            if ($i > 50) {
                return $slug.'.'.Str::lower(Str::random(4)).'@'.$domain;
            }
        }

        return $email;
    }

    private function ensureStaff(string $name, string $role, ?string $city): int
    {
        $map = $role === 'telecaller' ? 'telecallerByName' : 'execByName';
        $key = $this->nameKey($name);
        if (isset($this->{$map}[$key])) {
            return (int) $this->{$map}[$key];
        }

        $branch = $this->staffBranch($city);
        City::firstOrCreate(['name' => $branch]);

        $user = User::create([
            'name' => $name,
            'email' => $this->uniqueStaffEmail($name, $role),
            'password' => self::DEFAULT_STAFF_PASSWORD,
            'role' => $role,
            'branch' => $branch,
        ]);

        $this->{$map}[$key] = $user->id;
        $this->createdStaff[] = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $role,
        ];

        return $user->id;
    }

    private function ensureCityAndSubCity(string $cityName, ?string $subCityName): void
    {
        $city = City::firstOrCreate(['name' => $cityName]);
        if ($subCityName) {
            SubCity::firstOrCreate([
                'city_id' => $city->id,
                'name' => $subCityName,
            ]);
        }
    }

    private function findExisting(array $payload): ?Company
    {
        if (! empty($payload['gst_number'])) {
            $byGst = Company::where('gst_number', $payload['gst_number'])->first();
            if ($byGst) {
                return $byGst;
            }
        }

        $query = Company::where('name', $payload['name']);
        if (! empty($payload['mobile_number'])) {
            $match = (clone $query)->where('mobile_number', $payload['mobile_number'])->first();
            if ($match) {
                return $match;
            }
        }
        if (! empty($payload['city'])) {
            $match = (clone $query)->where('city', $payload['city'])->first();
            if ($match) {
                return $match;
            }
        }

        return $query->first();
    }

    private function mapColumns(array $headers): array
    {
        return [
            'name' => $this->findColumnIndex($headers, [
                'company name',
                'company',
                'customer name',
                'customer',
                'firm name',
            ]),
            'contact_person_name' => $this->findColumnIndex($headers, [
                'contact person',
                'contact pers',
                'contact name',
                'contact',
            ]),
            'mobile_number' => $this->findColumnIndex($headers, [
                'mobile number',
                'mobile num',
                'mobile',
                'phone',
                'primary mobile',
            ], exactFirst: true),
            'alternate_mobile' => $this->findColumnIndex($headers, [
                'alternate mobile',
                'alternate mob',
                'alt mobile',
                'alternate number',
                'secondary mobile',
            ]),
            'email' => $this->findColumnIndex($headers, [
                'email',
                'e-mail',
            ]),
            'address' => $this->findColumnIndex($headers, [
                'address',
            ]),
            'sub_city' => $this->findColumnIndex($headers, [
                'sub city station wise',
                'sub city station',
                'sub city',
                'subcity',
                'station',
            ]),
            'city' => $this->findColumnIndex($headers, [
                'city',
            ], exactFirst: true),
            'branch' => $this->findColumnIndex($headers, [
                'branch',
                'main city',
            ], exactFirst: true),
            'state' => $this->findColumnIndex($headers, [
                'state',
            ]),
            'pin_code' => $this->findColumnIndex($headers, [
                'pin code',
                'pincode',
                'pin',
                'zip',
                'postal code',
            ]),
            'sales_person_name' => $this->findColumnIndex($headers, [
                'sales person name',
                'salesperson name',
                'sales person',
                'salesperson',
            ]),
            'calling' => $this->findColumnIndex($headers, [
                'calling',
                'telecaller',
                'inside sales',
                'caller',
            ]),
            'sales' => $this->findColumnIndex($headers, [
                'sales',
            ], exactFirst: true),
            'brand' => $this->findColumnIndex($headers, [
                'brand',
            ]),
            'gst_number' => $this->findColumnIndex($headers, [
                'gst number',
                'gstin',
                'gst',
            ]),
        ];
    }

    private function rowToPayload(array $row, array $map): array
    {
        $get = function (string $key) use ($row, $map): string {
            $idx = $map[$key] ?? null;
            if ($idx === null) {
                return '';
            }

            return $this->cleanCell($row[$idx] ?? null);
        };

        $phones = $this->extractPhones($get('mobile_number'), $get('alternate_mobile'));
        $location = City::resolveLocation($get('branch'), $get('city'), $get('sub_city'));

        return [
            'name' => $this->limit($get('name'), 255),
            'contact_person_name' => $this->nullable($this->limit($get('contact_person_name'), 255)),
            'mobile_number' => $this->nullable($phones['mobile']),
            'alternate_mobile' => $this->nullable($phones['alternate']),
            'email' => $this->nullableEmail($get('email')),
            'address' => $this->nullable($get('address')),
            'sub_city' => $this->nullable($this->limit((string) ($location['sub_city'] ?? ''), 255)),
            'city' => $this->limit($location['city'], 255),
            'state' => $this->nullable($this->limit($get('state'), 100)),
            'pin_code' => $this->nullable($this->limit($get('pin_code'), 20)),
            'brand' => $this->nullable($this->limit($get('brand'), 150)),
            'gst_number' => $this->nullable($this->limit($get('gst_number'), 255)),
            '_sales_person_name' => $get('sales_person_name'),
            '_calling' => $get('calling'),
            '_sales' => $get('sales'),
        ];
    }

    /**
     * Sheets often jam several mobiles into one cell (with or without separators).
     * Keep the first as primary and the next as alternate, each within DB length.
     *
     * @return array{mobile: string, alternate: string}
     */
    private function extractPhones(string $primary, string $alternate): array
    {
        $numbers = [];
        foreach ([$primary, $alternate] as $raw) {
            foreach ($this->phoneCandidates($raw) as $number) {
                if (! in_array($number, $numbers, true)) {
                    $numbers[] = $number;
                }
            }
        }

        return [
            'mobile' => $this->limit($numbers[0] ?? '', 20),
            'alternate' => $this->limit($numbers[1] ?? '', 30),
        ];
    }

    /**
     * @return list<string>
     */
    private function phoneCandidates(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            return [];
        }

        if (preg_match('/^\d+(\.0+)?$/', $value)) {
            $value = preg_replace('/\.0+$/', '', $value) ?? $value;
        }

        $found = [];
        $parts = preg_split('/[\s,;\/\\\\|&]+/', $value) ?: [];
        foreach ($parts as $part) {
            $digits = preg_replace('/\D/', '', $part) ?? '';
            if (strlen($digits) >= 20 && strlen($digits) % 10 === 0) {
                foreach (str_split($digits, 10) as $chunk) {
                    $normalized = $this->toIndianMobile($chunk);
                    if ($normalized !== '') {
                        $found[] = $normalized;
                    }
                }

                continue;
            }

            $normalized = $this->toIndianMobile($part);
            if ($normalized !== '') {
                $found[] = $normalized;
            }
        }
        if ($found !== []) {
            return $found;
        }

        $single = $this->toIndianMobile($value);

        return $single !== '' ? [$single] : [];
    }

    private function toIndianMobile(string $value): string
    {
        $digits = preg_replace('/\D/', '', $value) ?? '';
        if ($digits === '') {
            return '';
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        } elseif (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (strlen($digits) === 10 && preg_match('/^[6-9]/', $digits)) {
            return $digits;
        }

        if (strlen($digits) > 20) {
            $firstTen = substr($digits, 0, 10);
            if (preg_match('/^[6-9]\d{9}$/', $firstTen)) {
                return $firstTen;
            }

            return substr($digits, 0, 20);
        }

        return $digits;
    }

    private function nullableEmail(string $value): ?string
    {
        $value = trim($value);
        if ($value === '' || ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $this->limit($value, 255);
    }

    private function nullable(string $value): ?string
    {
        return $value === '' ? null : $value;
    }

    private function limit(string $value, int $max): string
    {
        if ($value === '') {
            return '';
        }

        return mb_strlen($value) > $max ? mb_substr($value, 0, $max) : $value;
    }

    private function normalizeHeader(string $header): string
    {
        $header = Str::of($header)->replace("\xc2\xa0", ' ')->trim()->lower()->toString();
        $header = preg_replace('/\s+/', ' ', $header) ?? $header;

        return $header;
    }

    private function findColumnIndex(array $headers, array $candidates, bool $exactFirst = false): ?int
    {
        if ($exactFirst) {
            foreach ($headers as $idx => $header) {
                foreach ($candidates as $candidate) {
                    if ($header === $candidate) {
                        return (int) $idx;
                    }
                }
            }
        }

        foreach ($headers as $idx => $header) {
            // Don't match "sales person name" when looking for plain "sales"
            if (in_array('sales', $candidates, true) && str_contains($header, 'person')) {
                continue;
            }
            // Don't match "sub city..." when looking for plain "city"
            if (in_array('city', $candidates, true) && (str_contains($header, 'sub') || str_contains($header, 'station'))) {
                continue;
            }
            // Don't match alternate mobile when looking for mobile
            if (
                (in_array('mobile', $candidates, true) || in_array('mobile number', $candidates, true) || in_array('mobile num', $candidates, true))
                && (str_contains($header, 'alternate') || str_contains($header, 'alt ') || str_contains($header, 'whatsapp'))
            ) {
                continue;
            }

            foreach ($candidates as $candidate) {
                if ($header === $candidate || str_starts_with($header, $candidate) || str_contains($header, $candidate)) {
                    return (int) $idx;
                }
            }
        }

        return null;
    }

    private function cleanCell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        $text = trim((string) $value);
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return $text;
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) ($cell ?? '')) !== '') {
                return false;
            }
        }

        return true;
    }
}
