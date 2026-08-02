@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('My Signature') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card" style="max-width:520px;">
        <div class="card-header">
            <h5 class="mb-0">My Signature</h5>
        </div>
        <div class="card-body">
            <p class="text-muted" style="font-size:13px;">
                Whenever you approve or authorize a Requisition, Store Issue (Delivery Challan), or other document,
                this signature image is printed at your signature line automatically.
            </p>

            <div class="mb-3">
                <label class="form-label">Current Signature</label>
                <div class="border rounded p-3 text-center" style="background:#fafafa;">
                    @if($user->signature)
                        <img src="{{ asset($user->signature) }}" alt="Signature" style="max-height:80px; max-width:100%;">
                    @else
                        <span class="text-muted">No signature uploaded yet.</span>
                    @endif
                </div>
            </div>

            <form method="POST" action="{{ route('inventory.signature.update') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Upload New Signature <span class="text-danger">*</span></label>
                    <input type="file" name="signature" class="form-control" accept="image/*" required>
                    <div class="form-text">PNG with a transparent background works best. Max 1MB.</div>
                </div>
                <button type="submit" class="btn btn-primary">Save Signature</button>
            </form>

            @if($user->signature)
                <form method="POST" action="{{ route('inventory.signature.destroy') }}" class="mt-2" onsubmit="return confirm('Remove your signature?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">Remove Signature</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
