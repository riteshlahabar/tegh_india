@extends('layouts.admin')
@section('content')
<div class="row g-3">
  <div class="col-lg-6">
    <div class="section-card">
      <div class="section-title">General Settings</div>
      <div class="section-sub">Company profile and CRM preferences</div>
      <div class="mb-2"><label class="form-label" style="font-size:12px;font-weight:600;">Company Name</label><input class="form-control" value="{{ $companyName }}" disabled></div>
      <div class="mb-2"><label class="form-label" style="font-size:12px;font-weight:600;">GSTIN</label><input class="form-control" value="{{ $gstin }}" disabled></div>
      <div class="mb-2"><label class="form-label" style="font-size:12px;font-weight:600;">Registered Office</label><input class="form-control" value="{{ $office }}" disabled></div>
      <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        <div class="mb-2">
          <label class="form-label" style="font-size:12px;font-weight:600;">Default Follow-up Reminder</label>
          <input class="form-control" name="followup_reminder" value="{{ $reminder }}" {{ auth()->user()->isAdmin() ? '' : 'disabled' }}>
        </div>
        @if(auth()->user()->isAdmin())
          <button class="btn btn-crm-primary btn-sm" type="submit">Save</button>
        @endif
      </form>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="section-card">
      <div class="section-title">Data Source (BUSY Accounting)</div>
      <div class="section-sub">BUSY remains the accounting master. This CRM is the follow-up / collection master and does not permit manual edits to invoice amounts.</div>
      <div class="stat-mini mb-2 d-flex justify-content-between align-items-center">
        <span><i class="fa-solid fa-circle" style="color:{{ str_contains(strtolower($busyStatus), 'connected') ? 'var(--green)' : '#dc3545' }};font-size:8px;"></i> {{ $busyStatus }} to BUSY Cloud</span>
        <span class="text-muted" style="font-size:11px;">Last sync: {{ $lastSync }}</span>
      </div>
      <button class="btn btn-crm-outline w-100 mb-3" data-bs-toggle="modal" data-bs-target="#busySyncModal"><i class="fa-solid fa-arrows-rotate"></i> Manage Sync</button>
      @if(auth()->user()->isAdmin())
        <form method="POST" action="{{ route('admin.settings.update') }}" class="mb-3">
          @csrf
          <div class="mb-2">
            <label class="form-label" style="font-size:12px;font-weight:600;">Apna Bazar / BUSY API URL</label>
            <input class="form-control" name="busy_base_url" value="{{ old('busy_base_url', $busyBaseUrl) }}" placeholder="http://api.apnabazarapp.in/WebAPI/V2">
          </div>
          <div class="section-sub mb-2">Each company has its own Apna Bazar login — the API has no company code, so the login is what decides whose books are returned. Leave a company blank to skip it during sync.</div>
          @foreach($busyCompanies as $company)
            <div class="stat-mini mb-2">
              <div style="font-size:12px;font-weight:600;margin-bottom:6px;">
                {{ $company['label'] }}
                @if($company['username'] === '' || ! $company['hasPassword'])
                  <span class="text-muted" style="font-weight:400;">— not configured</span>
                @endif
              </div>
              <div class="mb-2">
                <label class="form-label" style="font-size:11px;">Username</label>
                <input class="form-control" name="busy_credentials[{{ $company['slug'] }}][username]" value="{{ old('busy_credentials.'.$company['slug'].'.username', $company['username']) }}" autocomplete="off">
              </div>
              <div>
                <label class="form-label" style="font-size:11px;">Password</label>
                <input class="form-control" type="password" name="busy_credentials[{{ $company['slug'] }}][password]" value="" placeholder="{{ $company['hasPassword'] ? '•••••••• (leave blank to keep)' : 'Password provided by Apna Bazar' }}" autocomplete="new-password">
              </div>
            </div>
          @endforeach
          <button class="btn btn-crm-primary btn-sm" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save BUSY credentials</button>
        </form>
      @endif
      <hr>
      <div class="section-title" style="font-size:13px;">Access &amp; Roles</div>
      <div class="section-sub">Telecallers can add remarks, calls, WhatsApp/email logs and payment promises. They cannot edit invoice amount, invoice date, received amount or outstanding — these sync from BUSY only.</div>
    </div>
  </div>

  @if(auth()->user()->isAdmin())
  <div class="col-12">
    <div class="section-card">
      <div class="section-title">SMTP Email Settings</div>
      <div class="section-sub">Configure outbound mail for outstanding reminders and collection emails. Leave disabled to keep the default log/mailer.</div>
      <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        <input type="hidden" name="followup_reminder" value="{{ $reminder }}">
        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" role="switch" id="smtpEnabled" name="smtp_enabled" value="1" {{ $smtpEnabled ? 'checked' : '' }}>
          <label class="form-check-label" for="smtpEnabled" style="font-size:13px;font-weight:600;">Enable custom SMTP</label>
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label" style="font-size:12px;font-weight:600;">SMTP Host</label>
            <input class="form-control" name="smtp_host" value="{{ old('smtp_host', $smtpHost) }}" placeholder="smtp.gmail.com">
          </div>
          <div class="col-md-3">
            <label class="form-label" style="font-size:12px;font-weight:600;">Port</label>
            <input class="form-control" type="number" name="smtp_port" value="{{ old('smtp_port', $smtpPort) }}" placeholder="587">
          </div>
          <div class="col-md-3">
            <label class="form-label" style="font-size:12px;font-weight:600;">Encryption</label>
            <select class="form-select" name="smtp_encryption">
              <option value="tls" @selected(old('smtp_encryption', $smtpEncryption) === 'tls')>TLS</option>
              <option value="ssl" @selected(old('smtp_encryption', $smtpEncryption) === 'ssl')>SSL</option>
              <option value="none" @selected(old('smtp_encryption', $smtpEncryption) === 'none')>None</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" style="font-size:12px;font-weight:600;">Username</label>
            <input class="form-control" name="smtp_username" value="{{ old('smtp_username', $smtpUsername) }}" placeholder="your@email.com" autocomplete="off">
          </div>
          <div class="col-md-6">
            <label class="form-label" style="font-size:12px;font-weight:600;">Password</label>
            <input class="form-control" type="password" name="smtp_password" value="" placeholder="{{ $smtpPassword ? '•••••••• (leave blank to keep)' : 'SMTP password / app password' }}" autocomplete="new-password">
          </div>
          <div class="col-md-6">
            <label class="form-label" style="font-size:12px;font-weight:600;">From Email</label>
            <input class="form-control" type="email" name="smtp_from_address" value="{{ old('smtp_from_address', $smtpFromAddress) }}" placeholder="noreply@teghcables.com">
          </div>
          <div class="col-md-6">
            <label class="form-label" style="font-size:12px;font-weight:600;">From Name</label>
            <input class="form-control" name="smtp_from_name" value="{{ old('smtp_from_name', $smtpFromName) }}" placeholder="Tegh Cables CRM">
          </div>
        </div>
        <div class="mt-3 d-flex gap-2 align-items-center">
          <button class="btn btn-crm-primary btn-sm" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save SMTP</button>
          <span class="text-muted" style="font-size:11px;">Used when sending email reminders from the CRM.</span>
        </div>
      </form>
    </div>
  </div>
  @endif
</div>
@endsection
