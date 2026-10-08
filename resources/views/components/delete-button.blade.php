@props(['action', 'message' => 'Data yang dihapus tidak bisa dikembalikan.', 'title' => 'Hapus data ini?', 'label' => 'Hapus', 'small' => true, 'iconOnly' => false])

<form action="{{ $action }}" method="POST" class="d-inline" data-confirm="{{ $message }}" data-confirm-title="{{ $title }}" data-confirm-button="Ya, hapus">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-ghost-danger {{ $small ? 'btn-sm' : '' }} {{ $iconOnly ? 'btn-icon' : '' }}" title="{{ $label }}">
        <i class="bi bi-trash3"></i>@unless($iconOnly) {{ $label }}@endunless
    </button>
</form>
