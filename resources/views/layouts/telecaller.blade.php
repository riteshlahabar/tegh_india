<!DOCTYPE html>
<html class="light" lang="en">
<head>
@include('partials.dashboard.head')
<title>{{ $docTitle ?? 'Inside Sales | Tegh India CRM' }}</title>
</head>
<body class="bg-surface text-on-surface font-sans font-body-md overflow-x-hidden">

@if(session('success'))
<div id="toast-success" class="fixed bottom-4 right-4 left-4 sm:left-auto sm:bottom-6 sm:right-6 bg-primary text-white border border-outline-variant/30 px-4 sm:px-6 py-3 sm:py-4 rounded-xl shadow-2xl flex items-center gap-3 z-50">
    <span class="material-symbols-outlined text-green-400">check_circle</span>
    <span class="font-body-sm font-semibold flex-1">{{ session('success') }}</span>
    <button onclick="document.getElementById('toast-success').remove()" class="ml-2 hover:opacity-80"><span class="material-symbols-outlined text-sm">close</span></button>
</div>
@endif
@if(session('error'))
<div id="toast-error" class="fixed bottom-4 right-4 left-4 sm:left-auto sm:bottom-6 sm:right-6 bg-red-700 text-white px-4 sm:px-6 py-3 sm:py-4 rounded-xl shadow-2xl flex items-center gap-3 z-50">
    <span class="material-symbols-outlined">error</span>
    <span class="font-body-sm font-semibold flex-1">{{ session('error') }}</span>
    <button onclick="document.getElementById('toast-error').remove()" class="ml-2 hover:opacity-80"><span class="material-symbols-outlined text-sm">close</span></button>
</div>
@endif
@if($errors->any())
<div id="toast-validation" class="fixed bottom-4 right-4 left-4 sm:left-auto sm:bottom-6 sm:right-6 bg-red-700 text-white px-4 sm:px-6 py-3 sm:py-4 rounded-xl shadow-2xl flex items-center gap-3 z-50 max-w-md">
    <span class="material-symbols-outlined">error</span>
    <span class="font-body-sm font-semibold flex-1">{{ $errors->first() }}</span>
    <button onclick="document.getElementById('toast-validation').remove()" class="ml-2 hover:opacity-80"><span class="material-symbols-outlined text-sm">close</span></button>
</div>
@endif

@include('partials.telecaller.sidebar')
@include('partials.telecaller.topbar')

<main class="ml-0 lg:ml-[260px] pt-20 sm:pt-24 min-h-[100dvh] px-3 sm:px-4 md:px-6 lg:px-xl pb-6 sm:pb-xl min-w-0 max-w-full lg:max-w-[calc(100%-260px)]">
    @yield('content')
</main>

<script>
    function toggleTelecallerSidebar() {
        const sidebar = document.getElementById('telecaller-sidebar');
        if (sidebar.classList.contains('admin-sidebar-open')) {
            closeTelecallerSidebar();
            return;
        }
        sidebar.classList.remove('-translate-x-full');
        sidebar.classList.add('admin-sidebar-open');
        document.body.classList.add('admin-sidebar-open');
        document.getElementById('telecaller-backdrop')?.classList.remove('hidden');
    }
    function closeTelecallerSidebar() {
        const sidebar = document.getElementById('telecaller-sidebar');
        sidebar.classList.add('-translate-x-full');
        sidebar.classList.remove('admin-sidebar-open');
        document.body.classList.remove('admin-sidebar-open');
        document.getElementById('telecaller-backdrop')?.classList.add('hidden');
    }
</script>
</body>
</html>
