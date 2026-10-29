<script>
window.crmRoutes = {
  invoice: @json(url('/admin/invoices')),
  drawer: @json(url('/admin/parties')),
  waStore: @json(route('admin.whatsapp.store')),
};
window.crmCsrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

function showToast(msg, type) {
  const el = document.createElement('div');
  el.className = 'toast-crm ' + (type || '');
  el.innerHTML = '<i class="fa-solid fa-circle-check"></i><span></span>';
  el.querySelector('span').textContent = msg;
  document.getElementById('toastHost').appendChild(el);
  requestAnimationFrame(() => el.classList.add('show'));
  setTimeout(() => { el.classList.remove('show'); setTimeout(() => el.remove(), 300); }, 3200);
}
if (window.__crmToast) showToast(window.__crmToast.msg, window.__crmToast.type);

function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarBackdrop').classList.toggle('open');
}

function isoPlus(days) {
  const d = new Date();
  d.setDate(d.getDate() + days);
  return d.toISOString().slice(0, 10);
}

async function fetchInvoice(id) {
  const res = await fetch(window.crmRoutes.invoice + '/' + id, {
    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
  });
  if (!res.ok) throw new Error('Invoice not found');
  return res.json();
}

async function openPartyDrawer(partyId) {
  const res = await fetch(window.crmRoutes.drawer + '/' + partyId + '/drawer', {
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  });
  const html = await res.text();
  const body = document.getElementById('pdBody');
  body.innerHTML = html;
  const wrap = body.querySelector('[data-party-name]');
  document.getElementById('pdName').textContent = wrap ? wrap.dataset.partyName : 'Party';
  document.getElementById('pdCode').textContent = wrap ? wrap.dataset.partyCode : '';
  bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('partyDrawer')).show();
}

async function openCallModal(id) {
  const r = await fetchInvoice(id);
  document.getElementById('cmInvoiceId').value = r.id;
  document.getElementById('cmParty').textContent = r.party;
  document.getElementById('cmInvoice').textContent = r.invoice_no;
  document.getElementById('cmOutstanding').textContent = r.outstanding_inr;
  document.getElementById('cmPhone').textContent = r.mobile;
  document.getElementById('cmOutcome').value = 'Connected';
  document.getElementById('cmRemarks').value = '';
  document.getElementById('cmPromiseAmt').value = r.outstanding;
  document.getElementById('cmPromiseDate').value = r.promise_date || isoPlus(2);
  document.getElementById('cmNextDate').value = isoPlus(1);
  toggleCmPromise();
  bootstrap.Modal.getOrCreateInstance(document.getElementById('callModal')).show();
}

function toggleCmPromise() {
  const v = document.getElementById('cmOutcome').value;
  document.getElementById('cmPromiseBlock').style.display = v === 'Payment Promise' ? 'block' : 'none';
}

let activeWaInvoiceId = null;
async function openWaModal(id) {
  const r = await fetchInvoice(id);
  activeWaInvoiceId = id;
  document.getElementById('waInvoiceId').value = r.id;
  document.getElementById('waParty').textContent = r.party;
  document.getElementById('waMobile').textContent = r.mobile;
  document.getElementById('waInvoice').textContent = r.invoice_no;
  document.getElementById('waOutstanding').textContent = r.outstanding_inr;
  pickWaTemplate('Payment Reminder');
  bootstrap.Modal.getOrCreateInstance(document.getElementById('waModal')).show();
}

async function pickWaTemplate(name) {
  document.querySelectorAll('#waTemplates .template-pill').forEach(p => p.classList.toggle('active', p.dataset.tpl === name));
  document.getElementById('waTemplateName').value = name;
  if (!activeWaInvoiceId) return;
  const res = await fetch(window.crmRoutes.invoice + '/' + activeWaInvoiceId + '/whatsapp-preview?template=' + encodeURIComponent(name), {
    headers: { 'Accept': 'application/json' }
  });
  const data = await res.json();
  document.getElementById('waMessage').value = data.message;
}

function copyWaMessage() {
  const ta = document.getElementById('waMessage');
  ta.select();
  document.execCommand('copy');
  showToast('Message copied to clipboard.', 'success');
}

async function sendWhatsApp() {
  const body = new FormData(document.getElementById('waForm'));
  const res = await fetch(window.crmRoutes.waStore, {
    method: 'POST',
    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    body
  });
  const data = await res.json();
  if (!res.ok || data.ok === false) {
    showToast(data.message || 'WhatsApp was not sent.', 'warn');
    return;
  }
  showToast(data.message || 'WhatsApp sent.', 'success');
  bootstrap.Modal.getInstance(document.getElementById('waModal')).hide();
}

async function openEmailModal(id) {
  const r = await fetchInvoice(id);
  document.getElementById('emInvoiceId').value = r.id;
  document.getElementById('emTo').value = r.email || '';
  document.getElementById('emSubject').value = 'Payment Reminder – Invoice ' + r.invoice_no + ' – Tegh Cables India Pvt. Ltd.';
  document.getElementById('emBody').value = 'Dear ' + (r.contact || r.party) + ',\n\nThis is a reminder that payment of ' + r.outstanding_inr + ' against Invoice No. ' + r.invoice_no + ' (due ' + r.due_date_label + ') is currently outstanding.\n\nKindly arrange the payment at the earliest and share the remittance details for our reference.\n\nRegards,\nAccounts Receivable Team\nTegh Cables India Pvt. Ltd.';
  bootstrap.Modal.getOrCreateInstance(document.getElementById('emailModal')).show();
}

function runBusySync() {
  document.getElementById('syncSummary').style.display = 'none';
  document.getElementById('syncProgress').style.display = 'block';
  document.getElementById('syncNowBtn').disabled = true;
  const steps = ['ss1','ss2','ss3','ss4','ss5','ss6'];
  steps.forEach(s => {
    document.getElementById(s).classList.remove('done');
    document.getElementById(s).querySelector('i').className = 'fa-regular fa-circle';
  });
  let i = 0;
  const iv = setInterval(() => {
    if (i > 0) {
      const prev = document.getElementById(steps[i - 1]);
      prev.classList.add('done');
      prev.querySelector('i').className = 'fa-solid fa-circle-check';
    }
    if (i >= steps.length) {
      clearInterval(iv);
      document.getElementById('busySyncForm').submit();
      return;
    }
    i++;
  }, 450);
}

document.getElementById('assignMethod')?.addEventListener('change', function () {
  document.getElementById('manualUserBlock').style.display = this.value === 'manual' ? 'block' : 'none';
});
</script>
