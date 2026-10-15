@extends('desktop.layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/desktop/admin/settings.css?v=5') }}" data-page-style="admin-settings">
@endpush

@section('content')
    @include('shared.admin.settings-content')
@endsection
