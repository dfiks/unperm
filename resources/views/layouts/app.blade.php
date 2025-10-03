<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'UnPerm Dashboard')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @livewireStyles
    <style>
        /* Кастомный scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Для Firefox */
        * {
            scrollbar-width: thin;
            scrollbar-color: #888 #f1f1f1;
        }

        body {
            background: #e5e5e5;
        }

        /* Плавные переходы */
        .transition-smooth {
            transition: all 0.2s ease;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Boxed Container -->
    <div class="min-h-screen p-5">
        <div class="max-w-[1920px] mx-auto bg-white rounded-xl shadow-2xl overflow-hidden" style="height: calc(100vh - 40px);">
            <div class="flex h-full">
                <!-- Sidebar -->
                <aside class="w-64 bg-black text-white flex-shrink-0 m-1.5 rounded-lg flex flex-col">
                    <div class="p-6 border-b border-gray-800">
                        <h1 class="text-2xl font-bold tracking-tight">UnPerm</h1>
                        <p class="text-gray-400 text-sm mt-1 font-light">Permission Manager</p>
                    </div>
                    
                    <nav class="mt-4 flex-1">
                        <a href="{{ route('unperm.dashboard') }}" class="flex items-center px-5 py-3 mx-2 rounded-lg transition-smooth hover:bg-gray-900 {{ request()->routeIs('unperm.dashboard') ? 'bg-gray-900 font-medium' : 'text-gray-400' }}">
                            <i class="fas fa-home w-5 text-sm"></i>
                            <span class="ml-3 text-sm">Dashboard</span>
                        </a>
                        <a href="{{ route('unperm.actions') }}" class="flex items-center px-5 py-3 mx-2 rounded-lg transition-smooth hover:bg-gray-900 {{ request()->routeIs('unperm.actions') ? 'bg-gray-900 font-medium' : 'text-gray-400' }}">
                            <i class="fas fa-bolt w-5 text-sm"></i>
                            <span class="ml-3 text-sm">Actions</span>
                        </a>
                        <a href="{{ route('unperm.roles') }}" class="flex items-center px-5 py-3 mx-2 rounded-lg transition-smooth hover:bg-gray-900 {{ request()->routeIs('unperm.roles') ? 'bg-gray-900 font-medium' : 'text-gray-400' }}">
                            <i class="fas fa-user-tag w-5 text-sm"></i>
                            <span class="ml-3 text-sm">Roles</span>
                        </a>
                        <a href="{{ route('unperm.groups') }}" class="flex items-center px-5 py-3 mx-2 rounded-lg transition-smooth hover:bg-gray-900 {{ request()->routeIs('unperm.groups') ? 'bg-gray-900 font-medium' : 'text-gray-400' }}">
                            <i class="fas fa-users w-5 text-sm"></i>
                            <span class="ml-3 text-sm">Groups</span>
                        </a>
                        <a href="{{ route('unperm.users') }}" class="flex items-center px-5 py-3 mx-2 rounded-lg transition-smooth hover:bg-gray-900 {{ request()->routeIs('unperm.users') ? 'bg-gray-900 font-medium' : 'text-gray-400' }}">
                            <i class="fas fa-user-shield w-5 text-sm"></i>
                            <span class="ml-3 text-sm">Users</span>
                        </a>
                        <a href="{{ route('unperm.resources') }}" class="flex items-center px-5 py-3 mx-2 rounded-lg transition-smooth hover:bg-gray-900 {{ request()->routeIs('unperm.resources') ? 'bg-gray-900 font-medium' : 'text-gray-400' }}">
                            <i class="fas fa-folder-open w-5 text-sm"></i>
                            <span class="ml-3 text-sm">Resources</span>
                        </a>
                    </nav>
                    
                    <div class="p-6 text-xs text-gray-500 border-t border-gray-800">
                        <p class="font-medium">Version 1.0.0</p>
                        <p class="mt-1">© 2025 DFiks</p>
                    </div>
                </aside>

                <!-- Main Content -->
                <main class="flex-1 flex flex-col min-w-0">
                    <!-- Header -->
                    <header class="bg-white border-b border-gray-200">
                        <div class="px-8 py-5 flex items-center justify-between">
                            <h2 class="text-2xl font-semibold text-gray-900 tracking-tight">
                                @yield('header', 'Dashboard')
                            </h2>
                            <div class="flex items-center space-x-4">
                                <button class="text-gray-400 hover:text-gray-600 transition-smooth">
                                    <i class="fas fa-bell text-lg"></i>
                                </button>
                                <div class="w-9 h-9 bg-black rounded-full flex items-center justify-center text-white text-sm font-medium">
                                    A
                                </div>
                            </div>
                        </div>
                    </header>

                    <!-- Content Area -->
                    <div class="flex-1 overflow-y-auto bg-gray-50 p-8">
                        @yield('content')
                    </div>
                </main>
            </div>
        </div>
    </div>

    @livewireScripts
</body>
</html>
