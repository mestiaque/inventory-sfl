@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Issue Report') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Issue Report</h5></div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-4">
                    <select name="department_id" class="form-control inv-select2">
                        <option value="">All Departments</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3"><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From"></div>
                <div class="col-md-3"><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To"></div>
                <div class="col-md-2"><button type="submit" class="btn btn-secondary">Filter</button></div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm align-middle">
                    <thead><tr><th>Issue No</th><th>Department</th><th>From Store</th><th>Date</th><th>Dept. Receive</th></tr></thead>
                    <tbody>
                        @forelse($issues as $issue)
                            <tr>
                                <td>{{ $issue->issue_no }}</td>
                                <td>{{ $issue->department?->name }}</td>
                                <td>{{ $issue->store?->name }}</td>
                                <td>{{ $issue->issue_date?->format('d M Y') }}</td>
                                <td>{{ ucfirst($issue->department_receive_status) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">No issues found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $issues->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@include('sfl-inventory::admin.partials.select2-init')
@endsection
