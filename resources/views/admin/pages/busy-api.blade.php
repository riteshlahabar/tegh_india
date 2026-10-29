@extends('layouts.admin')
@section('content')
<div class="section-card mb-3">
  <div class="section-title mb-1">Fetch from BUSY</div>
  <div class="section-sub">Choose a report, fill in the dates if asked, then click Show records. The list appears below.</div>
  <div class="row g-3 align-items-end">
    <div class="col-lg-3">
      <label class="form-label" for="busyCompany" style="font-size:12px;font-weight:600;">Which company?</label>
      <select class="form-select" id="busyCompany">
        @foreach($companies as $company)
          <option value="{{ $company['slug'] }}" @disabled(! $company['configured']) @selected($company['slug'] === $defaultCompany)>
            {{ $company['label'] }}@unless($company['configured']) — no credentials @endunless
          </option>
        @endforeach
      </select>
    </div>
    <div class="col-lg-4">
      <label class="form-label" for="busyReport" style="font-size:12px;font-weight:600;">What do you want to see?</label>
      <select class="form-select" id="busyReport">
        <option value="">Select a report</option>
        @foreach(collect($reports)->groupBy('group') as $group => $items)
          <optgroup label="{{ $group }}">
            @foreach($items as $report)
              <option value="{{ $report['action'] }}">{{ $report['name'] }}</option>
            @endforeach
          </optgroup>
        @endforeach
      </select>
    </div>
    <div class="col-lg-5" id="busyFilters"></div>
  </div>
  <button class="btn btn-crm-primary mt-3" type="button" id="busyShow" disabled><i class="fa-solid fa-table"></i> <span>Show records</span></button>
</div>

<div class="section-card" id="busyResults">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
    <div>
      <div class="section-title mb-0" id="busyTitle">Records</div>
      <div class="section-sub mb-0" id="busyMeta">Select a report to load records from BUSY.</div>
    </div>
    <input class="form-control" id="busySearch" style="max-width:260px;" placeholder="Search these records" disabled>
  </div>
  <div class="table-wrap" style="max-height:65vh;">
    <table class="crm-table" id="busyTable" style="min-width:0;">
      <thead id="busyHead"></thead>
      <tbody id="busyBody">
        <tr><td class="text-muted">No records yet.</td></tr>
      </tbody>
    </table>
  </div>
</div>
@endsection

@section('scripts')
<script>
const busyReports = @json($reports);
const busyDefaults = { from: @json($yearStart), to: @json($today) };
const busyReport = document.getElementById('busyReport');
const busyCompany = document.getElementById('busyCompany');
const busyFilters = document.getElementById('busyFilters');
const busyShow = document.getElementById('busyShow');
const busyTitle = document.getElementById('busyTitle');
const busyMeta = document.getElementById('busyMeta');
const busySearch = document.getElementById('busySearch');
const busyHead = document.getElementById('busyHead');
const busyBody = document.getElementById('busyBody');
let busyRows = [];

function selectedReport() {
  return busyReports.find(function (report) { return report.action === busyReport.value; }) || null;
}

function fieldControl(field) {
  if (field.type === 'select') {
    const select = document.createElement('select');
    select.className = 'form-select';
    field.options.forEach(function (option) {
      const item = document.createElement('option');
      item.value = option.value;
      item.textContent = option.label;
      select.appendChild(item);
    });
    return select;
  }
  if (field.type === 'textarea') {
    const area = document.createElement('textarea');
    area.className = 'form-control';
    area.rows = 3;
    return area;
  }
  const input = document.createElement('input');
  input.className = 'form-control';
  input.type = field.type === 'date' ? 'date' : 'text';
  if (field.type === 'date') {
    input.value = ['End_date', 'ed', 'PriceDate', 'Tran_date', 'Grdate'].includes(field.name) ? busyDefaults.to : busyDefaults.from;
  }
  return input;
}

function renderFilters() {
  const report = selectedReport();
  busyFilters.innerHTML = '';
  busyShow.disabled = !report;
  busyShow.querySelector('span').textContent = report && report.write ? 'Save to BUSY' : 'Show records';
  if (!report) {
    return;
  }
  if (report.fields.length === 0) {
    const note = document.createElement('div');
    note.className = 'text-muted';
    note.style.fontSize = '13px';
    note.textContent = 'Nothing else is needed. Click Show records.';
    busyFilters.appendChild(note);
    return;
  }
  const row = document.createElement('div');
  row.className = 'row g-2';
  report.fields.forEach(function (field) {
    const col = document.createElement('div');
    col.className = 'col-md-4';
    const label = document.createElement('label');
    label.className = 'form-label';
    label.style.fontSize = '12px';
    label.style.fontWeight = '600';
    label.textContent = field.label;
    const control = fieldControl(field);
    control.name = field.name;
    col.append(label, control);
    row.appendChild(col);
  });
  busyFilters.appendChild(row);
}

