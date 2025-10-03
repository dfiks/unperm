<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'UnPerm Dashboard')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    @livewireStyles
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="min-h-screen">
    <!-- Boxed Container -->
    <div class="container mx-auto px-[30px] py-[30px] min-h-screen">
        <div class="bg-white rounded-[24px] shadow-2xl overflow-hidden min-h-[calc(100vh-60px)] flex">
            
            <!-- Sidebar -->
            <aside class="w-64 bg-gradient-to-b from-indigo-900 to-indigo-700 text-white m-[5px] rounded-[20px] flex flex-col">
                <!-- Logo Section -->
                <div class="p-6 border-b border-indigo-600">
                    <h1 class="text-2xl font-bold flex items-center">
                        <i class="fas fa-shield-alt mr-2"></i>
                        UnPerm
                    </h1>
                    <p class="text-indigo-200 text-sm mt-1">Управление разрешениями</p>
                </div>
                
                <!-- Navigation -->
                <nav class="mt-4 flex-1 overflow-y-auto">
                    <a href="{{ route('unperm.dashboard') }}" 
                       class="flex items-center px-6 py-3 mx-2 rounded-lg hover:bg-indigo-800 transition-all duration-200 {{ request()->routeIs('unperm.dashboard') ? 'bg-indigo-800 shadow-lg' : '' }}">
                        <i class="fas fa-chart-line w-5"></i>
                        <span class="ml-3">Dashboard</span>
                    </a>
                    
                    <a href="{{ route('unperm.actions') }}" 
                       class="flex items-center px-6 py-3 mx-2 rounded-lg hover:bg-indigo-800 transition-all duration-200 {{ request()->routeIs('unperm.actions') ? 'bg-indigo-800 shadow-lg' : '' }}">
                        <i class="fas fa-bolt w-5"></i>
                        <span class="ml-3">Actions</span>
                    </a>
                    
                    <a href="{{ route('unperm.roles') }}" 
                       class="flex items-center px-6 py-3 mx-2 rounded-lg hover:bg-indigo-800 transition-all duration-200 {{ request()->routeIs('unperm.roles') ? 'bg-indigo-800 shadow-lg' : '' }}">
                        <i class="fas fa-user-tag w-5"></i>
                        <span class="ml-3">Roles</span>
                    </a>
                    
                    <a href="{{ route('unperm.groups') }}" 
                       class="flex items-center px-6 py-3 mx-2 rounded-lg hover:bg-indigo-800 transition-all duration-200 {{ request()->routeIs('unperm.groups') ? 'bg-indigo-800 shadow-lg' : '' }}">
                        <i class="fas fa-users w-5"></i>
                        <span class="ml-3">Groups</span>
                    </a>
                    
                    <a href="{{ route('unperm.users') }}" 
                       class="flex items-center px-6 py-3 mx-2 rounded-lg hover:bg-indigo-800 transition-all duration-200 {{ request()->routeIs('unperm.users') ? 'bg-indigo-800 shadow-lg' : '' }}">
                        <i class="fas fa-user-shield w-5"></i>
                        <span class="ml-3">Users</span>
                    </a>
                    
                    <a href="{{ route('unperm.resources') }}" 
                       class="flex items-center px-6 py-3 mx-2 rounded-lg hover:bg-indigo-800 transition-all duration-200 {{ request()->routeIs('unperm.resources') ? 'bg-indigo-800 shadow-lg' : '' }}">
                        <i class="fas fa-folder-open w-5"></i>
                        <span class="ml-3">Resources</span>
                    </a>
                </nav>
                
                <!-- Footer -->
                <div class="p-6 border-t border-indigo-600 text-sm text-indigo-200">
                    <p class="font-semibold">Version 1.0.0</p>
                    <p class="mt-1">© 2025 DFiks</p>
                </div>
            </aside>

            <!-- Main Content Area -->
            <main class="flex-1 flex flex-col bg-gray-50">
                <!-- Header -->
                <header class="bg-white border-b border-gray-200 sticky top-0 z-10">
                    <div class="px-8 py-6 flex items-center justify-between">
                        <div>
                            <h2 class="text-3xl font-bold text-gray-900">
                                @yield('header', 'Dashboard')
                            </h2>
                            <p class="text-gray-500 text-sm mt-1">
                                @yield('header_subtitle', 'Управление системой разрешений')
                            </p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <button class="relative text-gray-600 hover:text-gray-900 transition">
                                <i class="fas fa-bell text-xl"></i>
                                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">3</span>
                            </button>
                            <div class="flex items-center space-x-3 pl-4 border-l border-gray-300">
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-gray-900">Admin</p>
                                    <p class="text-xs text-gray-500">Super Admin</p>
                                </div>
                                <div class="w-10 h-10 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-full flex items-center justify-center text-white font-bold shadow-lg">
                                    A
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Content Area -->
                <div class="flex-1 p-8 overflow-y-auto">
                    @if (session('message'))
                        <div class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-800 px-6 py-4 rounded-lg shadow-sm animate-pulse" role="alert">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
                                <span class="font-medium">{{ session('message') }}</span>
                            </div>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-800 px-6 py-4 rounded-lg shadow-sm" role="alert">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-circle text-red-500 text-xl mr-3"></i>
                                <span class="font-medium">{{ session('error') }}</span>
                            </div>
                        </div>
                    @endif

                    @yield('content')
                </div>

                <!-- Footer -->
                <footer class="bg-white border-t border-gray-200 py-4 px-8">
                    <div class="flex items-center justify-between text-sm text-gray-600">
                        <div>
                            <span>Powered by</span>
                            <span class="font-semibold text-indigo-600">UnPerm Package</span>
                        </div>
                        <div class="flex items-center space-x-4">
                            <a href="#" class="hover:text-indigo-600 transition">
                                <i class="fas fa-book mr-1"></i> Документация
                            </a>
                            <a href="#" class="hover:text-indigo-600 transition">
                                <i class="fas fa-life-ring mr-1"></i> Поддержка
                            </a>
                        </div>
                    </div>
                </footer>
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
