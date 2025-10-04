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
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f3f4f6;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }

        * {
            scrollbar-width: thin;
            scrollbar-color: #d1d5db #f3f4f6;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .sidebar-gradient {
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
        }

        .transition-smooth {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .boxed-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .nav-item-active {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body>
    <div class="min-h-screen p-6">
        <div class="max-w-[2200px] mx-auto bg-white rounded-3xl shadow-2xl overflow-hidden" style="height: calc(100vh - 48px);">
            <div class="flex h-full gap-6 p-6">
                <!-- Sidebar -->
                <aside class="w-72 sidebar-gradient text-white flex-shrink-0 rounded-2xl flex flex-col shadow-xl">
                    <div class="p-8">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-white bg-opacity-20 rounded-xl flex items-center justify-center backdrop-blur">
                                <i class="fas fa-shield-alt text-white text-lg"></i>
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold tracking-tight">UnPerm</h1>
                                <p class="text-purple-200 text-xs font-medium">Permission Manager</p>
                            </div>
                        </div>
                    </div>
                    
                    <nav class="flex-1 px-4 space-y-1">
                        <a href="{{ route('unperm.dashboard') }}" class="flex items-center px-4 py-3.5 rounded-xl transition-smooth nav-item {{ request()->routeIs('unperm.dashboard') ? 'nav-item-active font-semibold' : 'font-medium opacity-90' }}">
                            <i class="fas fa-home w-5 text-base"></i>
                            <span class="ml-3">Dashboard</span>
                        </a>
                        <a href="{{ route('unperm.actions') }}" class="flex items-center px-4 py-3.5 rounded-xl transition-smooth nav-item {{ request()->routeIs('unperm.actions') ? 'nav-item-active font-semibold' : 'font-medium opacity-90' }}">
                            <i class="fas fa-bolt w-5 text-base"></i>
                            <span class="ml-3">Actions</span>
                        </a>
                        <a href="{{ route('unperm.roles') }}" class="flex items-center px-4 py-3.5 rounded-xl transition-smooth nav-item {{ request()->routeIs('unperm.roles') ? 'nav-item-active font-semibold' : 'font-medium opacity-90' }}">
                            <i class="fas fa-user-tag w-5 text-base"></i>
                            <span class="ml-3">Roles</span>
                        </a>
                        <a href="{{ route('unperm.groups') }}" class="flex items-center px-4 py-3.5 rounded-xl transition-smooth nav-item {{ request()->routeIs('unperm.groups') ? 'nav-item-active font-semibold' : 'font-medium opacity-90' }}">
                            <i class="fas fa-users w-5 text-base"></i>
                            <span class="ml-3">Groups</span>
                        </a>
                        <a href="{{ route('unperm.users') }}" class="flex items-center px-4 py-3.5 rounded-xl transition-smooth nav-item {{ request()->routeIs('unperm.users') ? 'nav-item-active font-semibold' : 'font-medium opacity-90' }}">
                            <i class="fas fa-user-shield w-5 text-base"></i>
                            <span class="ml-3">Users</span>
                        </a>
                        <a href="{{ route('unperm.resources') }}" class="flex items-center px-4 py-3.5 rounded-xl transition-smooth nav-item {{ request()->routeIs('unperm.resources') ? 'nav-item-active font-semibold' : 'font-medium opacity-90' }}">
                            <i class="fas fa-folder-open w-5 text-base"></i>
                            <span class="ml-3">Resources</span>
                        </a>
                    </nav>
                    
                    <div class="p-6 mt-auto">
                        <div class="bg-white bg-opacity-10 backdrop-blur rounded-xl p-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                    <span class="text-sm font-bold">A</span>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold">Admin</p>
                                    <p class="text-xs text-purple-200">admin@example.com</p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 text-xs text-purple-200 text-center opacity-75">
                            <p>Version 1.0.0 © 2025 DFiks</p>
                        </div>
                    </div>
                </aside>

                <!-- Main Content -->
                <main class="flex-1 flex flex-col min-w-0 bg-gray-50 rounded-2xl">
                    <!-- Header -->
                    <header class="bg-white rounded-2xl mb-6 shadow-sm">
                        <div class="px-8 py-6 flex items-center justify-between">
                            <div>
                                <h2 class="text-3xl font-bold text-gray-800 tracking-tight">
                                    @yield('header', 'Dashboard')
                                </h2>
                                <p class="text-gray-500 text-sm mt-1">@yield('description', 'Управление разрешениями')</p>
                            </div>
                            <div class="flex items-center space-x-3">
                                <button class="w-10 h-10 bg-gray-100 hover:bg-gray-200 rounded-xl flex items-center justify-center transition-smooth">
                                    <i class="fas fa-search text-gray-600"></i>
                                </button>
                                <button class="w-10 h-10 bg-gray-100 hover:bg-gray-200 rounded-xl flex items-center justify-center transition-smooth">
                                    <i class="fas fa-bell text-gray-600"></i>
                                </button>
                            </div>
                        </div>
                    </header>

                    <!-- Content Area -->
                    <div class="flex-1 overflow-y-auto px-2">
                        @yield('content')
                    </div>
                </main>
            </div>
        </div>
    </div>

    @livewireScripts
</body>
</html>
