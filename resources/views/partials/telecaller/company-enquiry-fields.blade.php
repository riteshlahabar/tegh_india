@php
    $cities = $cities ?? collect();
    $salesExecutives = $salesExecutives ?? collect();
    $telecallers = $telecallers ?? collect();
    $defaultCity = $defaultCity ?? '';
    $defaultTcId = $defaultTcId ?? null;
@endphp
<div>
    <label class="block text-xs font-bold text-on-surface-variant mb-1">Company Name *</label>
    <input required name="name" value="{{ old('name') }}" type="text" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary" placeholder="Enter corporate name">
</div>

<div class="rounded-xl border border-outline-variant/40 bg-surface-container-low/60 p-3 sm:p-4 space-y-3">
    <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wide">Wire Discounts</p>

    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
        <label class="flex items-center gap-2 cursor-pointer shrink-0 sm:w-48">
            <input type="checkbox" name="house_wire_discount" value="1" @checked(old('house_wire_discount')) class="rounded border-outline-variant text-secondary focus:ring-secondary">
            <span class="text-sm text-primary font-medium">House Wire Discount</span>
        </label>
        <input type="text" name="house_wire_discount_note" value="{{ old('house_wire_discount_note') }}" class="w-full flex-1 border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary" placeholder="Note for house wire discount...">
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
        <label class="flex items-center gap-2 cursor-pointer shrink-0 sm:w-48">
            <input type="checkbox" name="flexible_wire_discount" value="1" @checked(old('flexible_wire_discount')) class="rounded border-outline-variant text-secondary focus:ring-secondary">
            <span class="text-sm text-primary font-medium">Flexible Wire Discount</span>
        </label>
        <input type="text" name="flexible_wire_discount_note" value="{{ old('flexible_wire_discount_note') }}" class="w-full flex-1 border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary" placeholder="Note for flexible wire discount...">
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
        <label class="flex items-center gap-2 cursor-pointer shrink-0 sm:w-48">
            <input type="checkbox" name="other_wire_discount" value="1" @checked(old('other_wire_discount')) class="rounded border-outline-variant text-secondary focus:ring-secondary">
            <span class="text-sm text-primary font-medium">Other Wire Discount</span>
        </label>
        <input type="text" name="other_wire_discount_note" value="{{ old('other_wire_discount_note') }}" class="w-full flex-1 border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary" placeholder="Note for other wire discount...">
    </div>
</div>

<div>
    <label class="block text-xs font-bold text-on-surface-variant mb-1">Contact Person Name</label>
    <input name="contact_person_name" value="{{ old('contact_person_name') }}" type="text" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary" placeholder="e.g. Rajesh Sharma">
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-bold text-on-surface-variant mb-1">Mobile Number</label>
        <input name="mobile_number" value="{{ old('mobile_number') }}" type="tel" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary" placeholder="e.g. 9876543210">
    </div>
    <div>
        <label class="block text-xs font-bold text-on-surface-variant mb-1">WhatsApp Number</label>
        <input name="whatsapp_number" value="{{ old('whatsapp_number') }}" type="tel" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary" placeholder="e.g. 9876543210">
    </div>
</div>
<div>
    <label class="block text-xs font-bold text-on-surface-variant mb-1">Email ID</label>
    <input name="email" value="{{ old('email') }}" type="email" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary" placeholder="e.g. contact@company.com">
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-bold text-on-surface-variant mb-1">Industry Sector</label>
        <input name="industry" value="{{ old('industry') }}" type="text" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary" placeholder="e.g. Energy">
    </div>
    <div>
        <label class="block text-xs font-bold text-on-surface-variant mb-1">GST Number</label>
        <input name="gst_number" value="{{ old('gst_number') }}" type="text" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary" placeholder="GSTIN">
    </div>
</div>
<div>
    <label class="block text-xs font-bold text-on-surface-variant mb-1">Office / Shipping Address</label>
    <textarea name="address" rows="2" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary" placeholder="Physical location details">{{ old('address') }}</textarea>
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-bold text-on-surface-variant mb-1">Latitude (optional)</label>
        <input name="latitude" value="{{ old('latitude') }}" type="number" step="any" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary" placeholder="19.0760">
    </div>
    <div>
        <label class="block text-xs font-bold text-on-surface-variant mb-1">Longitude (optional)</label>
        <input name="longitude" value="{{ old('longitude') }}" type="number" step="any" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary" placeholder="72.8777">
    </div>
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-bold text-on-surface-variant mb-1">City / Branch *</label>
        <select required name="city" id="tc-register-city" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
            <option value="">Select city</option>
            @foreach($cities as $city)
                <option value="{{ $city->name }}" @selected(old('city', $defaultCity) === $city->name)>{{ $city->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-bold text-on-surface-variant mb-1">Sub City</label>
        <select name="sub_city" id="tc-register-sub-city" data-selected="{{ old('sub_city') }}" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
            <option value="">No sub cities for this city</option>
        </select>
    </div>
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-bold text-on-surface-variant mb-1">Credit Limit (₹) *</label>
        <input required name="credit_limit" type="number" min="0" step="0.01" value="{{ old('credit_limit', 0) }}" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary" placeholder="e.g. 100000">
    </div>
    <div>
        <label class="block text-xs font-bold text-on-surface-variant mb-1">Payment Terms</label>
        <input name="payment_terms" value="{{ old('payment_terms') }}" type="text" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary" placeholder="e.g. 30 Days">
    </div>
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-bold text-on-surface-variant mb-1">Assign Salesperson</label>
        <select name="assigned_exec_id" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
            <option value="">Unassigned</option>
            @foreach($salesExecutives as $exec)
                <option value="{{ $exec->id }}" @selected((string) old('assigned_exec_id') === (string) $exec->id)>{{ $exec->name }} ({{ $exec->branch }})</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-bold text-on-surface-variant mb-1">Assign Telecaller</label>
        <select name="assigned_tc_id" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
            <option value="">Unassigned</option>
            @foreach($telecallers as $tc)
                <option value="{{ $tc->id }}" @selected((string) old('assigned_tc_id', $defaultTcId) === (string) $tc->id)>{{ $tc->name }}</option>
            @endforeach
        </select>
    </div>
</div>
<div>
    <label class="block text-xs font-bold text-on-surface-variant mb-1">Initial Pipeline Stage *</label>
    <select required name="stage" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
        @foreach(\App\Models\Company::STAGES as $stage)
            <option value="{{ $stage }}" @selected(old('stage', 'New') === $stage)>{{ $stage }}</option>
        @endforeach
    </select>
</div>
