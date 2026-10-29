@php
  $compact = $compact ?? false;
  $rows = $invoices ?? collect();
@endphp
<div class="table-wrap">
<table class="crm-table">
  <thead>
    <tr>
      <th>Priority</th>
      <th>Party</th>
      <th>Contact</th>
      <th>Invoice</th>
      <th>Due Date</th>
      <th>Outstanding</th>
      @unless($compact)
        <th>Last Follow-up</th>
        <th>Next Follow-up</th>
        <th>PTP Date</th>
        <th>Assigned</th>
      @endunless
      <th>Status</th>
      <th class="col-actions">Actions</th>
    </tr>
  </thead>
  <tbody>
    @forelse($rows as $invoice)
      <tr onclick="openPartyDrawer({{ $invoice->party_id }})">
        <td><span class="priority-dot {{ crm_priority_class($invoice->priority) }}"></span></td>
        <td>
          <b>{{ $invoice->party?->name }}</b>
          <div style="font-size:10.5px;color:var(--muted);">{{ $invoice->party?->city }}</div>
        </td>
        <td>{{ $invoice->party?->contact_person }}</td>
        <td>{{ $invoice->invoice_no }}</td>
        <td>{{ crm_date($invoice->due_date) }}</td>
        <td><b style="color:{{ $invoice->computed_status === 'Overdue' ? 'var(--red)' : 'var(--navy)' }};">{{ inr($invoice->outstanding) }}</b></td>
        @unless($compact)
          <td>{{ $invoice->last_followup_at ? crm_date($invoice->last_followup_at) : '-' }}</td>
          <td>{{ $invoice->next_followup_on ? crm_date($invoice->next_followup_on) : '-' }}</td>
          <td>{{ $invoice->activePromise ? crm_date($invoice->activePromise->promise_date) : '-' }}</td>
          <td>{{ $invoice->assignee?->name ?? '-' }}</td>
        @endunless
        <td><span class="badge-status {{ crm_status_class($invoice->computed_status) }}">{{ $invoice->computed_status }}</span></td>
        <td class="col-actions">@include('partials.dashboard.action-buttons', ['invoice' => $invoice])</td>
      </tr>
    @empty
      <tr>
        <td colspan="12">
          <div class="empty-state"><i class="fa-regular fa-folder-open"></i><div>No follow-ups match your filters.</div></div>
        </td>
      </tr>
    @endforelse
  </tbody>
</table>
</div>
