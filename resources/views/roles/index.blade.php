@extends('unperm::layouts.app')

@section('title', 'Roles - UnPerm')
@section('header', 'Roles')

@section('content')
<div class="bg-white rounded-lg shadow-md">
    @livewire('unperm::manage-roles')
</div>
@endsection

