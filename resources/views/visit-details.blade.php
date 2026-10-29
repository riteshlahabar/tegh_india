<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Meeting #FV-{{ $visit->id }} | Tegh Cables</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        html, body, input, select, textarea, button, table, th, td,
        .font-sans, .font-mono, .font-body-md, .font-headline-md,
        .font-label-caps, .font-data-tabular {
            font-family: "Hanken Grotesk", system-ui, -apple-system, sans-serif !important;
        }
        .font-label-caps, .text-label-caps { letter-spacing: normal; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #0d2149; border-radius: 3px; }
    </style>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "primary": "#000b28",
                        "primary-container": "#0d2149",
                        "on-primary": "#ffffff",
                        "secondary": "#994700",
                        "secondary-container": "#fb7800",
                        "on-secondary": "#ffffff",
                        "on-secondary-container": "#592600",
                        "secondary-fixed": "#ffdbc8",
                        "surface": "#f7f9fb",
                        "surface-container-low": "#f2f4f6",
                        "surface-container": "#eceef0",
                        "surface-container-highest": "#e0e3e5",
                        "surface-container-lowest": "#ffffff",
                        "on-surface": "#191c1e",
                        "on-surface-variant": "#44464e",
                        "outline": "#75777f",
                        "outline-variant": "#c5c6d0",
                        "error": "#ba1a1a",
                        "background": "#f7f9fb"
                    },
                    "fontFamily": {
                        "sans": ["Hanken Grotesk", "system-ui", "sans-serif"],
                        "mono": ["Hanken Grotesk", "system-ui", "sans-serif"],
                        "body-md": ["Hanken Grotesk", "system-ui", "sans-serif"],
                        "headline-md": ["Hanken Grotesk", "system-ui", "sans-serif"],
                        "label-caps": ["Hanken Grotesk", "system-ui", "sans-serif"],
                        "data-tabular": ["Hanken Grotesk", "system-ui", "sans-serif"]
                    }
                },
            },
        }
    </script>
</head>
<body class="bg-surface text-on-surface font-sans font-body-md overflow-x-hidden">
@php
    $isLive = $visit->status === 'checked_in';
    $photoUrl = $visit->proof_photo_path;
    if ($photoUrl && !str_starts_with($photoUrl, 'http') && !str_starts_with($photoUrl, '/')) {
        $photoUrl = '/storage/' . ltrim($photoUrl, '/');
    }
    $mapUrl = null;
    $embedUrl = null;
    $useGoogleMaps = !empty($mapsEnabled) && !empty($googleMapsApiKey);
    if ($visit->latitude && $visit->longitude) {
        if ($useGoogleMaps) {
            $mapUrl = 'https://www.google.com/maps?q=' . $visit->latitude . ',' . $visit->longitude;
        } else {
            $mapUrl = 'https://www.openstreetmap.org/?mlat=' . $visit->latitude . '&mlon=' . $visit->longitude . '#map=16/' . $visit->latitude . '/' . $visit->longitude;
            $embedUrl = 'https://www.openstreetmap.org/export/embed.html?bbox=' .
                ($visit->longitude - 0.01) . '%2C' . ($visit->latitude - 0.01) . '%2C' .
                ($visit->longitude + 0.01) . '%2C' . ($visit->latitude + 0.01) .
                '&layer=mapnik&marker=' . $visit->latitude . '%2C' . $visit->longitude;
        }
    }
    $durationLabel = '—';
    $durationSub = 'Minutes';
    if ($durationMinutes !== null) {
        if ($durationMinutes < 60) {
            $durationLabel = (string) $durationMinutes;
            $durationSub = 'min' . ($isLive ? ' (ongoing)' : '');
        } else {
            $hours = intdiv($durationMinutes, 60);
            $mins = $durationMinutes % 60;
            $durationLabel = $hours . 'h ' . $mins . 'm';
            $durationSub = $isLive ? 'ongoing' : 'total';
        }
    }
@endphp

