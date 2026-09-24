@extends('desktop.layouts.app')
@section('title', 'Practice Activity Calendar')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/desktop/user/progress.css?v=57') }}" data-page-style="user-practice-calendar">
@endpush

@section('content')
@include('desktop.partials.page-hero-styles')
@include('shared.user.practice-calendar-content', ['serverDetectedMobile' => false])
@endsection
