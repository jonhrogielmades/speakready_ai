@extends('mobile.layouts.app')
@section('title', 'Personalized Practice Plan')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/mobile/user/progress.css?v=25') }}" data-page-style="user-practice-plan">
@endpush

@section('content')
@include('mobile.partials.page-hero-styles')
@include('shared.user.practice-plan-content', ['serverDetectedMobile' => true])
@endsection
