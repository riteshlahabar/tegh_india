{{-- Legacy monolith entry; routes now use layouts.admin + admin.pages.* --}}
@extends('layouts.admin')
@section('content')
@include('partials.dashboard.command-centre')
@endsection
