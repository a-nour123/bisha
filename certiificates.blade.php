@extends('admin/layouts/contentLayoutMaster')

@section('title', __('physicalCourses.certificates'))

@section('vendor-style')
    <link rel="stylesheet" href="{{ asset('fonts/fontawesome-6.2.1/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/extensions/toastr.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/pickers/pickadate/pickadate.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/pickers/flatpickr/flatpickr.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/tables/datatable/dataTables.bootstrap5.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/tables/datatable/responsive.bootstrap5.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/tables/datatable/buttons.bootstrap5.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/forms/select/select2.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/animate/animate.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/extensions/sweetalert2.min.css')) }}">
@endsection

@section('page-style')
    <link rel="stylesheet" href="{{ asset(mix('css/base/plugins/extensions/ext-component-toastr.css')) }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/base/plugins/forms/pickers/form-flat-pickr.css') }}">
    <link rel="stylesheet" href="{{ asset(mix('css/base/plugins/forms/pickers/form-flat-pickr.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('css/base/plugins/forms/pickers/form-pickadate.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('css/base/plugins/extensions/ext-component-sweet-alerts.css')) }}">
    <link rel="stylesheet" type="text/css" href="{{ asset(mix('vendors/css/forms/wizard/bs-stepper.min.css')) }}">
    <link rel="stylesheet" type="text/css" href="{{ asset(mix('css/base/plugins/forms/form-wizard.css')) }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('new_d/course_addon.css') }}">
    <style>
        .stats-card {
            transition: transform 0.2s;
        }

        .stats-card:hover {
            transform: translateY(-2px);
        }

        .certificate-progress {
            height: 8px;
            border-radius: 4px;
        }

        .certificate-id {
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 4px;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid">
        <!-- Header with Breadcrumbs -->
        <div class="content-header row">
            <div class="content-header-left col-12 mb-2">
                <div class="row breadcrumbs-top widget-grid">
                    <div class="col-12">
                        <div class="page-title mt-2">
                            <div class="row">
                                <div class="col-sm-6 ps-0">
                                    @if (@isset($breadcrumbs))
                                        <ol class="breadcrumb">
                                            <li class="breadcrumb-item">
                                                <a href="{{ route('admin.dashboard') }}" style="display: flex;">
                                                    <svg class="stroke-icon">
                                                        <use href="{{ asset('fonts/icons/icon-sprite.svg#stroke-home') }}">
                                                        </use>
                                                    </svg>
                                                </a>
                                            </li>
                                            @foreach ($breadcrumbs as $breadcrumb)
                                                <li class="breadcrumb-item">
                                                    @if (isset($breadcrumb['link']))
                                                        <a
                                                            href="{{ $breadcrumb['link'] == 'javascript:void(0)' ? $breadcrumb['link'] : url($breadcrumb['link']) }}">
                                                    @endif
                                                    {{ $breadcrumb['name'] }}
                                                    @if (isset($breadcrumb['link']))
                                                        </a>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ol>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page Title -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-certificate text-primary"></i> {{ __('physicalCourses.Certificates') }}
            </h1>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary" onclick="printCertificatesTable()">
                    <i class="fas fa-print me-1"></i> {{ __('physicalCourses.print') }}
                </button>
                <button class="btn btn-outline-success" id="exportBtn">
                    <i class="fas fa-file-excel me-1"></i> {{ __('physicalCourses.export') }}
                </button>
            </div>
        </div>



        <!-- Certificates Table -->
        <div class="card shadow-sm mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-certificate me-2"></i>{{ __('physicalCourses.generated_certificates') }}
                </h6>
                <div class="card-tools">
                    <button class="btn btn-sm btn-outline-primary" id="refreshCertificatesTable">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="certificatesTable">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('physicalCourses.student_name') }}</th>
                                <th>{{ __('physicalCourses.training_name') }}</th>
                                <th>{{ __('physicalCourses.certificate_id') }}</th>
                                <th>{{ __('physicalCourses.grade') }}</th>
                                <th>{{ __('physicalCourses.percentage') }}</th>
                                <th>{{ __('physicalCourses.issue_date') }}</th>
                                <th>{{ __('physicalCourses.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTable will populate this -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">{{ __('physicalCourses.loading') }}</span>
                    </div>
                    <p class="mt-2 mb-0">{{ __('physicalCourses.processing_request') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Certificate Preview Modal -->
    <div class="modal fade" id="certificatePreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('physicalCourses.certificate_preview') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="certificatePreviewContent" class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">{{ __('physicalCourses.loading') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('vendor-script')
    <script src="{{ asset(mix('vendors/js/extensions/toastr.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/pickers/flatpickr/flatpickr.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/forms/select/select2.full.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/sweetalert2.all.min.js')) }}"></script>
    <script src="{{ asset('vendors/js/extensions/quill.min.js') }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/jquery.dataTables.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/dataTables.bootstrap5.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/dataTables.responsive.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/responsive.bootstrap5.js')) }}"></script>
    {{-- <script src="{{ asset(mix('vendors/js/tables/datatable/dataTables.buttons.min.js')) }}"></script> --}}
    <script src="{{ asset(mix('vendors/js/tables/datatable/buttons.html5.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/buttons.print.min.js')) }}"></script>
@endsection

@section('page-script')
    <script src="{{ asset('new_d/js/form-wizard/image-upload.js') }}"></script>
    <script src="{{ asset('ajax-files/asset_management/asset/index.js') }}"></script>
    <script src="{{ asset(mix('js/scripts/forms/form-select2.js')) }}"></script>

    <script>
        // Print all certificates (all columns and all rows) without the surrounding page design.
        // We fetch the full dataset from the server instead of cloning the on-screen table,
        // because the table uses server-side pagination and the Responsive extension, so the
        // rendered DOM only contains the current page and the columns that fit the screen.
        function escapeHtml(value) {
            return String(value == null ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function printCertificatesTable() {
            var title = @json(__('physicalCourses.Certificates'));
            var dir = @json(app()->getLocale() === 'ar' ? 'rtl' : 'ltr');

            // Columns to print (data key => header label). Actions column is intentionally excluded.
            var columns = [
                { key: 'user_name', label: @json(__('physicalCourses.student_name')) },
                { key: 'train_name', label: @json(__('physicalCourses.training_name')) },
                { key: 'certificate_id', label: @json(__('physicalCourses.certificate_id')) },
                { key: 'grade_display', label: @json(__('physicalCourses.grade')) },
                { key: 'percentage', label: @json(__('physicalCourses.percentage')) },
                { key: 'issued_date', label: @json(__('physicalCourses.issue_date')) }
            ];

            // Open the popup synchronously, as a direct result of the click,
            // so browsers don't flag it as a blocked/non-user-initiated popup.
            var printWindow = window.open('', '', 'height=700,width=1000');
            if (!printWindow) {
                toastr.error(@json(__('physicalCourses.error_deleting_certificate')));
                return;
            }

            $.ajax({
                url: "{{ route('user.lms.training.modules.listCertificatesAjax') }}",
                type: 'GET',
                data: {
                    draw: 1,
                    start: 0,
                    length: -1 // -1 tells the server to return every row
                },
                success: function(response) {
                    var rows = (response && response.data) ? response.data : [];

                    var thead = '<thead><tr>' + columns.map(function(col) {
                        return '<th>' + escapeHtml(col.label) + '</th>';
                    }).join('') + '</tr></thead>';

                    var tbody = '<tbody>';
                    if (!rows.length) {
                        tbody += '<tr><td colspan="' + columns.length + '" style="text-align:center;">' +
                            @json(__('physicalCourses.no_data_available')) + '</td></tr>';
                    } else {
                        rows.forEach(function(row) {
                            tbody += '<tr>' + columns.map(function(col) {
                                return '<td>' + escapeHtml(row[col.key]) + '</td>';
                            }).join('') + '</tr>';
                        });
                    }
                    tbody += '</tbody>';

                    printWindow.document.write('<!DOCTYPE html><html dir="' + dir + '"><head><title>' +
                        escapeHtml(title) + '</title>');
                    printWindow.document.write(
                        '<style>' +
                        'body{font-family:Arial,Helvetica,sans-serif;padding:24px;color:#000;}' +
                        'h2{text-align:center;margin-bottom:20px;}' +
                        'table{width:100%;border-collapse:collapse;}' +
                        'th,td{border:1px solid #444;padding:8px 10px;font-size:12px;text-align:' + (dir === 'rtl' ? 'right' : 'left') + ';}' +
                        'thead th{background:#f0f0f0;}' +
                        '</style>'
                    );
                    printWindow.document.write('</head><body>');
                    printWindow.document.write('<h2>' + escapeHtml(title) + '</h2>');
                    printWindow.document.write('<table>' + thead + tbody + '</table>');
                    printWindow.document.write('</body></html>');
                    printWindow.document.close();
                    printWindow.focus();

                    setTimeout(function() {
                        printWindow.print();
                        printWindow.close();
                    }, 350);
                },
                error: function() {
                    printWindow.close();
                    toastr.error(@json(__('physicalCourses.error_deleting_certificate')));
                }
            });
        }

        $(document).ready(function() {
            // Initialize DataTables
            let certificatesTable = $('#certificatesTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('user.lms.training.modules.listCertificatesAjax') }}",
                    type: 'GET'
                },
                columns: [
                    {
                        data: 'user_name',
                        name: 'user_name'
                    },
                    {
                        data: 'train_name',
                        name: 'train_name'
                    },
                    {
                        data: 'certificate_id',
                        name: 'certificate_id'
                    },
                    {
                        data: 'grade_display',
                        name: 'grade_display'
                    },
                    {
                        data: 'percentage',
                        name: 'percentage'
                    },
                    {
                        data: 'issued_date',
                        name: 'issued_date'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [4, 'desc']
                ],
                language: {
                    url: "{{ asset('vendors/js/tables/datatable/lang/' . app()->getLocale() . '.json') }}"
                }
            });


            $('#refreshCertificatesTable').on('click', function() {
                certificatesTable.ajax.reload();
            });

            // Export certificates to Excel
            $('#exportBtn').on('click', function() {
                window.location.href = "{{ route('user.lms.training.modules.export-certificates') }}";
            });


            // Delete certificate
            $(document).on('click', '.delete-certificate', function() {
                let certificateId = $(this).data('id');
                let trainingId = $(this).data('training-id');
                let button = $(this);
                let url =
                    "{{ route('user.lms.training.modules.delete-certificate', [':training', ':certificate']) }}"
                    .replace(':training', trainingId)
                    .replace(':certificate', certificateId);

                Swal.fire({
                    title: '{{ __('physicalCourses.delete_certificate') }}',
                    text: '{{ __('physicalCourses.are_you_sure_delete_certificate') }}',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: '{{ __('physicalCourses.yes_delete') }}',
                    cancelButtonText: '{{ __('physicalCourses.cancel') }}'
                }).then((result) => {
                    if (result.isConfirmed) {
                        button.prop('disabled', true);

                        $.ajaxSetup({
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            }
                        });

                        $.ajax({
                            url: url,
                            type: 'DELETE',
                            success: function(response) {
                                toastr.success(
                                    '{{ __('physicalCourses.certificate_deleted_successfully') }}'
                                );
                                certificatesTable.ajax.reload();
                                // Reload page to update statistics
                                location.reload();
                            },
                            error: function(xhr) {
                                toastr.error(
                                    '{{ __('physicalCourses.error_deleting_certificate') }}: ' +
                                    xhr.responseJSON?.message);
                            },
                            complete: function() {
                                button.prop('disabled', false);
                            }
                        });
                    }
                });
            });

        });
    </script>

@endsection