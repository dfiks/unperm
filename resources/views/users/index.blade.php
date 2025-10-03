@extends('unperm::layouts.app')

@section('title', 'Users - UnPerm')
@section('header', 'Управление правами пользователей')

@section('content')
<div class="bg-white rounded-lg shadow-md">
    @livewire('unperm::manage-user-permissions')
</div>
@endsection

