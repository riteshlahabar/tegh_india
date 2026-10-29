<?php

namespace App\Http\Controllers\Telecaller;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\City;
use App\Models\Company;
use App\Models\Quotation;
use App\Models\SubCity;
use App\Services\QuotationDocumentService;
use App\Services\Telecaller\TelecallerCallService;
use App\Services\Telecaller\TelecallerPortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TelecallerPageController extends Controller
{
    public function __construct(
        private TelecallerPortalService $portal,
        private TelecallerCallService $calls,
        private QuotationDocumentService $quotes,
    ) {}

    public function dashboard(Request $request): View
    {
        return view('telecaller.pages.dashboard', array_merge(
            $this->portal->dashboard($request->user()),
            $this->meta('queue', 'Dial Queue', 'Inside Sales Floor')
        ));
    }

    public function leads(Request $request): View
    {
        return view('telecaller.pages.leads', array_merge(
            $this->portal->leads(
                $request->user(),
                $request->query('q'),
                $request->query('stage')
            ),
            $this->meta('leads', 'Leads', 'Your queue and open branch accounts')
        ));
    }

    public function customers(Request $request): View
    {
        $user = $request->user();
        $q = $request->query('q');

        return view('telecaller.pages.customers', array_merge(
            [
                'q' => (string) $q,
                'customers' => $this->portal->branchCustomers($user, $q)->values(),
                'branch' => $user->branch,
            ],
            $this->portal->assignmentOptions(),
            $this->meta('customers', 'Customers', 'Branch customer directory')
        ));
    }

    public function assignCompany(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'assigned_exec_id' => 'nullable|exists:users,id',
            'assigned_tc_id' => 'nullable|exists:users,id',
        ]);

        $this->portal->assignCompany(
            $request->user(),
            $id,
            $request->filled('assigned_exec_id') ? (int) $request->assigned_exec_id : null,
            $request->filled('assigned_tc_id') ? (int) $request->assigned_tc_id : null,
        );

        return redirect()
            ->back()
            ->with('success', 'Lead assigned successfully.');
    }

    public function leadShow(Request $request, int $id): View
    {
        return view('telecaller.pages.lead-show', array_merge(
            $this->portal->show($request->user(), $id),
            $this->meta('leads', 'Lead Desk', 'Call, log, follow up')
        ));
    }

    public function calls(Request $request): View
    {
        return view('telecaller.pages.calls', array_merge(
            $this->portal->calls($request->user()),
            $this->meta('calls', 'Call Log', 'Your dial ledger')
        ));
    }

    public function quotations(Request $request): View
    {
        $user = $request->user();
        $companyId = (int) (old('company_id') ?: $request->integer('company_id'));
        $preselectCompany = $companyId ? $this->portal->quoteCompany($user, $companyId) : null;

        return view('telecaller.pages.quotations', array_merge(
            [
                'allQuotations' => $this->portal->quotationsFor($user),
                'preselectCompany' => $preselectCompany,
                'defaultQuoteTerms' => Quotation::defaultTerms(),
            ],
            $this->portal->quotationFormMeta($user),
            $this->meta('quotations', 'Quotations', 'Create and send price lists')
        ));
    }

    public function storeCompany(Request $request): RedirectResponse
    {
        $company = $this->portal->createCompanyFromEnquiry(
            $request->user(),
            $this->validatedCompanyPayload($request)
        );

        return redirect()
            ->route('telecaller.quotations', ['company_id' => $company->id])
            ->with('success', $company->name.' added. Continue the quotation.');
    }

    public function searchCompanies(Request $request): JsonResponse
    {
        return response()->json($this->portal->searchQuoteCompanies(
            $request->user(),
            $request->query('q'),
            $request->integer('limit', 40)
        ));
    }

    public function searchProducts(Request $request): JsonResponse
    {
        return response()->json($this->portal->searchProducts(
            $request->query('q'),
            $request->integer('limit', 40)
        ));
    }

    public function storeQuotation(Request $request): RedirectResponse
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'call_outcome' => 'nullable|string|in:'.implode(',', Call::OUTCOMES),
            'call_remarks' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:5000',
            'payment_terms' => 'nullable|string|max:255',
            'product_make' => 'nullable|string|max:255',
            'terms_conditions' => 'nullable|string|max:5000',
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
            return redirect()
                ->back()
                ->withInput()
                ->with('error', collect($e->errors())->flatten()->first() ?: 'Could not create quotation.');
        }

        return redirect()
            ->route('telecaller.quotations')
            ->with('success', 'Quotation '.$quote->quotation_number.' created.');
    }

    public function downloadQuotation(Request $request, int $id)
    {
        $quote = Quotation::with(['company', 'items'])->findOrFail($id);
        $user = $request->user();

        if (! $quote->company || ! $this->portal->canAccess($user, $quote->company)) {
            abort(404);
        }

        return $this->quotes->download($quote);
    }

    public function storeCall(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'outcome' => 'required|string|in:'.implode(',', Call::allowedOutcomes()),
            'remarks' => 'nullable|string|max:2000',
            'next_followup' => 'nullable|date',
        ]);

        $this->calls->log($request->user(), $data);

        return redirect()
            ->back()
            ->with('success', 'Call outcome saved to the CRM.');
    }

    public function updateStage(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'stage' => 'required|in:'.implode(',', Company::STAGES),
        ]);

        $this->calls->updateStage($request->user(), $id, $request->input('stage'));

        return redirect()
            ->back()
            ->with('success', 'Lead status updated to '.$request->input('stage').'.');
    }

    public function claimLead(Request $request, int $id): RedirectResponse
    {
        $this->calls->claim($request->user(), $id);

        return redirect()
            ->route('telecaller.leads.show', $id)
            ->with('success', 'Lead added to your calling queue.');
    }

    private function validatedCompanyPayload(Request $request): array
    {
        $cityId = City::where('name', $request->city)->value('id');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'house_wire_discount' => 'nullable|boolean',
            'flexible_wire_discount' => 'nullable|boolean',
            'other_wire_discount' => 'nullable|boolean',
            'house_wire_discount_note' => 'nullable|string|max:1000',
            'flexible_wire_discount_note' => 'nullable|string|max:1000',
            'other_wire_discount_note' => 'nullable|string|max:1000',
            'contact_person_name' => 'nullable|string|max:255',
            'mobile_number' => 'nullable|string|max:20',
            'whatsapp_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'industry' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'sub_city' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($cityId) {
                    if (! filled($value) || ! $cityId) {
                        return;
                    }
                    $exists = SubCity::where('city_id', $cityId)->where('name', $value)->exists();
                    if (! $exists) {
                        $fail('The selected sub city is invalid for this city.');
                    }
                },
            ],
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'gst_number' => 'nullable|string|max:255',
            'credit_limit' => 'required|numeric|min:0',
            'payment_terms' => 'nullable|string|max:255',
            'stage' => 'required|in:'.implode(',', Company::STAGES),
            'assigned_exec_id' => 'nullable|exists:users,id',
            'assigned_tc_id' => 'nullable|exists:users,id',
        ]);

        foreach ([
            'house_wire_discount_note', 'flexible_wire_discount_note', 'other_wire_discount_note',
            'contact_person_name', 'mobile_number', 'whatsapp_number', 'email', 'industry',
            'sub_city', 'address', 'gst_number', 'payment_terms',
        ] as $key) {
            if (array_key_exists($key, $validated) && is_string($validated[$key]) && trim($validated[$key]) === '') {
                $validated[$key] = null;
            }
        }

        $validated['house_wire_discount'] = $request->boolean('house_wire_discount');
        $validated['flexible_wire_discount'] = $request->boolean('flexible_wire_discount');
        $validated['other_wire_discount'] = $request->boolean('other_wire_discount');
        $validated['credit_limit'] = $request->input('credit_limit', 0);
        $validated['assigned_exec_id'] = $request->filled('assigned_exec_id') ? $request->assigned_exec_id : null;
        $validated['assigned_tc_id'] = $request->filled('assigned_tc_id') ? $request->assigned_tc_id : null;

        return $validated;
    }

    private function meta(string $nav, string $title, string $subtitle): array
    {
        return [
            'activeNav' => $nav,
            'pageTitle' => $title,
            'pageSubtitle' => $subtitle,
            'docTitle' => $title.' | Tegh Inside Sales',
        ];
    }
}
