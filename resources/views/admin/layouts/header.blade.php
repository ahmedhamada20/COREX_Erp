<div class="loader-bg">
    <div class="loader-track">
        <div class="loader-fill"></div>
    </div>
</div>
<!-- { Pre-loader } End -->
<!-- { Header } start -->
<header class="site-header">
    <div class="header-wrapper">
        <div class="me-auto flex-grow-1 d-flex align-items-center">
            <ul class="list-unstyled header-menu-nav">
                <li class="hdr-itm mob-hamburger">
                    <a href="#!" class="app-head-link" id="mobile-collapse">
                        <div class="hamburger hamburger-arrowturn">
                            <div class="hamburger-box">
                                <div class="hamburger-inner"></div>
                            </div>
                        </div>
                    </a>
                </li>
            </ul>
            <div class="d-none d-md-none d-lg-block header-search ms-3">
                <form action="#">
                    <div class="input-group ">
                        <input class="form-control rounded-3" type="search" value="" id="searchInput" placeholder="Search">
                        <div class="search-btn">
                            <button class="p-0 btn rounded-0 rounded-end" type="button">
                                <i data-feather="search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <nav class="ms-auto">
            <ul class="header-menu-nav list-unstyled">
                <li class="hdr-itm dropdown ntf-dropdown">
                    <a href="#" class="app-head-link dropdown-toggle no-caret" data-bs-toggle="dropdown"
                       role="button" aria-haspopup="false" aria-expanded="false" id="notif-bell">
                        <i class="ti ti-bell"></i>
                        @php $unreadCount = auth()->user()?->unreadNotifications()->count() ?? 0; @endphp
                        @if($unreadCount > 0)
                            <span class="bg-danger h-dots" id="notif-badge">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                        @else
                            <span class="h-dots d-none" id="notif-badge"></span>
                        @endif
                    </a>
                    <div class="dropdown-menu header-dropdown dropdown-menu-end" style="min-width:340px;">
                        <div class="notification-header d-flex align-items-center justify-content-between px-3 py-2">
                            <h6 class="m-0 fw-bold"><i class="ti ti-bell me-1 text-primary"></i> الإشعارات</h6>
                            <button class="btn btn-sm btn-outline-secondary" onclick="markAllRead()" type="button">
                                قراءة الكل
                            </button>
                        </div>
                        <div class="notification-list" style="max-height:320px;overflow-y:auto;" id="notif-list">
                            @php
                                $notifications = auth()->user()?->notifications()->latest()->limit(10)->get() ?? collect();
                            @endphp
                            @forelse($notifications as $notif)
                                <div class="d-flex align-items-start px-3 py-2 border-bottom {{ $notif->read_at ? '' : 'bg-light' }}"
                                     id="notif-{{ $notif->id }}">
                                    <div class="flex-grow-1 small">
                                        <div class="fw-semibold text-dark">
                                            @if(($notif->data['type'] ?? '') === 'invoice_posted')
                                                <i class="ti ti-file-invoice text-success me-1"></i>
                                            @elseif(($notif->data['type'] ?? '') === 'low_stock')
                                                <i class="ti ti-alert-triangle text-warning me-1"></i>
                                            @else
                                                <i class="ti ti-info-circle text-info me-1"></i>
                                            @endif
                                            {{ $notif->data['message'] ?? 'إشعار جديد' }}
                                        </div>
                                        <div class="text-muted" style="font-size:.75rem;">
                                            {{ $notif->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm p-0 ms-2 text-muted"
                                            onclick="deleteNotif('{{ $notif->id }}')">
                                        <i class="ti ti-x"></i>
                                    </button>
                                </div>
                            @empty
                                <div class="text-center text-muted py-4" id="notif-empty">
                                    <i class="ti ti-bell-off fs-4 d-block mb-1"></i>
                                    لا توجد إشعارات
                                </div>
                            @endforelse
                        </div>
                        <div class="notification-footer px-3 py-2">
                            <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-outline-primary w-100">
                                عرض كل الإشعارات
                            </a>
                        </div>
                    </div>
                </li>

                <script>
                function markAllRead() {
                    fetch('{{ route('notifications.mark_all_read') }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
                    }).then(() => {
                        document.querySelectorAll('#notif-list .bg-light').forEach(el => el.classList.remove('bg-light'));
                        document.getElementById('notif-badge').classList.add('d-none');
                    });
                }
                function deleteNotif(id) {
                    fetch('/notifications/' + id, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
                    }).then(() => {
                        const el = document.getElementById('notif-' + id);
                        if (el) el.remove();
                    });
                }
                </script>

                <li class="hdr-itm dropdown user-dropdown ">
                    <a class="app-head-link dropdown-toggle no-caret me-0" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                        <span class="avtar"><img src="{{asset('dash/assets/images/user/avatar-2.jpg')}}" alt=""></span>
                    </a>
                    <div class="dropdown-menu header-dropdown">
                        <ul class="p-0">


                            <li class="dropdown-item ">
                                <a href="#" class="drp-link">
                                    <span>Account Settings</span>
                                </a>
                            </li>


                            <hr class="dropdown-divider">
                            <li class="dropdown-item">
                                <a href="#"
                                   class="drp-link"
                                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i data-feather="log-out"></i>
                                    <span>تسجيل الخروج</span>
                                </a>

                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </li>

                        </ul>
                    </div>
                </li>
            </ul>
        </nav>
    </div>
</header>