<!-- SideNav -->
<aside class="fixed h-screen w-[260px] left-0 top-0 bg-primary border-r border-outline-variant flex flex-col py-6 z-50">
    <div class="px-6 mb-8 border-b border-white/10 pb-6">
        <img alt="Tegh Cables (India) Pvt. Ltd." class="h-10 w-full max-w-[200px] object-contain object-left" src="{{ asset('images/logo.png') }}"/>
        <p class="text-[10px] uppercase tracking-widest text-on-primary/60 mt-2">Unified CRM</p>
    </div>
    <nav class="flex-1 space-y-1 px-2">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-3 text-on-primary/70 hover:text-on-primary hover:bg-white/5 transition-colors">
            <span class="material-symbols-outlined">dashboard</span>
            <span class="text-sm font-medium">Command Centre</span>
        </a>
        <a href="{{ route('admin.dashboard') }}#field" class="flex items-center gap-3 px-4 py-3 border-l-4 border-secondary-container bg-white/10 text-on-primary font-bold">
            <span class="material-symbols-outlined">explore</span>
            <span class="text-sm font-medium">Field Visits</span>
        </a>
    </nav>
    <div class="px-4 pt-4 border-t border-white/10">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex items-center gap-3 text-on-primary/70 hover:text-on-primary w-full px-2 py-2">
                <span class="material-symbols-outlined">logout</span>
                <span class="text-sm font-medium">Logout</span>
            </button>
        </form>
    </div>
</aside>

<header class="fixed top-0 right-0 left-[260px] h-16 bg-white border-b border-outline-variant flex justify-between items-center px-8 z-40">
    <div>
        <h2 class="text-xl font-bold text-primary">Meeting Details</h2>
        <p class="text-xs text-on-surface-variant uppercase tracking-wider font-label-caps">Audit ID #FV-{{ $visit->id }}</p>
    </div>
    <a href="{{ route('admin.dashboard') }}#field"
       class="inline-flex items-center gap-2 px-4 py-2 rounded bg-primary text-on-primary text-sm font-semibold hover:bg-primary-container transition-all">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Back to Field Visits
    </a>
</header>