function heading(key) {
  return String(key).replace(/_/g, ' ').replace(/([a-z])([A-Z])/g, '$1 $2').replace(/\b\w/g, function (letter) {
    return letter.toUpperCase();
  });
}

function cellText(value) {
  if (value === null || value === undefined || value === '') {
    return '—';
  }
  if (Array.isArray(value)) {
    return value.length + (value.length === 1 ? ' item' : ' items');
  }
  if (typeof value === 'object') {
    return Object.entries(value).map(function (entry) {
      return heading(entry[0]) + ': ' + cellText(entry[1]);
    }).join(', ');
  }
  return String(value);
}

function extractRows(payload) {
  const body = payload && payload.success && payload.data && typeof payload.data === 'object' ? payload.data : payload;
  const keys = ['result', 'Result', 'data', 'Data', 'records', 'Records', 'list', 'List', 'accounts', 'outstanding', 'vouchers', 'items'];
  for (let i = 0; i < keys.length; i++) {
    if (body && Array.isArray(body[keys[i]])) {
      return body[keys[i]].filter(function (row) { return row && typeof row === 'object' && !Array.isArray(row); });
    }
  }
  if (Array.isArray(body)) {
    return body.filter(function (row) { return row && typeof row === 'object' && !Array.isArray(row); });
  }
  return [];
}

function statusText(payload) {
  const body = payload && payload.data && typeof payload.data === 'object' ? payload.data : {};
  return body.statusresponse || body.message || body.Message || '';
}

function drawTable(rows) {
  const query = busySearch.value.trim().toLowerCase();
  const visible = rows.filter(function (row) {
    return query === '' || Object.values(row).some(function (value) {
      return cellText(value).toLowerCase().includes(query);
    });
  });
  busyHead.innerHTML = '';
  busyBody.innerHTML = '';
  if (visible.length === 0) {
    const empty = document.createElement('tr');
    const cell = document.createElement('td');
    cell.className = 'text-muted';
    cell.textContent = rows.length === 0 ? 'No records were found.' : 'No records match your search.';
    empty.appendChild(cell);
    busyBody.appendChild(empty);
    return;
  }
  const columns = [];
  visible.forEach(function (row) {
    Object.keys(row).forEach(function (key) {
      if (!columns.includes(key) && (typeof row[key] !== 'object' || row[key] === null)) {
        columns.push(key);
      }
    });
  });
  const headRow = document.createElement('tr');
  columns.forEach(function (key) {
    const th = document.createElement('th');
    th.textContent = heading(key);
    headRow.appendChild(th);
  });
  busyHead.appendChild(headRow);
  visible.forEach(function (row) {
    const tr = document.createElement('tr');
    columns.forEach(function (key) {
      const td = document.createElement('td');
      td.textContent = cellText(row[key]);
      tr.appendChild(td);
    });
    busyBody.appendChild(tr);
  });
}

busyReport.addEventListener('change', renderFilters);
busySearch.addEventListener('input', function () { drawTable(busyRows); });

busyShow.addEventListener('click', async function () {
  const report = selectedReport();
  if (!report) {
    return;
  }
  if (report.write && !window.confirm('This will create a record in BUSY. Continue?')) {
    return;
  }
  const body = new FormData();
  // Selects the credentials, and therefore whose books answer. The controller
  // strips it before forwarding -- Apna Bazar has no company parameter.
  body.append('company', busyCompany.value);
  busyFilters.querySelectorAll('input, select, textarea').forEach(function (field) {
    if (field.name && field.value !== '') {
      body.append(field.name, field.value);
    }
  });
  busyShow.disabled = true;
  busyTitle.textContent = report.name + ' — ' + busyCompany.options[busyCompany.selectedIndex].text.trim();
  busyMeta.textContent = 'Loading records…';
  busySearch.disabled = true;
  busyRows = [];
  busyHead.innerHTML = '';
  busyBody.innerHTML = '<tr><td class="text-muted">Loading records…</td></tr>';
  try {
    const response = await fetch(@json(url('/admin/busy/api')) + '/' + report.action, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: body
    });
    const payload = await response.json();
    if (!response.ok || payload.success === false) {
      busyMeta.textContent = payload.error || 'BUSY could not return this report.';
      busyRows = [];
      drawTable([]);
      return;
    }
    busyRows = extractRows(payload);
    const note = statusText(payload);
    busyMeta.textContent = busyRows.length + (busyRows.length === 1 ? ' record' : ' records') + (note ? ' · ' + note : '');
    busySearch.disabled = busyRows.length === 0;
    busySearch.value = '';
    drawTable(busyRows);
  } catch (error) {
    busyMeta.textContent = 'The report could not be loaded. Please try again.';
  } finally {
    busyShow.disabled = false;
  }
});
</script>
@endsection
