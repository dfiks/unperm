<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'UnPerm Dashboard')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @livewireStyles
    <style>
        /* Кастомный scrollbar для темной темы */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #2d2d2d;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #555;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #777;
        }

        /* Для Firefox */
        * {
            scrollbar-width: thin;
            scrollbar-color: #555 #2d2d2d;
        }

        body {
            background: #0a0a0a;
        }

        .transition-smooth {
            transition: all 0.2s ease;
        }
    </style>
</head>
<body class="bg-gray-950">
    <!-- Larger Boxed Container -->
    <div class="min-h-screen p-5">
        <div class="max-w-[2200px] mx-auto bg-zinc-900 rounded-2xl shadow-2xl overflow-hidden border border-zinc-800" style="height: calc(100vh - 40px);">
            <div class="flex h-full">
                <!-- Sidebar -->
                <aside class="w-64 bg-zinc-950 text-white flex-shrink-0 m-1.5 rounded-xl flex flex-col border-r border-zinc-800">
                    <div class="p-6 border-b border-zinc-800">
                        <h1 class="text-2xl font-bold tracking-tight">UnPerm</h1>
                        <p class="text-zinc-500 text-sm mt-1 font-light">Permission Manager</p>
                    </div>
                    
                    <nav class="mt-4 flex-1 px-3">
                        <a href="{{ route('unperm.dashboard') }}" class="flex items-center px-4 py-3 mb-1 rounded-lg transition-smooth {{ request()->routeIs('unperm.dashboard') ? 'bg-zinc-800 text-white' : 'text-zinc-500 hover:bg-zinc-900 hover:text-zinc-300' }}">
                            <i class="fas fa-home w-5 text-sm"></i>
                            <span class="ml-3 text-sm">Dashboard</span>
                        </a>
                        <a href="{{ route('unperm.actions') }}" class="flex items-center px-4 py-3 mb-1 rounded-lg transition-smooth {{ request()->routeIs('unperm.actions') ? 'bg-zinc-800 text-white' : 'text-zinc-500 hover:bg-zinc-900 hover:text-zinc-300' }}">
                            <i class="fas fa-bolt w-5 text-sm"></i>
                            <span class="ml-3 text-sm">Actions</span>
                        </a>
                        <a href="{{ route('unperm.roles') }}" class="flex items-center px-4 py-3 mb-1 rounded-lg transition-smooth {{ request()->routeIs('unperm.roles') ? 'bg-zinc-800 text-white' : 'text-zinc-500 hover:bg-zinc-900 hover:text-zinc-300' }}">
                            <i class="fas fa-user-tag w-5 text-sm"></i>
                            <span class="ml-3 text-sm">Roles</span>
                        </a>
                        <a href="{{ route('unperm.groups') }}" class="flex items-center px-4 py-3 mb-1 rounded-lg transition-smooth {{ request()->routeIs('unperm.groups') ? 'bg-zinc-800 text-white' : 'text-zinc-500 hover:bg-zinc-900 hover:text-zinc-300' }}">
                            <i class="fas fa-users w-5 text-sm"></i>
                            <span class="ml-3 text-sm">Groups</span>
                        </a>
                        <a href="{{ route('unperm.users') }}" class="flex items-center px-4 py-3 mb-1 rounded-lg transition-smooth {{ request()->routeIs('unperm.users') ? 'bg-zinc-800 text-white' : 'text-zinc-500 hover:bg-zinc-900 hover:text-zinc-300' }}">
                            <i class="fas fa-user-shield w-5 text-sm"></i>
                            <span class="ml-3 text-sm">Users</span>
                        </a>
                        <a href="{{ route('unperm.resources') }}" class="flex items-center px-4 py-3 mb-1 rounded-lg transition-smooth {{ request()->routeIs('unperm.resources') ? 'bg-zinc-800 text-white' : 'text-zinc-500 hover:bg-zinc-900 hover:text-zinc-300' }}">
                            <i class="fas fa-folder-open w-5 text-sm"></i>
                            <span class="ml-3 text-sm">Resources</span>
                        </a>
                    </nav>
                    
                    <div class="p-6 text-xs text-zinc-600 border-t border-zinc-800">
                        <p class="font-medium">Version 1.0.0</p>
                        <p class="mt-1">© 2025 DFiks</p>
                    </div>
                </aside>

                <!-- Main Content -->
                <main class="flex-1 flex flex-col min-w-0">
                    <!-- Header -->
                    <header class="bg-zinc-900 border-b border-zinc-800">
                        <div class="px-8 py-5 flex items-center justify-between">
                            <h2 class="text-2xl font-semibold text-white tracking-tight">
                                @yield('header', 'Dashboard')
                            </h2>
                            <div class="flex items-center space-x-4">
                                <button class="text-zinc-500 hover:text-zinc-300 transition-smooth">
                                    <i class="fas fa-bell text-lg"></i>
                                </button>
                                <div class="w-9 h-9 bg-zinc-800 rounded-full flex items-center justify-center text-white text-sm font-medium border border-zinc-700">
                                    A
                                </div>
                            </div>
                        </div>
                    </header>

                    <!-- Content Area -->
                    <div class="flex-1 overflow-y-auto bg-zinc-900 p-8">
                        @yield('content')
                    </div>
                </main>
            </div>
        </div>
    </div>

    @livewireScripts
</body>
</html>
