<?php

namespace App\Services\Telecaller;

use App\Models\Call;
use App\Models\City;
use App\Models\Company;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\User;
use App\Services\QuotationDocumentService;
use App\Support\IndiaTime;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TelecallerPortalService
{
    public function dashboard(User $user): array
    {
        $leads = $this->visibleLeadsQuery($user)->get();
        $assigned = $leads->where('assigned_tc_id', $user->id);
        $available = $leads->whereNull('assigned_tc_id')->values();
        [$todayStart, $todayEnd] = IndiaTime::todayUtcRange();
        $callsToday = Call::query()
            ->where('telecaller_id', $user->id)
            ->whereBetween('called_at', [$todayStart, $todayEnd])
            ->count();

        $dueEnd = IndiaTime::now()->endOfDay()->utc();
        $dueFollowups = $assigned
            ->filter(fn (Company $c) => $this->latestFollowup($c)?->lte($dueEnd))
            ->values();

        return [
            'leadCount' => $leads->count(),
            'assignedCount' => $assigned->count(),
            'availableCount' => $available->count(),
            'branch' => City::canonicalName($user->branch) ?: $user->branch,
            'callsToday' => $callsToday,
            'dueCount' => $dueFollowups->count(),
            'dueFollowups' => $dueFollowups->take(8),
            'availableLeads' => $available->take(10),
            'recentCalls' => Call::with('company')
                ->where('telecaller_id', $user->id)
                ->orderByDesc('called_at')
                ->limit(8)
                ->get(),
        ];
    }

    public function leads(User $user, ?string $q = null, ?string $stage = null): array
    {
        $query = $this->visibleLeadsQuery($user);

        if (filled($q)) {
            $like = '%'.$q.'%';
            $query->where(function ($inner) use ($like) {
                $inner->where('name', 'like', $like)
                    ->orWhere('contact_person_name', 'like', $like)
                    ->orWhere('mobile_number', 'like', $like)
                    ->orWhere('city', 'like', $like)
                    ->orWhere('industry', 'like', $like);
            });
        }

        if (filled($stage)) {
            $query->where('stage', $stage);
        }

        $activeCount = (clone $query)->whereNotIn('stage', ['Won', 'Lost'])->count();
        $leads = $query->paginate(50)->withQueryString();

        return [
            'leads' => $leads,
            'q' => $q,
            'stage' => $stage,
            'activeCount' => $activeCount,
            'branch' => City::canonicalName($user->branch) ?: $user->branch,
        ];
    }

    public function show(User $user, int $id): array
    {
        $lead = $this->visibleLeadsQuery($user)->whereKey($id)->firstOrFail();

        return [
            'lead' => $lead,
        ];
    }

    public function calls(User $user): array
    {
        return [
            'calls' => Call::with('company')
                ->where('telecaller_id', $user->id)
                ->orderByDesc('called_at')
                ->limit(200)
                ->get(),
        ];
    }

    public function assignedLeadsQuery(User $user)
    {
        return Company::query()
            ->where('assigned_tc_id', $user->id)
            ->with([
                'calls' => fn ($q) => $q->orderByDesc('called_at'),
                'quotations' => fn ($q) => $q->orderByDesc('date')->orderByDesc('id'),
            ])
            ->orderByDesc('updated_at');
    }

    public function visibleLeadsQuery(User $user)
    {
        return Company::query()
            ->where(function ($q) use ($user) {
                $q->where('assigned_tc_id', $user->id)
                    ->orWhere(function ($inner) use ($user) {
                        $inner->whereNull('assigned_tc_id');
                        $this->applyBranch($inner, $user);
                    });
            })
            ->with([
                'calls' => fn ($q) => $q->orderByDesc('called_at'),
                'quotations' => fn ($q) => $q->orderByDesc('date')->orderByDesc('id'),
            ])
            ->orderByRaw('CASE WHEN assigned_tc_id = ? THEN 0 ELSE 1 END', [$user->id])
            ->orderByDesc('updated_at');
    }

    public function canAccess(User $user, Company $company): bool
    {
        if ((int) $company->assigned_tc_id === (int) $user->id) {
            return true;
        }

        if ($company->assigned_tc_id) {
            return false;
        }

        return $this->isInBranch($user, $company);
    }

    public function canAssign(User $user, Company $company): bool
    {
        if ((int) $company->assigned_tc_id === (int) $user->id) {
            return true;
        }

        return $this->isInBranch($user, $company);
    }

    public function assignmentOptions(): array
    {
        return [
            'salesExecutives' => User::query()
                ->where('role', 'sales_executive')
                ->orderBy('name')
                ->get(['id', 'name', 'branch']),
            'telecallers' => User::query()
                ->where('role', 'telecaller')
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }

    public function assignCompany(User $user, int $id, ?int $execId, ?int $tcId): Company
    {
        $company = Company::findOrFail($id);

        if (! $this->canAssign($user, $company)) {
            abort(404);
        }

        $company->update([
            'assigned_exec_id' => $execId,
            'assigned_tc_id' => $tcId,
        ]);

        return $company;
    }

    private function isInBranch(User $user, Company $company): bool
    {
        $branch = City::canonicalName($user->branch ?? '');
        if (! $branch) {
            return true;
        }

        if (strcasecmp((string) $company->city, $branch) === 0) {
            return true;
        }

        $parent = City::parentForSubCity($company->city)
            ?: City::parentForSubCity($company->sub_city);

        return $parent === $branch;
    }

    private function applyBranch($query, User $user): void
    {
        $branch = City::canonicalName($user->branch ?? '');
        if (! $branch) {
            return;
        }

        $subs = array_keys(array_filter(
            City::SUBCITY_PARENT,
            fn (string $parent) => $parent === $branch
        ));

        $query->where(function ($q) use ($branch, $subs) {
            $q->where('city', $branch);
            if ($subs !== []) {
                $q->orWhereIn('city', $subs)->orWhereIn('sub_city', $subs);
            }
        });
    }

    public function serializeLead(Company $company): array
    {
        $latest = $company->relationLoaded('calls')
            ? $company->calls->first()
            : $company->calls()->orderByDesc('called_at')->first();

        return [
            'id' => $company->id,
            'name' => $company->name,
            'contact_person_name' => $company->contact_person_name,
            'mobile_number' => $company->mobile_number,
            'alternate_mobile' => $company->alternate_mobile,
            'whatsapp_number' => $company->whatsapp_number,
            'email' => $company->email,
            'industry' => $company->industry,
            'city' => $company->city,
            'sub_city' => $company->sub_city,
            'address' => $company->address,
            'gst_number' => $company->gst_number,
            'stage' => $company->stage,
            'latitude' => $company->latitude,
            'longitude' => $company->longitude,
            'credit_limit' => $company->credit_limit,
            'payment_terms' => $company->payment_terms,
            'assigned_exec_id' => $company->assigned_exec_id,
            'assigned_tc_id' => $company->assigned_tc_id,
            'enquiry_product_name' => $company->enquiry_product_name,
            'house_wire_discount' => (bool) $company->house_wire_discount,
            'house_wire_discount_note' => $company->house_wire_discount_note,
            'flexible_wire_discount' => (bool) $company->flexible_wire_discount,
            'flexible_wire_discount_note' => $company->flexible_wire_discount_note,
            'other_wire_discount' => (bool) $company->other_wire_discount,
            'other_wire_discount_note' => $company->other_wire_discount_note,
            'outstanding_amount' => $company->outstanding_amount,
            'last_call' => $latest ? [
                'id' => $latest->id,
                'outcome' => $latest->outcome,
                'remarks' => $latest->remarks,
                'called_at' => IndiaTime::iso($latest->called_at),
                'called_at_display' => IndiaTime::format($latest->called_at),
                'next_followup' => IndiaTime::iso($latest->next_followup),
                'next_followup_display' => IndiaTime::format($latest->next_followup, 'd M Y, h:i A'),
            ] : null,
            'next_followup' => IndiaTime::date($latest?->next_followup),
            'next_followup_display' => IndiaTime::format($latest?->next_followup, 'd M Y, h:i A'),
            'calls_count' => $company->relationLoaded('calls')
                ? $company->calls->count()
                : $company->calls()->count(),
            'assigned' => $company->assigned_tc_id !== null,
            'quotations' => $this->serializeQuotations($company),
            'calls' => $company->relationLoaded('calls')
                ? $company->calls->take(20)->map(fn (Call $call) => [
                    'id' => $call->id,
                    'outcome' => $call->outcome,
                    'remarks' => $call->remarks,
                    'called_at' => IndiaTime::iso($call->called_at),
                    'called_at_display' => IndiaTime::format($call->called_at),
                    'next_followup' => IndiaTime::iso($call->next_followup),
                    'next_followup_display' => IndiaTime::format($call->next_followup, 'd M Y, h:i A'),
                ])->values()
                : [],
        ];
    }

    public function branchCustomers(User $user, ?string $search = null): Collection
    {
        $query = Company::query()
            ->with(['executive:id,name', 'telecaller:id,name'])
            ->orderBy('name')
            ->select([
                'id',
                'name',
                'contact_person_name',
                'mobile_number',
                'city',
                'sub_city',
                'address',
                'stage',
                'assigned_exec_id',
                'assigned_tc_id',
            ]);

        $this->applyBranch($query, $user);

        if (filled($search)) {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('contact_person_name', 'like', $like)
                    ->orWhere('mobile_number', 'like', $like)
                    ->orWhere('city', 'like', $like);
            });
        }

        return $query->limit(200)->get()->map(fn (Company $c) => [
            'id' => $c->id,
            'name' => $c->name,
            'contact_person_name' => $c->contact_person_name,
            'mobile_number' => $c->mobile_number,
            'city' => $c->city,
            'sub_city' => $c->sub_city,
            'address' => $c->address,
            'stage' => $c->stage,
            'assigned_exec_id' => $c->assigned_exec_id,
            'assigned_tc_id' => $c->assigned_tc_id,
            'executive_name' => $c->executive?->name,
            'telecaller_name' => $c->telecaller?->name,
            'assigned' => (int) $c->assigned_tc_id === (int) $user->id,
        ]);
    }

    public function searchProducts(?string $q = null, int $limit = 40): array
    {
        return Product::searchCatalog($q, $limit);
    }

    public function quotationFormMeta(User $user): array
    {
        return array_merge($this->assignmentOptions(), [
            'cities' => City::main()->with('subCities')->get(),
            'defaultCity' => City::canonicalName($user->branch) ?: '',
            'defaultTcId' => $user->id,
        ]);
    }

    public function createCompanyFromEnquiry(User $user, array $payload): Company
    {
        $payload['created_by'] = $user->id;
        $payload['outstanding_amount'] = 0;

        return Company::create($payload);
    }

    private function quoteCompaniesQuery(User $user)
    {
        return $this->visibleLeadsQuery($user)
            ->clone()
            ->without(['calls', 'quotations'])
            ->reorder();
    }

    public function quoteCompany(User $user, int $id): ?Company
    {
        return $this->quoteCompaniesQuery($user)
            ->whereKey($id)
            ->first(['id', 'name', 'city', 'mobile_number']);
    }

    public function searchQuoteCompanies(User $user, ?string $q = null, int $limit = 40): array
    {
        $limit = max(1, min($limit, 50));
        $query = $this->quoteCompaniesQuery($user);

        $term = trim((string) $q);
        if ($term !== '') {
            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';
            $query->where(function ($inner) use ($like) {
                $inner->where('name', 'like', $like)
                    ->orWhere('contact_person_name', 'like', $like)
                    ->orWhere('mobile_number', 'like', $like)
                    ->orWhere('alternate_mobile', 'like', $like)
                    ->orWhere('whatsapp_number', 'like', $like)
                    ->orWhere('city', 'like', $like)
                    ->orWhere('sub_city', 'like', $like);
            });
        }

        $total = (clone $query)->count();
        $companies = $query->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'city', 'sub_city', 'mobile_number', 'contact_person_name']);

        return [
            'total' => $total,
            'companies' => $companies->map(fn (Company $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'city' => $c->city ?: '',
                'mobile' => $c->mobile_number ?: '',
                'contact' => $c->contact_person_name ?: '',
                'label' => trim($c->name.($c->city ? ' · '.$c->city : '')),
            ])->values(),
        ];
    }

    public function quoteCompanies(User $user): Collection
    {
        return $this->quoteCompaniesQuery($user)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'email',
                'mobile_number',
                'gst_number',
                'address',
                'city',
                'sub_city',
                'assigned_tc_id',
            ]);
    }

    public function createQuotation(User $user, array $input, QuotationDocumentService $docs): Quotation
    {
        $company = Company::findOrFail((int) $input['company_id']);

        if (! $this->canAccess($user, $company)) {
            throw ValidationException::withMessages([
                'company_id' => ['This lead is not in your calling queue.'],
            ]);
        }

        if ((int) $company->assigned_tc_id !== (int) $user->id) {
            $company->update(['assigned_tc_id' => $user->id]);
            $company->refresh();
        }

        $items = array_values(array_filter($input['items'] ?? [], function ($row) {
            return filled($row['description'] ?? null);
        }));

        $quote = $docs->createWithItems([
            'company_id' => $company->id,
            'quotation_number' => Quotation::nextNumber(),
            'status' => 'Sent',
            'date' => Carbon::now(),
            'notes' => $input['notes'] ?? null,
            'sales_person_name' => $user->name,
            'payment_terms' => ($input['payment_terms'] ?? null) ?: 'Against Proforma Invoice',
            'terms_conditions' => ($input['terms_conditions'] ?? null) ?: Quotation::defaultTerms(),
            'product_make' => $input['product_make'] ?? null,
            'party_gstin' => ($input['party_gstin'] ?? null) ?: $company->gst_number,
            'party_mobile' => ($input['party_mobile'] ?? null) ?: $company->mobile_number,
            'party_address' => ($input['party_address'] ?? null) ?: $company->address,
            'amount' => 0,
        ], $items);

        if (! in_array($company->stage, ['Won', 'Lost'], true)) {
            $company->update(['stage' => 'Quotation Sent']);
        }

        return $quote->load(['company', 'items']);
    }

    public function quotationsFor(User $user)
    {
        $companyIds = $this->visibleLeadsQuery($user)
            ->clone()
            ->without(['calls', 'quotations'])
            ->pluck('id');

        return Quotation::query()
            ->with(['company:id,name,city', 'items'])
            ->whereIn('company_id', $companyIds)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn (Quotation $quote) => $this->serializeQuotation($quote));
    }

    public function serializeQuotation(Quotation $quote): array
    {
        $quote->loadMissing(['company', 'items']);

        return [
            'id' => $quote->id,
            'quotation_number' => $quote->quotation_number,
            'company_id' => $quote->company_id,
            'company_name' => $quote->company?->name,
            'status' => $quote->status,
            'date' => IndiaTime::date($quote->date),
            'date_display' => IndiaTime::format($quote->date, 'd M Y'),
            'amount' => $quote->amount,
            'notes' => $quote->notes,
            'payment_terms' => $quote->payment_terms,
            'product_make' => $quote->product_make,
            'items' => $quote->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'description' => $item->description,
                'hsn_code' => $item->hsn_code,
                'qty' => $item->qty,
                'unit' => $item->unit,
                'list_price' => $item->list_price,
                'discount_percent' => $item->discount_percent,
                'price' => $item->price,
                'amount' => $item->amount,
            ])->values(),
        ];
    }

    private function serializeQuotations(Company $company)
    {
        $quotes = $company->relationLoaded('quotations')
            ? $company->quotations
            : $company->quotations()->orderByDesc('date')->orderByDesc('id')->get();

        return $quotes->take(12)->map(fn (Quotation $quote) => [
            'id' => $quote->id,
            'quotation_number' => $quote->quotation_number,
            'status' => $quote->status,
            'date' => IndiaTime::date($quote->date),
            'date_display' => IndiaTime::format($quote->date, 'd M Y'),
            'amount' => $quote->amount,
        ])->values();
    }

    private function latestFollowup(Company $company): ?Carbon
    {
        $latest = $company->calls->first();

        return $latest?->next_followup;
    }
}
