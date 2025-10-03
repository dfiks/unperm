@extends('unperm::layouts.app')

@section('title', 'Users - UnPerm')
@section('header', 'Управление правами пользователей')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="text-center py-12">
        <i class="fas fa-user-shield text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-xl font-bold text-gray-700 mb-2">Управление правами пользователей</h3>
        <p class="text-gray-500 mb-6">Здесь будет интерфейс для назначения permissions пользователям</p>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 max-w-2xl mx-auto text-left">
            <h4 class="font-bold text-blue-900 mb-2">Функционал:</h4>
            <ul class="text-blue-800 space-y-1">
                <li>✓ Поиск пользователей</li>
                <li>✓ Назначение Actions, Roles, Groups</li>
                <li>✓ Просмотр текущих разрешений</li>
                <li>✓ Массовое назначение прав</li>
                <li>✓ Resource permissions для конкретных записей</li>
            </ul>
        </div>
    </div>
</div>
@endsection

