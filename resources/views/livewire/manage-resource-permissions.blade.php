<div class="bg-white rounded-2xl shadow-sm p-8">
    {{-- Header --}}
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-800">Управление правами на ресурсы</h2>
        <p class="text-gray-500 mt-1">Назначайте права пользователям на конкретные записи</p>
    </div>

    {{-- Flash messages --}}
    @if (session()->has('message'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
            {{ session('error') }}
        </div>
    @endif

    {{-- Model selector --}}
    <div class="mb-6 bg-gradient-to-r from-purple-50 to-indigo-50 rounded-xl shadow-sm p-5 border border-purple-200">
        <div class="flex items-center space-x-4">
            <div class="flex-1">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-layer-group mr-2 text-purple-600"></i>Выберите тип ресурса
                </label>
                <select wire:model.live="selectedResourceModel" wire:change="changeModel" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-smooth bg-white">
                    @foreach($availableResourceModels as $class => $info)
                        <option value="{{ $class }}">
                            {{ $info['name'] }} ({{ $info['table'] }})
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="flex-1">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-search mr-2 text-indigo-600"></i>Поиск
                </label>
                <input type="text" wire:model.live.debounce.300ms="search" 
                       placeholder="Поиск по названию..." 
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-smooth bg-gray-50">
            </div>
        </div>
    </div>

    {{-- Resources table --}}
    @if($selectedResourceModel && count($resources) > 0)
        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            ID
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Название
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Создано
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Действия
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($resources as $resource)
                        <tr class="hover:bg-gray-50 transition-smooth">
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <code class="bg-purple-50 text-purple-700 px-3 py-1.5 rounded-lg font-mono text-xs">{{ substr($resource->getKey(), 0, 8) }}...</code>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-800">
                                    {{ $resource->name ?? $resource->title ?? $resource->slug ?? 'Без названия' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $resource->created_at?->format('d.m.Y H:i') ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <button wire:click="managePermissions('{{ $resource->getKey() }}')" 
                                        class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-purple-500 to-indigo-600 hover:from-purple-600 hover:to-indigo-700 text-white text-sm font-semibold rounded-xl transition-smooth shadow-md">
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
        <div class="bg-white rounded-xl shadow-sm p-12 text-center border border-gray-200">
            <i class="fas fa-inbox text-gray-300 text-6xl mb-4"></i>
            <p class="text-gray-500 font-medium">{{ $search ? 'Ничего не найдено' : 'Нет доступных ресурсов' }}</p>
        </div>
    @endif

    {{-- Permissions modal --}}
    @if($showPermissionsModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm overflow-y-auto h-full w-full z-50 p-4" wire:click="closeModal">
            <div class="relative top-20 mx-auto p-6 border border-gray-200 w-11/12 max-w-4xl shadow-2xl rounded-2xl bg-white" 
                 wire:click.stop>
                {{-- Modal header --}}
                <div class="flex justify-between items-center pb-5 border-b border-gray-200">
                    <h3 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-key mr-2 text-purple-600"></i>Права доступа: {{ $resourceName }}
                    </h3>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 transition-smooth">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>

                {{-- Add user form --}}
                <div class="mt-6 p-5 bg-gradient-to-r from-purple-50 to-indigo-50 rounded-xl border border-purple-200">
                    <h4 class="text-lg font-semibold mb-4 text-gray-800">Добавить пользователя</h4>
                    
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Модель пользователя</label>
                            <select wire:model.live="selectedUserModel" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-smooth bg-white">
                                @foreach($availableUserModels as $modelClass => $modelInfo)
                                    <option value="{{ $modelClass }}">{{ $modelInfo['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Пользователь</label>
                            <select wire:model="newUserId" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-smooth bg-white">
                                <option value="">Выберите пользователя...</option>
                                @foreach($availableUsers as $userId => $userLabel)
                                    <option value="{{ $userId }}">{{ $userLabel }}</option>
                                @endforeach
                            </select>
                            @error('newUserId') 
                                <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> 
                            @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Действия на ресурс</label>
                            <div class="border border-gray-300 rounded-xl p-3 bg-white space-y-2">
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach($availableActions as $action)
                                        <label class="flex items-center hover:bg-gray-50 p-2 rounded cursor-pointer transition-smooth">
                                            <input type="checkbox" wire:model="newUserActions" value="{{ $action }}" 
                                                   class="w-4 h-4 rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                                            <span class="ml-2 text-sm text-gray-700 font-medium">{{ ucfirst($action) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                
                                <div class="pt-2 border-t border-gray-200">
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Или кастомное действие:</label>
                                    <input type="text" wire:model="customAction" 
                                           placeholder="например: approve, archive..."
                                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-smooth">
                                </div>
                            </div>
                            @error('newUserActions') 
                                <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> 
                            @enderror
                        </div>
                    </div>
                    
                    <button wire:click="addUserPermission" 
                            class="mt-4 px-6 py-3 bg-gradient-to-r from-purple-500 to-indigo-600 hover:from-purple-600 hover:to-indigo-700 text-white rounded-xl transition-smooth font-semibold shadow-md">
                        <i class="fas fa-plus mr-2"></i> Добавить
                    </button>
                </div>

                {{-- Current permissions list --}}
                <div class="mt-6">
                    <h4 class="text-lg font-semibold mb-4 text-gray-800">Текущие права</h4>
                    
                    @if(count($userPermissions) > 0)
                        <div class="space-y-3">
                            @foreach($userPermissions as $userId => $userInfo)
                                <div class="flex items-center justify-between p-5 bg-gray-50 border border-gray-200 rounded-xl hover:shadow-md transition-smooth">
                                    <div class="flex-1">
                                        <div class="font-semibold text-gray-800">{{ $userInfo['name'] }}</div>
                                        <div class="text-sm text-gray-500">{{ $userInfo['email'] }}</div>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @foreach($userInfo['actions'] as $action)
                                                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                    {{ $action }}
                                                    <button wire:click="revokeUserPermission('{{ $userId }}', '{{ $action }}')" 
                                                            class="ml-2 text-indigo-500 hover:text-indigo-700 transition-smooth">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                    <button wire:click="revokeAllUserPermissions('{{ $userId }}')" 
                                            class="ml-4 px-4 py-2 bg-red-50 hover:bg-red-100 text-red-600 text-sm rounded-xl transition-smooth font-semibold border border-red-200">
                                        <i class="fas fa-trash mr-2"></i> Отозвать все
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12 text-gray-500 bg-gray-50 rounded-xl border border-gray-200">
                            <i class="fas fa-users-slash text-5xl mb-3 text-gray-300"></i>
                            <p class="font-medium">Нет назначенных прав</p>
                        </div>
                    @endif
                </div>

                {{-- Modal footer --}}
                <div class="mt-6 flex justify-end space-x-3 pt-5 border-t border-gray-200">
                    <button wire:click="closeModal" 
                            class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-smooth font-semibold">
                        Закрыть
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
