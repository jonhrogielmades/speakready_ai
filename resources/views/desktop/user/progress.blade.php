@extends('desktop.layouts.app')
@section('title', 'Interview Progress')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/desktop/user/progress.css?v=62') }}" data-page-style="user-progress">
@endpush

@section('content')
@include('shared.user.progress-content', ['serverDetectedMobile' => false, 'historyPageSizeDesktop' => 4])
@endsection
