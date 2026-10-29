@extends('layouts.admin')
@section('content')
<div class="section-card mb-3 outstanding-screen">
  <form class="filter-bar" method="GET" action="{{ route('admin.outstanding') }}">
    <input class="form-control" name="party" value="{{ $filters['party'] ?? '' }}" placeholder="Search party">
    <input class="form-control" name="invoice" value="{{ $filters['invoice'] ?? '' }}" placeholder="Invoice number">
    <select class="form-select" name="sales">
      <option value="">Sales Person</option>
      @foreach($salesPersons as $person)
        <option value="{{ $person }}" @selected(($filters['sales'] ?? '') === $person)>{{ $person }}</option>
      @endforeach
    </select>
    <select class="form-select" name="branch">
      <option value="">Branch</option>
      @foreach($branches as $branch)
        <option value="{{ $branch }}" @selected(($filters['branch'] ?? '') === $branch)>{{ $branch }}</option>
      @endforeach
    </select>
    <select class="form-select" name="city">
      <option value="">Region</option>
      @foreach($cities as $city)
        <option value="{{ $city }}" @selected(($filters['city'] ?? '') === $city)>{{ $city }}</option>
      @endforeach
    </select>
    <select class="form-select" name="assigned">
      <option value="">Assigned User</option>
      @foreach($telecallers as $user)
        <option value="{{ $user->id }}" @selected((string)($filters['assigned'] ?? '') === (string)$user->id)>{{ $user->name }}</option>
      @endforeach
    </select>
    <select class="form-select" name="status">
      <option value="">Status</option>
      @foreach(['Overdue','Due Today','Promise Given','Paid'] as $st)
        <option value="{{ $st }}" @selected(($filters['status'] ?? '') === $st)>{{ $st }}</option>
      @endforeach
    </select>
    <select class="form-select" name="aging">
      <option value="">Aging</option>
      @foreach(\App\Models\Invoice::AGING_BUCKETS as $bucket)
        <option value="{{ $bucket }}" @selected(($filters['aging'] ?? '') === $bucket)>{{ $bucket }}</option>
      @endforeach
    </select>
    <button class="btn btn-crm-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
    <a class="btn btn-crm-outline" href="{{ route('admin.outstanding') }}">Reset</a>
    <a class="btn btn-crm-outline" href="{{ route('admin.outstanding.export', request()->query()) }}"><i class="fa-solid fa-file-excel"></i> Export Excel</a>
    <button class="btn btn-crm-primary" type="button" style="margin-left:auto;" data-bs-toggle="modal" data-bs-target="#busySyncModal"><i class="fa-solid fa-arrows-rotate"></i> Sync from BUSY</button>
  </form>
  <div class="section-sub">{{ $filteredCount }} of {{ $totalCount }} invoices</div>
  <div class="table-wrap">
    <table class="crm-table">
      <thead>
        <tr>
          <th>Party</th><th class="col-wide">Code</th><th class="col-wide">City</th><th class="col-wide">Contact</th><th class="col-wide">Mobile</th><th class="col-wide">Email</th><th>Invoice No.</th><th class="col-wide">Narration</th>
          <th>Invoice Date</th><th>Due Date</th><th>Invoice Amt</th><th class="col-wide">Received</th><th>Outstanding</th><th>Days Overdue</th>
          <th class="col-wide">Sales Person</th><th class="col-wide">Assigned</th><th>Status</th><th class="col-actions">Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($invoices as $invoice)
          <tr onclick="openPartyDrawer({{ $invoice->party_id }})">
            <td><b>{{ $invoice->party?->name }}</b></td>
            <td class="col-wide">{{ $invoice->party?->code }}</td>
            <td class="col-wide">{{ $invoice->party?->city ?: '-' }}</td>
            <td class="col-wide">{{ $invoice->party?->contact_person }}</td>
            <td class="col-wide">{{ $invoice->party?->mobile }}</td>
            <td class="col-wide">{{ $invoice->party?->email }}</td>
            <td>{{ $invoice->invoice_no }}</td>
            <td class="col-wide">{{ $invoice->narration ?: $invoice->po_no ?: '-' }}</td>
            <td>{{ crm_date($invoice->invoice_date) }}</td>
            <td>{{ crm_date($invoice->due_date) }}</td>
            <td>{{ inr($invoice->invoice_amount) }}</td>
            <td class="col-wide">{{ inr($invoice->received_amount) }}</td>
            <td><b style="color:{{ $invoice->outstanding > 0 ? 'var(--red)' : 'var(--green)' }};">{{ inr($invoice->outstanding) }}</b></td>
            <td>{{ $invoice->days_overdue > 0 ? $invoice->days_overdue : '-' }}</td>
            <td class="col-wide">{{ $invoice->sales_person }}</td>
            <td class="col-wide">{{ $invoice->assignee?->name }}</td>
            <td><span class="badge-status {{ crm_status_class($invoice->computed_status) }}">{{ $invoice->computed_status }}</span></td>
            <td class="col-actions">@include('partials.dashboard.action-buttons', ['invoice' => $invoice])</td>
          </tr>
        @empty
          <tr><td colspan="18"><div class="empty-state"><i class="fa-regular fa-folder-open"></i><div>No records match these filters.</div></div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  {{ $invoices->links() }}
</div>
@endsection
