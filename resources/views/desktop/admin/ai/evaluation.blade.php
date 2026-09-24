@extends('desktop.layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/desktop/admin/ai/evaluation.css?v=2') }}" data-page-style="admin-ai-evaluation">
@endpush

@section('content')
    @include('admin.ai.partials.evaluation-content')
@endsection
