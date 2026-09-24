@extends('mobile.layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/mobile/admin/settings.css?v=2') }}" data-page-style="admin-settings">
@endpush

@section('content')
    @include('shared.admin.settings-content')
@endsection
