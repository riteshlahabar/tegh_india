<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductSheetImporter
{
    /**
     * Import one product per sheet row with all Standardized Product List columns.
     *
     * @return array{added:int,updated:int,skipped:int,total_rows:int}
     */
    public function import(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        if (count($rows) < 2) {
            return ['added' => 0, 'updated' => 0, 'skipped' => 0, 'total_rows' => 0];
        }

        $headerRow = array_map(fn ($h) => $this->normalizeHeader((string) $h), $rows[0]);
        $map = $this->mapColumns($headerRow);

        $added = 0;
        $updated = 0;
        $skipped = 0;
        $dataRows = 0;

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (! is_array($row) || $this->rowIsEmpty($row)) {
                continue;
            }
            $dataRows++;

            $payload = $this->rowToPayload($row, $map);
            if ($payload['name'] === '' && $payload['product_code'] === '') {
                $skipped++;

                continue;
            }

            // Prefer standardized name; fall back to product code/name.
            if ($payload['name'] === '') {
                $payload['name'] = $payload['product_code'];
            }

            $payload['name'] = $this->limit($payload['name'], 255);
            $payload['product_code'] = $payload['product_code'] !== ''
                ? $this->limit($payload['product_code'], 255)
                : null;

            $existing = null;
            if ($payload['product_code']) {
                $existing = Product::where('product_code', $payload['product_code'])->first();
            }
            if (! $existing) {
                $existing = Product::where('name', $payload['name'])->first();
            }

            $payload['is_active'] = true;

            if ($existing) {
                $existing->update($payload);
                $updated++;

                continue;
            }

            Product::create($payload);
            $added++;
        }

        return [
            'added' => $added,
            'updated' => $updated,
            'skipped' => $skipped,
            'total_rows' => $dataRows,
        ];
    }

    private function mapColumns(array $headers): array
    {
        return [
            'product_code' => $this->findColumnIndex($headers, [
                'product name',
                'product_name',
                'product code',
                'productcode',
            ]) ?? 0,
            'name' => $this->findColumnIndex($headers, [
                'standardized product name',
                'standardised product name',
                'standardized_product_name',
                'standard name',
            ]) ?? 1,
            'usage_unit' => $this->findColumnIndex($headers, [
                'usage unit',
                'usage_unit',
            ]),
            'description' => $this->findColumnIndex($headers, [
                'description',
                'desc',
            ], exactFirst: true),
            'category' => $this->findColumnIndex($headers, [
                'category',
            ]),
            'hsn_code' => $this->findColumnIndex($headers, [
                'hsn code',
                'hsn_code',
                'hsn',
            ]),
            'mts' => $this->findColumnIndex($headers, [
                'mts.',
                'mts',
                'meters',
                'metres',
            ]),
            'colour' => $this->findColumnIndex($headers, [
                'colour',
                'color',
            ]),
            'size_sqmm' => $this->findColumnIndex($headers, [
                'size (sq.mm)',
                'size (sq.mm',
                'size sq.mm',
                'size_sqmm',
                'size',
            ]),
            'core' => $this->findColumnIndex($headers, [
                'core',
            ]),
            'spec' => $this->findColumnIndex($headers, [
                'spec.',
                'spec',
                'specification',
            ]),
            'unit' => $this->findColumnIndex($headers, [
                'unit',
            ], exactFirst: true),
            'des1' => $this->findColumnIndex($headers, [
                'des1',
                'des 1',
            ]),
            'des2' => $this->findColumnIndex($headers, [
                'des2',
                'des 2',
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

        return [
            'product_code' => $get('product_code'),
            'name' => $get('name'),
            'usage_unit' => $this->nullable($get('usage_unit')),
            'description' => $this->nullable($get('description')),
            'category' => $this->nullable($get('category')),
            'hsn_code' => $this->nullable($this->limit($get('hsn_code'), 50)),
            'mts' => $this->nullable($this->limit($get('mts'), 50)),
            'colour' => $this->nullable($this->limit($get('colour'), 100)),
            'size_sqmm' => $this->nullable($this->limit($get('size_sqmm'), 50)),
            'core' => $this->nullable($this->limit($get('core'), 50)),
            'spec' => $this->nullable($this->limit($get('spec'), 100)),
            'unit' => $this->nullable($this->limit($get('unit'), 50)),
            'des1' => $this->nullable($this->limit($get('des1'), 255)),
            'des2' => $this->nullable($this->limit($get('des2'), 255)),
        ];
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
            // Avoid matching "usage unit" when looking for plain "unit"
            if (in_array('unit', $candidates, true) && str_contains($header, 'usage')) {
                continue;
            }
            // Avoid matching "description" with des1/des2
            if (in_array('description', $candidates, true) || in_array('desc', $candidates, true)) {
                if (preg_match('/^des\s*\d+$/', $header)) {
                    continue;
                }
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
