@extends('mobile.layouts.auth')

@section('title', 'Log In | SpeakReady AI')
@section('body_class', 'auth-page-body')

@section('auth_page')
   @include('shared.auth-page', ['authMode' => 'login', 'isMobile' => true])
@endsection
