<?php

namespace App\Services\Admin;

use App\Models\Company;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Visit;
use App\Services\SmtpConfigService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class QuotationPageService
{
    public function __construct(private SmtpConfigService $smtp) {}

    public function data(Request $request): array
    {
        $from = $request->filled('from') ? Carbon::parse($request->query('from'))->startOfDay() : null;
        $to = $request->filled('to') ? Carbon::parse($request->query('to'))->endOfDay() : null;
        $salesmanId = $request->integer('salesman_id') ?: null;
        $telecallerId = $request->integer('telecaller_id') ?: null;
        $companyId = $request->integer('company_id') ?: null;
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        if (! in_array($status, ['Sent', 'Approved', 'Lost'], true)) {
            $status = '';
        }
        $city = trim((string) $request->query('city', ''));
        $stageGroup = $request->query('stage_group') ? strtolower((string) $request->query('stage_group')) : null;
        if (! in_array($stageGroup, ['won', 'lost', 'other'], true)) {
            $stageGroup = null;
        }

        $quoteCompanies = Company::orderBy('name')->get([
            'id', 'name', 'email', 'mobile_number', 'gst_number', 'address', 'city', 'sub_city',
        ]);

        $quotes = Quotation::with(['company', 'items'])
            ->orderBy('created_at', 'desc');

        if ($from) {
            $quotes->where('date', '>=', $from);
        }
        if ($to) {
            $quotes->where('date', '<=', $to);
        }
        if ($companyId) {
            $quotes->where('company_id', $companyId);
        }
        if ($status !== '') {
            $quotes->where('status', $status);
        }
        if ($salesmanId || $telecallerId || $city !== '') {
            $quotes->whereHas('company', function ($q) use ($salesmanId, $telecallerId, $city) {
                if ($salesmanId) {
                    $q->where('assigned_exec_id', $salesmanId);
                }
                if ($telecallerId) {
                    $q->where('assigned_tc_id', $telecallerId);
                }
                if ($city !== '') {
                    $q->where('city', $city);
                }
            });
        }
        if ($search !== '') {
            $like = '%'.$search.'%';
            $quotes->where(function ($inner) use ($like) {
                $inner->where('quotation_number', 'like', $like)
                    ->orWhere('sales_person_name', 'like', $like)
                    ->orWhereHas('company', function ($company) use ($like) {
                        $company->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like)
                            ->orWhere('mobile_number', 'like', $like);
                    });
            });
        }
        if ($stageGroup) {
            $quotes->whereHas('company', function ($q) use ($stageGroup) {
                if ($stageGroup === 'won') {
                    $q->where('stage', 'Won');
                } elseif ($stageGroup === 'lost') {
                    $q->where('stage', 'Lost');
                } elseif ($stageGroup === 'other') {
                    $q->whereNotIn('stage', ['Won', 'Lost']);
                }
            });
        }

        return [
            'allQuotations' => $quotes->get(),
            'smtpSettings' => $this->smtp->settings(),
            'smtpReady' => $this->smtp->isReady(),
            'quoteCompanies' => $quoteCompanies,
            'quoteMeetingProducts' => $this->meetingProductsByCompany($quoteCompanies->pluck('id')->all()),
            'defaultQuoteTerms' => Quotation::defaultTerms(),
            'quoteFilters' => [
                'q' => $search,
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString(),
                'salesman_id' => $salesmanId,
                'telecaller_id' => $telecallerId,
                'company_id' => $companyId,
                'status' => $status,
                'city' => $city,
                'stage_group' => $stageGroup,
            ],
        ];
    }

    /**
     * Products salesmen added during meetings (visits), keyed by company id.
     * Falls back to company_product when a company has no visit products yet.
     *
     * @param  list<int|string>  $companyIds
     * @return array<string, list<array{id:int,name:string,hsn_code:?string,unit:string,description:string}>>
     */
    private function meetingProductsByCompany(array $companyIds): array
    {
        if ($companyIds === []) {
            return [];
        }

        $map = [];

        $visits = Visit::query()
            ->whereIn('company_id', $companyIds)
            ->with(['products' => function ($q) {
                $q->where('products.is_active', true)
                    ->orderBy('products.name')
                    ->select([
                        'products.id',
                        'products.name',
                        'products.hsn_code',
                        'products.unit',
                        'products.usage_unit',
                    ]);
            }])
            ->orderByDesc('checked_out_at')
            ->orderByDesc('created_at')
            ->get(['id', 'company_id', 'checked_out_at', 'created_at']);

        foreach ($visits as $visit) {
            $key = (string) $visit->company_id;
            $map[$key] ??= [];
            $seen = array_column($map[$key], 'id');

            foreach ($visit->products as $product) {
                if (in_array($product->id, $seen, true)) {
                    continue;
                }
                $map[$key][] = $this->productPayload($product);
                $seen[] = $product->id;
            }
        }

        // Fallback: products linked on the company (also synced at checkout).
        $missingIds = array_values(array_filter(
            $companyIds,
            fn ($id) => empty($map[(string) $id])
        ));

        if ($missingIds !== []) {
            $companies = Company::query()
                ->whereIn('id', $missingIds)
                ->with(['products' => function ($q) {
                    $q->where('products.is_active', true)
                        ->orderBy('products.name')
                        ->select([
                            'products.id',
                            'products.name',
                            'products.hsn_code',
                            'products.unit',
                            'products.usage_unit',
                        ]);
                }])
                ->get(['id']);

            foreach ($companies as $company) {
                $key = (string) $company->id;
                $map[$key] = $company->products
                    ->map(fn (Product $p) => $this->productPayload($p))
                    ->values()
                    ->all();
            }
        }

        return $map;
    }

    private function productPayload(Product $product): array
    {
        $unit = $product->unit ?: $product->usage_unit ?: 'Metre';

        return [
            'id' => (int) $product->id,
            'name' => (string) $product->name,
            'hsn_code' => $product->hsn_code,
            'unit' => $unit,
            'description' => (string) $product->name,
        ];
    }
}
