<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\City;
use App\Models\Company;
use App\Models\SubCity;
use App\Services\QuotationDocumentService;
use App\Services\Telecaller\TelecallerCallService;
use App\Services\Telecaller\TelecallerPortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TelecallerController extends Controller
{
    public function __construct(
        private TelecallerPortalService $portal,
        private TelecallerCallService $calls,
        private QuotationDocumentService $quotes,
    ) {}

    public function getCalls(Request $request)
    {
        return $this->getLeads($request);
    }

    public function getLeads(Request $request)
    {
        $user = $request->user();
        $leads = $this->portal->assignedLeadsQuery($user)->get()
            ->map(fn (Company $company) => $this->portal->serializeLead($company));

        return response()->json($leads->values());
    }

    public function getCustomers(Request $request)
    {
        $user = $request->user();
        $customers = $this->portal->branchCustomers($user, $request->query('q'));

        return response()->json([
            'total' => $customers->count(),
            'branch' => City::canonicalName($user->branch),
            'customers' => $customers->values(),
        ]);
    }

    public function claimLead(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $user = $request->user();
        $company = Company::findOrFail($request->integer('company_id'));
        $alreadyMine = (int) $company->assigned_tc_id === (int) $user->id;
        $company = $this->calls->claim($user, $company->id);

        return response()->json([
            'message' => $alreadyMine
                ? 'This customer is already in your lead queue.'
                : 'Customer added to your lead queue.',
            'already_assigned' => $alreadyMine,
            'company' => $company,
        ]);
    }

    public function addCompany(Request $request)
    {
        $request->validate($this->companyFormRules($request));

        $user = $request->user();
        $company = Company::create(array_merge($this->companyFormPayload($request), [
            'city' => $request->city ?: City::canonicalName($user->branch),
            'credit_limit' => $request->input('credit_limit', 0) ?? 0,
            'outstanding_amount' => 0,
            'stage' => $request->stage ?: 'New',
            'assigned_exec_id' => $request->filled('assigned_exec_id') ? $request->integer('assigned_exec_id') : null,
            'assigned_tc_id' => $request->filled('assigned_tc_id') ? $request->integer('assigned_tc_id') : $user->id,
            'created_by' => $user->id,
        ]));

        return response()->json([
            'message' => 'Lead added to your queue.',
            'company' => $company,
        ], 201);
    }

    public function enquiryOptions(Request $request): JsonResponse
    {
        $user = $request->user();
        $meta = $this->portal->quotationFormMeta($user);

        return response()->json([
            'cities' => $meta['cities']->map(fn ($city) => [
                'name' => $city->name,
                'sub_cities' => $city->subCities->pluck('name')->values(),
            ])->values(),
            'sales_executives' => $meta['salesExecutives']->map(fn ($exec) => [
                'id' => $exec->id,
                'name' => $exec->name,
                'branch' => $exec->branch,
            ])->values(),
            'telecallers' => $meta['telecallers']->map(fn ($tc) => [
                'id' => $tc->id,
                'name' => $tc->name,
            ])->values(),
            'stages' => Company::STAGES,
            'default_city' => $meta['defaultCity'],
            'default_tc_id' => $meta['defaultTcId'],
            'default_exec_id' => null,
        ]);
    }

    public function updateCompany(Request $request)
    {
        $request->validate(array_merge($this->companyFormRules($request), [
            'company_id' => 'required|exists:companies,id',
        ]));

        $user = $request->user();
        $company = Company::findOrFail($request->integer('company_id'));

        if (! $this->portal->canAccess($user, $company)) {
            return response()->json([
                'message' => 'This lead is not in your calling queue.',
            ], 403);
        }

        $company->update(array_merge($this->companyFormPayload($request), [
            'city' => $request->city ?: $company->city,
        ]));

        $company->load([
            'calls' => fn ($q) => $q->orderByDesc('called_at'),
            'quotations' => fn ($q) => $q->orderByDesc('date')->orderByDesc('id'),
        ]);

        return response()->json([
            'message' => 'Company details updated.',
            'company' => $this->portal->serializeLead($company),
        ]);
    }

    private function companyFormRules(Request $request): array
    {
        $cityId = City::where('name', $request->city)->value('id');

        return [
            'name' => 'required|string|max:255',
            'contact_person_name' => 'nullable|string|max:255',
            'mobile_number' => 'nullable|string|max:20',
            'whatsapp_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'industry' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'sub_city' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($cityId) {
                    if (! filled($value) || ! $cityId) {
                        return;
                    }
                    $names = SubCity::where('city_id', $cityId)->pluck('name');
                    if ($names->isEmpty()) {
                        return;
                    }
                    if (! $names->contains($value)) {
                        $fail('The selected sub city is invalid for this city.');
                    }
                },
            ],
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'gst_number' => 'nullable|string|max:255',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|string|max:255',
            'stage' => 'nullable|in:'.implode(',', Company::STAGES),
            'assigned_exec_id' => 'nullable|exists:users,id',
            'assigned_tc_id' => 'nullable|exists:users,id',
            'house_wire_discount' => 'nullable|boolean',
            'flexible_wire_discount' => 'nullable|boolean',
            'other_wire_discount' => 'nullable|boolean',
            'house_wire_discount_note' => 'nullable|string|max:1000',
            'flexible_wire_discount_note' => 'nullable|string|max:1000',
            'other_wire_discount_note' => 'nullable|string|max:1000',
        ];
    }

    private function companyFormPayload(Request $request): array
    {
        $note = static fn ($value) => filled($value) && trim((string) $value) !== ''
            ? trim((string) $value)
            : null;

        $payload = [
            'name' => $request->name,
            'contact_person_name' => $note($request->input('contact_person_name')),
            'mobile_number' => $note($request->input('mobile_number')),
            'whatsapp_number' => $note($request->input('whatsapp_number')),
            'email' => $note($request->input('email')),
            'industry' => $note($request->input('industry')),
            'sub_city' => $note($request->input('sub_city')),
            'address' => $note($request->input('address')),
            'gst_number' => $note($request->input('gst_number')),
            'house_wire_discount' => $request->boolean('house_wire_discount'),
            'flexible_wire_discount' => $request->boolean('flexible_wire_discount'),
            'other_wire_discount' => $request->boolean('other_wire_discount'),
            'house_wire_discount_note' => $note($request->input('house_wire_discount_note')),
            'flexible_wire_discount_note' => $note($request->input('flexible_wire_discount_note')),
            'other_wire_discount_note' => $note($request->input('other_wire_discount_note')),
        ];

        if ($request->exists('latitude')) {
            $payload['latitude'] = $request->filled('latitude') ? $request->input('latitude') : null;
        }
        if ($request->exists('longitude')) {
            $payload['longitude'] = $request->filled('longitude') ? $request->input('longitude') : null;
        }
        if ($request->exists('payment_terms')) {
            $payload['payment_terms'] = $note($request->input('payment_terms'));
        }
        if ($request->exists('credit_limit')) {
            $payload['credit_limit'] = $request->input('credit_limit', 0) ?? 0;
        }

        return $payload;
    }

    public function logCall(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'outcome' => 'required|string|in:'.implode(',', Call::allowedOutcomes()),
            'remarks' => 'nullable|string',
            'next_followup' => 'nullable|date',
        ]);

        try {
            $call = $this->calls->log($request->user(), $request->only([
                'company_id',
                'outcome',
                'remarks',
                'next_followup',
            ]));
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?: 'Could not log call.',
            ], 403);
        }

        return response()->json([
            'message' => 'Call logged successfully',
            'call' => $call,
        ], 201);
    }

    public function updateStage(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'stage' => 'required|in:'.implode(',', Company::STAGES),
        ]);

        try {
            $company = $this->calls->updateStage(
                $request->user(),
                $request->integer('company_id'),
                $request->input('stage')
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?: 'Could not update status.',
            ], 403);
        }

        return response()->json([
            'message' => 'Lead status updated to '.$company->stage.'.',
            'company' => $this->portal->serializeLead($company->load(['calls', 'quotations'])),
        ]);
    }

    public function getProducts(Request $request)
    {
        $payload = $this->portal->searchProducts(
            $request->query('q'),
            $request->integer('limit', 40)
        );

        return response()->json($payload);
    }

    public function getQuotations(Request $request)
    {
        return response()->json($this->portal->quotationsFor($request->user())->values());
    }

    public function storeQuotation(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'call_outcome' => 'nullable|string|in:'.implode(',', Call::OUTCOMES),
            'call_remarks' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:5000',
            'payment_terms' => 'nullable|string|max:255',
            'product_make' => 'nullable|string|max:255',
            'party_gstin' => 'nullable|string|max:50',
            'party_mobile' => 'nullable|string|max:30',
            'party_address' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:2000',
            'items.*.product_id' => 'nullable|integer|exists:products,id',
            'items.*.hsn_code' => 'nullable|string|max:50',
            'items.*.qty' => 'required|numeric|min:0.001',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.list_price' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            if ($request->filled('call_outcome')) {
                $this->calls->log($request->user(), [
                    'company_id' => $request->integer('company_id'),
                    'outcome' => $request->input('call_outcome'),
                    'remarks' => $request->input('call_remarks'),
                ]);
            }

            $quote = $this->portal->createQuotation(
                $request->user(),
                $request->all(),
                $this->quotes
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?: 'Could not create quotation.',
            ], 403);
        }

        return response()->json([
            'message' => 'Quotation '.$quote->quotation_number.' created.',
            'quotation' => $this->portal->serializeQuotation($quote),
        ], 201);
    }
}
