<div class="topbar">
  <button id="burgerBtn" type="button" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
  <div>
    <p class="page-title">{{ $pageTitle ?? 'Dashboard' }}</p>
    <div class="page-sub">{{ $pageSubtitle ?? '' }}</div>
  </div>
  <div class="topbar-right">
    <div class="bell-wrap" data-bs-toggle="dropdown">
      <i class="fa-regular fa-bell"></i>
      @if(($notificationCount ?? 0) > 0)
        <span class="bell-dot">{{ $notificationCount }}</span>
      @endif
    </div>
    <ul class="dropdown-menu dropdown-menu-end" style="width:300px;font-size:12.5px;">
      <li class="dropdown-header" style="font-weight:700;color:var(--navy);">Notifications</li>
      @foreach($notifications ?? [] as $note)
        <li>
          <a class="dropdown-item d-flex gap-2" href="{{ route($note['route']) }}">
            <span>{{ $note['tone'] === 'red' ? '🔴' : ($note['tone'] === 'orange' ? '🟠' : '🔵') }}</span>
            {{ $note['label'] }}
          </a>
        </li>
      @endforeach
    </ul>
    <div class="d-none d-md-block" style="font-size:12px;color:var(--muted);"><i class="fa-regular fa-clock"></i> {{ $clockLabel ?? now()->format('D, d M') }}</div>
  </div>
</div>
