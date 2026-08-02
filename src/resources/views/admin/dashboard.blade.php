@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Inventory Dashboard') }}</title>
@endsection

@section('contents')
    @include(adminTheme() . 'alerts')
    <div class="flex-grow-1" style="padding:20px 20px 30px;">
        @include('sfl-inventory::admin.partials.dashboard-widget')
    </div>
@endsection
