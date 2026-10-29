{{-- ================= VIEW: LIVE MAP ================= --}}
<div id="view-maps" class="space-y-xl">
    <section class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <span class="material-symbols-outlined text-secondary text-[24px]">map</span>
                <h2 class="font-headline-md text-headline-md text-primary font-bold">Live Field Map</h2>
            </div>
            <p class="text-on-surface-variant font-body-sm font-medium">
                Track sales executives and customer locations on Google Maps.
            </p>
        </div>
        <form action="{{ route('admin.maps.settings') }}" method="POST" class="flex items-center gap-3 bg-white border border-outline-variant/30 rounded-xl px-4 py-3 shadow-sm">
            @csrf
            <div class="flex flex-col">
                <span class="text-[10px] font-bold uppercase tracking-wide text-on-surface-variant">Google Maps</span>
                <span class="text-xs text-on-surface-variant">{{ $mapsEnabled ? 'Currently ON' : 'Currently OFF' }}</span>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="hidden" name="google_maps_enabled" value="0">
                <input type="checkbox" name="google_maps_enabled" value="1" class="sr-only peer" {{ $mapsEnabled ? 'checked' : '' }} onchange="this.form.submit()">
                <div class="w-11 h-6 bg-surface-container-highest peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-secondary"></div>
            </label>
        </form>
    </section>

    @if(!$mapsEnabled)
        <section class="bg-white border border-dashed border-outline-variant rounded-2xl p-10 text-center shadow-sm">
            <span class="material-symbols-outlined text-5xl text-on-surface-variant/40">location_off</span>
            <h3 class="mt-3 font-headline-md text-primary font-bold">Google Maps is turned off</h3>
            <p class="mt-2 text-sm text-on-surface-variant max-w-md mx-auto">
                Enable the toggle above to show live salesman and customer pins, and to allow the sales app to share GPS location.
            </p>
        </section>
    @else
        <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white border border-outline-variant/30 rounded-xl p-4 shadow-sm">
                <span class="font-label-caps text-label-caps text-on-surface-variant/70 mb-2 block">SALESMEN ONLINE</span>
                <span class="text-3xl font-bold text-secondary" id="stat-sales-online">{{ $salesmenOnMap->filter(fn($s) => $s->last_location_at && $s->last_location_at->gt(now()->subMinutes(15)))->count() }}</span>
                <span class="text-xs text-on-surface-variant ml-1">/ {{ $salesmen->count() }} total</span>
            </div>
            <div class="bg-white border border-outline-variant/30 rounded-xl p-4 shadow-sm">
                <span class="font-label-caps text-label-caps text-on-surface-variant/70 mb-2 block">CUSTOMERS ON MAP</span>
                <span class="text-3xl font-bold text-primary" id="stat-customers">{{ $customersOnMap->count() }}</span>
                <span class="text-xs text-on-surface-variant ml-1">with coordinates</span>
            </div>
            <div class="bg-white border border-outline-variant/30 rounded-xl p-4 shadow-sm">
                <span class="font-label-caps text-label-caps text-on-surface-variant/70 mb-2 block">ADDRESSES WAITING</span>
                <span class="text-3xl font-bold text-secondary" id="stat-missing-pins">{{ $missingPinCount }}</span>
                <span class="text-xs text-on-surface-variant ml-1">from sheet, no pin yet</span>
            </div>
        </section>

        @if($missingPinCount > 0)
        <section id="missing-pins-banner" class="bg-secondary-fixed/50 border border-secondary/20 rounded-2xl p-5 shadow-sm flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="flex-1">
                <h3 class="font-bold text-primary">Sheet addresses are not on the map yet</h3>
                <p class="text-sm text-on-surface-variant mt-1">
                    {{ $missingPinCount }} imported customer{{ $missingPinCount === 1 ? '' : 's' }} have an address but no latitude/longitude, so Google Maps cannot drop a pin. Place them now — this uses the same Maps key already on this page.
                </p>
                <p id="geocode-progress" class="text-xs font-semibold text-secondary mt-2 hidden"></p>
            </div>
            <button type="button" id="btn-place-addresses" onclick="placeSheetAddressesOnMap()" class="shrink-0 bg-primary text-on-primary px-4 py-3 rounded-lg text-sm font-bold shadow-md hover:bg-secondary transition-all">
                Place addresses on map
            </button>
        </section>
        @endif

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 space-y-4">
                <section class="bg-white border border-outline-variant/30 rounded-2xl overflow-hidden shadow-sm">
                    <div class="px-5 py-3 border-b border-outline-variant/30 flex flex-wrap items-center justify-between gap-2 bg-surface-container-lowest">
                        <div class="flex items-center gap-4 text-xs font-semibold">
                            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-secondary animate-pulse"></span> Salesman</span>
                            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-primary"></span> Customer</span>
                        </div>
                        <button type="button" onclick="fitMapBounds()" class="text-xs font-bold uppercase text-secondary hover:underline flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">fit_screen</span> Fit all
                        </button>
                    </div>
                    <div id="admin-live-map" class="w-full h-[280px] sm:h-[400px] lg:h-[520px] bg-surface-container"></div>
                </section>

                @if($missingPinCount > 0)
                <section id="waiting-address-list" class="bg-white border border-outline-variant/30 rounded-2xl overflow-hidden shadow-sm">
                    <div class="px-5 py-3 border-b border-outline-variant/30 bg-surface-container-lowest">
                        <h3 class="font-label-caps text-label-caps text-primary font-bold">Imported addresses waiting for a pin</h3>
                    </div>
                    <ul class="divide-y divide-outline-variant/20 max-h-[220px] overflow-y-auto">
                        @foreach($customersMissingPins->take(12) as $waiting)
                            <li class="px-5 py-3 text-sm">
                                <p class="font-semibold text-primary">{{ $waiting->name }}</p>
                                <p class="text-[11px] text-on-surface-variant mt-0.5">
                                    {{ collect([$waiting->address, $waiting->sub_city, $waiting->city])->filter()->implode(', ') }}
                                </p>
                            </li>
                        @endforeach
                    </ul>
                    @if($missingPinCount > 12)
                        <p class="px-5 py-2 text-[11px] text-on-surface-variant border-t border-outline-variant/20">+ {{ $missingPinCount - 12 }} more</p>
                    @endif
                </section>
                @endif
            </div>

            <div class="space-y-4">
                <section class="bg-white border border-outline-variant/30 rounded-2xl p-5 shadow-sm">
                    <h3 class="font-label-caps text-label-caps text-primary font-bold mb-3">Add / Update Customer Address</h3>
                    <form action="#" method="POST" id="form-company-location" class="space-y-3" onsubmit="return submitCompanyLocation(event)">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant mb-1">Customer *</label>
                            <select required id="loc-company-id" name="company_id" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary" onchange="fillCompanyLocationForm()">
                                <option value="">Select customer</option>
                                @foreach($mapCustomers as $c)
                                    <option
                                        value="{{ $c->id }}"
                                        data-address="{{ e($c->address ?? '') }}"
                                        data-lat="{{ $c->latitude ?? '' }}"
                                        data-lng="{{ $c->longitude ?? '' }}"
                                    >{{ $c->name }}@if($c->city) ({{ $c->city }})@endif</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant mb-1">Address</label>
                            <textarea id="loc-address" name="address" rows="2" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary" placeholder="Office / shipping address"></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant mb-1">Latitude *</label>
                                <input required id="loc-lat" name="latitude" type="number" step="any" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary" placeholder="19.0760">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant mb-1">Longitude *</label>
                                <input required id="loc-lng" name="longitude" type="number" step="any" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary" placeholder="72.8777">
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" onclick="geocodeAddress()" class="flex-1 bg-surface-container-high text-primary border border-outline-variant px-3 py-2.5 rounded-lg text-xs font-bold hover:bg-surface-container-highest transition-all">
                                Geocode Address
                            </button>
                            <button type="button" onclick="useMapClickMode()" class="flex-1 bg-surface-container-high text-primary border border-outline-variant px-3 py-2.5 rounded-lg text-xs font-bold hover:bg-surface-container-highest transition-all">
                                Pick on Map
                            </button>
                        </div>
                        <p id="pick-hint" class="text-[11px] text-secondary hidden">Click the map to set customer pin…</p>
                        <button type="submit" class="w-full bg-primary text-on-primary py-3 rounded-lg font-bold shadow-md hover:bg-secondary transition-all">
                            Save Location
                        </button>
                    </form>
                </section>

                <section class="bg-white border border-outline-variant/30 rounded-2xl overflow-hidden shadow-sm max-h-[320px] flex flex-col">
                    <div class="px-5 py-3 border-b border-outline-variant/30 bg-surface-container-lowest">
                        <h3 class="font-label-caps text-label-caps text-primary font-bold">Salesmen Status</h3>
                    </div>
                    <ul id="salesmen-list" class="flex-1 overflow-y-auto divide-y divide-outline-variant/20">
                        @forelse($salesmen as $s)
                            <li class="px-5 py-3 text-sm">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="font-semibold text-primary">{{ $s->name }}</p>
                                        <p class="text-[11px] text-on-surface-variant">{{ $s->current_address ?: ($s->branch ?: 'No address yet') }}</p>
                                    </div>
                                    @if($s->latitude && $s->longitude)
                                        <span class="text-[9px] font-bold uppercase px-2 py-0.5 rounded {{ $s->last_location_at && $s->last_location_at->gt(now()->subMinutes(15)) ? 'bg-secondary-fixed text-on-secondary-container' : 'bg-surface-container text-on-surface-variant' }}">
                                            {{ $s->last_location_at && $s->last_location_at->gt(now()->subMinutes(15)) ? 'Live' : 'Stale' }}
                                        </span>
                                    @else
                                        <span class="text-[9px] font-bold uppercase px-2 py-0.5 rounded bg-surface-container text-on-surface-variant">Offline</span>
                                    @endif
                                </div>
                            </li>
                        @empty
                            <li class="px-5 py-6 text-sm text-on-surface-variant text-center">No sales executives found.</li>
                        @endforelse
                    </ul>
                </section>
            </div>
        </div>
    @endif
