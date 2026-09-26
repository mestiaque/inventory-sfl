{{--
    Generic trash list modal, shared by every module's index page.

    props:
      modalId        (string) unique id for this modal, e.g. "brandTrashModal"
      label          (string) singular display label, e.g. "Brand"
      rows           (iterable of ['id', 'title', 'subtitle' (optional), 'deleted_at'])
      restoreRoute   (string|null) route name for restore (POST), null hides the restore action
      forceRoute     (string|null) route name for permanent delete (DELETE), null hides the action
      canRestore     (bool)
      canForce       (bool)
      forceWarning   (string, optional) custom body text for the permanent-delete confirm, defaults to a generic warning
--}}
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-trash"></i> {{ $label }} Trash</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-0">
                        <thead>
                            <tr><th>#</th><th>{{ $label }}</th><th>Deleted At</th><th class="text-right">Actions</th></tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $row)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        {{ $row['title'] }}
                                        @if (! empty($row['subtitle']))
                                            <br><small class="text-muted">{{ $row['subtitle'] }}</small>
                                        @endif
                                    </td>
                                    <td>{{ optional($row['deleted_at'])->format('d-M-Y h:i A') }}</td>
                                    <td class="text-right">
                                        @if ($canRestore && $restoreRoute)
                                            <form method="POST" action="{{ route($restoreRoute, $row['id']) }}" class="d-inline" onsubmit="return confirm('Restore this {{ strtolower($label) }}?')">
                                                @csrf
                                                <button type="submit" class="btn-custom success" title="Restore"><i class="fa-solid fa-rotate-left"></i></button>
                                            </form>
                                        @endif
                                        @if ($canForce && $forceRoute)
                                            <button type="button" class="btn-custom danger" title="Delete Permanently" data-toggle="modal" data-target="#{{ $modalId }}ForceConfirm" data-action="{{ route($forceRoute, $row['id']) }}"><i class="fa-solid fa-triangle-exclamation"></i></button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted">Trash is empty.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@if ($canForce && $forceRoute)
    <div class="modal fade" id="{{ $modalId }}ForceConfirm" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="{{ $modalId }}ForceConfirmForm">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title text-danger"><i class="fa-solid fa-triangle-exclamation"></i> Delete Permanently</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-danger mb-0">{{ $forceWarning ?? 'Permanently delete this ' . strtolower($label) . '? This cannot be undone.' }}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger btn-sm">Delete Permanently</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @push('js')
    <script>
        $('#{{ $modalId }}ForceConfirm').on('show.bs.modal', function (event) {
            $('#{{ $modalId }}ForceConfirmForm').attr('action', $(event.relatedTarget).data('action'));
        });
    </script>
    @endpush
@endif
