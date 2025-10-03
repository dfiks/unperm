@extends('unperm::layouts.app')

@section('title', 'Groups - UnPerm')
@section('header', 'Groups')

@section('content')
<div class="bg-white rounded-lg shadow-md">
    @livewire('unperm::manage-groups')
</div>
@endsection

