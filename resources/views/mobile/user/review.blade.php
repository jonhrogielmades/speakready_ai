@extends('mobile.layouts.app')
@section('title', 'Detailed Review')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/mobile/user/review.css?v=15') }}" data-page-style="user-review">
@endpush

@section('content')
@include('shared.user.review-content', ['isMobile' => true])
@endsection
