@extends('mobile.layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/mobile/admin/ai/evaluation.css?v=1') }}" data-page-style="admin-ai-evaluation">
@endpush

@section('content')
    @include('admin.ai.partials.evaluation-content')
@endsection
