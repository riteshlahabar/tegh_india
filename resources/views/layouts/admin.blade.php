<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $docTitle ?? 'Tegh Cables CRM' }}</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="{{ asset('css/crm.css') }}" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
</head>
<body>
<div id="appShell">
@include('partials.dashboard.sidebar')
<div class="main-wrap">
@include('partials.dashboard.topbar')
<div class="content">
    @yield('content')
</div>
</div>
</div>
@include('partials.dashboard.modals')
<div id="toastHost"></div>
@if(session('success'))
<script>window.__crmToast = {msg: @json(session('success')), type: 'success'};</script>
@endif
@if(session('error'))
<script>window.__crmToast = {msg: @json(session('error')), type: 'warn'};</script>
@endif
@if($errors->any())
<script>window.__crmToast = {msg: @json($errors->first()), type: 'warn'};</script>
@endif
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
@include('partials.dashboard.scripts')
@yield('scripts')
</body>
</html>
