@extends('admin.layouts.master')

@section('title', 'الإشعارات')

@section('content')
    <div class="row">
        <div class="col-12">
            @include('admin.Alerts')

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-2"
                     style="background:linear-gradient(135deg,#6f42c1,#5a32a3);border-radius:.5rem;">
                    <div>
                        <h4 class="text-white fw-bold mb-1"><i class="ti ti-bell me-1"></i> مركز الإشعارات</h4>
                        <p class="text-white-50 mb-0">إجمالي الإشعارات: {{ $notifications->total() }}</p>
                    </div>
                    <form method="POST" action="{{ route('notifications.mark_all_read') }}">
                        @csrf
                        <button class="btn btn-light btn-sm">
                            <i class="ti ti-checks me-1"></i> تعليم الكل كمقروء
                        </button>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    @forelse($notifications as $notif)
                        <div class="d-flex align-items-start px-4 py-3 border-bottom {{ $notif->read_at ? '' : 'bg-light' }}">
                            <div class="me-3 mt-1">
                                @if(($notif->data['type'] ?? '') === 'invoice_posted')
                                    <span class="badge bg-success rounded-circle p-2"><i class="ti ti-file-invoice"></i></span>
                                @elseif(($notif->data['type'] ?? '') === 'low_stock')
                                    <span class="badge bg-warning rounded-circle p-2"><i class="ti ti-alert-triangle"></i></span>
                                @else
                                    <span class="badge bg-info rounded-circle p-2"><i class="ti ti-info-circle"></i></span>
                                @endif
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold {{ $notif->read_at ? 'text-muted' : 'text-dark' }}">
                                    {{ $notif->data['message'] ?? 'إشعار' }}
                                </div>
                                <div class="small text-muted mt-1">{{ $notif->created_at->format('Y/m/d H:i') }} — {{ $notif->created_at->diffForHumans() }}</div>
                            </div>
                            <div class="ms-3">
                                @if(!$notif->read_at)
                                    <span class="badge bg-danger">جديد</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 text-muted">
                            <i class="ti ti-bell-off fs-1 d-block mb-2"></i>
                            لا توجد إشعارات
                        </div>
                    @endforelse
                </div>
                @if($notifications->hasPages())
                    <div class="card-footer">{{ $notifications->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection

