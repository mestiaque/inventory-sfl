@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Negative Stock Auto Fix') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Auto Fix — Preview</h4>
            <a href="{{ route('inventory.negative-stock.index') }}" class="btn btn-light btn-sm">&larr; Back to list</a>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                Nothing has been changed yet. This is exactly what <strong>Apply Auto Fix</strong> will do:
                first, any challan that issued more than the store held is moved to a store that has the stock;
                whatever is still negative after that gets a Stock Adjustment to bring it to 0.
                @unless($canApprove)
                    <br><strong class="text-warning">You can't approve Stock Adjustments, so those will wait for approval.</strong>
                @endunless
            </p>

            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle">
                    <thead>
                        <tr><th>Item</th><th>Color / Size</th><th>Store</th><th class="text-right">Now</th><th>What will happen</th><th class="text-right">After</th></tr>
                    </thead>
                    <tbody>
                        @forelse($log as $entry)
                            <tr>
                                <td>{{ $entry['item'] }}</td>
                                <td>{{ $entry['variant'] }}</td>
                                <td>{{ $entry['store'] }}</td>
                                <td class="text-right text-danger font-weight-bold">{{ inv_qty($entry['before']) }}</td>
                                <td class="small">
                                    @forelse($entry['actions'] as $action)
                                        <div>
                                            <i class="fa-solid {{ str_starts_with($action, 'Moved') ? 'fa-right-left text-primary' : 'fa-scale-balanced text-danger' }}"></i>
                                            {{ $action }}
                                        </div>
                                    @empty
                                        <span class="text-muted">Already fixed by an earlier move</span>
                                    @endforelse
                                </td>
                                <td class="text-right font-weight-bold {{ $entry['after'] < 0 ? 'text-danger' : 'text-success' }}">{{ inv_qty($entry['after']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-success">No negative stock. Nothing to fix.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(count($log))
                <form method="POST" action="{{ route('inventory.negative-stock.auto-fix.apply') }}" class="text-right"
                      onsubmit="return confirm('Apply all {{ count($log) }} fixes now?');">
                    @csrf
                    <button type="submit" class="btn btn-danger"><i class="fa-solid fa-wand-magic-sparkles"></i> Apply Auto Fix</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
