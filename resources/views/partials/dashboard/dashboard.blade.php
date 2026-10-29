@php $k = $kpis; @endphp
<div class="row g-3 mb-3">
  <div class="col-6 col-md-4 col-xl-2">
    <a class="kpi-card" href="{{ route('admin.outstanding') }}">
      <div class="kpi-icon" style="background:var(--blue-bg);color:var(--blue);"><i class="fa-solid fa-sack-dollar"></i></div>
      <div class="kpi-label">Total Outstanding</div>
      <div class="kpi-amount">{{ inr($k['totalOutstanding']) }}</div>
      <div class="kpi-delta" style="color:var(--muted);">Across {{ $k['openInvoices'] }} open invoices</div>
    </a>
  </div>
  <div class="col-6 col-md-4 col-xl-2">
    <a class="kpi-card" href="{{ route('admin.outstanding', ['status' => 'Overdue']) }}">
      <div class="kpi-icon" style="background:var(--red-bg);color:var(--red);"><i class="fa-solid fa-triangle-exclamation"></i></div>
      <div class="kpi-label">Overdue</div>
      <div class="kpi-amount">{{ inr($k['overdue']) }}</div>
      <div class="kpi-delta" style="color:var(--red);">Past due date</div>
    </a>
  </div>
  <div class="col-6 col-md-4 col-xl-2">
    <a class="kpi-card" href="{{ route('admin.outstanding', ['status' => 'Due Today']) }}">
      <div class="kpi-icon" style="background:var(--orange-bg);color:var(--orange);"><i class="fa-solid fa-calendar-day"></i></div>
      <div class="kpi-label">Due Today</div>
      <div class="kpi-amount">{{ inr($k['dueToday']) }}</div>
      <div class="kpi-delta" style="color:var(--muted);">{{ $k['dueTodayCount'] }} invoices</div>
    </a>
  </div>
  <div class="col-6 col-md-4 col-xl-2">
    <a class="kpi-card" href="{{ route('admin.ptp') }}">
      <div class="kpi-icon" style="background:var(--yellow-bg);color:var(--yellow);"><i class="fa-solid fa-handshake"></i></div>
      <div class="kpi-label">Payment Promise</div>
      <div class="kpi-amount">{{ inr($k['ptpAmount']) }}</div>
      <div class="kpi-delta" style="color:var(--muted);">{{ $k['ptpCount'] }} active PTPs</div>
    </a>
  </div>
  <div class="col-6 col-md-4 col-xl-2">
    <a class="kpi-card" href="{{ route('admin.reports', ['type' => 'collection']) }}">
      <div class="kpi-icon" style="background:var(--green-bg);color:var(--green);"><i class="fa-solid fa-circle-check"></i></div>
      <div class="kpi-label">Collected This Month</div>
      <div class="kpi-amount">{{ inr($k['collectedThisMonth']) }}</div>
      <div class="kpi-delta" style="color:var(--green);">Received vs invoices</div>
    </a>
  </div>
  <div class="col-6 col-md-4 col-xl-2">
    <a class="kpi-card" href="{{ route('admin.followups') }}">
      <div class="kpi-icon" style="background:var(--blue-bg);color:var(--navy);"><i class="fa-solid fa-phone-volume"></i></div>
      <div class="kpi-label">Follow-ups Today</div>
      <div class="kpi-amount">{{ $k['followupsToday'] }}</div>
      <div class="kpi-delta" style="color:var(--muted);">{{ $k['completedFollowups'] }} completed so far</div>
    </a>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-lg-7">
    <div class="section-card h-100">
      <div class="section-title">Outstanding Aging</div>
      <div class="section-sub">Outstanding value split by number of days pending</div>
      <canvas id="agingChart" height="140"></canvas>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="section-card h-100">
      <div class="section-title">Collection Performance</div>
      <div class="section-sub">Monthly target vs actual collections</div>
      <canvas id="collectionChart" height="160"></canvas>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="section-card">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
          <div class="section-title mb-0">Today's Follow-ups</div>
          <div class="section-sub mb-0">Top priority accounts for today</div>
        </div>
        <a class="btn btn-crm-outline btn-sm" href="{{ route('admin.followups') }}">View all <i class="fa-solid fa-arrow-right"></i></a>
      </div>
      @include('partials.dashboard.followup-table', ['invoices' => $todayFollowups, 'compact' => true])
    </div>
  </div>
  <div class="col-lg-4">
    <div class="section-card h-100">
      <div class="section-title">Team Snapshot</div>
      <div class="section-sub">Today's activity by telecaller</div>
      @foreach($teamSnapshot as $row)
        <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--line);">
          <div class="d-flex align-items-center gap-2">
            <div class="avatar-sm" style="background:var(--blue-bg);color:var(--blue);width:30px;height:30px;font-size:11px;">{{ $row['initials'] }}</div>
            <div>
              <div style="font-size:12.5px;font-weight:600;">{{ $row['name'] }}</div>
              <div style="font-size:10.5px;color:var(--muted);">{{ $row['count'] }} accounts pending</div>
            </div>
          </div>
          <div style="font-size:12px;font-weight:700;color:var(--navy);">{{ inr($row['amount']) }}</div>
        </div>
      @endforeach
    </div>
  </div>
</div>
