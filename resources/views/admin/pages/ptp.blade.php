@extends('layouts.admin')
@section('content')
<div class="row g-3 mb-3">
  <div class="col-6 col-md-3"><div class="kpi-card"><div class="kpi-label">Total PTP</div><div class="kpi-amount">{{ inr($ptpTotal) }}</div></div></div>
  <div class="col-6 col-md-3"><div class="kpi-card"><div class="kpi-label">PTP Due Today</div><div class="kpi-amount">{{ $ptpDueToday }}</div></div></div>
  <div class="col-6 col-md-3"><div class="kpi-card"><div class="kpi-label">PTP Overdue</div><div class="kpi-amount">{{ $ptpOverdue }}</div></div></div>
  <div class="col-6 col-md-3"><div class="kpi-card"><div class="kpi-label">PTP Collected</div><div class="kpi-amount">{{ inr($ptpCollected) }}</div></div></div>
</div>
<div class="section-card">
  <div class="section-title">Payment Promises</div>
  <div class="section-sub">Track PTP status across all telecallers</div>
  <div class="table-wrap">
    <table class="crm-table">
      <thead><tr><th>Party</th><th>Invoice</th><th>Outstanding</th><th>Promise Amount</th><th>Promise Date</th><th>Assigned</th><th>Status</th><th class="col-actions">Action</th></tr></thead>
      <tbody>
        @forelse($promises as $ptp)
          <tr onclick="openPartyDrawer({{ $ptp->party_id }})">
            <td><b>{{ $ptp->party?->name }}</b></td>
            <td>{{ $ptp->invoice?->invoice_no }}</td>
            <td>{{ inr($ptp->invoice?->outstanding) }}</td>
            <td>{{ inr($ptp->amount) }}</td>
            <td>{{ crm_date($ptp->promise_date) }}</td>
            <td>{{ $ptp->invoice?->assignee?->name ?? $ptp->telecaller?->name }}</td>
            <td><span class="badge-status {{ crm_status_class($ptp->display_status) }}">{{ $ptp->display_status }}</span></td>
            <td class="col-actions">@include('partials.dashboard.action-buttons', ['invoice' => $ptp->invoice])</td>
          </tr>
        @empty
          <tr><td colspan="8"><div class="empty-state"><i class="fa-regular fa-folder-open"></i><div>No active payment promises.</div></div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
