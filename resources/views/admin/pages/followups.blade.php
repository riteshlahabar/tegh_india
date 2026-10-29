@extends('layouts.admin')
@section('content')
<div class="section-card">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
    <div>
      <div class="section-title mb-0">Today's Payment Follow-ups</div>
      <div class="section-sub mb-0">{{ $todayLabel }} — accounts due for calling today</div>
    </div>
    <form method="GET" action="{{ route('admin.followups') }}">
      <input class="form-control form-control-sm" style="width:220px;" name="q" value="{{ $search }}" placeholder="Search party / invoice...">
    </form>
  </div>
  @include('partials.dashboard.followup-table', ['invoices' => $invoices, 'compact' => false])
</div>
@endsection
