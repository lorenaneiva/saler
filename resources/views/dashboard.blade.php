@extends('layout')

@section('content')
    <h1 class="text-2xl font-semibold">Olá, {{ auth()->user()->name }}</h1>
@endsection
