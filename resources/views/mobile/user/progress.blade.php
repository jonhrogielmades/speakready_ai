@extends('mobile.layouts.app')
@section('title', 'Interview Progress')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/mobile/user/progress.css?v=36') }}" data-page-style="user-progress">
@endpush

@section('content')
@include('shared.user.progress-content', ['serverDetectedMobile' => true])
@endsection
