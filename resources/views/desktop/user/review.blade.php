@extends('desktop.layouts.app')
@section('title', 'Detailed Review')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/desktop/user/review.css?v=12') }}" data-page-style="user-review">
@endpush

@section('content')
@include('shared.user.review-content', ['isMobile' => false])
@endsection
