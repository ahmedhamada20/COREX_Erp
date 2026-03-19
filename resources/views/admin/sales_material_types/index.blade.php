@extends('admin.layouts.master')

@section('title', 'فئات مواد المبيعات')

@section('css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <style>
        .switch {
            position: relative;
            display: inline-block;
            width: 46px;
            height: 24px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #dc3545;
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #28a745;
        }

        input:checked + .slider:before {
            transform: translateX(22px);
        }
    </style>
@endsection

@section('content')

    <div class="content-header mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">فئات مواد المبيعات</h5>

            <a href="{{ route('sales_material_types.create') }}" class="btn btn-sm btn-primary">
                <i class="ti ti-plus"></i> إضافة فئة جديدة
            </a>
        </div>
    </div>

    @include('admin.Alerts')

    <div class="row">
        <div class="col-12">

            <div class="card table-card">
                <div class="card-header">
                    <h6 class="mb-0">قائمة الفئات</h6>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="pc-dt-simple">
                            <thead>
                            <tr>
                                <th class="text-start">#</th>
                                <th class="text-start">الاسم</th>
                                <th class="text-start">التاريخ</th>
                                <th class="text-start">آخر تعديل بواسطة</th>
                                <th class="text-start">الحالة</th>
                                <th class="text-center">العمليات</th>
                            </tr>
                            </thead>

                            <tbody>
                            @if($items->count())
                                @foreach($items as $item)
                                    <tr>
                                        <td>{{ $item->id }}</td>
                                        <td>{{ $item->name }}</td>
                                        <td>{{ $item->date ? \Carbon\Carbon::parse($item->date)->format('Y-m-d') : '-' }}</td>
                                        <td>{{ $item->updated_by ?? '-' }}</td>

                                        <td class="text-center">
                                            <label class="switch">
                                                <input type="checkbox"
                                                       class="toggle-status"
                                                       data-id="{{ $item->id }}"
                                                    {{ $item->status ? 'checked' : '' }}>
                                                <span class="slider round"></span>
                                            </label>
                                        </td>

                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('sales_material_types.edit', $item->id) }}"
                                                   class="btn btn-sm btn-success">
                                                    <i class="fa fa-edit"></i>
                                                </a>

                                                <a href="{{ route('sales_material_types.show', $item->id) }}"


                                                        class="btn btn-sm btn-info">
                                                    <i class="fa fa-eye"></i>
                                                </a>

                                                <form action="{{ route('sales_material_types.destroy', $item->id) }}"
                                                      method="POST"
                                                      onsubmit="return confirm('هل أنت متأكد من حذف هذه الفئة؟');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                            </tbody>

                        </table>


                    </div>

                    @if(method_exists($items, 'links'))
                        <div class="mt-3">
                            {{ $items->links() }}
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

@endsection

@section('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
        $(document).on('change', '.toggle-status', function () {
            let id = $(this).data('id');

            $.ajax({
                url: "{{ url('/sales_material_types/toggle-status') }}/" + id,
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function (res) {
                    toastr.success("تم تحديث الحالة إلى: " + res.status_text, "نجاح");
                },
                error: function () {
                    toastr.error("حدث خطأ، حاول مرة أخرى", "خطأ");
                }
            });
        });
    </script>
@endsection
