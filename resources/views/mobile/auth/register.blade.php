@extends('mobile.layouts.auth')

@section('title', 'Create Account | SpeakReady AI')
@section('body_class', 'auth-page-body')

@section('auth_page')
   @include('shared.auth-page', ['authMode' => 'register', 'isMobile' => true])
@endsection
