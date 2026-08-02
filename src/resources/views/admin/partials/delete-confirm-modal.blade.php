{{-- props: modalId (string), label (string, e.g. "store") --}}
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="{{ $modalId }}Form">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title">Delete {{ ucfirst($label) }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Are you sure you want to delete this {{ $label }}? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>
@push('js')
<script>
    document.getElementById('{{ $modalId }}').addEventListener('show.bs.modal', function (event) {
        const action = event.relatedTarget.getAttribute('data-action');
        document.getElementById('{{ $modalId }}Form').setAttribute('action', action);
    });
</script>
@endpush
