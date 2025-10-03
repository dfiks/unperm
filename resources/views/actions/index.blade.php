@extends('unperm::layouts.app')

@section('title', 'Actions - UnPerm')
@section('header', 'Actions')

@section('content')
<div class="bg-white rounded-lg shadow-md">
    @livewire('unperm::manage-actions')
</div>
@endsection

