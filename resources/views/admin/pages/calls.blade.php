@extends('layouts.admin')
@section('content')
<div class="section-card">
  <form class="filter-bar" method="GET" action="{{ route('admin.calls') }}">
    <input type="date" class="form-control" style="width:160px;" name="date" value="{{ $filters['date'] ?? '' }}">
    <select class="form-select" style="width:160px;" name="telecaller">
      <option value="">Telecaller</option>
      @foreach($telecallers as $user)
        <option value="{{ $user->id }}" @selected((string)($filters['telecaller'] ?? '') === (string)$user->id)>{{ $user->name }}</option>
      @endforeach
    </select>
    <select class="form-select" style="width:170px;" name="outcome">
      <option value="">Call Outcome</option>
      @foreach($callOutcomes as $outcome)
        <option value="{{ $outcome }}" @selected(($filters['outcome'] ?? '') === $outcome)>{{ $outcome }}</option>
      @endforeach
    </select>
    <input class="form-control" style="width:180px;" name="party" value="{{ $filters['party'] ?? '' }}" placeholder="Party">
    <button class="btn btn-crm-primary" type="submit">Filter</button>
    <a class="btn btn-crm-outline" href="{{ route('admin.calls') }}">Reset</a>
  </form>
  <div class="section-title mb-0">Call History</div>
  <div class="section-sub">Complete log of telecaller follow-up calls</div>
  <div class="table-wrap">
    <table class="crm-table">
      <thead><tr><th>Date</th><th>Time</th><th>Party</th><th>Contact</th><th>Invoice</th><th>Outstanding</th><th>Telecaller</th><th>Outcome</th><th>Remarks</th><th>Next Follow-up</th><th class="col-actions">Action</th></tr></thead>
      <tbody>
        @forelse($calls as $call)
          <tr onclick="openPartyDrawer({{ $call->party_id }})">
            <td>{{ crm_date($call->called_at) }}</td>
            <td>{{ $call->called_at?->format('h:i A') }}</td>
            <td><b>{{ $call->party?->name }}</b></td>
            <td>{{ $call->party?->contact_person }}</td>
            <td>{{ $call->invoice?->invoice_no }}</td>
            <td>{{ inr($call->invoice?->outstanding) }}</td>
            <td>{{ $call->telecaller?->name }}</td>
            <td>{{ $call->outcome }}</td>
            <td style="white-space:normal;max-width:220px;">{{ $call->remarks }}</td>
            <td>{{ $call->next_followup_on ? crm_date($call->next_followup_on) : '-' }}</td>
            <td class="col-actions">@include('partials.dashboard.action-buttons', ['invoice' => $call->invoice])</td>
          </tr>
        @empty
          <tr><td colspan="11"><div class="empty-state"><i class="fa-regular fa-folder-open"></i><div>No calls match your filters.</div></div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  {{ $calls->links() }}
</div>
@endsection
