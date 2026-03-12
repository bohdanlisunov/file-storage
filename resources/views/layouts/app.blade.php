<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title','File Storage')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background:#f0f2f5; }
        .dropzone-wrapper { border:2px dashed #6c757d; border-radius:12px; background:#fff; cursor:pointer; min-height:160px; display:flex; align-items:center; justify-content:center; transition:.2s; }
        .dropzone-wrapper:hover,.dropzone-wrapper.dragging { border-color:#0d6efd; background:#f0f5ff; }
        .dropzone-wrapper.has-file { border-color:#198754; background:#f0fff4; }
        .file-icon { width:36px; height:36px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.75rem; font-weight:700; flex-shrink:0; }
        .file-icon.pdf  { background:#fee2e2; color:#dc2626; }
        .file-icon.docx,.file-icon.doc { background:#dbeafe; color:#2563eb; }
        .badge-ok { background:#d1e7dd; color:#0a3622; }
        .badge-warn { background:#fff3cd; color:#856404; }
        .badge-exp { background:#f8d7da; color:#58151c; }
        .empty-state { padding:3rem; text-align:center; color:#adb5bd; }
        .toast-container { z-index:1100; }
        table th { font-size:.78rem; text-transform:uppercase; letter-spacing:.05em; color:#6c757d; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/files"><i class="bi bi-cloud-upload me-2"></i>FileStorage</a>
        <span class="text-white-50 small"><i class="bi bi-clock me-1"></i>Auto-deleted after 24h</span>
    </div>
</nav>
<main class="container py-4">@yield('content')</main>

<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="appToast" class="toast align-items-center border-0"><div class="d-flex">
        <div class="toast-body fw-medium" id="toastMsg"></div>
        <button class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
    </div></div>
</div>

<div class="modal fade" id="delModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header border-0"><h5 class="modal-title"><i class="bi bi-exclamation-triangle text-danger me-2"></i>Delete File</h5>
            <button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">Delete <strong id="delName"></strong>?<br><small class="text-muted">A notification will be queued via RabbitMQ.</small></div>
        <div class="modal-footer border-0">
            <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button class="btn btn-danger" id="confirmDel"><i class="bi bi-trash me-1"></i>Delete</button>
        </div>
    </div></div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});
window.showToast = function(msg,type='success'){
    const $t=$('#appToast');
    $t.removeClass('text-bg-success text-bg-danger text-bg-warning');
    $t.addClass('text-bg-'+type);
    $('#toastMsg').text(msg);
    bootstrap.Toast.getOrCreateInstance($t[0],{delay:4000}).show();
};
</script>
@stack('scripts')
</body>
</html>
