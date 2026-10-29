    <!-- ================= VIEW: PRODUCTS ================= -->
    <div id="view-products" class="space-y-xl">
        <section class="flex flex-col sm:flex-row sm:justify-between sm:items-end gap-4">
            <div>
                <h2 class="font-headline-md text-headline-md text-primary font-bold">Products</h2>
                <p class="text-on-surface-variant font-body-sm">Catalog for salespeople to multi-select on leads and visits.</p>
            </div>
            <div class="flex flex-col xs:flex-row flex-wrap gap-2 w-full sm:w-auto">
                <button type="button" onclick="toggleModal('modal-import-products')" class="bg-surface-container-high text-primary border border-outline-variant px-5 py-3 rounded-lg font-title-sm flex items-center justify-center gap-2 hover:bg-surface-container-highest transition-all w-full sm:w-auto">
                    <span class="material-symbols-outlined">upload_file</span>
                    Upload Sheet
                </button>
                <button type="button" onclick="toggleModal('modal-add-product')" class="bg-primary text-on-primary hover:bg-secondary px-6 py-3 rounded-lg font-title-sm flex items-center justify-center gap-2 shadow-md w-full sm:w-auto">
                    <span class="material-symbols-outlined">add</span>
                    Add Product
                </button>
            </div>
        </section>

        <section class="bg-white border border-outline-variant/30 rounded-2xl p-5 shadow-sm">
            <h3 class="font-label-caps text-label-caps text-primary font-bold mb-2">BULK UPLOAD</h3>
            <p class="text-sm text-on-surface-variant mb-4">Upload <strong>Standardized Product List</strong> (.xlsx / .xls / .csv). Each row is saved with all columns (Product Name, Standardized Name, Category, HSN, Size, Core, Spec, Unit, Colour, etc.).</p>
            <form action="{{ route('admin.products.import') }}" method="POST" enctype="multipart/form-data" class="flex flex-col sm:flex-row gap-3 sm:items-end">
                @csrf
                <div class="flex-1">
                    <label class="block text-xs font-bold text-on-surface-variant mb-1">Excel / CSV file</label>
                    <input required type="file" name="sheet" accept=".xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv" class="w-full text-sm border border-outline-variant/50 rounded-lg file:mr-3 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:bg-primary file:text-on-primary file:font-bold file:text-xs">
                </div>
                <button type="submit" class="bg-secondary text-on-secondary px-6 py-2.5 rounded-lg font-bold flex items-center justify-center gap-2 hover:opacity-90 transition-all">
                    <span class="material-symbols-outlined text-[20px]">cloud_upload</span>
                    Import Products
                </button>
            </form>
        </section>

        <section class="bg-white border border-outline-variant/30 rounded-2xl overflow-hidden shadow-sm">
            <div class="p-5 border-b border-outline-variant/30 bg-surface-container-lowest space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="font-label-caps text-label-caps text-primary font-bold">PRODUCT CATALOG</h3>
                    <span class="text-xs font-semibold text-on-surface-variant bg-surface-container-low px-3 py-1.5 rounded-full">
                        {{ $products->total() }} {{ \Illuminate\Support\Str::plural('product', $products->total()) }}
                        @if(($search ?? '') !== '')
                            · matching “{{ $search }}”
                        @endif
                    </span>
                </div>
                <form method="GET" action="{{ route('admin.products') }}" class="grid grid-cols-1 lg:grid-cols-[1fr_auto_auto] gap-3 items-stretch lg:items-center">
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">search</span>
                        <input
                            type="search"
                            name="q"
                            value="{{ $search ?? '' }}"
                            placeholder="Search products by name, code, category, HSN, size, colour…"
                            class="w-full pl-10 pr-4 py-2.5 bg-white border border-outline-variant/50 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none"
                        >
                    </div>
                    <div class="flex items-center gap-2">
                        <label for="products-per-page" class="text-xs font-bold text-on-surface-variant whitespace-nowrap">Per page</label>
                        <select
                            id="products-per-page"
                            name="per_page"
                            onchange="this.form.submit()"
                            class="border border-outline-variant/50 rounded-lg text-sm py-2.5 px-3 bg-white focus:ring-2 focus:ring-primary focus:border-primary"
                        >
                            @foreach(($perPageOptions ?? [25, 50, 100, 200]) as $option)
                                <option value="{{ $option }}" @selected(($perPage ?? 50) == $option)>{{ $option }}</option>
                            @endforeach
                            <option value="all" @selected(($perPage ?? 50) === 'all')>All</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 lg:flex-none px-5 py-2.5 rounded-lg text-sm font-bold bg-primary text-on-primary hover:bg-secondary transition-all">
                            Search
                        </button>
                        @if(($search ?? '') !== '')
                            <a href="{{ route('admin.products', array_filter(['per_page' => (($perPage ?? 50) == 50) ? null : $perPage])) }}" class="px-4 py-2.5 rounded-lg text-sm font-bold border border-outline-variant/50 text-on-surface-variant hover:bg-surface-container-low transition-all">
                                Clear
                            </a>
                        @endif
                    </div>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[1100px]">
                    <thead class="bg-surface-container-low border-b border-outline-variant/30">
                        <tr>
                            <th class="px-4 py-3 font-label-caps text-label-caps text-on-surface-variant">Product Name</th>
                            <th class="px-4 py-3 font-label-caps text-label-caps text-on-surface-variant">Standardized Name</th>
                            <th class="px-4 py-3 font-label-caps text-label-caps text-on-surface-variant">Category</th>
                            <th class="px-4 py-3 font-label-caps text-label-caps text-on-surface-variant">HSN</th>
                            <th class="px-4 py-3 font-label-caps text-label-caps text-on-surface-variant">Size</th>
                            <th class="px-4 py-3 font-label-caps text-label-caps text-on-surface-variant">Core</th>
                            <th class="px-4 py-3 font-label-caps text-label-caps text-on-surface-variant">Spec</th>
                            <th class="px-4 py-3 font-label-caps text-label-caps text-on-surface-variant">Unit</th>
                            <th class="px-4 py-3 font-label-caps text-label-caps text-on-surface-variant">Colour</th>
                            <th class="px-4 py-3 font-label-caps text-label-caps text-on-surface-variant">Status</th>
                            <th class="px-4 py-3 font-label-caps text-label-caps text-on-surface-variant text-right sticky right-0 z-20 bg-surface-container-low min-w-[200px] shadow-[-6px_0_8px_-6px_rgba(0,0,0,0.12)]">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20">
                        @forelse($products as $product)
                        <tr class="hover:bg-primary/5 transition-all">
                            <td class="px-4 py-3 text-xs font-semibold text-primary max-w-[180px]">{{ $product->product_code ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm font-bold text-primary max-w-[220px]">
                                {{ $product->name }}
                                @if($product->description)
                                    <p class="text-[11px] font-normal text-on-surface-variant mt-0.5">{{ \Illuminate\Support\Str::limit($product->description, 60) }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-on-surface-variant">{{ $product->category ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs font-data-tabular">{{ $product->hsn_code ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs">{{ $product->size_sqmm ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs">{{ $product->core ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs">{{ $product->spec ?? $product->usage_unit ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs">{{ $product->unit ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs">{{ $product->colour ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @if($product->is_active)
                                    <span class="px-2.5 py-1 rounded text-[10px] font-bold uppercase bg-green-100 text-green-800">Active</span>
                                @else
                                    <span class="px-2.5 py-1 rounded text-[10px] font-bold uppercase bg-slate-100 text-slate-600">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right sticky right-0 z-10 bg-white group-hover:bg-primary/5 min-w-[200px] shadow-[-6px_0_8px_-6px_rgba(0,0,0,0.12)]">
                                <div class="inline-flex items-center gap-2 flex-wrap justify-end">
                                    <button
                                        type="button"
                                        class="js-edit-product px-3 py-1.5 rounded-lg text-xs font-bold bg-primary text-on-primary hover:bg-secondary transition-all"
                                        data-product="{{ json_encode([
                                            'id' => $product->id,
                                            'name' => $product->name,
                                            'product_code' => $product->product_code,
                                            'usage_unit' => $product->usage_unit,
                                            'description' => $product->description,
                                            'category' => $product->category,
                                            'hsn_code' => $product->hsn_code,
                                            'mts' => $product->mts,
                                            'colour' => $product->colour,
                                            'size_sqmm' => $product->size_sqmm,
                                            'core' => $product->core,
                                            'spec' => $product->spec,
                                            'unit' => $product->unit,
                                            'des1' => $product->des1,
                                            'des2' => $product->des2,
                                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
                                    >
                                        Edit
                                    </button>
                                    <form action="{{ route('admin.products.toggle', $product->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-bold border border-outline-variant/40 hover:bg-surface-container-low transition-all">
                                            {{ $product->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Delete this product?');">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-red-50 text-red-700 hover:bg-red-100 transition-all">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="py-8 text-center text-on-surface-variant/60 text-xs">
                                @if(($search ?? '') !== '')
                                    No products match “{{ $search }}”.
                                    <a href="{{ route('admin.products') }}" class="text-primary font-semibold underline ml-1">Clear search</a>
                                @else
                                    No products yet. Upload the product sheet or add products manually.
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($products->total() > 0)
            <div class="p-5 border-t border-outline-variant/30 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 bg-surface-container-lowest">
                <p class="text-xs text-on-surface-variant">
                    Showing {{ $products->firstItem() }}–{{ $products->lastItem() }} of {{ $products->total() }}
                    · Page {{ $products->currentPage() }} of {{ $products->lastPage() }}
                </p>
                <div class="flex flex-wrap items-center gap-3">
                    <form method="GET" action="{{ route('admin.products') }}" class="flex items-center gap-2">
                        @if(($search ?? '') !== '')
                            <input type="hidden" name="q" value="{{ $search }}">
                        @endif
                        <label for="products-per-page-footer" class="text-xs font-bold text-on-surface-variant whitespace-nowrap">Rows</label>
                        <select
                            id="products-per-page-footer"
                            name="per_page"
                            onchange="this.form.submit()"
                            class="border border-outline-variant/50 rounded-lg text-xs py-1.5 px-2 bg-white focus:ring-2 focus:ring-primary focus:border-primary"
                        >
                            @foreach(($perPageOptions ?? [25, 50, 100, 200]) as $option)
                                <option value="{{ $option }}" @selected(($perPage ?? 50) == $option)>{{ $option }} / page</option>
                            @endforeach
                            <option value="all" @selected(($perPage ?? 50) === 'all')>All</option>
                        </select>
                    </form>
                    @if($products->hasPages())
                    <div class="flex flex-wrap items-center gap-1">
                        @if($products->onFirstPage())
                            <span class="px-3 py-1.5 rounded-lg text-xs font-semibold text-on-surface-variant/40 border border-outline-variant/20">Prev</span>
                        @else
                            <a href="{{ $products->previousPageUrl() }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-primary border border-outline-variant/40 hover:bg-white transition-all">Prev</a>
                        @endif

                        @foreach($products->getUrlRange(max(1, $products->currentPage() - 2), min($products->lastPage(), $products->currentPage() + 2)) as $page => $url)
                            @if($page == $products->currentPage())
                                <span class="px-3 py-1.5 rounded-lg text-xs font-bold bg-primary text-on-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-primary border border-outline-variant/40 hover:bg-white transition-all">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if($products->hasMorePages())
                            <a href="{{ $products->nextPageUrl() }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-primary border border-outline-variant/40 hover:bg-white transition-all">Next</a>
                        @else
                            <span class="px-3 py-1.5 rounded-lg text-xs font-semibold text-on-surface-variant/40 border border-outline-variant/20">Next</span>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </section>
    </div>
