@extends('admin.layouts.master')

@section('css')
    <style>
        .setting-card-header{
            background: linear-gradient(135deg, rgba(13,110,253,.95), rgba(13,110,253,.75));
            color:#fff;
        }
        .form-hint{
            font-size:.85rem;
            color:rgba(255,255,255,.85);
        }
        .kv{
            padding: .75rem;
            border: 1px solid rgba(0,0,0,.08);
            border-radius: .75rem;
            background: #fff;
        }
        .kv .k{
            font-size: .85rem;
            color: #6c757d;
            margin-bottom: .25rem;
        }
        .kv .v{
            font-weight: 700;
            color: #212529;
        }
    </style>
@endsection

@section('title')
    عرض الخزنة
@endsection

@section('content')
    @php
        $updatedAt = $treasury->updated_at?->copy()->timezone('Africa/Cairo');
        $period = $updatedAt ? ($updatedAt->format('H') < 12 ? 'صباحًا' : 'مساءً') : null;
    @endphp

    <div class="row">
        <div class="col-12">

            @include('admin.Alerts')

            <div class="card border-0 shadow-sm">

                {{-- Header --}}
                <div class="card-body setting-card-header rounded-3">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <h4 class="mb-1 fw-bold">
                                <i class="ti ti-eye me-1"></i>
                                تفاصيل الخزنة
                            </h4>
                            <div class="form-hint">
                                تخص هذه الخزنة حساب:
                                <span class="fw-semibold">{{ auth()->user()->name }}</span>
                            </div>
                        </div>

                        <span class="badge bg-light text-primary fw-semibold">
                        <i class="ti ti-safe me-1"></i>
                        {{ $treasury->name }}
                    </span>
                    </div>
                </div>

                {{-- Body --}}
                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <div class="kv">
                                <div class="k">اسم الخزنة</div>
                                <div class="v">{{ $treasury->name }}</div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="kv">
                                <div class="k">النوع</div>
                                <div class="v">
                                    @if($treasury->is_master)
                                        <span class="badge bg-primary">رئيسية</span>
                                    @else
                                        <span class="badge bg-secondary">فرعية</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="kv">
                                <div class="k">الحالة</div>
                                <div class="v">
                                    @if($treasury->status)
                                        <span class="badge bg-success">مفعل</span>
                                    @else
                                        <span class="badge bg-danger">غير مفعل</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="kv">
                                <div class="k">آخر إيصال صرف</div>
                                <div class="v">{{ $treasury->last_payment_receipt_no }}</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="kv">
                                <div class="k">آخر إيصال تحصيل</div>
                                <div class="v">{{ $treasury->last_collection_receipt_no }}</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="kv">
                                <div class="k">التاريخ</div>
                                <div class="v">{{ $treasury->date?->format('Y-m-d') ?? '—' }}</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="kv">
                                <div class="k">آخر تعديل</div>
                                <div class="v">
                                    @if($updatedAt && $treasury->updated_by)
                                        <span class="text-dark fw-semibold">{{ $treasury->updated_by }}</span>
                                        <span class="mx-1">•</span>
                                        <span class="text-muted">
                                        {{ $updatedAt->translatedFormat('d F Y - h:i') }} {{ $period }}
                                    </span>
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="col-12">
                            <div class="d-flex align-items-center justify-content-end gap-2 mt-2">
                                <a href="{{ route('treasuries.index') }}" class="btn btn-light">
                                    <i class="ti ti-arrow-back me-1"></i>
                                    رجوع
                                </a>

                                <a href="{{ route('treasuries.edit', $treasury->id) }}" class="btn btn-primary">
                                    <i class="ti ti-pencil me-1"></i>
                                    تعديل
                                </a>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

        </div>
    </div>
@endsection
