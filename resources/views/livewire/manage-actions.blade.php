<div class="bg-white rounded-2xl shadow-sm p-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h3 class="text-2xl font-bold text-gray-800">Actions</h3>
            <p class="text-gray-500 mt-1">Управление действиями и разрешениями</p>
        </div>
        <button wire:click="create" class="bg-gradient-to-r from-purple-500 to-indigo-600 hover:from-purple-600 hover:to-indigo-700 text-white px-6 py-3 rounded-xl font-semibold transition-smooth flex items-center shadow-lg">
            <i class="fas fa-plus mr-2"></i> Создать Action
        </button>
    </div>

    <!-- Search -->
    <div class="mb-6">
        <input wire:model.live="search" type="text" placeholder="Поиск по названию или slug..." 
               class="w-full px-5 py-3.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-gray-50 transition-smooth">
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-xl border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Название</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Slug</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Описание</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Bitmask</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Действия</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($actions as $action)
                    <tr class="hover:bg-gray-50 transition-smooth">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <button wire:click="toggleExpand('{{ $action->id }}')" 
                                        class="mr-2 text-gray-400 hover:text-gray-600 transition-smooth">
                                    <i class="fas fa-chevron-{{ isset($expandedActions[$action->id]) && $expandedActions[$action->id] ? 'down' : 'right' }} text-xs"></i>
                                </button>
                                <div class="text-sm font-semibold text-gray-800">{{ $action->name }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1.5 text-xs font-mono bg-purple-50 text-purple-700 rounded-lg">{{ $action->slug }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-600">{{ Str::limit($action->description, 50) }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1.5 text-xs font-mono bg-gray-100 text-gray-700 rounded-lg">{{ Str::limit($action->bitmask, 12) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button wire:click="edit('{{ $action->id }}')" class="text-indigo-600 hover:text-indigo-800 mr-4 transition-smooth">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button wire:click="delete('{{ $action->id }}')" onclick="return confirm('Удалить этот action?')" class="text-red-500 hover:text-red-700 transition-smooth">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    
                    @if(isset($expandedActions[$action->id]) && $expandedActions[$action->id])
                        <tr class="bg-indigo-50">
                            <td colspan="5" class="px-6 py-4">
                                <div class="ml-8">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                                        <i class="fas fa-link mr-2 text-indigo-600"></i>
                                        Связанные ресурсы (Resource Actions)
                                    </h4>
                                    
                                    @if(isset($resourceActionsMap[$action->id]) && $resourceActionsMap[$action->id]->count() > 0)
                                        <div class="space-y-2">
                                            @foreach($resourceActionsMap[$action->id] as $resourceAction)
                                                <div class="bg-white rounded-lg p-3 border border-indigo-200">
                                                    <div class="flex items-start justify-between">
                                                        <div class="flex-1">
                                                            <div class="flex items-center space-x-2 mb-1">
                                                                <span class="px-2 py-0.5 text-xs font-mono bg-purple-100 text-purple-800 rounded">
                                                                    {{ $resourceAction->getResourceClassName() }}
                                                                </span>
                                                                <span class="text-xs text-gray-500">#{{ Str::limit($resourceAction->resource_id, 8) }}</span>
                                                                <span class="px-2 py-0.5 text-xs bg-indigo-100 text-indigo-700 rounded font-medium">
                                                                    {{ $resourceAction->action_type }}
                                                                </span>
                                                            </div>
                                                            <div class="text-xs text-gray-600 mt-1">
                                                                {{ $resourceAction->description }}
                                                            </div>
                                                            @if(isset($resourceAction->usersCount) && $resourceAction->usersCount > 0)
                                                                <div class="flex items-center mt-2 space-x-1">
                                                                    <i class="fas fa-users text-xs text-gray-400"></i>
                                                                    <span class="text-xs text-gray-500">
                                                                        Пользователей: {{ $resourceAction->usersCount }}
                                                                    </span>
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <div class="text-xs text-gray-400">
                                                            {{ $resourceAction->created_at->diffForHumans() }}
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-sm text-gray-500 italic">
                                            Нет связанных resource actions
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center">
                            <i class="fas fa-inbox text-5xl mb-4 text-gray-300"></i>
                            <p class="text-gray-500 font-medium">Нет actions. Создайте первый!</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $actions->links() }}
    </div>

    <!-- Orphaned Resource Actions (без глобального action) -->
    @if(isset($orphanedResourceActions) && $orphanedResourceActions->count() > 0)
        <div class="mt-8">
            <div class="bg-gradient-to-r from-yellow-50 to-amber-50 border-l-4 border-yellow-400 rounded-xl p-6 mb-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-600 text-2xl"></i>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-lg font-bold text-gray-900 mb-2">
                            Resource Actions без глобального Action
                        </h3>
                        <p class="text-sm text-gray-700 mb-4">
                            Найдены Resource Actions, для которых не существует соответствующий глобальный Action. 
                            Создайте глобальные Actions, чтобы удобно управлять ими в админке.
                        </p>
                        
                        <div class="space-y-3">
                            @foreach($orphanedResourceActions as $group)
                                <div class="bg-white rounded-lg p-4 border border-yellow-200 flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3">
                                            <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-lg text-sm font-mono">
                                                {{ class_basename($group->resource_type) }}
                                            </span>
                                            <span class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded-lg text-sm font-semibold">
                                                {{ $group->action_type }}
                                            </span>
                                            <span class="text-gray-500 text-sm">
                                                <i class="fas fa-database mr-1"></i>
                                                {{ $group->count }} записей
                                            </span>
                                        </div>
                                        <div class="mt-2 text-xs text-gray-600">
                                            Будет создан Action: 
                                            <code class="bg-gray-100 px-2 py-1 rounded text-xs">
                                                {{ $this->getResourceKeyFromType($group->resource_type) }}.{{ $group->action_type }}
                                            </code>
                                        </div>
                                    </div>
                                    <button 
                                        wire:click="createGlobalActionFromGroup('{{ $group->resource_type }}', '{{ $group->action_type }}')"
                                        class="ml-4 px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all shadow-md hover:shadow-lg flex items-center space-x-2"
                                    >
                                        <i class="fas fa-plus"></i>
                                        <span>Создать Global Action</span>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4">
                <div class="flex justify-between items-center p-6 border-b border-gray-200">
                    <h3 class="text-2xl font-bold text-gray-800">
                        {{ $editingActionId ? 'Редактировать Action' : 'Создать Action' }}
                    </h3>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 transition-smooth">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form wire:submit.prevent="save" class="p-6">
                    <div class="space-y-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Название</label>
                            <input wire:model="name" type="text" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-smooth" placeholder="View users">
                            @error('name') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Slug</label>
                            <input wire:model="slug" type="text" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent font-mono transition-smooth" placeholder="users.view">
                            @error('slug') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Описание</label>
                            <textarea wire:model="description" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-smooth" placeholder="Описание action..."></textarea>
                            @error('description') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-8">
                        <button type="button" wire:click="closeModal" class="px-6 py-3 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-50 transition-smooth font-medium">
                            Отмена
                        </button>
                        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-purple-500 to-indigo-600 text-white rounded-xl hover:from-purple-600 hover:to-indigo-700 transition-smooth font-semibold shadow-lg">
                            {{ $editingActionId ? 'Обновить' : 'Создать' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
