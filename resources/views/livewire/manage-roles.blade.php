<div class="bg-white rounded-2xl shadow-sm p-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h3 class="text-2xl font-bold text-gray-800">Roles</h3>
            <p class="text-gray-500 mt-1">Управление ролями</p>
        </div>
        <button wire:click="create" class="bg-gradient-to-r from-purple-500 to-indigo-600 hover:from-purple-600 hover:to-indigo-700 text-white px-6 py-3 rounded-xl font-semibold transition-smooth flex items-center shadow-lg">
            <i class="fas fa-plus mr-2"></i> Создать Role
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
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Действия</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($roles as $role)
                    <tr class="hover:bg-gray-50 transition-smooth">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-gray-800">{{ $role->name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1.5 text-xs font-mono bg-purple-50 text-purple-700 rounded-lg">{{ $role->slug }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-600">{{ Str::limit($role->description, 50) }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-2">
                                @forelse($role->actions as $action)
                                    <span class="px-2.5 py-1 text-xs bg-indigo-50 text-indigo-700 rounded-lg font-medium">{{ $action->slug }}</span>
                                @empty
                                    <span class="text-xs text-gray-400">Нет actions</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button wire:click="edit('{{ $role->id }}')" class="text-indigo-600 hover:text-indigo-800 mr-4 transition-smooth">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button wire:click="delete('{{ $role->id }}')" onclick="return confirm('Удалить эту role?')" class="text-red-500 hover:text-red-700 transition-smooth">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center">
                            <i class="fas fa-inbox text-5xl mb-4 text-gray-300"></i>
                            <p class="text-gray-500 font-medium">Нет roles. Создайте первую!</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $roles->links() }}
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4">
                <div class="flex justify-between items-center p-6 border-b border-gray-200">
                    <h3 class="text-2xl font-bold text-gray-800">
                        {{ $editingRoleId ? 'Редактировать Role' : 'Создать Role' }}
                    </h3>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 transition-smooth">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form wire:submit.prevent="save" class="p-6">
                    <div class="space-y-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Название</label>
                            <input wire:model="name" type="text" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-smooth" placeholder="Administrator">
                            @error('name') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Slug</label>
                            <input wire:model="slug" type="text" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent font-mono transition-smooth" placeholder="admin">
                            @error('slug') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Описание</label>
                            <textarea wire:model="description" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-smooth" placeholder="Описание role..."></textarea>
                            @error('description') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Actions</label>
                            <div class="border border-gray-300 rounded-xl p-4 max-h-48 overflow-y-auto bg-gray-50">
                                @foreach($allActions as $action)
                                    <label class="flex items-center mb-2 hover:bg-white p-2.5 rounded-lg cursor-pointer transition-smooth">
                                        <input type="checkbox" wire:model="selectedActions" value="{{ $action->id }}" class="mr-3 w-4 h-4 text-purple-600 rounded focus:ring-purple-500">
                                        <span class="text-sm text-gray-700 font-medium">{{ $action->name }} <span class="text-gray-500 font-mono text-xs">({{ $action->slug }})</span></span>
                                    </label>
                                @endforeach
                            </div>
                            @error('selectedActions') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-8">
                        <button type="button" wire:click="closeModal" class="px-6 py-3 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-50 transition-smooth font-medium">
                            Отмена
                        </button>
                        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-purple-500 to-indigo-600 text-white rounded-xl hover:from-purple-600 hover:to-indigo-700 transition-smooth font-semibold shadow-lg">
                            {{ $editingRoleId ? 'Обновить' : 'Создать' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
