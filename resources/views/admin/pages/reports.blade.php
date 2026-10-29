@extends('layouts.admin')
@section('content')
<div class="section-card mb-3">
  <form class="filter-bar" method="GET" action="{{ route('admin.reports') }}">
    <input type="hidden" name="type" value="{{ $type }}">
    <input type="date" class="form-control" style="width:150px;" name="from" value="{{ $filters['from'] ?? '' }}">
    <input type="date" class="form-control" style="width:150px;" name="to" value="{{ $filters['to'] ?? '' }}">
    <select class="form-select" style="width:150px;" name="party_id">
      <option value="">Party</option>
      @foreach($parties as $party)
        <option value="{{ $party->id }}" @selected((string)($filters['party_id'] ?? '') === (string)$party->id)>{{ $party->name }}</option>
      @endforeach
    </select>
    <select class="form-select" style="width:160px;" name="sales">
      <option value="">Salesperson</option>
      @foreach($salesPersons as $person)
        <option value="{{ $person }}" @selected(($filters['sales'] ?? '') === $person)>{{ $person }}</option>
      @endforeach
    </select>
    <select class="form-select" style="width:150px;" name="telecaller">
      <option value="">Telecaller</option>
      @foreach($telecallers as $user)
        <option value="{{ $user->id }}" @selected((string)($filters['telecaller'] ?? '') === (string)$user->id)>{{ $user->name }}</option>
      @endforeach
    </select>
    <select class="form-select" style="width:130px;" name="branch">
      <option value="">Branch</option>
      @foreach($branches as $branch)
        <option value="{{ $branch }}" @selected(($filters['branch'] ?? '') === $branch)>{{ $branch }}</option>
      @endforeach
    </select>
    <select class="form-select" style="width:130px;" name="status">
      <option value="">Status</option>
      <option value="Overdue" @selected(($filters['status'] ?? '') === 'Overdue')>Overdue</option>
      <option value="Paid" @selected(($filters['status'] ?? '') === 'Paid')>Paid</option>
    </select>
    <button class="btn btn-crm-primary" type="submit">View Report</button>
    <a class="btn btn-crm-outline" href="{{ route('admin.outstanding.export') }}">Export Excel</a>
    <button class="btn btn-crm-outline" type="button" onclick="window.print()">Print</button>
  </form>
</div>
<div class="row g-3 mb-3">
  @foreach($reports as $report)
    <div class="col-md-6 col-xl-3">
      <a class="section-card h-100 d-block text-decoration-none" href="{{ route('admin.reports', array_merge(request()->except('type'), ['type' => $report['key']])) }}">
        <div class="kpi-icon" style="background:var(--blue-bg);color:var(--blue);"><i class="fa-solid {{ $report['icon'] }}"></i></div>
        <div style="font-weight:700;font-size:13px;color:var(--navy);margin-top:6px;">{{ $report['name'] }}</div>
        <div style="font-size:11.5px;color:var(--muted);margin-top:4px;">{{ $report['desc'] }}</div>
      </a>
    </div>
  @endforeach
</div>
@if($type)
  <div class="section-card">
    <div class="section-title">{{ $resultTitle }}</div>
    <div class="table-wrap">
      <table class="crm-table">
        <thead>
          <tr>
            @if($type === 'aging')
              <th>Bucket</th><th>Invoices</th><th>Outstanding</th>
            @elseif($type === 'telecaller')
              <th>Telecaller</th><th>Calls</th><th>Connected</th><th>PTP</th><th>Outstanding</th>
            @elseif($type === 'collection')
              <th>Month</th><th>Collected</th><th>Target</th>
            @elseif($type === 'ptp')
              <th>Party</th><th>Invoice</th><th>Amount</th><th>Date</th><th>Status</th>
            @elseif(in_array($type, ['party','salesperson']))
              <th>Name</th><th>Invoices</th><th>Outstanding</th>
            @else
              <th>Party</th><th>Invoice</th><th>Due Date</th><th>Outstanding</th><th>Status</th><th>Assigned</th>
            @endif
          </tr>
        </thead>
        <tbody>
          @forelse($resultRows as $row)
            @if($type === 'aging')
              <tr><td>{{ $row['label'] }}</td><td>{{ $row['count'] }}</td><td>{{ inr($row['amount']) }}</td></tr>
            @elseif($type === 'telecaller')
              <tr><td>{{ $row['label'] }}</td><td>{{ $row['calls'] }}</td><td>{{ $row['connected'] }}</td><td>{{ $row['ptp'] }}</td><td>{{ inr($row['amount']) }}</td></tr>
            @elseif($type === 'collection')
              <tr><td>{{ $row['label'] }}</td><td>{{ inr($row['collected']) }}</td><td>{{ inr($row['target']) }}</td></tr>
            @elseif($type === 'ptp')
              <tr><td>{{ $row['label'] }}</td><td>{{ $row['invoice'] }}</td><td>{{ inr($row['amount']) }}</td><td>{{ $row['date'] }}</td><td>{{ $row['status'] }}</td></tr>
            @elseif(in_array($type, ['party','salesperson']))
              <tr><td>{{ $row['label'] }}</td><td>{{ $row['count'] }}</td><td>{{ inr($row['amount']) }}</td></tr>
            @else
              <tr>
                <td>{{ $row->party?->name ?? ($row['party'] ?? '-') }}</td>
                <td>{{ $row->invoice_no ?? '-' }}</td>
                <td>{{ isset($row->due_date) ? crm_date($row->due_date) : '-' }}</td>
                <td>{{ inr($row->outstanding ?? 0) }}</td>
                <td>{{ $row->computed_status ?? '-' }}</td>
                <td>{{ $row->assignee?->name ?? '-' }}</td>
              </tr>
            @endif
          @empty
            <tr><td colspan="6"><div class="empty-state"><i class="fa-regular fa-folder-open"></i><div>No data for this report.</div></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endif
@endsection
