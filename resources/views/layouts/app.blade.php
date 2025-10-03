<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'UnPerm Dashboard')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    @livewireStyles
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-gradient-to-b from-indigo-900 to-indigo-700 text-white">
            <div class="p-6">
                <h1 class="text-2xl font-bold flex items-center">
                    <i class="fas fa-shield-alt mr-2"></i>
                    UnPerm
                </h1>
                <p class="text-indigo-200 text-sm mt-1">Управление разрешениями</p>
            </div>
            
            <nav class="mt-6">
                <a href="{{ route('unperm.dashboard') }}" class="flex items-center px-6 py-3 hover:bg-indigo-800 transition {{ request()->routeIs('unperm.dashboard') ? 'bg-indigo-800 border-l-4 border-white' : '' }}">
                    <i class="fas fa-chart-line w-5"></i>
                    <span class="ml-3">Dashboard</span>
                </a>
                <a href="{{ route('unperm.actions') }}" class="flex items-center px-6 py-3 hover:bg-indigo-800 transition {{ request()->routeIs('unperm.actions') ? 'bg-indigo-800 border-l-4 border-white' : '' }}">
                    <i class="fas fa-bolt w-5"></i>
                    <span class="ml-3">Actions</span>
                </a>
                <a href="{{ route('unperm.roles') }}" class="flex items-center px-6 py-3 hover:bg-indigo-800 transition {{ request()->routeIs('unperm.roles') ? 'bg-indigo-800 border-l-4 border-white' : '' }}">
                    <i class="fas fa-user-tag w-5"></i>
                    <span class="ml-3">Roles</span>
                </a>
                <a href="{{ route('unperm.groups') }}" class="flex items-center px-6 py-3 hover:bg-indigo-800 transition {{ request()->routeIs('unperm.groups') ? 'bg-indigo-800 border-l-4 border-white' : '' }}">
                    <i class="fas fa-users w-5"></i>
                    <span class="ml-3">Groups</span>
                </a>
                <a href="{{ route('unperm.users') }}" class="flex items-center px-6 py-3 hover:bg-indigo-800 transition {{ request()->routeIs('unperm.users') ? 'bg-indigo-800 border-l-4 border-white' : '' }}">
                    <i class="fas fa-user-shield w-5"></i>
                    <span class="ml-3">Users</span>
                </a>
                <a href="{{ route('unperm.resources') }}" class="flex items-center px-6 py-3 hover:bg-indigo-800 transition {{ request()->routeIs('unperm.resources') ? 'bg-indigo-800 border-l-4 border-white' : '' }}">
                    <i class="fas fa-folder-open w-5"></i>
                    <span class="ml-3">Resources</span>
                </a>
            </nav>
            
            <div class="absolute bottom-0 w-64 p-6 text-sm text-indigo-200">
                <p>Version 1.0.0</p>
                <p class="mt-1">© 2025 DFiks</p>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1">
            <!-- Header -->
            <header class="bg-white shadow-sm">
                <div class="px-8 py-4 flex items-center justify-between">
                    <h2 class="text-2xl font-semibold text-gray-800">
                        @yield('header', 'Dashboard')
                    </h2>
                    <div class="flex items-center space-x-4">
                        <button class="text-gray-600 hover:text-gray-800">
                            <i class="fas fa-bell text-xl"></i>
                        </button>
                        <div class="w-10 h-10 bg-indigo-600 rounded-full flex items-center justify-center text-white font-bold">
                            A
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="p-8">
                @if (session('message'))
                    <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('message') }}</span>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    @livewireScripts
</body>
</html>