</div>

@if($mapsEnabled && !empty($googleMapsApiKey))
<script>
(function () {
    const LIVE_URL = @json(route('admin.maps.live'));
    const UNGEOCODED_URL = @json(route('admin.maps.ungeocoded'));
    const GEOCODE_MISSING_URL = @json(route('admin.maps.geocode-missing'));
    const SAVE_LOCATIONS_URL = @json(route('admin.maps.locations.batch'));
    const LOCATION_URL_BASE = @json(url('/admin/companies'));
    const CSRF = @json(csrf_token());
    const INITIAL = @json($initialMapData);

    let map = null;
    let geocoder = null;
    let pickMode = false;
    let pickMarker = null;
    let placingAddresses = false;
    const salesmanMarkers = {};
    const customerMarkers = {};
    const customerInfoWindows = {};
    const pulseOverlays = {};
    let PulseOverlay = null;

    function defaultCenter() {
        const withCoords = [...INITIAL.salesmen, ...INITIAL.customers].filter(p => p.latitude && p.longitude);
        if (withCoords.length) {
            return { lat: withCoords[0].latitude, lng: withCoords[0].longitude };
        }
        return { lat: 20.5937, lng: 78.9629 };
    }

    window.initAdminLiveMap = function () {
        PulseOverlay = class extends google.maps.OverlayView {
            constructor(position, mapRef) {
                super();
                this.position = position;
                this.div = null;
                this.setMap(mapRef);
            }
            onAdd() {
                this.div = document.createElement('div');
                this.div.className = 'tpi-pulse-marker';
                this.getPanes().overlayLayer.appendChild(this.div);
            }
            draw() {
                const projection = this.getProjection();
                if (!projection || !this.div) return;
                const point = projection.fromLatLngToDivPixel(this.position);
                if (!point) return;
                this.div.style.left = (point.x - 18) + 'px';
                this.div.style.top = (point.y - 18) + 'px';
            }
            onRemove() {
                if (this.div && this.div.parentNode) this.div.parentNode.removeChild(this.div);
                this.div = null;
            }
            setPosition(latLng) {
                this.position = latLng;
                this.draw();
            }
        };

        const el = document.getElementById('admin-live-map');
        if (!el) return;
        map = new google.maps.Map(el, {
            center: defaultCenter(),
            zoom: 5,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true,
        });
        geocoder = new google.maps.Geocoder();
        map.addListener('click', (e) => {
            if (!pickMode) return;
            const lat = e.latLng.lat();
            const lng = e.latLng.lng();
            document.getElementById('loc-lat').value = lat.toFixed(7);
            document.getElementById('loc-lng').value = lng.toFixed(7);
            if (pickMarker) pickMarker.setMap(null);
            pickMarker = new google.maps.Marker({
                position: e.latLng,
                map,
                title: 'New customer pin',
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 10,
                    fillColor: '#0d2149',
                    fillOpacity: 1,
                    strokeColor: '#ffffff',
                    strokeWeight: 2,
                },
            });
            pickMode = false;
            document.getElementById('pick-hint').classList.add('hidden');
        });
        renderMarkers(INITIAL);
        fitMapBounds();
        setInterval(refreshLive, 20000);
    };

    function salesmanIcon(online) {
        return {
            path: google.maps.SymbolPath.CIRCLE,
            scale: 9,
            fillColor: online ? '#fb7800' : '#994700',
            fillOpacity: 1,
            strokeColor: '#ffffff',
            strokeWeight: 2,
        };
    }

    function customerIcon() {
        return {
            path: google.maps.SymbolPath.BACKWARD_CLOSED_ARROW,
            scale: 6,
            fillColor: '#0d2149',
            fillOpacity: 1,
            strokeColor: '#ffffff',
            strokeWeight: 1.5,
            rotation: 180,
        };
    }

    function renderMarkers(payload) {
        (payload.salesmen || []).forEach((s) => {
            if (s.latitude == null || s.longitude == null) {
                if (salesmanMarkers[s.id]) {
                    salesmanMarkers[s.id].setMap(null);
                    delete salesmanMarkers[s.id];
                }
                if (pulseOverlays[s.id]) {
                    pulseOverlays[s.id].setMap(null);
                    delete pulseOverlays[s.id];
                }
                return;
            }
            const pos = new google.maps.LatLng(s.latitude, s.longitude);
            if (salesmanMarkers[s.id]) {
                animateMarkerMove(salesmanMarkers[s.id], pos);
                salesmanMarkers[s.id].setIcon(salesmanIcon(!!s.online));
                salesmanMarkers[s.id].setTitle(s.name + (s.address ? ' — ' + s.address : ''));
                if (pulseOverlays[s.id]) pulseOverlays[s.id].setPosition(pos);
            } else {
                salesmanMarkers[s.id] = new google.maps.Marker({
                    position: pos,
                    map,
                    title: s.name + (s.address ? ' — ' + s.address : ''),
                    icon: salesmanIcon(!!s.online),
                    zIndex: 10,
                });
                const info = new google.maps.InfoWindow({
                    content: `<div style="padding:4px 2px;max-width:220px"><strong>${escapeHtml(s.name)}</strong><br><span style="font-size:12px;color:#555">${escapeHtml(s.address || s.branch || 'Sales executive')}</span></div>`,
                });
                salesmanMarkers[s.id].addListener('click', () => info.open({ map, anchor: salesmanMarkers[s.id] }));
                if (s.online) {
                    pulseOverlays[s.id] = new PulseOverlay(pos, map);
                }
            }
        });

        (payload.customers || []).forEach((c) => {
            if (c.latitude == null || c.longitude == null) {
                if (customerMarkers[c.id]) {
                    customerMarkers[c.id].setMap(null);
                    delete customerMarkers[c.id];
                }
                return;
            }
            const pos = { lat: c.latitude, lng: c.longitude };
            if (customerMarkers[c.id]) {
                customerMarkers[c.id].setPosition(pos);
                customerMarkers[c.id].setTitle(c.name);
                if (customerInfoWindows[c.id]) {
                    customerInfoWindows[c.id].setContent(customerInfoHtml(c));
                }
            } else {
                customerMarkers[c.id] = new google.maps.Marker({
                    position: pos,
                    map,
                    title: c.name,
                    icon: customerIcon(),
                    zIndex: 5,
                });
                const info = new google.maps.InfoWindow({
                    content: customerInfoHtml(c),
                });
                customerInfoWindows[c.id] = info;
                customerMarkers[c.id].addListener('click', () => info.open({ map, anchor: customerMarkers[c.id] }));
            }
        });

        updateSalesmenList(payload.salesmen || []);
        const online = (payload.salesmen || []).filter(s => s.online && s.latitude != null).length;
        const customersCount = (payload.customers || []).filter(c => c.latitude != null).length;
        const onlineEl = document.getElementById('stat-sales-online');
        const custEl = document.getElementById('stat-customers');
        const missingEl = document.getElementById('stat-missing-pins');
        if (onlineEl) onlineEl.textContent = online;
        if (custEl) custEl.textContent = customersCount;
        if (missingEl && payload.missing_pin_count != null) missingEl.textContent = payload.missing_pin_count;
        if (payload.missing_pin_count === 0) {
            const banner = document.getElementById('missing-pins-banner');
            const waiting = document.getElementById('waiting-address-list');
            if (banner) banner.classList.add('hidden');
            if (waiting) waiting.classList.add('hidden');
        }
    }

    function animateMarkerMove(marker, newPos) {
        const start = marker.getPosition();
        if (!start) {
            marker.setPosition(newPos);
            return;
        }
        const frames = 24;
        let i = 0;
        const lat0 = start.lat();
        const lng0 = start.lng();
        const lat1 = newPos.lat();
        const lng1 = newPos.lng();
        const timer = setInterval(() => {
            i++;
            const t = i / frames;
            marker.setPosition(new google.maps.LatLng(
                lat0 + (lat1 - lat0) * t,
                lng0 + (lng1 - lng0) * t
            ));
            if (i >= frames) clearInterval(timer);
        }, 30);
    }

    function updateSalesmenList(salesmen) {
        const list = document.getElementById('salesmen-list');
        if (!list) return;
        if (!salesmen.length) {
            list.innerHTML = '<li class="px-5 py-6 text-sm text-on-surface-variant text-center">No sales executives found.</li>';
            return;
        }
        list.innerHTML = salesmen.map(s => {
            const status = (s.latitude != null && s.longitude != null)
                ? (s.online
                    ? '<span class="text-[9px] font-bold uppercase px-2 py-0.5 rounded bg-secondary-fixed text-on-secondary-container">Live</span>'
                    : '<span class="text-[9px] font-bold uppercase px-2 py-0.5 rounded bg-surface-container text-on-surface-variant">Stale</span>')
                : '<span class="text-[9px] font-bold uppercase px-2 py-0.5 rounded bg-surface-container text-on-surface-variant">Offline</span>';
            return `<li class="px-5 py-3 text-sm"><div class="flex items-start justify-between gap-2"><div><p class="font-semibold text-primary">${escapeHtml(s.name)}</p><p class="text-[11px] text-on-surface-variant">${escapeHtml(s.address || s.branch || 'No address yet')}</p></div>${status}</div></li>`;
        }).join('');
    }

    async function refreshLive() {
        try {
            const res = await fetch(LIVE_URL, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            if (!data.maps_enabled) return;
            renderMarkers(data);
        } catch (e) {}
    }

    window.fitMapBounds = function () {
        if (!map) return;
        const bounds = new google.maps.LatLngBounds();
        let has = false;
        Object.values(salesmanMarkers).forEach(m => { bounds.extend(m.getPosition()); has = true; });
        Object.values(customerMarkers).forEach(m => { bounds.extend(m.getPosition()); has = true; });
        if (has) map.fitBounds(bounds, 64);
    };

    window.fillCompanyLocationForm = function () {
        const sel = document.getElementById('loc-company-id');
        const opt = sel.options[sel.selectedIndex];
        if (!opt || !opt.value) return;
        document.getElementById('loc-address').value = opt.dataset.address || '';
        document.getElementById('loc-lat').value = opt.dataset.lat || '';
        document.getElementById('loc-lng').value = opt.dataset.lng || '';
        if (opt.dataset.lat && opt.dataset.lng && map) {
            map.panTo({ lat: parseFloat(opt.dataset.lat), lng: parseFloat(opt.dataset.lng) });
            map.setZoom(15);
        }
    };

    window.useMapClickMode = function () {
        pickMode = true;
        document.getElementById('pick-hint').classList.remove('hidden');
    };

    function customerLocationLabel(c) {
        return [c.address, c.sub_city, c.city].filter(Boolean).join(', ') || 'Customer';
    }

    function customerInfoHtml(c) {
        return `<div style="padding:4px 2px;max-width:260px"><strong>${escapeHtml(c.name)}</strong><br><span style="font-size:12px;color:#555">${escapeHtml(customerLocationLabel(c))}</span>${c.stage ? '<br><span style="font-size:11px;color:#994700">' + escapeHtml(c.stage) + '</span>' : ''}</div>`;
    }

    function setGeocodeProgress(text) {
        const el = document.getElementById('geocode-progress');
        if (!el) return;
        el.textContent = text || '';
        el.classList.toggle('hidden', !text);
    }

    function geocodeQuery(query) {
        return new Promise((resolve) => {
            if (!query || !geocoder) {
                resolve(null);
                return;
            }
            geocoder.geocode({ address: query, region: 'IN' }, (results, status) => {
                if (status === 'OK' && results[0]) {
                    const loc = results[0].geometry.location;
                    resolve({ latitude: loc.lat(), longitude: loc.lng() });
                    return;
                }
                if (status === 'OVER_QUERY_LIMIT') {
                    resolve({ retry: true });
                    return;
                }
                resolve(null);
            });
        });
    }

    async function saveLocationBatch(companies) {
        if (!companies.length) return;
        const res = await fetch(SAVE_LOCATIONS_URL, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ companies }),
        });
        if (!res.ok) {
            throw new Error('Could not save map pins.');
        }
    }

    async function placeWithServerGeocoder() {
        let totalPlaced = 0;
        while (true) {
            const res = await fetch(GEOCODE_MISSING_URL, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ limit: 12 }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                return { ok: false, error: data.error || 'Server geocoding is unavailable.', placed: totalPlaced };
            }
            totalPlaced += data.geocoded || 0;
            setGeocodeProgress(`Placed ${totalPlaced} address${totalPlaced === 1 ? '' : 'es'} on the map. ${data.remaining || 0} remaining…`);
            await refreshLive();
            if ((data.remaining || 0) === 0 || (data.processed || 0) === 0 || (data.geocoded || 0) === 0) {
                return { ok: true, placed: totalPlaced, remaining: data.remaining || 0 };
            }
        }
    }

    async function placeWithMapsGeocoder() {
        const failedIds = [];
        let totalPlaced = 0;
        while (true) {
            const params = new URLSearchParams({ limit: '8' });
            failedIds.forEach((id) => params.append('exclude[]', String(id)));
            const res = await fetch(UNGEOCODED_URL + '?' + params.toString(), {
                headers: { 'Accept': 'application/json' },
            });
            if (!res.ok) throw new Error('Could not load addresses to pin.');
            const data = await res.json();
            const batch = data.customers || [];
            if (!batch.length) {
                return { placed: totalPlaced, remaining: data.remaining || 0 };
            }
            const saved = [];
            for (const customer of batch) {
                let hit = await geocodeQuery(customer.query);
                if (hit && hit.retry) {
                    await new Promise((r) => setTimeout(r, 1500));
                    hit = await geocodeQuery(customer.query);
                }
                if (!hit || hit.retry) {
                    hit = await geocodeQuery(customer.fallback_query);
                }
                if (hit && !hit.retry) {
                    saved.push({
                        id: customer.id,
                        latitude: hit.latitude,
                        longitude: hit.longitude,
                    });
                } else {
                    failedIds.push(customer.id);
                }
                await new Promise((r) => setTimeout(r, 220));
            }
            await saveLocationBatch(saved);
            totalPlaced += saved.length;
            setGeocodeProgress(`Placed ${totalPlaced} address${totalPlaced === 1 ? '' : 'es'} on the map. ${Math.max(0, (data.remaining || 0) - saved.length)} remaining…`);
            await refreshLive();
            if (saved.length === 0 && batch.every((c) => failedIds.includes(c.id))) {
                return { placed: totalPlaced, remaining: data.remaining || 0 };
            }
        }
    }

    window.placeSheetAddressesOnMap = async function () {
        if (placingAddresses) return;
        placingAddresses = true;
        const btn = document.getElementById('btn-place-addresses');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Placing…';
        }
        setGeocodeProgress('Looking up sheet addresses…');
        try {
            const server = await placeWithServerGeocoder();
            if (!server.ok) {
                setGeocodeProgress(server.error + ' Trying the map geocoder instead…');
                const jsResult = await placeWithMapsGeocoder();
                setGeocodeProgress(jsResult.placed
                    ? `Done. ${jsResult.placed} address${jsResult.placed === 1 ? '' : 'es'} placed on the map.`
                    : 'Could not place those addresses. Check that Geocoding is enabled for the Google Maps key.');
            } else {
                setGeocodeProgress(server.placed
                    ? `Done. ${server.placed} address${server.placed === 1 ? '' : 'es'} placed on the map.`
                    : 'No new pins were added.');
            }
            await refreshLive();
            fitMapBounds();
        } catch (e) {
            setGeocodeProgress(e.message || 'Could not place addresses on the map.');
        } finally {
            placingAddresses = false;
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Place addresses on map';
            }
        }
    };

    window.geocodeAddress = function () {
        const address = document.getElementById('loc-address').value.trim();
        if (!address || !geocoder) {
            alert('Enter an address first.');
            return;
        }
        geocoder.geocode({ address }, (results, status) => {
            if (status !== 'OK' || !results[0]) {
                alert('Could not find that address.');
                return;
            }
            const loc = results[0].geometry.location;
            document.getElementById('loc-lat').value = loc.lat().toFixed(7);
            document.getElementById('loc-lng').value = loc.lng().toFixed(7);
            map.panTo(loc);
            map.setZoom(15);
            if (pickMarker) pickMarker.setMap(null);
            pickMarker = new google.maps.Marker({ position: loc, map, title: 'Geocoded pin' });
        });
    };

    window.submitCompanyLocation = async function (event) {
        event.preventDefault();
        const id = document.getElementById('loc-company-id').value;
        if (!id) return false;
        const body = new FormData();
        body.append('_token', CSRF);
        body.append('address', document.getElementById('loc-address').value);
        body.append('latitude', document.getElementById('loc-lat').value);
        body.append('longitude', document.getElementById('loc-lng').value);
        try {
            const res = await fetch(LOCATION_URL_BASE + '/' + id + '/location', {
                method: 'POST',
                body,
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                redirect: 'follow',
            });
            if (res.ok || res.redirected) {
                window.location.reload();
            } else {
                alert('Could not save location.');
            }
        } catch (e) {
            // Form POST fallback
            const form = document.getElementById('form-company-location');
            form.action = LOCATION_URL_BASE + '/' + id + '/location';
            form.method = 'POST';
            form.submit();
        }
        return false;
    };

    function escapeHtml(str) {
        return String(str || '').replace(/[&<>"']/g, (m) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        })[m]);
    }
})();
</script>
<style>
.tpi-pulse-marker {
    position: absolute;
    width: 36px;
    height: 36px;
    border-radius: 9999px;
    background: rgba(251, 120, 0, 0.35);
    animation: tpi-pulse 1.6s ease-out infinite;
    pointer-events: none;
}
@keyframes tpi-pulse {
    0% { transform: scale(0.5); opacity: 0.9; }
    100% { transform: scale(2.2); opacity: 0; }
}
</style>
<script async defer src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsApiKey }}&callback=initAdminLiveMap"></script>
@elseif($mapsEnabled)
<div class="bg-red-50 border border-red-200 text-red-800 rounded-xl p-4 text-sm">
    Google Maps is enabled but <code>GOOGLE_MAPS_API_KEY</code> is missing in the server environment.
</div>
@endif
