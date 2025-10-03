@extends('unperm::layouts.app')

@section('title', 'Dashboard - UnPerm')
@section('header', 'Dashboard')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Actions Card -->
    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500 hover:shadow-lg transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 uppercase font-semibold">Actions</p>
                <p class="text-3xl font-bold text-gray-800 mt-2">{{ $stats['actions'] }}</p>
            </div>
            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-bolt text-3xl text-blue-600"></i>
            </div>
        </div>
        <a href="{{ route('unperm.actions') }}" class="mt-4 text-blue-600 hover:text-blue-800 text-sm font-semibold inline-flex items-center">
            Управление <i class="fas fa-arrow-right ml-2"></i>
        </a>
    </div>

    <!-- Roles Card -->
    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500 hover:shadow-lg transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 uppercase font-semibold">Roles</p>
                <p class="text-3xl font-bold text-gray-800 mt-2">{{ $stats['roles'] }}</p>
            </div>
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-user-tag text-3xl text-green-600"></i>
            </div>
        </div>
        <a href="{{ route('unperm.roles') }}" class="mt-4 text-green-600 hover:text-green-800 text-sm font-semibold inline-flex items-center">
            Управление <i class="fas fa-arrow-right ml-2"></i>
        </a>
    </div>

    <!-- Groups Card -->
    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500 hover:shadow-lg transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 uppercase font-semibold">Groups</p>
                <p class="text-3xl font-bold text-gray-800 mt-2">{{ $stats['groups'] }}</p>
            </div>
            <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center">
                <i class="fas fa-users text-3xl text-purple-600"></i>
            </div>
        </div>
        <a href="{{ route('unperm.groups') }}" class="mt-4 text-purple-600 hover:text-purple-800 text-sm font-semibold inline-flex items-center">
            Управление <i class="fas fa-arrow-right ml-2"></i>
        </a>
    </div>
</div>

<!-- Quick Actions -->
<div class="bg-white rounded-lg shadow-md p-6 mb-8">
    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
        <i class="fas fa-rocket mr-2 text-indigo-600"></i>
        Быстрые действия
    </h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <button onclick="window.location='{{ route('unperm.actions') }}'" class="bg-blue-50 hover:bg-blue-100 text-blue-700 font-semibold py-3 px-4 rounded-lg transition flex items-center justify-center">
            <i class="fas fa-plus mr-2"></i> Новый Action
        </button>
        <button onclick="window.location='{{ route('unperm.roles') }}'" class="bg-green-50 hover:bg-green-100 text-green-700 font-semibold py-3 px-4 rounded-lg transition flex items-center justify-center">
            <i class="fas fa-plus mr-2"></i> Новая Role
        </button>
        <button onclick="window.location='{{ route('unperm.groups') }}'" class="bg-purple-50 hover:bg-purple-100 text-purple-700 font-semibold py-3 px-4 rounded-lg transition flex items-center justify-center">
            <i class="fas fa-plus mr-2"></i> Новая Group
        </button>
        <button onclick="window.location='{{ route('unperm.users') }}'" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold py-3 px-4 rounded-lg transition flex items-center justify-center">
            <i class="fas fa-user-plus mr-2"></i> Назначить права
        </button>
    </div>
</div>

<!-- Info Panel -->
<div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg shadow-lg p-8 text-white">
    <h3 class="text-2xl font-bold mb-4">🎉 Добро пожаловать в UnPerm!</h3>
    <p class="text-indigo-100 mb-4">
        Мощная система управления разрешениями для Laravel. Управляйте Actions, Roles, Groups и назначайте права пользователям с помощью интуитивного интерфейса.
    </p>
    <div class="flex space-x-4">
        <a href="https://github.com/dfiks/unperm" target="_blank" class="bg-white text-indigo-600 px-6 py-2 rounded-lg font-semibold hover:bg-indigo-50 transition">
            <i class="fab fa-github mr-2"></i> GitHub
        </a>
        <a href="/unperm/docs" class="bg-indigo-700 text-white px-6 py-2 rounded-lg font-semibold hover:bg-indigo-800 transition">
            <i class="fas fa-book mr-2"></i> Документация
        </a>
    </div>
</div>
@endsection

