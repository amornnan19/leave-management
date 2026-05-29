@extends('errors.layout')

@section('page_title', 'Forbidden')
@section('code', '403')
@section('accent_class', 'accent-signal')
@section('glow_class', 'glow-signal')

@section('label', 'ERROR // 403 — ACCESS DENIED')

@section('title', 'You don\'t have clearance.')

@section('message', 'This area is restricted. If you think this is a mistake, contact your administrator.')

@section('actions')
    <a href="{{ url('/') }}" class="btn btn-primary signal">Return home</a>
@endsection
