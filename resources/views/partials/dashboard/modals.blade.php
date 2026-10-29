<div class="offcanvas offcanvas-end" style="width:480px;" tabindex="-1" id="partyDrawer">
  <div class="offcanvas-header">
    <div>
      <h6 class="mb-0 font-display" style="font-weight:700;color:var(--navy);" id="pdName">Party Name</h6>
      <div class="text-muted" style="font-size:11.5px;" id="pdCode">Party Code</div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body" id="pdBody"></div>
</div>

<div class="modal fade" id="callModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:14px;">
      <form method="POST" action="{{ route('admin.calls.store') }}">
        @csrf
        <input type="hidden" name="invoice_id" id="cmInvoiceId">
        <div class="modal-header">
          <h6 class="modal-title font-display" style="font-weight:700;">Call Follow-up</h6>
          <button class="btn-close" data-bs-dismiss="modal" type="button"></button>
        </div>
        <div class="modal-body">
          <div class="stat-mini mb-3">
            <div class="d-flex justify-content-between"><span class="text-muted" style="font-size:11.5px;">Party</span><b id="cmParty" style="font-size:13px;"></b></div>
            <div class="d-flex justify-content-between"><span class="text-muted" style="font-size:11.5px;">Invoice</span><span id="cmInvoice" style="font-size:12.5px;"></span></div>
            <div class="d-flex justify-content-between"><span class="text-muted" style="font-size:11.5px;">Outstanding</span><b id="cmOutstanding" style="font-size:13px;color:var(--red);"></b></div>
            <div class="d-flex justify-content-between"><span class="text-muted" style="font-size:11.5px;">Contact Number</span><span id="cmPhone" style="font-size:12.5px;"></span></div>
          </div>
          <div class="mb-2">
            <label class="form-label" style="font-size:12px;font-weight:600;">Call Outcome</label>
            <select class="form-select" name="outcome" id="cmOutcome" onchange="toggleCmPromise()">
              @foreach($callOutcomes as $outcome)
                <option>{{ $outcome }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label" style="font-size:12px;font-weight:600;">Remarks</label>
            <textarea class="form-control" name="remarks" id="cmRemarks" rows="2" placeholder="Enter call remarks..."></textarea>
          </div>
          <div id="cmPromiseBlock" style="display:none;">
            <div class="row g-2 mb-2">
              <div class="col-6">
                <label class="form-label" style="font-size:12px;font-weight:600;">Promise Amount</label>
                <input type="number" class="form-control" name="promise_amount" id="cmPromiseAmt" placeholder="₹">
              </div>
              <div class="col-6">
                <label class="form-label" style="font-size:12px;font-weight:600;">Promise Date</label>
                <input type="date" class="form-control" name="promise_date" id="cmPromiseDate">
              </div>
            </div>
          </div>
          <div class="mb-1">
            <label class="form-label" style="font-size:12px;font-weight:600;">Next Follow-up Date</label>
            <input type="date" class="form-control" name="next_followup_on" id="cmNextDate">
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-crm-outline" data-bs-dismiss="modal" type="button">Cancel</button>
          <button class="btn btn-crm-primary" type="submit">Save Follow-up</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="waModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="border-radius:14px;">
      <form id="waForm">
        @csrf
        <input type="hidden" name="invoice_id" id="waInvoiceId">
        <input type="hidden" name="template" id="waTemplateName" value="Payment Reminder">
        <div class="modal-header">
          <h6 class="modal-title font-display" style="font-weight:700;"><i class="fa-brands fa-whatsapp" style="color:#25D366;"></i> Send WhatsApp Reminder</h6>
          <button class="btn-close" data-bs-dismiss="modal" type="button"></button>
        </div>
        <div class="modal-body">
          <div class="row g-2 mb-3">
            <div class="col-6"><div class="stat-mini"><div class="l">Party</div><div class="v" style="font-size:13px;" id="waParty"></div></div></div>
            <div class="col-6"><div class="stat-mini"><div class="l">Mobile</div><div class="v" style="font-size:13px;" id="waMobile"></div></div></div>
            <div class="col-6"><div class="stat-mini"><div class="l">Invoice</div><div class="v" style="font-size:13px;" id="waInvoice"></div></div></div>
            <div class="col-6"><div class="stat-mini"><div class="l">Outstanding</div><div class="v" style="font-size:13px;" id="waOutstanding"></div></div></div>
          </div>
          <label class="form-label" style="font-size:12px;font-weight:600;">Message Template</label>
          <div class="row g-2 mb-3" id="waTemplates">
            @foreach($waTemplates as $i => $name)
              <div class="col-6 col-md-4">
                <div class="template-pill {{ $i === 0 ? 'active' : '' }}" data-tpl="{{ $name }}" onclick="pickWaTemplate('{{ $name }}')">{{ $name }}</div>
              </div>
            @endforeach
          </div>
          <label class="form-label" style="font-size:12px;font-weight:600;">Message Preview</label>
          <textarea class="form-control" name="message" id="waMessage" rows="6"></textarea>
        </div>
        <div class="modal-footer">
          <button class="btn btn-crm-outline" type="button" onclick="copyWaMessage()"><i class="fa-regular fa-copy"></i> Copy Message</button>
          <button class="btn btn-crm-primary" type="button" onclick="sendWhatsApp()" style="background:#25D366;border-color:#25D366;"><i class="fa-brands fa-whatsapp"></i> Send WhatsApp</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="emailModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="border-radius:14px;">
      <form method="POST" action="{{ route('admin.email.store') }}">
        @csrf
        <input type="hidden" name="invoice_id" id="emInvoiceId">
        <div class="modal-header">
          <h6 class="modal-title font-display" style="font-weight:700;"><i class="fa-regular fa-envelope"></i> Send Email Reminder</h6>
          <button class="btn-close" data-bs-dismiss="modal" type="button"></button>
        </div>
        <div class="modal-body">
          <div class="row g-2 mb-2">
            <div class="col-6"><label class="form-label" style="font-size:12px;font-weight:600;">To</label><input class="form-control" name="to_email" id="emTo" required></div>
            <div class="col-6"><label class="form-label" style="font-size:12px;font-weight:600;">CC</label><input class="form-control" name="cc" id="emCc" value="accounts@teghcables.com"></div>
          </div>
          <div class="mb-2"><label class="form-label" style="font-size:12px;font-weight:600;">Subject</label><input class="form-control" name="subject" id="emSubject"></div>
          <div class="mb-1"><label class="form-label" style="font-size:12px;font-weight:600;">Message</label><textarea class="form-control" name="body" id="emBody" rows="8"></textarea></div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-crm-outline" data-bs-dismiss="modal" type="button">Cancel</button>
          <button class="btn btn-crm-primary" type="submit">Send Email</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="busySyncModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:14px;">
      <div class="modal-header">
        <h6 class="modal-title font-display" style="font-weight:700;">BUSY Cloud Sync</h6>
        <button class="btn-close" data-bs-dismiss="modal" type="button"></button>
      </div>
      <div class="modal-body">
        <div id="syncSummary">
          <div class="d-flex justify-content-between mb-1"><span class="text-muted" style="font-size:12.5px;">Last Sync</span><b style="font-size:12.5px;">{{ $lastBusySync ?? 'Never' }}</b></div>
          <div class="d-flex justify-content-between mb-1"><span class="text-muted" style="font-size:12.5px;">Status</span><span class="badge-status badge-paid">Connected</span></div>
          <div class="d-flex justify-content-between mb-3"><span class="text-muted" style="font-size:12.5px;">Source</span><b style="font-size:12.5px;">BUSY remains the accounting master</b></div>
        </div>
        <div id="syncProgress" style="display:none;">
          <div class="sync-step" id="ss1"><i class="fa-regular fa-circle"></i> Connecting to BUSY...</div>
          <div class="sync-step" id="ss2"><i class="fa-regular fa-circle"></i> Fetching outstanding...</div>
          <div class="sync-step" id="ss3"><i class="fa-regular fa-circle"></i> Updating invoices...</div>
          <div class="sync-step" id="ss4"><i class="fa-regular fa-circle"></i> Updating payments...</div>
          <div class="sync-step" id="ss5"><i class="fa-regular fa-circle"></i> Updating CRM...</div>
          <div class="sync-step" id="ss6" style="font-weight:700;"><i class="fa-regular fa-circle"></i> Sync Completed Successfully.</div>
        </div>
        <div class="text-muted mt-2" style="font-size:11px;">Invoice amounts, dates and payments are never edited manually here.</div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-crm-outline" data-bs-dismiss="modal" type="button">Close</button>
        <form method="POST" action="{{ route('admin.busy.sync') }}" id="busySyncForm">
          @csrf
          <button class="btn btn-crm-primary" id="syncNowBtn" type="button" onclick="runBusySync()"><i class="fa-solid fa-arrows-rotate"></i> Sync Now</button>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="assignModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:14px;">
      <form method="POST" action="{{ route('admin.assign') }}">
        @csrf
        <div class="modal-header">
          <h6 class="modal-title font-display" style="font-weight:700;">Assign Party</h6>
          <button class="btn-close" data-bs-dismiss="modal" type="button"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label" style="font-size:12px;font-weight:600;">Assignment Method</label>
            <select class="form-select" name="method" id="assignMethod">
              <option value="manual">Manual Assignment</option>
              <option value="round_robin">Round Robin Assignment</option>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label" style="font-size:12px;font-weight:600;">Select Party</label>
            <select class="form-select" name="party_id" required>
              @foreach($parties as $party)
                <option value="{{ $party->id }}">{{ $party->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-2" id="manualUserBlock">
            <label class="form-label" style="font-size:12px;font-weight:600;">Assign To</label>
            <select class="form-select" name="user_id">
              @foreach($telecallers as $user)
                <option value="{{ $user->id }}">{{ $user->name }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-crm-outline" data-bs-dismiss="modal" type="button">Cancel</button>
          <button class="btn btn-crm-primary" type="submit">Assign</button>
        </div>
      </form>
    </div>
  </div>
</div>
