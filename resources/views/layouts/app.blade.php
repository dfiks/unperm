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
            background: #f5f5f7;
        }

        .sidebar-purple {
            background: #7c3aed;
        }

        .transition-smooth {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .boxed-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .nav-item {
            position: relative;
        }

        .nav-item-active {
            background: rgba(255, 255, 255, 0.15);
        }

        .nav-item:hover:not(.nav-item-active) {
            background: rgba(255, 255, 255, 0.08);
        }
    </style>
</head>
<body>
    <div class="min-h-screen p-6">
        <div class="max-w-[2200px] mx-auto bg-white rounded-3xl shadow-2xl overflow-hidden" style="height: calc(100vh - 48px);">
            <div class="flex h-full gap-6 p-6">
                <!-- Sidebar -->
                <aside class="w-64 sidebar-purple text-white flex-shrink-0 rounded-2xl flex flex-col shadow-xl">
                    <div class="px-4 py-5 border-b border-white border-opacity-10">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-8 h-8 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-shield-alt text-white text-sm"></i>
                            </div>
                            <div>
                                <h1 class="text-lg font-bold tracking-tight">UnPerm</h1>
                                <p class="text-purple-200 text-xs">Permission Manager</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="px-3 py-2 text-xs font-semibold text-purple-200 uppercase tracking-wider mt-3">
                        Main Menu
                    </div>
                    
                    <nav class="flex-1 px-2.5 space-y-0.5">
                        <a href="{{ route('unperm.dashboard') }}" class="flex items-center px-3 py-2 rounded-lg transition-smooth nav-item {{ request()->routeIs('unperm.dashboard') ? 'nav-item-active' : '' }}">
                            <i class="fas fa-home w-4 text-sm"></i>
                            <span class="ml-2.5 text-sm font-medium">Dashboard</span>
                        </a>
                        <a href="{{ route('unperm.actions') }}" class="flex items-center px-3 py-2 rounded-lg transition-smooth nav-item {{ request()->routeIs('unperm.actions') ? 'nav-item-active' : '' }}">
                            <i class="fas fa-bolt w-4 text-sm"></i>
                            <span class="ml-2.5 text-sm font-medium">Actions</span>
                        </a>
                        <a href="{{ route('unperm.roles') }}" class="flex items-center px-3 py-2 rounded-lg transition-smooth nav-item {{ request()->routeIs('unperm.roles') ? 'nav-item-active' : '' }}">
                            <i class="fas fa-user-tag w-4 text-sm"></i>
                            <span class="ml-2.5 text-sm font-medium">Roles</span>
                        </a>
                        <a href="{{ route('unperm.groups') }}" class="flex items-center px-3 py-2 rounded-lg transition-smooth nav-item {{ request()->routeIs('unperm.groups') ? 'nav-item-active' : '' }}">
                            <i class="fas fa-users w-4 text-sm"></i>
                            <span class="ml-2.5 text-sm font-medium">Groups</span>
                        </a>
                        <a href="{{ route('unperm.users') }}" class="flex items-center px-3 py-2 rounded-lg transition-smooth nav-item {{ request()->routeIs('unperm.users') ? 'nav-item-active' : '' }}">
                            <i class="fas fa-user-shield w-4 text-sm"></i>
                            <span class="ml-2.5 text-sm font-medium">Users</span>
                        </a>
                        <a href="{{ route('unperm.resources') }}" class="flex items-center px-3 py-2 rounded-lg transition-smooth nav-item {{ request()->routeIs('unperm.resources') ? 'nav-item-active' : '' }}">
                            <i class="fas fa-folder-open w-4 text-sm"></i>
                            <span class="ml-2.5 text-sm font-medium">Resources</span>
                        </a>
                    </nav>
                    
                    <div class="px-3 py-2 text-xs font-semibold text-purple-200 uppercase tracking-wider border-t border-white border-opacity-10 mt-2">
                        Account
                    </div>
                    
                    <nav class="px-2.5 pb-3 space-y-0.5">
                        <a href="#" class="flex items-center px-3 py-2 rounded-lg transition-smooth nav-item">
                            <i class="fas fa-cog w-4 text-sm"></i>
                            <span class="ml-2.5 text-sm font-medium">Settings</span>
                        </a>
                        <a href="#" class="flex items-center px-3 py-2 rounded-lg transition-smooth nav-item">
                            <i class="fas fa-bell w-4 text-sm"></i>
                            <span class="ml-2.5 text-sm font-medium">Notifications</span>
                        </a>
                    </nav>
                    
                    <div class="p-3 mt-auto border-t border-white border-opacity-10">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-8 h-8 bg-white bg-opacity-20 rounded-lg flex items-center justify-center flex-shrink-0">
                                <span class="text-xs font-bold">A</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold truncate">Admin</p>
                                <p class="text-xs text-purple-200 truncate">admin@example.com</p>
                            </div>
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
