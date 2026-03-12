@extends('layouts.app')
@section('title','File Manager')
@section('content')
<div class="row g-4">
  <div class="col-12">
    <div class="card shadow-sm border-0">
      <div class="card-body p-4">
        <h5 class="fw-semibold mb-3"><i class="bi bi-upload me-2 text-primary"></i>Upload File</h5>
        <div id="dz" class="dropzone-wrapper p-4 mb-3">
          <div id="dzDefault" class="text-center">
            <i class="bi bi-cloud-arrow-up display-5 text-muted mb-2 d-block"></i>
            <p class="mb-1 fw-medium text-muted">Drag & drop or click to browse</p>
            <p class="small text-muted mb-2">PDF, DOC, DOCX · Max 10MB · Auto-deleted after 24h</p>
            <span class="badge bg-secondary">PDF</span> <span class="badge bg-secondary">DOCX</span>
          </div>
          <div id="dzFile" class="text-center d-none">
            <i class="bi bi-file-earmark-check display-5 text-success mb-2 d-block"></i>
            <p class="mb-1 fw-medium" id="dzName"></p>
            <p class="small text-muted" id="dzSize"></p>
          </div>
          <input type="file" id="fileInput" accept=".pdf,.doc,.docx" class="d-none">
        </div>
        <div id="progWrap" style="display:none" class="mb-3">
          <div class="d-flex justify-content-between small mb-1"><span>Uploading…</span><span id="progPct">0%</span></div>
          <div class="progress" style="height:6px"><div id="progBar" class="progress-bar progress-bar-striped progress-bar-animated" style="width:0%"></div></div>
        </div>
        <div class="d-flex gap-2">
          <button id="uploadBtn" class="btn btn-primary px-4" disabled><i class="bi bi-upload me-1"></i>Upload</button>
          <button id="clearBtn" class="btn btn-outline-secondary" style="display:none"><i class="bi bi-x me-1"></i>Clear</button>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card shadow-sm border-0">
      <div class="card-body p-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
          <h5 class="fw-semibold mb-0">
            <i class="bi bi-folder2-open me-2 text-primary"></i>Files
            <span class="badge bg-primary ms-2" id="fileCount">{{ $files->total() }}</span>
          </h5>
          <form method="GET" class="d-flex gap-2 flex-wrap">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search…" value="{{ request('search') }}" style="width:200px">
            <select name="type" class="form-select form-select-sm" style="width:120px" onchange="this.form.submit()">
              <option value="">All types</option>
              <option value="pdf" {{ request('type')=='pdf'?'selected':'' }}>PDF</option>
              <option value="docx" {{ request('type')=='docx'?'selected':'' }}>DOCX</option>
            </select>
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
            @if(request('search')||request('type'))
              <a href="/files" class="btn btn-sm btn-outline-danger"><i class="bi bi-x"></i></a>
            @endif
          </form>
        </div>
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead class="table-light">
              <tr><th style="width:40%">File</th><th>Size</th><th>Uploaded</th><th>Expires</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody id="tbody">
            @forelse($files as $f)
              <tr id="row-{{ $f->id }}">
                <td><div class="d-flex align-items-center gap-3">
                  <div class="file-icon {{ strtolower($f->extension) }}">{{ $f->extension }}</div>
                  <span class="fw-medium text-truncate" style="max-width:260px" title="{{ $f->original_name }}">{{ $f->original_name }}</span>
                </div></td>
                <td class="text-muted small">{{ $f->human_size }}</td>
                <td class="text-muted small">{{ $f->created_at->format('d.m.Y H:i') }}</td>
                <td>
                  @php $h = now()->diffInHours($f->expires_at, false); @endphp
                  <span class="badge {{ $f->isExpired()?'badge-exp':($h<2?'badge-warn':'badge-ok') }}">
                    <i class="bi bi-clock me-1"></i>{{ $f->time_remaining }}
                  </span>
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <a href="/files/{{ $f->id }}/download" class="btn btn-outline-primary" title="Download"><i class="bi bi-download"></i></a>
                    <button class="btn btn-outline-danger btn-del" data-id="{{ $f->id }}" data-name="{{ $f->original_name }}"><i class="bi bi-trash"></i></button>
                  </div>
                </td>
              </tr>
            @empty
              <tr id="emptyRow"><td colspan="5"><div class="empty-state"><i class="bi bi-inbox display-4 d-block mb-3"></i><p>No files yet. Upload above.</p></div></td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
        @if($files->hasPages())<div class="mt-4 d-flex justify-content-end">{{ $files->links('pagination::bootstrap-5') }}</div>@endif
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
$(function(){
  const $dz=$('#dz'),$input=$('#fileInput'),$btn=$('#uploadBtn'),$clear=$('#clearBtn');

  $dz.on('click',()=>$input.trigger('click'));
  $dz.on('dragover dragenter',e=>{e.preventDefault();$dz.addClass('dragging');})
     .on('dragleave dragend drop',e=>{e.preventDefault();$dz.removeClass('dragging');})
     .on('drop',e=>{const f=e.originalEvent.dataTransfer.files[0];if(f){$input[0].files=e.originalEvent.dataTransfer.files;setFile(f);}});
  $input.on('change',function(){if(this.files[0])setFile(this.files[0]);});

  function setFile(f){
    $('#dzDefault').addClass('d-none');$('#dzFile').removeClass('d-none');
    $dz.addClass('has-file');$('#dzName').text(f.name);
    $('#dzSize').text(f.size>=1048576?(f.size/1048576).toFixed(2)+' MB':(f.size/1024).toFixed(2)+' KB');
    $btn.prop('disabled',false);$clear.show();
  }
  function reset(){$input.val('');$('#dzDefault').removeClass('d-none');$('#dzFile').addClass('d-none');$dz.removeClass('has-file');$btn.prop('disabled',true);$clear.hide();}
  $clear.on('click',e=>{e.stopPropagation();reset();});

  $btn.on('click',function(){
    const f=$input[0].files[0]; if(!f)return;
    const fd=new FormData(); fd.append('file',f);
    $('#progWrap').show(); $btn.prop('disabled',true).html('<span class="spinner-border spinner-border-sm me-1"></span>Uploading…');
    $.ajax({url:'/files',method:'POST',data:fd,processData:false,contentType:false,
      xhr(){const x=new XMLHttpRequest();x.upload.addEventListener('progress',e=>{if(e.lengthComputable){const p=Math.round(e.loaded/e.total*100);$('#progBar').css('width',p+'%');$('#progPct').text(p+'%');}});return x;},
      success(r){
        if(r.success){
          showToast('✅ '+r.message);
          const ext=(r.file.extension||'').toLowerCase();
          const badge=`<span class="badge badge-ok"><i class="bi bi-clock me-1"></i>${r.file.time_remaining}</span>`;
          $('#emptyRow').remove();
          $('#tbody').prepend(`<tr id="row-${r.file.id}">
            <td><div class="d-flex align-items-center gap-3"><div class="file-icon ${ext}">${r.file.extension}</div>
            <span class="fw-medium text-truncate" style="max-width:260px">${$('<span>').text(r.file.original_name).html()}</span></div></td>
            <td class="text-muted small">${r.file.size}</td>
            <td class="text-muted small">${r.file.created_at}</td>
            <td>${badge}</td>
            <td class="text-end"><div class="btn-group btn-group-sm">
              <a href="/files/${r.file.id}/download" class="btn btn-outline-primary"><i class="bi bi-download"></i></a>
              <button class="btn btn-outline-danger btn-del" data-id="${r.file.id}" data-name="${$('<span>').text(r.file.original_name).html()}"><i class="bi bi-trash"></i></button>
            </div></td></tr>`);
          $('#fileCount').text(parseInt($('#fileCount').text()||0)+1);
        } else showToast(r.message,'danger');
      },
      error(x){const e=x.responseJSON;showToast(e?.errors?Object.values(e.errors).flat().join(' '):(e?.message||'Upload failed.'),'danger');},
      complete(){reset();$('#progWrap').hide();$('#progBar').css('width','0%');$btn.html('<i class="bi bi-upload me-1"></i>Upload');}
    });
  });

  let delId=null; const modal=new bootstrap.Modal('#delModal');
  $(document).on('click','.btn-del',function(){delId=$(this).data('id');$('#delName').text($(this).data('name'));modal.show();});
  $('#confirmDel').on('click',function(){
    if(!delId)return;
    const $b=$(this); $b.prop('disabled',true).html('<span class="spinner-border spinner-border-sm me-1"></span>');
    $.ajax({url:'/files/'+delId,method:'DELETE',
      success(r){
        if(r.success){
          $('#row-'+delId).fadeOut(300,function(){$(this).remove();
            const c=parseInt($('#fileCount').text())-1; $('#fileCount').text(Math.max(0,c));
            if(!$('#tbody tr').length) $('#tbody').append('<tr id="emptyRow"><td colspan="5"><div class="empty-state"><i class="bi bi-inbox display-4 d-block mb-3"></i><p>No files yet.</p></div></td></tr>');
          });
          showToast('🗑 '+r.message);
        } else showToast(r.message,'danger');
      },
      error(){showToast('Delete failed.','danger');},
      complete(){$b.prop('disabled',false).html('<i class="bi bi-trash me-1"></i>Delete');modal.hide();delId=null;}
    });
  });
});
</script>
@endpush
