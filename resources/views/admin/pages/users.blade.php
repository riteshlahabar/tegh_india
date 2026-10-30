@extends('layouts.admin')
@section('content')
@if(auth()->user()->isAdmin())
<div class="section-card mb-3">
  <div class="section-title">Add telecaller</div>
  <div class="section-sub">Creates an app login. The telecaller signs in with this username and password on the Tegh Cables mobile app and can work the same outstanding, follow-up, call, WhatsApp, email and report screens.</div>
  <form method="POST" action="{{ route('admin.telecallers.store') }}" class="row g-2 align-items-end">
    @csrf
    <div class="col-md-4">
      <label class="form-label" style="font-size:12px;font-weight:600;">Name</label>
      <input class="form-control" name="name" value="{{ old('name') }}" required maxlength="255">
    </div>
    <div class="col-md-4">
      <label class="form-label" style="font-size:12px;font-weight:600;">Username</label>
      <input class="form-control" name="username" value="{{ old('username') }}" required maxlength="255" pattern="[A-Za-z0-9_\-]+" title="Letters, numbers, dash or underscore">
    </div>
    <div class="col-md-4">
      <label class="form-label" style="font-size:12px;font-weight:600;">Email</label>
      <input class="form-control" type="email" name="email" value="{{ old('email') }}" required maxlength="255">
    </div>
    <div class="col-md-4">
      <label class="form-label" style="font-size:12px;font-weight:600;">Password</label>
      <input class="form-control" type="text" name="password" required minlength="4" maxlength="255" autocomplete="new-password">
    </div>
    {{-- Branch was a free-text field nothing ever read. Region and City are
         what every query actually scopes by, so they replace it outright. --}}
    <div class="col-md-4">
      <label class="form-label" style="font-size:12px;font-weight:600;">Region</label>
      <select class="form-select" name="region" id="newRegion">
        <option value="">All regions</option>
        @foreach(($companies ?? []) as $slug => $company)
          <option value="{{ $slug }}" @selected(old('region') === $slug)>{{ $company['label'] }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label" style="font-size:12px;font-weight:600;">City</label>
      <select class="form-select" name="city" id="newCity" data-selected="{{ old('city') }}">
        <option value="">All cities</option>
      </select>
    </div>
    <div class="col-12">
      <div class="text-muted mb-2" style="font-size:12px;">
        Region and city scope everything this account sees &mdash; outstanding, follow-ups and the mobile app. Leave both blank for every company.
      </div>
      <button class="btn btn-crm-primary" type="submit"><i class="fa-solid fa-user-plus"></i> Add telecaller</button>
    </div>
  </form>
</div>
@endif
<div class="row g-3 mb-3">
  @foreach($team as $row)
    @php $user = $row['user']; @endphp
    <div class="col-md-4">
      <div class="section-card h-100">
        <div class="d-flex align-items-center gap-2 mb-2">
          <div class="avatar-sm" style="background:{{ $user->role === 'admin' ? 'var(--navy)' : 'var(--accent)' }};">{{ $user->initials ?: crm_initials($user->name) }}</div>
          <div>
            <div style="font-weight:700;font-size:13.5px;color:var(--navy);">{{ $user->name }}</div>
            <div style="font-size:11px;color:var(--muted);">{{ $user->role === 'admin' ? 'Administrator' : 'Payment Follow-up Executive' }}@if($user->role !== 'admin') · {{ $user->username }}@endif</div>
          </div>
        </div>
        @if($user->role === 'admin')
          <div class="text-muted" style="font-size:12px;">Full visibility across all telecallers, accounts and reports.</div>
        @else
          <div class="row g-2">
            <div class="col-6"><div class="stat-mini"><div class="l">Assigned</div><div class="v">{{ $row['assigned'] }}</div></div></div>
            <div class="col-6"><div class="stat-mini"><div class="l">Calls</div><div class="v">{{ $row['calls'] }}</div></div></div>
            <div class="col-6"><div class="stat-mini"><div class="l">PTP Created</div><div class="v">{{ $row['ptp'] }}</div></div></div>
            <div class="col-6"><div class="stat-mini"><div class="l">Collected</div><div class="v" style="font-size:14px;">{{ inr($row['collected']) }}</div></div></div>
          </div>
          <div class="mt-2" style="font-size:11px;color:var(--muted);">
            Scope:
            <strong>{{ $user->region ? ($companies[$user->region]['label'] ?? $user->region) : 'All regions' }}</strong>
            &middot;
            <strong>{{ $user->city ?: 'All cities' }}</strong>
          </div>
          @if(auth()->user()->isAdmin() && $user->role !== 'telecaller')
            {{-- Legacy accounts from the old sales portal (role `sales_executive`).
                 They are not app logins, and the telecaller actions 404 on them,
                 so the buttons are not offered. --}}
            <div class="mt-3 text-muted" style="font-size:12px;">
              Role <code>{{ $user->role ?: 'none' }}</code> &mdash; not a mobile-app
              account. Scope and password are managed only for telecallers.
            </div>
          @elseif(auth()->user()->isAdmin())
            {{-- One form now: the password was the only field this accepted, so a
                 telecaller's scope could never be changed after creation. --}}
            <form method="POST" action="{{ route('admin.telecallers.update', $user) }}" class="mt-3">
              @csrf
              @method('PUT')
              <div class="row g-2">
                <div class="col-6">
                  <select class="form-select form-select-sm js-region" name="region" data-target="city-{{ $user->id }}">
                    <option value="">All regions</option>
                    @foreach(($companies ?? []) as $slug => $company)
                      <option value="{{ $slug }}" @selected($user->region === $slug)>{{ $company['label'] }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-6">
                  <select class="form-select form-select-sm js-city" id="city-{{ $user->id }}" name="city" data-selected="{{ $user->city }}">
                    <option value="">All cities</option>
                  </select>
                </div>
                <div class="col-12 d-flex gap-2">
                  <input class="form-control form-control-sm" type="text" name="password" placeholder="New app password (optional)" minlength="4" autocomplete="new-password">
                  <button class="btn btn-crm-outline btn-sm" type="submit">Save</button>
                </div>
              </div>
            </form>
            <form method="POST" action="{{ route('admin.telecallers.destroy', $user) }}" class="mt-2" onsubmit="return confirm('Remove {{ $user->name }}? Past calls stay on the accounts.')">
              @csrf
              @method('DELETE')
              <button class="btn btn-sm btn-outline-danger" type="submit">Remove telecaller</button>
            </form>
          @endif
        @endif
      </div>
    </div>
  @endforeach
</div>
<div class="section-card">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <div>
      <div class="section-title mb-0">Team Performance</div>
      <div class="section-sub mb-0">Assigned parties, calls, PTP and collections by user</div>
    </div>
    <button class="btn btn-crm-outline btn-sm" data-bs-toggle="modal" data-bs-target="#assignModal"><i class="fa-solid fa-user-plus"></i> Assign Party</button>
  </div>
  <div class="table-wrap">
    <table class="crm-table">
      <thead><tr><th>User</th><th>Role</th><th>Assigned Parties</th><th>Today's Follow-ups</th><th>Calls Made</th><th>Connected</th><th>PTP Created</th><th>Amount Collected</th></tr></thead>
      <tbody>
        @foreach($team as $row)
          @continue($row['user']->role === 'admin')
          <tr>
            <td><b>{{ $row['user']->name }}</b></td>
            <td>Payment Follow-up Executive</td>
            <td>{{ $row['assigned'] }}</td>
            <td>{{ $row['followups'] }}</td>
            <td>{{ $row['calls'] }}</td>
            <td>{{ $row['connected'] }}</td>
            <td>{{ $row['ptp'] }}</td>
            <td>{{ inr($row['collected']) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <canvas id="teamChart" height="90" class="mt-4"></canvas>
</div>
@endsection
@section('scripts')
<script>
(function () {
  const ctx = document.getElementById('teamChart');
  if (!ctx) return;
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: @json($chartLabels),
      datasets: [
        { label: 'Calls', data: @json($chartCalls), backgroundColor: '#2E6FE0', borderRadius: 6 },
        { label: 'Connected', data: @json($chartConnected), backgroundColor: '#198754', borderRadius: 6 },
        { label: 'PTP', data: @json($chartPtp), backgroundColor: '#FD7E14', borderRadius: 6 }
      ]
    },
    options: { plugins: { legend: { position: 'bottom' } } }
  });
})();
</script>

{{-- Its own tag on purpose. Sharing one <script> with the chart meant a throw
     from Chart.js stopped everything after it, so the City dropdowns silently
     stayed empty while the Outstanding page's identical cascade worked. --}}
<script>
(function () {
  // Same cascade as the Outstanding page, and the same source:
  // OutstandingPageService::citiesByCompany(). Apna Bazar has no branch field,
  // so the town read out of the party's address is the only geography there is.
  // Narrowing by region keeps Gujarat from offering Thane.
  const citiesByCompany = @json($citiesByCompany ?? []);

  function fill(region, city) {
    const wanted = city.dataset.selected || '';
    const list = citiesByCompany[(region.value || '').toLowerCase()] || citiesByCompany[''] || [];

    city.innerHTML = '<option value="">All cities</option>';
    list.forEach(function (name) {
      const option = document.createElement('option');
      option.value = name;
      option.textContent = name;
      option.selected = name === wanted;
      city.appendChild(option);
    });

    // A town from the previous region is no longer in the list, so the scope
    // would silently keep a value the admin cannot see.
    if (wanted && city.value !== wanted) {
      city.dataset.selected = '';
    }
  }

  function bind(region, city) {
    if (!region || !city) return;
    fill(region, city);
    region.addEventListener('change', function () {
      city.dataset.selected = '';
      fill(region, city);
    });
    city.addEventListener('change', function () {
      city.dataset.selected = city.value;
    });
  }

  bind(document.getElementById('newRegion'), document.getElementById('newCity'));

  document.querySelectorAll('.js-region').forEach(function (region) {
    bind(region, document.getElementById(region.dataset.target));
  });
})();
</script>
@endsection
