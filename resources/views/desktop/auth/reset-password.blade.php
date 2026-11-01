@extends('desktop.layouts.auth')

@section('title', 'Create a new password - SpeakReady AI')
@section('body_class', 'auth-page-body')

@section('auth_page')
   @include('shared.auth-support-page', ['supportMode' => 'reset', 'isMobile' => false])
@endsection
