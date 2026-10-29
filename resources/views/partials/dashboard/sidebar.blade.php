@php $nav = $activeNav ?? 'dashboard'; @endphp
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebar()"></div>
<div class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-name">TEGH CABLES INDIA</div>
    <div class="brand-sub">Outstanding &amp; Payment Follow-up CRM</div>
  </div>
  <div class="nav-scroll">
    @foreach($navItems as $item)
      <a class="nav-item-crm {{ $nav === $item['id'] ? 'active' : '' }}" href="{{ route($item['route']) }}">
        <i class="{{ $item['icon'] }}"></i><span>{{ $item['label'] }}</span>
      </a>
    @endforeach
  </div>
  <div class="sidebar-footer">
    <div class="avatar-sm">{{ auth()->user()->initials ?? crm_initials(auth()->user()->name) }}</div>
    <div class="who">
      <div class="nm">{{ auth()->user()->name }}</div>
      <div class="rl">{{ auth()->user()->isAdmin() ? 'Tegh Cables Admin' : 'Payment Follow-up Executive' }}</div>
    </div>
    <form method="POST" action="{{ route('logout') }}" class="ms-auto">
      @csrf
      <button type="submit" class="logout-btn btn p-0 border-0 bg-transparent" title="Logout"><i class="fa-solid fa-right-from-bracket"></i></button>
    </form>
  </div>
</div>
