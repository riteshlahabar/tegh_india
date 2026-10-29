@extends('layouts.admin')
@section('content')
<div class="section-card">
  <div class="section-title">Email Follow-ups</div>
  <div class="section-sub">Payment reminder emails sent to customers</div>
  <div class="table-wrap">
    <table class="crm-table">
      <thead><tr><th>Date</th><th>Party</th><th>To</th><th>Subject</th><th>Sent By</th></tr></thead>
      <tbody>
        @forelse($messages as $row)
          <tr>
            <td>{{ crm_date($row->sent_at) }}</td>
            <td>{{ $row->party?->name }}</td>
            <td>{{ $row->to_email }}</td>
            <td>{{ $row->subject }}</td>
            <td>{{ $row->user?->name }}</td>
          </tr>
        @empty
          <tr><td colspan="5"><div class="empty-state"><i class="fa-regular fa-envelope"></i><div>No emails sent yet.</div></div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  {{ $messages->links() }}
</div>
@endsection
