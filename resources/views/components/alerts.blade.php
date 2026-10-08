@props(['showErrors' => true])

@if(session('success'))
    <div class="notice notice-success" role="status">
        <i class="bi bi-check-circle-fill"></i>
        <div>{{ session('success') }}</div>
    </div>
@endif

@if(session('error'))
    <div class="notice notice-danger" role="alert">
        <i class="bi bi-x-octagon-fill"></i>
        <div>{{ session('error') }}</div>
    </div>
@endif

@if(session('warning'))
    <div class="notice notice-warning" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>{{ session('warning') }}</div>
    </div>
@endif

@if($showErrors && $errors->any())
    <div class="notice notice-danger" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>
            <span class="notice-title">Ada isian yang perlu diperbaiki:</span>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
