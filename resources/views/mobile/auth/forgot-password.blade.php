@extends('mobile.layouts.auth')

@section('title', 'Reset your password - SpeakReady AI')
@section('body_class', 'auth-page-body')

@section('auth_page')
   @include('shared.auth-support-page', ['supportMode' => 'forgot', 'isMobile' => true])
@endsection
