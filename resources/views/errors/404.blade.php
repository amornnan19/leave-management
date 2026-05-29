@extends('errors.layout')

@section('page_title', 'Not Found')
@section('code', '404')
@section('accent_class', 'accent-spectre')
@section('glow_class', 'glow-spectre')

@section('label', 'ERROR // 404 — SIGNAL LOST')

@section('title', 'This page slipped into the void.')

@section('message', 'The page you\'re looking for has drifted off the grid or never existed. Check the URL, or navigate back to somewhere solid.')

@section('actions')
    <a href="{{ url('/') }}" class="btn btn-primary">Return home</a>
    <button type="button" class="btn btn-secondary" onclick="history.back()">Go back</button>
@endsection
