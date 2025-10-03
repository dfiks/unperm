<div class="p-6">
    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Управление правами на ресурсы</h2>
        <p class="text-gray-600">Назначайте права пользователям на конкретные записи</p>
    </div>

    {{-- Flash messages --}}
    @if (session()->has('message'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
            {{ session('error') }}
        </div>
    @endif

    {{-- Model selector --}}
    <div class="mb-6 bg-white rounded-lg shadow p-4">
        <div class="flex items-center space-x-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-layer-group mr-2"></i>Выберите тип ресурса
                </label>
                <select wire:model.live="selectedResourceModel" wire:change="changeModel" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    @foreach($availableResourceModels as $class => $info)
                        <option value="{{ $class }}">
                            {{ $info['name'] }} ({{ $info['table'] }})
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-search mr-2"></i>Поиск
                </label>
                <input type="text" wire:model.live.debounce.300ms="search" 
                       placeholder="Поиск по названию..." 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>
    </div>

    {{-- Resources table --}}
    @if($selectedResourceModel && count($resources) > 0)
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            ID
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Название
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Создано
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Действия
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($resources as $resource)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <code class="bg-gray-100 px-2 py-1 rounded">{{ substr($resource->getKey(), 0, 8) }}...</code>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $resource->name ?? $resource->title ?? $resource->slug ?? 'Без названия' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $resource->created_at?->format('d.m.Y H:i') ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <button wire:click="managePermissions('{{ $resource->getKey() }}')" 
                                        class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                                    <i class="fas fa-key mr-2"></i> Управление правами
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            
            {{-- Pagination --}}
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                {{ $resources->links() }}
            </div>
        </div>
    @else
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <i class="fas fa-inbox text-gray-400 text-5xl mb-4"></i>
            <p class="text-gray-600">{{ $search ? 'Ничего не найдено' : 'Нет доступных ресурсов' }}</p>
        </div>
    @endif

    {{-- Permissions modal --}}
    @if($showPermissionsModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click="closeModal">
            <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-lg bg-white" 
                 wire:click.stop>
                {{-- Modal header --}}
                <div class="flex justify-between items-center pb-4 border-b">
                    <h3 class="text-xl font-bold text-gray-900">
                        <i class="fas fa-key mr-2"></i>Права доступа: {{ $resourceName }}
                    </h3>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 transition">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>

                {{-- Add user form --}}
                <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                    <h4 class="text-lg font-semibold mb-4">Добавить пользователя</h4>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email пользователя</label>
                            <input type="email" wire:model="newUserEmail" 
                                   placeholder="user@example.com"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            @error('newUserEmail') 
                                <span class="text-red-600 text-sm">{{ $message }}</span> 
                            @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Действия</label>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach($availableActions as $action)
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" wire:model="newUserActions" value="{{ $action }}" 
                                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="ml-2 text-sm">{{ ucfirst($action) }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('newUserActions') 
                                <span class="text-red-600 text-sm">{{ $message }}</span> 
                            @enderror
                        </div>
                    </div>
                    
                    <button wire:click="addUserPermission" 
                            class="mt-4 px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                        <i class="fas fa-plus mr-2"></i> Добавить
                    </button>
                </div>

                {{-- Current permissions list --}}
                <div class="mt-6">
                    <h4 class="text-lg font-semibold mb-4">Текущие права</h4>
                    
                    @if(count($userPermissions) > 0)
                        <div class="space-y-3">
                            @foreach($userPermissions as $userId => $userInfo)
                                <div class="flex items-center justify-between p-4 bg-white border border-gray-200 rounded-lg hover:shadow-md transition">
                                    <div class="flex-1">
                                        <div class="font-semibold text-gray-900">{{ $userInfo['name'] }}</div>
                                        <div class="text-sm text-gray-600">{{ $userInfo['email'] }}</div>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @foreach($userInfo['actions'] as $action)
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                                    {{ ucfirst($action) }}
                                                    <button wire:click="revokeUserPermission('{{ $userId }}', '{{ $action }}')" 
                                                            class="ml-2 text-indigo-600 hover:text-indigo-800">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                    <button wire:click="revokeAllUserPermissions('{{ $userId }}')" 
                                            class="ml-4 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg transition">
                                        <i class="fas fa-trash mr-2"></i> Отозвать все
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-users-slash text-4xl mb-2"></i>
                            <p>Нет назначенных прав</p>
                        </div>
                    @endif
                </div>

                {{-- Modal footer --}}
                <div class="mt-6 flex justify-end space-x-3 pt-4 border-t">
                    <button wire:click="closeModal" 
                            class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition">
                        Закрыть
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

