@extends('layouts.admin')
@section('content')
<div class="section-card">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <div>
      <div class="section-title mb-0">Customers / Parties</div>
      <div class="section-sub mb-0">All accounts synced from BUSY accounting</div>
    </div>
    <form method="GET" action="{{ route('admin.customers') }}">
      <input class="form-control form-control-sm" style="width:220px;" name="q" value="{{ $search }}" placeholder="Search party...">
    </form>
  </div>
  <div class="row g-3">
    @forelse($parties as $party)
      <div class="col-md-6 col-xl-4">
        <div class="section-card h-100" style="cursor:pointer;" onclick="openPartyDrawer({{ $party->id }})">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div style="font-weight:700;font-size:13.5px;color:var(--navy);">{{ $party->name }}</div>
              <div style="font-size:11px;color:var(--muted);">{{ $party->city }}, {{ $party->state }} · {{ $party->code }}</div>
            </div>
            <span class="badge-status {{ crm_status_class($party->rollup_status) }}">{{ $party->rollup_status }}</span>
          </div>
          <hr style="margin:10px 0;">
          <div class="d-flex justify-content-between" style="font-size:12px;"><span class="text-muted">Contact</span><span>{{ $party->contact_person }}</span></div>
          <div class="d-flex justify-content-between" style="font-size:12px;"><span class="text-muted">Invoices</span><span>{{ $party->invoice_count }}</span></div>
          <div class="d-flex justify-content-between" style="font-size:12px;"><span class="text-muted">Total Outstanding</span><b style="color:var(--navy);">{{ inr($party->total_outstanding) }}</b></div>
          <div class="d-flex justify-content-between align-items-center mt-2">
            <span style="font-size:11.5px;color:var(--muted);">{{ $party->mobile ?: 'No mobile saved' }}</span>
            @if($callInvoice = $party->callableInvoice())
              <button class="btn btn-crm-primary btn-sm" type="button" onclick="event.stopPropagation(); openCallModal({{ $callInvoice->id }})"><i class="fa-solid fa-phone"></i> Call</button>
            @endif
          </div>
        </div>
      </div>
    @empty
      <div class="empty-state w-100"><i class="fa-regular fa-folder-open"></i><div>No parties found.</div></div>
    @endforelse
  </div>
</div>
@endsection
