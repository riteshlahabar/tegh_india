@php
    $idPrefix = isset($prefix) && $prefix ? $prefix.'-' : '';
@endphp
<div>
    <label class="block text-xs font-bold text-on-surface-variant mb-1">Standardized Product Name *</label>
    <input required id="{{ $idPrefix }}product-name" name="name" type="text" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary" placeholder="e.g. 0.5 Sq.mm x 8 Core Screened Flexible Cable FRLS">
</div>
<div>
    <label class="block text-xs font-bold text-on-surface-variant mb-1">Product Name / Code</label>
    <input id="{{ $idPrefix }}product-code" name="product_code" type="text" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary" placeholder="e.g. 0.5(16/.2) X 8C SCREEN FLEXI FRLS P">
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    <div>
        <label class="block text-xs font-bold text-on-surface-variant mb-1">Category</label>
        <input id="{{ $idPrefix }}product-category" name="category" type="text" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary" placeholder="e.g. CU.FLEXIBLE">
    </div>
    <div>
        <label class="block text-xs font-bold text-on-surface-variant mb-1">HSN Code</label>
        <input id="{{ $idPrefix }}product-hsn" name="hsn_code" type="text" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary" placeholder="e.g. 85444920">
    </div>
</div>
<div>
    <label class="block text-xs font-bold text-on-surface-variant mb-1">Description</label>
    <textarea id="{{ $idPrefix }}product-description" name="description" rows="2" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary" placeholder="Description"></textarea>
</div>
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
    <div>
        <label class="block text-xs font-bold text-on-surface-variant mb-1">Size (Sq.mm)</label>
        <input id="{{ $idPrefix }}product-size" name="size_sqmm" type="text" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
    </div>
    <div>
        <label class="block text-xs font-bold text-on-surface-variant mb-1">Core</label>
        <input id="{{ $idPrefix }}product-core" name="core" type="text" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
    </div>
    <div>
        <label class="block text-xs font-bold text-on-surface-variant mb-1">Spec</label>
        <input id="{{ $idPrefix }}product-spec" name="spec" type="text" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
    </div>
    <div>
        <label class="block text-xs font-bold text-on-surface-variant mb-1">Unit</label>
        <input id="{{ $idPrefix }}product-unit" name="unit" type="text" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary" placeholder="Metre">
    </div>
</div>
<div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
    <div>
        <label class="block text-xs font-bold text-on-surface-variant mb-1">Usage Unit</label>
        <input id="{{ $idPrefix }}product-usage-unit" name="usage_unit" type="text" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
    </div>
    <div>
        <label class="block text-xs font-bold text-on-surface-variant mb-1">Colour</label>
        <input id="{{ $idPrefix }}product-colour" name="colour" type="text" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
    </div>
    <div>
        <label class="block text-xs font-bold text-on-surface-variant mb-1">Mts.</label>
        <input id="{{ $idPrefix }}product-mts" name="mts" type="text" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
    </div>
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    <div>
        <label class="block text-xs font-bold text-on-surface-variant mb-1">Des1</label>
        <input id="{{ $idPrefix }}product-des1" name="des1" type="text" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
    </div>
    <div>
        <label class="block text-xs font-bold text-on-surface-variant mb-1">Des2</label>
        <input id="{{ $idPrefix }}product-des2" name="des2" type="text" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
    </div>
</div>
