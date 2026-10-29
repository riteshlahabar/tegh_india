<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login | Tegh Cables CRM</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="{{ asset('css/crm.css') }}" rel="stylesheet">
</head>
<body>
<div id="loginScreen">
  <div class="login-card">
    <div class="login-logo">TC</div>
    <h4 class="font-display" style="color:var(--navy);font-weight:700;">Tegh Cables India Pvt. Ltd.</h4>
    <div class="login-sub">Outstanding &amp; Payment Follow-up CRM</div>
    @if ($errors->any())
      <div class="login-error" style="display:block;">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('login.submit') }}">
      @csrf
      <div class="mb-3">
        <label class="form-label" style="font-size:12px;font-weight:600;color:var(--muted);">Username</label>
        <input type="text" name="username" id="loginUser" value="{{ old('username') }}" class="form-control" placeholder="e.g. admin" autocomplete="username" required autofocus>
      </div>
      <div class="mb-3">
        <label class="form-label" style="font-size:12px;font-weight:600;color:var(--muted);">Password</label>
        <input type="password" name="password" id="loginPass" class="form-control" placeholder="••••" autocomplete="current-password" required>
      </div>
      <button type="submit" class="btn btn-crm-primary w-100 py-2" style="border-radius:8px;">Login to CRM</button>
    </form>
    <div style="margin-top:18px;font-size:11px;color:var(--muted);font-weight:600;">DEMO ACCOUNTS — click to autofill</div>
    <div class="demo-users">
      <span class="demo-chip" data-user="admin">admin (Admin)</span>
      <span class="demo-chip" data-user="aakanksha">aakanksha</span>
      <span class="demo-chip" data-user="javed">javed</span>
      <span class="demo-chip" data-user="amol">amol</span>
    </div>
  </div>
</div>
<script>
document.querySelectorAll('.demo-chip').forEach(function (chip) {
  chip.addEventListener('click', function () {
    document.getElementById('loginUser').value = chip.dataset.user;
    document.getElementById('loginPass').value = '1234';
  });
});
</script>
</body>
</html>
