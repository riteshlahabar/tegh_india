<div data-party-name="{{ $party->name }}" data-party-code="{{ $party->code }} · {{ $party->city }}, {{ $party->state }}">
  <div class="section-title" style="font-size:12.5px;">Party Information</div>
  <div class="stat-mini mb-3" style="line-height:1.9;font-size:12.5px;">
    <div class="d-flex justify-content-between"><span class="text-muted">Contact Person</span><span>{{ $party->contact_person }}</span></div>
    <div class="d-flex justify-content-between"><span class="text-muted">Mobile</span><span>{{ $party->mobile }}</span></div>
    <div class="d-flex justify-content-between"><span class="text-muted">Email</span><span>{{ $party->email }}</span></div>
    <div class="d-flex justify-content-between"><span class="text-muted">Sales Person</span><span>{{ $party->sales_person }}</span></div>
    <div class="d-flex justify-content-between"><span class="text-muted">Credit Limit</span><span>{{ inr($party->credit_limit) }}</span></div>
    <div class="d-flex justify-content-between"><span class="text-muted">Payment Terms</span><span>{{ $party->payment_terms }}</span></div>
  </div>
  <div class="row g-2 mb-3">
    <div class="col-12"><div class="stat-mini" style="background:var(--red-bg);"><div class="l" style="color:var(--red);">Total Outstanding</div><div class="v" style="color:var(--red);font-size:19px;">{{ inr($invoices->sum('outstanding')) }}</div></div></div>
  </div>
  <div class="section-title" style="font-size:12.5px;">Invoice Summary</div>
  <div class="table-wrap mb-3">
    <table class="crm-table" style="min-width:0;">
      <thead><tr><th>Invoice</th><th>Due</th><th>Amount</th><th>Outstanding</th><th>Status</th></tr></thead>
      <tbody>
        @foreach($invoices as $invoice)
          <tr>
            <td>{{ $invoice->invoice_no }}</td>
            <td>{{ crm_date($invoice->due_date) }}</td>
            <td>{{ inr($invoice->invoice_amount) }}</td>
            <td>{{ inr($invoice->outstanding) }}</td>
            <td><span class="badge-status {{ crm_status_class($invoice->computed_status) }}">{{ $invoice->computed_status }}</span></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="section-title" style="font-size:12.5px;">Call History</div>
  <div class="timeline mb-3">
    @forelse($calls as $call)
      <div class="timeline-item">
        <div class="timeline-date">{{ crm_date($call->called_at) }} · {{ $call->outcome }}</div>
        <div class="timeline-text">{{ $call->remarks }}</div>
        <div class="timeline-by">By {{ $call->telecaller?->name }}</div>
      </div>
    @empty
      <div class="text-muted" style="font-size:12px;">No calls logged yet.</div>
    @endforelse
  </div>
  <div class="section-title" style="font-size:12.5px;">Follow-up Actions</div>
  @php $first = $party->callableInvoice() ?? $invoices->first(); @endphp
  @if($first)
    <div class="d-flex flex-wrap gap-2 mb-2">
      <button class="btn btn-crm-primary btn-sm" type="button" onclick="openCallModal({{ $first->id }})"><i class="fa-solid fa-phone"></i> Call</button>
      <button class="btn btn-crm-outline btn-sm" type="button" onclick="openWaModal({{ $first->id }})"><i class="fa-brands fa-whatsapp"></i> WhatsApp</button>
      <button class="btn btn-crm-outline btn-sm" type="button" onclick="openEmailModal({{ $first->id }})"><i class="fa-regular fa-envelope"></i> Email</button>
      <button class="btn btn-crm-outline btn-sm" type="button" onclick="openCallModal({{ $first->id }})"><i class="fa-solid fa-comment-dots"></i> Add Remark</button>
    </div>
  @endif
</div>
