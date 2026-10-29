@extends('layouts.admin')
@section('content')
@if(auth()->user()->isAdmin())
<div class="section-card mb-3">
  <div class="section-title">WhatsApp connection</div>
  <div class="section-sub">Messages go out from {{ $greenPhone !== '' ? $greenPhone : 'the linked Tegh number' }}. This uses the same GREEN-API session as the other Tegh app.</div>
  <form method="POST" action="{{ route('admin.whatsapp.settings') }}">
    @csrf
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label" style="font-size:12px;font-weight:600;">API URL</label>
        <input class="form-control" name="greenapi_api_url" value="{{ old('greenapi_api_url', $greenApiUrl) }}" required>
      </div>
      <div class="col-md-6">
        <label class="form-label" style="font-size:12px;font-weight:600;">Media URL</label>
        <input class="form-control" name="greenapi_media_url" value="{{ old('greenapi_media_url', $greenMediaUrl) }}" required>
      </div>
      <div class="col-md-4">
        <label class="form-label" style="font-size:12px;font-weight:600;">Instance ID</label>
        <input class="form-control" name="greenapi_id_instance" value="{{ old('greenapi_id_instance', $greenInstanceId) }}" required>
      </div>
      <div class="col-md-4">
        <label class="form-label" style="font-size:12px;font-weight:600;">Sender phone</label>
        <input class="form-control" name="greenapi_phone" value="{{ old('greenapi_phone', $greenPhone) }}">
      </div>
      <div class="col-md-4">
        <label class="form-label" style="font-size:12px;font-weight:600;">API token</label>
        <input class="form-control" type="password" name="greenapi_api_token" value="" placeholder="{{ $hasGreenToken ? '•••••••• (leave blank to keep)' : 'Token from GREEN-API' }}" autocomplete="new-password">
      </div>
    </div>
    <button class="btn btn-crm-primary btn-sm mt-3" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save WhatsApp connection</button>
  </form>
</div>
@endif
<div class="section-card">
  <div class="section-title">WhatsApp Follow-ups</div>
  <div class="section-sub">Messages sent to customers regarding outstanding payments</div>
  <div class="table-wrap">
    <table class="crm-table">
      <thead><tr><th>Date</th><th>Party</th><th>Mobile</th><th>Invoice</th><th>Sent By</th><th>Message</th></tr></thead>
      <tbody>
        @forelse($messages as $row)
          <tr>
            <td>{{ crm_date($row->sent_at) }}</td>
            <td>{{ $row->party?->name }}</td>
            <td>{{ $row->mobile }}</td>
            <td>{{ $row->invoice?->invoice_no }}</td>
            <td>{{ $row->user?->name }}</td>
            <td style="white-space:normal;max-width:360px;">{{ \Illuminate\Support\Str::limit($row->message, 80) }}</td>
          </tr>
        @empty
          <tr><td colspan="6"><div class="empty-state"><i class="fa-brands fa-whatsapp"></i><div>No WhatsApp messages sent yet.</div></div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  {{ $messages->links() }}
</div>
@endsection