<main class="ml-[260px] mt-16 p-8 space-y-6 min-h-screen">
    <!-- Visit Summary -->
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="sm:col-span-2 lg:col-span-2 bg-white p-6 border border-outline-variant rounded shadow-sm flex justify-between items-start">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h3 class="text-2xl font-bold text-primary">{{ $visit->company->name ?? 'Unknown Company' }}</h3>
                    <span class="px-2 py-1 text-[10px] font-bold uppercase rounded {{ $isLive ? 'bg-secondary-fixed text-on-secondary-container' : 'bg-green-100 text-green-700' }}">
                        {{ $isLive ? 'In Progress' : 'Completed' }}
                    </span>
                </div>
                <p class="mt-1 text-sm text-on-surface-variant">
                    Field meeting by <span class="font-semibold text-primary">{{ $visit->executive->name ?? 'Unknown' }}</span>
                    @if($visit->company->city)
                        • {{ $visit->company->city }}@if($visit->company->sub_city) / {{ $visit->company->sub_city }}@endif
                    @endif
                </p>
            </div>
        </div>
        <div class="bg-white p-4 border border-outline-variant rounded shadow-sm">
            <p class="text-[10px] uppercase font-bold text-on-surface-variant mb-1 font-label-caps">Check-in</p>
            <p class="text-base font-bold text-primary font-data-tabular">{{ $visit->checked_in_at ? $visit->checked_in_at->format('d M Y') : '—' }}</p>
            <p class="text-sm font-medium text-on-surface-variant font-data-tabular">{{ $visit->checked_in_at ? $visit->checked_in_at->format('h:i A') : '' }}</p>
        </div>
        <div class="bg-white p-4 border border-outline-variant rounded shadow-sm">
            <p class="text-[10px] uppercase font-bold text-on-surface-variant mb-1 font-label-caps">Check-out</p>
            <p class="text-base font-bold text-primary font-data-tabular">
                {{ $visit->checked_out_at ? $visit->checked_out_at->format('d M Y') : ($isLive ? 'Live' : '—') }}
            </p>
            <p class="text-sm font-medium text-on-surface-variant font-data-tabular">
                {{ $visit->checked_out_at ? $visit->checked_out_at->format('h:i A') : '' }}
            </p>
        </div>
        <div class="bg-white p-4 border border-outline-variant rounded shadow-sm">
            <p class="text-[10px] uppercase font-bold text-on-surface-variant mb-1 font-label-caps">Duration</p>
            <p class="text-base font-bold text-primary font-data-tabular">{{ $durationLabel }}</p>
            <p class="text-sm font-medium text-on-surface-variant">{{ $durationSub }}</p>
        </div>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: notes, photo, map -->
        <div class="lg:col-span-2 space-y-6">
            <section class="bg-white border border-outline-variant rounded overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-outline-variant bg-surface flex items-center">
                    <span class="material-symbols-outlined text-secondary text-[20px] mr-2">edit_note</span>
                    <h4 class="text-sm font-bold uppercase tracking-wide">Meeting Notes</h4>
                </div>
                <div class="p-6">
                    @if($visit->notes)
                        <div class="bg-surface/50 p-6 rounded border border-dashed border-outline-variant min-h-[120px]">
                            <p class="text-sm text-on-surface leading-relaxed whitespace-pre-wrap">{{ $visit->notes }}</p>
                        </div>
                    @else
                        <div class="bg-surface/50 p-6 rounded border border-dashed border-outline-variant min-h-[120px]">
                            <p class="text-sm text-on-surface-variant italic">
                                {{ $isLive ? 'Meeting is still in progress — notes will appear after checkout.' : 'No notes were submitted for this meeting.' }}
                            </p>
                        </div>
                    @endif
                </div>
            </section>

            <section class="bg-white border border-outline-variant rounded overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-outline-variant bg-surface flex items-center">
                    <span class="material-symbols-outlined text-secondary text-[20px] mr-2">sell</span>
                    <h4 class="text-sm font-bold uppercase tracking-wide">Discount by Salesperson</h4>
                </div>
                <div class="p-6">
                    @if($visit->discount_amount || $visit->discount_note)
                        <div class="bg-surface/50 p-6 rounded border border-dashed border-outline-variant space-y-3">
                            @if($visit->discount_amount)
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant mb-1">Requested discount</p>
                                    <p class="text-2xl font-bold text-secondary font-data-tabular">₹{{ \App\Support\IndianNumber::format($visit->discount_amount, 0) }}</p>
                                </div>
                            @endif
                            @if($visit->discount_note)
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant mb-1">Note</p>
                                    <p class="text-sm text-on-surface leading-relaxed whitespace-pre-wrap">{{ $visit->discount_note }}</p>
                                </div>
                            @endif
                            <p class="text-xs text-on-surface-variant">Submitted by {{ $visit->executive->name ?? 'salesperson' }} at checkout.</p>
                        </div>
                    @else
                        <div class="bg-surface/50 p-6 rounded border border-dashed border-outline-variant min-h-[80px]">
                            <p class="text-sm text-on-surface-variant italic">
                                {{ $isLive ? 'No discount request yet — salesperson can add it when checking out.' : 'No discount was requested for this meeting.' }}
                            </p>
                        </div>
                    @endif
                </div>
            </section>

            <section class="bg-white border border-outline-variant rounded overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-outline-variant bg-surface flex items-center">
                    <span class="material-symbols-outlined text-secondary text-[20px] mr-2">photo_camera</span>
                    <h4 class="text-sm font-bold uppercase tracking-wide">Proof Photo</h4>
                </div>
                <div class="p-6">
                    @if($photoUrl)
                        <div class="relative group bg-surface rounded overflow-hidden flex justify-center">
                            <img alt="Proof photo of site visit" class="max-h-[400px] object-contain" src="{{ $photoUrl }}"/>
                            <div class="absolute bottom-4 left-4">
                                <a href="{{ $photoUrl }}" target="_blank" rel="noopener"
                                   class="bg-white/90 backdrop-blur-sm px-3 py-1.5 text-[10px] font-bold uppercase flex items-center gap-1 rounded shadow-sm border border-outline-variant hover:bg-white transition-colors">
                                    <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                                    Open full image
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="rounded border border-dashed border-outline-variant bg-surface p-10 text-center">
                            <span class="material-symbols-outlined text-4xl text-on-surface-variant/40">image_not_supported</span>
                            <p class="mt-2 text-sm text-on-surface-variant">No proof photo uploaded.</p>
                        </div>
                    @endif
                </div>
            </section>

            <section class="bg-white border border-outline-variant rounded overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-outline-variant bg-surface flex justify-between items-center">
                    <div class="flex items-center">
                        <span class="material-symbols-outlined text-secondary text-[20px] mr-2">map</span>
                        <h4 class="text-sm font-bold uppercase tracking-wide">Check-in Location</h4>
                    </div>
                    @if($mapUrl)
                        <a class="text-[10px] font-bold uppercase text-secondary flex items-center gap-1 hover:underline" href="{{ $mapUrl }}" target="_blank" rel="noopener">
                            Open in maps
                            <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                        </a>
                    @endif
                </div>
                @if($useGoogleMaps && $visit->latitude && $visit->longitude)
                    <div id="visit-google-map" class="h-[300px] relative bg-surface-container"></div>
                    <script>
                        function initVisitMap() {
                            const pos = { lat: {{ (float) $visit->latitude }}, lng: {{ (float) $visit->longitude }} };
                            const map = new google.maps.Map(document.getElementById('visit-google-map'), {
                                center: pos,
                                zoom: 16,
                                mapTypeControl: false,
                                streetViewControl: false,
                            });
                            new google.maps.Marker({ position: pos, map, title: 'Check-in location' });
                        }
                    </script>
                    <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsApiKey }}&callback=initVisitMap"></script>
                @elseif(!empty($embedUrl))
                    <div class="h-[300px] relative bg-surface-container">
                        <iframe title="Visit map" class="w-full h-full border-0" src="{{ $embedUrl }}" loading="lazy"></iframe>
                    </div>
                @else
                    <div class="h-[200px] bg-surface flex flex-col items-center justify-center text-on-surface-variant">
                        <span class="material-symbols-outlined text-4xl opacity-40">location_off</span>
                        <p class="mt-2 text-sm">No check-in coordinates recorded.</p>
                    </div>
                @endif
            </section>
        </div>

        <!-- Right: info panels -->
        <div class="space-y-6">
            <section class="bg-white border border-outline-variant rounded shadow-sm overflow-hidden">
                <div class="p-6">
                    <h4 class="text-xs font-bold uppercase tracking-widest text-on-surface-variant mb-6 pb-2 border-b border-outline-variant font-label-caps">Visit Info</h4>
                    <dl class="space-y-4">
                        <div class="flex justify-between text-sm gap-4">
                            <dt class="text-on-surface-variant font-medium">Visit ID</dt>
                            <dd class="font-bold text-primary font-data-tabular">#FV-{{ $visit->id }}</dd>
                        </div>
                        <div class="flex justify-between text-sm gap-4">
                            <dt class="text-on-surface-variant font-medium">Status</dt>
                            <dd class="font-bold {{ $isLive ? 'text-secondary' : 'text-green-600' }}">{{ ucfirst(str_replace('_', ' ', $visit->status)) }}</dd>
                        </div>
                        <div class="flex justify-between text-sm gap-4">
                            <dt class="text-on-surface-variant font-medium">Salesperson</dt>
                            <dd class="font-bold text-primary text-right">{{ $visit->executive->name ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between text-sm gap-4">
                            <dt class="text-on-surface-variant font-medium">Email</dt>
                            <dd class="text-primary text-right text-xs break-all">{{ $visit->executive->email ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between text-sm gap-4">
                            <dt class="text-on-surface-variant font-medium">Latitude</dt>
                            <dd class="font-data-tabular text-primary">{{ $visit->latitude !== null ? number_format((float) $visit->latitude, 6) : '—' }}</dd>
                        </div>
                        <div class="flex justify-between text-sm gap-4">
                            <dt class="text-on-surface-variant font-medium">Longitude</dt>
                            <dd class="font-data-tabular text-primary">{{ $visit->longitude !== null ? number_format((float) $visit->longitude, 6) : '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </section>

            <section class="bg-white border border-outline-variant rounded shadow-sm overflow-hidden">
                <div class="p-6">
                    <h4 class="text-xs font-bold uppercase tracking-widest text-on-surface-variant mb-6 pb-2 border-b border-outline-variant font-label-caps">Customer</h4>
                    <div class="mb-6">
                        <p class="text-lg font-bold text-primary">{{ $visit->company->name ?? '—' }}</p>
                        <p class="text-xs font-medium text-on-surface-variant uppercase tracking-tight">{{ $visit->company->industry ?? 'Industry not set' }}</p>
                        @if($visit->company->showsEnquiryProduct())
                            <p class="text-sm text-primary mt-2">
                                <span class="text-[10px] uppercase font-bold text-on-surface-variant font-label-caps">Enquiry Product:</span>
                                {{ $visit->company->enquiryProductsLabel() }}
                            </p>
                        @endif
                        @if($visit->products->isNotEmpty())
                            <p class="text-sm text-primary mt-2">
                                <span class="text-[10px] uppercase font-bold text-on-surface-variant font-label-caps">Visit Products:</span>
                                {{ $visit->products->pluck('name')->implode(', ') }}
                            </p>
                        @endif
                    </div>
                    <div class="space-y-5">
                        @if($visit->company->contact_person_name || $visit->company->mobile_number || $visit->company->whatsapp_number || $visit->company->email)
                        <div class="space-y-3 pb-4 border-b border-dashed border-outline-variant">
                            <p class="text-[10px] uppercase font-bold text-on-surface-variant font-label-caps">Contact</p>
                            @if($visit->company->contact_person_name)
                                <div class="flex justify-between items-center gap-4">
                                    <p class="text-[10px] uppercase font-bold text-on-surface-variant font-label-caps">Person</p>
                                    <p class="text-sm font-medium text-primary text-right">{{ $visit->company->contact_person_name }}</p>
                                </div>
                            @endif
                            @if($visit->company->mobile_number)
                                <div class="flex justify-between items-center gap-4">
                                    <p class="text-[10px] uppercase font-bold text-on-surface-variant font-label-caps">Mobile</p>
                                    <p class="text-sm font-medium text-primary text-right">{{ $visit->company->mobile_number }}</p>
                                </div>
                            @endif
                            @if($visit->company->whatsapp_number)
                                <div class="flex justify-between items-center gap-4">
                                    <p class="text-[10px] uppercase font-bold text-on-surface-variant font-label-caps">WhatsApp</p>
                                    <p class="text-sm font-medium text-primary text-right">{{ $visit->company->whatsapp_number }}</p>
                                </div>
                            @endif
                            @if($visit->company->email)
                                <div class="flex justify-between items-center gap-4">
                                    <p class="text-[10px] uppercase font-bold text-on-surface-variant font-label-caps">Email</p>
                                    <p class="text-sm font-medium text-primary text-right break-all">{{ $visit->company->email }}</p>
                                </div>
                            @endif
                        </div>
                        @endif
                        <div>
                            <p class="text-[10px] uppercase font-bold text-on-surface-variant mb-1 font-label-caps">Address</p>
                            <p class="text-sm text-primary leading-relaxed">{{ $visit->company->address ?? 'No address logged' }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-[10px] uppercase font-bold text-on-surface-variant mb-1 font-label-caps">City</p>
                                <p class="text-sm font-bold text-primary">{{ $visit->company->city ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] uppercase font-bold text-on-surface-variant mb-1 font-label-caps">Sub City</p>
                                <p class="text-sm font-bold text-primary">{{ $visit->company->sub_city ?? '—' }}</p>
                            </div>
                        </div>
                        <div>
                            <p class="text-[10px] uppercase font-bold text-on-surface-variant mb-1 font-label-caps">Stage</p>
                            <span class="px-2 py-0.5 bg-primary text-on-primary text-[9px] font-bold rounded uppercase">{{ $visit->company->stage ?? '—' }}</span>
                        </div>
                        <div class="pt-4 border-t border-dashed border-outline-variant space-y-4">
                            <div class="flex justify-between items-center gap-4">
                                <p class="text-[10px] uppercase font-bold text-on-surface-variant font-label-caps">GST</p>
                                <p class="text-sm font-medium text-primary font-data-tabular">{{ $visit->company->gst_number ?? '—' }}</p>
                            </div>
                            <div class="flex justify-between items-center gap-4">
                                <p class="text-[10px] uppercase font-bold text-on-surface-variant font-label-caps">Outstanding</p>
                                <p class="text-sm font-bold text-secondary font-data-tabular">₹ {{ \App\Support\IndianNumber::format($visit->company->outstanding_amount ?? 0, 0) }}</p>
                            </div>
                        </div>
                        <div class="pt-4 border-t border-dashed border-outline-variant space-y-4">
                            <div class="flex justify-between items-center gap-4">
                                <p class="text-[10px] uppercase font-bold text-on-surface-variant font-label-caps">Added By</p>
                                <p class="text-sm font-medium text-primary text-right">{{ $visit->company->creator->name ?? '—' }}</p>
                            </div>
                            <div class="flex justify-between items-center gap-4">
                                <p class="text-[10px] uppercase font-bold text-on-surface-variant font-label-caps">Assigned Sales</p>
                                <p class="text-sm font-medium text-primary text-right">{{ $visit->company->executive->name ?? 'Unassigned' }}</p>
                            </div>
                            <div class="flex justify-between items-center gap-4">
                                <p class="text-[10px] uppercase font-bold text-on-surface-variant font-label-caps">Telecaller</p>
                                <p class="text-sm font-medium text-on-surface-variant text-right {{ ($visit->company->telecaller->name ?? null) ? '' : 'italic' }}">
                                    {{ $visit->company->telecaller->name ?? 'Unassigned' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            @if($companyVisits->isNotEmpty())
            <section class="bg-white border border-outline-variant rounded shadow-sm overflow-hidden">
                <div class="p-6">
                    <h4 class="text-xs font-bold uppercase tracking-widest text-on-surface-variant mb-4 pb-2 border-b border-outline-variant font-label-caps">Other Visits</h4>
                    <ul class="space-y-2">
                        @foreach($companyVisits as $other)
                        <li>
                            <a href="{{ route('admin.visits.show', $other->id) }}"
                               class="flex items-center justify-between gap-2 p-3 rounded hover:bg-surface-container-low border border-transparent hover:border-outline-variant transition-all">
                                <div>
                                    <p class="text-xs font-bold text-primary">#FV-{{ $other->id }} · {{ $other->executive->name ?? '—' }}</p>
                                    <p class="text-[11px] text-on-surface-variant font-data-tabular">
                                        {{ $other->checked_in_at ? $other->checked_in_at->format('d M Y, h:i A') : '—' }}
                                    </p>
                                </div>
                                <span class="text-[9px] font-bold uppercase px-2 py-0.5 rounded {{ $other->status === 'checked_in' ? 'bg-secondary-fixed text-on-secondary-container' : 'bg-green-100 text-green-800' }}">
                                    {{ $other->status === 'checked_in' ? 'Live' : 'Done' }}
                                </span>
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </section>
            @endif
        </div>
    </div>
</main>
</body>
</html>
