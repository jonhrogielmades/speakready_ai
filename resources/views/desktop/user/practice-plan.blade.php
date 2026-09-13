@extends('desktop.layouts.app')
@section('title', 'Personalized Practice Plan')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/desktop/user/progress.css?v=29') }}" data-page-style="user-practice-plan">
@endpush

@section('content')
@include('desktop.partials.page-hero-styles')
@include('shared.user.practice-plan-content', ['serverDetectedMobile' => false])
@endsection
