@extends('unperm::layouts.app')

@section('title', 'Resource Permissions - UnPerm')
@section('header', 'Управление правами на ресурсы')

@section('content')
<div class="bg-white rounded-lg shadow-md">
    @livewire('unperm::manage-resource-permissions')
</div>
@endsection

