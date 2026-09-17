@extends('mobile.layouts.app')
@section('title', 'Practice Activity Calendar')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/mobile/user/progress.css?v=25') }}" data-page-style="user-practice-calendar">
@endpush

@section('content')
@include('mobile.partials.page-hero-styles')
@include('shared.user.practice-calendar-content', ['serverDetectedMobile' => true])
@endsection
