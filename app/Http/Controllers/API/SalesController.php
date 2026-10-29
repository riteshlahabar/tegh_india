<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\City;
use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SalesController extends Controller
{
    public function getProducts()
    {
        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'product_code',
                'usage_unit',
                'description',
                'category',
                'hsn_code',
                'mts',
                'colour',
                'size_sqmm',
                'core',
                'spec',
                'unit',
                'des1',
                'des2',
            ]);

        return response()->json($products);
    }

    public function getLeads(Request $request)
    {
        try {
            $user = $request->user();

            $query = Company::where('assigned_exec_id', $user->id)
                ->with(['creator', 'products'])
                ->orderBy('updated_at', 'desc');

            if (Schema::hasTable('visits')) {
                $query->with(['visits' => function ($q) use ($user) {
                    $q->where('executive_id', $user->id)
                        ->orderBy('created_at', 'desc');
                }]);
            }

            $leads = $query->get()->map(function (Company $company) {
                $arr = $company->toArray();
                $arr['enquiry_product_name'] = $company->enquiryProductsLabel()
                    ?: ($company->enquiry_product_name ?? '');
                $arr['product_ids'] = $company->products->pluck('id')->values();
                $arr['products'] = $company->products->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                ])->values();

                return $arr;
            });

            return response()->json($leads);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Could not load leads: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getCustomers(Request $request)
    {
        $user = $request->user();
        $search = trim((string) $request->query('q', ''));
        $city = trim((string) $request->query('city', ''));

        $query = Company::query()->orderBy('name');
        $this->restrictToSalesBranch($query, $user);
        $query->select([
            'id',
            'name',
            'contact_person_name',
            'mobile_number',
            'whatsapp_number',
            'email',
            'industry',
            'city',
            'sub_city',
            'address',
            'stage',
            'gst_number',
            'assigned_exec_id',
        ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where('name', 'like', $like)
                    ->orWhere('contact_person_name', 'like', $like)
                    ->orWhere('mobile_number', 'like', $like)
                    ->orWhere('city', 'like', $like)
                    ->orWhere('sub_city', 'like', $like)
                    ->orWhere('gst_number', 'like', $like)
                    ->orWhere('address', 'like', $like);
            });
        }

        if ($city !== '') {
            $branch = City::canonicalName($user->branch);
            if ($branch === null || strcasecmp($city, $branch) === 0) {
                $query->where('city', $city);
            }
        }

        $customers = $query->get()->map(fn (Company $c) => [
            'id' => $c->id,
            'name' => $c->name,
            'contact_person_name' => $c->contact_person_name,
            'mobile_number' => $c->mobile_number,
            'whatsapp_number' => $c->whatsapp_number,
            'email' => $c->email,
            'industry' => $c->industry,
            'city' => $c->city,
            'sub_city' => $c->sub_city,
            'address' => $c->address,
            'stage' => $c->stage,
            'gst_number' => $c->gst_number,
            'assigned' => (int) $c->assigned_exec_id === (int) $user->id,
        ]);

        return response()->json([
            'total' => $customers->count(),
            'branch' => City::canonicalName($user->branch),
            'customers' => $customers->values(),
        ]);
    }

    public function addCompany(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'enquiry_product_name' => 'nullable|string|max:255',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer|exists:products,id',
            'contact_person_name' => 'nullable|string|max:255',
            'mobile_number' => 'nullable|string|max:20',
            'whatsapp_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'industry' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'sub_city' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'gst_number' => 'nullable|string|max:255',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|string',
            'stage' => 'nullable|in:'.implode(',', Company::STAGES),
            'assigned_exec_id' => 'nullable|exists:users,id',
            'assigned_tc_id' => 'nullable|exists:users,id',
            'house_wire_discount' => 'nullable|boolean',
            'flexible_wire_discount' => 'nullable|boolean',
            'other_wire_discount' => 'nullable|boolean',
            'house_wire_discount_note' => 'nullable|string|max:1000',
            'flexible_wire_discount_note' => 'nullable|string|max:1000',
            'other_wire_discount_note' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();
        $note = static fn ($value) => filled($value) && trim((string) $value) !== ''
            ? trim((string) $value)
            : null;
        $productIds = collect($request->input('product_ids', []))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $productNames = Product::whereIn('id', $productIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name');

        $enquiryProductName = $productNames->isNotEmpty()
            ? $productNames->implode(', ')
            : $request->enquiry_product_name;

        $company = Company::create([
            'name' => $request->name,
            'enquiry_product_name' => $enquiryProductName,
            'contact_person_name' => $note($request->contact_person_name),
            'mobile_number' => $note($request->mobile_number),
            'whatsapp_number' => $note($request->whatsapp_number),
            'email' => $note($request->email),
            'industry' => $note($request->industry),
            'city' => $request->city ?: City::canonicalName($user->branch),
            'sub_city' => $note($request->sub_city),
            'address' => $note($request->address),
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'gst_number' => $note($request->gst_number),
            'credit_limit' => $request->input('credit_limit', 0) ?? 0,
            'payment_terms' => $note($request->payment_terms),
            'assigned_exec_id' => $request->filled('assigned_exec_id') ? $request->integer('assigned_exec_id') : $user->id,
            'assigned_tc_id' => $request->filled('assigned_tc_id') ? $request->integer('assigned_tc_id') : null,
            'created_by' => $user->id,
            'outstanding_amount' => 0,
            'stage' => $request->stage ?: 'New',
            'house_wire_discount' => $request->boolean('house_wire_discount'),
            'flexible_wire_discount' => $request->boolean('flexible_wire_discount'),
            'other_wire_discount' => $request->boolean('other_wire_discount'),
            'house_wire_discount_note' => $note($request->house_wire_discount_note),
            'flexible_wire_discount_note' => $note($request->flexible_wire_discount_note),
            'other_wire_discount_note' => $note($request->other_wire_discount_note),
        ]);

        if (! empty($productIds)) {
            $company->products()->sync(
                Product::whereIn('id', $productIds)->where('is_active', true)->pluck('id')
            );
        }

        $company->load('products');

        return response()->json([
            'message' => 'Company created successfully',
            'company' => $company,
        ], 201);
    }

    public function enquiryOptions(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'cities' => City::main()->with('subCities')->get()->map(fn ($city) => [
                'name' => $city->name,
                'sub_cities' => $city->subCities->pluck('name')->values(),
            ])->values(),
            'sales_executives' => User::query()
                ->where('role', 'sales_executive')
                ->orderBy('name')
                ->get(['id', 'name', 'branch'])
                ->map(fn ($exec) => [
                    'id' => $exec->id,
                    'name' => $exec->name,
                    'branch' => $exec->branch,
                ])->values(),
            'telecallers' => User::query()
                ->where('role', 'telecaller')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($tc) => [
                    'id' => $tc->id,
                    'name' => $tc->name,
                ])->values(),
            'stages' => Company::STAGES,
            'default_city' => City::canonicalName($user->branch) ?: '',
            'default_exec_id' => $user->id,
            'default_tc_id' => null,
        ]);
    }

    public function claimLead(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $user = $request->user();
        $company = Company::findOrFail($request->integer('company_id'));
        $alreadyMine = (int) $company->assigned_exec_id === (int) $user->id;

        if (! $alreadyMine) {
            $company->update(['assigned_exec_id' => $user->id]);
        }

        return response()->json([
            'message' => $alreadyMine
                ? 'This customer is already in your lead queue.'
                : 'Customer added to your lead queue.',
            'already_assigned' => $alreadyMine,
            'company' => $company->fresh(),
        ]);
    }

    public function checkin(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        $user = $request->user();

        $activeVisit = Visit::where('executive_id', $user->id)
            ->where('status', 'checked_in')
            ->first();

        if ($activeVisit) {
            return response()->json([
                'message' => 'You are already checked in to another meeting. Please check out first.',
                'visit' => $activeVisit,
            ], 400);
        }

        $visit = Visit::create([
            'company_id' => $request->company_id,
            'executive_id' => $user->id,
            'checked_in_at' => Carbon::now(),
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'status' => 'checked_in',
        ]);

        if (AppSetting::googleMapsEnabled()) {
            $user->update([
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'last_location_at' => Carbon::now(),
            ]);
        }

        $productIds = collect($request->input('product_ids', []))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (! empty($productIds)) {
            $activeIds = Product::whereIn('id', $productIds)->where('is_active', true)->pluck('id');
            $visit->products()->sync($activeIds);
            $visit->company->products()->syncWithoutDetaching($activeIds);
            $this->refreshCompanyEnquiryProductName($visit->company);
        }

        $visit->load('products');

        return response()->json([
            'message' => 'Checked in successfully',
            'visit' => $visit,
        ]);
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'visit_id' => 'required|exists:visits,id',
            'notes' => 'nullable|string',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_note' => 'nullable|string|max:1000',
            'proof_photo' => 'nullable|image|max:5120',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        $visit = Visit::findOrFail($request->visit_id);

        if ($visit->status === 'checked_out') {
            return response()->json([
                'message' => 'Meeting already checked out.',
                'visit' => $visit,
            ], 400);
        }

        $photoPath = null;
        if ($request->hasFile('proof_photo')) {
            $photoPath = $request->file('proof_photo')->store('visits', 'public');
        }

        $visit->update([
            'checked_out_at' => Carbon::now(),
            'notes' => $request->notes,
            'discount_amount' => $request->filled('discount_amount')
                ? $request->discount_amount
                : null,
            'discount_note' => $request->filled('discount_note')
                ? $request->discount_note
                : null,
            'proof_photo_path' => $photoPath ? '/storage/'.$photoPath : null,
            'status' => 'checked_out',
        ]);

        if ($request->has('product_ids')) {
            $productIds = collect($request->input('product_ids', []))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
            $activeIds = Product::whereIn('id', $productIds)->where('is_active', true)->pluck('id');
            $visit->products()->sync($activeIds);
            $visit->company->products()->syncWithoutDetaching($activeIds);
            $this->refreshCompanyEnquiryProductName($visit->company);
        }

        $visit->load('products');

        return response()->json([
            'message' => 'Checked out successfully',
            'visit' => $visit,
        ]);
    }

    private function refreshCompanyEnquiryProductName(Company $company): void
    {
        $company->load('products');
        $label = $company->products->pluck('name')->filter()->implode(', ');
        if ($label !== '') {
            $company->update(['enquiry_product_name' => $label]);
        }
    }

    public function mapConfig()
    {
        $enabled = AppSetting::googleMapsEnabled();

        return response()
            ->json([
                'enabled' => $enabled,
                'api_key' => $enabled ? (config('services.google.maps_api_key') ?: null) : null,
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache');
    }

    public function updateLocation(Request $request)
    {
        if (! AppSetting::googleMapsEnabled()) {
            return response()->json([
                'message' => 'Google Maps is disabled by admin.',
            ], 403);
        }

        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'address' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $user->update([
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'current_address' => $request->address,
            'last_location_at' => Carbon::now(),
        ]);

        return response()->json([
            'message' => 'Location updated',
            'latitude' => (float) $user->latitude,
            'longitude' => (float) $user->longitude,
            'address' => $user->current_address,
            'last_location_at' => optional($user->last_location_at)?->toIso8601String(),
        ]);
    }

    public function mapData(Request $request)
    {
        if (! AppSetting::googleMapsEnabled()) {
            return response()->json([
                'enabled' => false,
                'customers' => [],
            ]);
        }

        $user = $request->user();

        $query = Company::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('name');

        $customers = $query
            ->get([
                'id',
                'name',
                'contact_person_name',
                'mobile_number',
                'address',
                'city',
                'sub_city',
                'latitude',
                'longitude',
                'stage',
                'assigned_exec_id',
            ])
            ->map(fn (Company $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'contact_person_name' => $c->contact_person_name,
                'mobile_number' => $c->mobile_number,
                'address' => $c->address,
                'city' => $c->city,
                'sub_city' => $c->sub_city,
                'stage' => $c->stage,
                'latitude' => (float) $c->latitude,
                'longitude' => (float) $c->longitude,
                'assigned' => (int) $c->assigned_exec_id === (int) $user->id,
                'assigned_exec_id' => $c->assigned_exec_id,
            ]);

        return response()->json([
            'enabled' => true,
            'radius_km' => 5,
            'self' => [
                'latitude' => $user->latitude !== null ? (float) $user->latitude : null,
                'longitude' => $user->longitude !== null ? (float) $user->longitude : null,
                'address' => $user->current_address,
                'last_location_at' => optional($user->last_location_at)?->toIso8601String(),
            ],
            'customers' => $customers,
        ]);
    }

    private function restrictToSalesBranch($query, $user)
    {
        $branch = City::canonicalName($user->branch ?? '');
        if ($branch) {
            $query->where('city', $branch);
        }

        return $query;
    }
}
