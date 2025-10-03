<div class="p-6">
    <!-- Header -->
    <div class="mb-6">
        <h3 class="text-2xl font-bold text-gray-800">Управление разрешениями пользователей</h3>
        <p class="text-gray-600 mt-1">Назначение Actions, Roles и Groups пользователям</p>
    </div>

    <!-- Model Selector -->
    @if(count($availableModels) > 0)
        <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <label class="block text-sm font-medium text-blue-900 mb-3">
                <i class="fas fa-database mr-2"></i>
                Выберите модель пользователя
            </label>
            <select wire:model.live="selectedUserModel" wire:change="changeModel" class="w-full px-4 py-2 border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                @foreach($availableModels as $modelClass => $modelInfo)
                    <option value="{{ $modelClass }}">
                        {{ $modelInfo['name'] }} ({{ $modelInfo['table'] }})
                    </option>
                @endforeach
            </select>
            <p class="text-xs text-blue-700 mt-2">
                <i class="fas fa-info-circle mr-1"></i>
                Найдено моделей с HasPermissions trait: {{ count($availableModels) }}
            </p>
        </div>
    @else
        <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
            <i class="fas fa-exclamation-triangle text-4xl text-yellow-400 mb-3"></i>
            <h4 class="text-lg font-bold text-yellow-900 mb-2">Модели не найдены</h4>
            <p class="text-yellow-700">
                Не найдено ни одной модели, использующей HasPermissions trait.
                Добавьте trait к вашей модели User:
            </p>
            <code class="block mt-3 bg-yellow-100 p-3 rounded text-left text-sm">
                use DFiks\UnPerm\Traits\HasPermissions;<br>
                <br>
                class User extends Model {<br>
                &nbsp;&nbsp;&nbsp;&nbsp;use HasPermissions;<br>
                }
            </code>
        </div>
    @endif

    @if($selectedUserModel)
        <!-- Messages -->
        @if (session('error'))
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        <!-- Search -->
        <div class="mb-6">
            <input wire:model.live="search" type="text" placeholder="Поиск по имени или email..." 
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        <!-- Users Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Пользователь</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Roles</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Groups</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($users as $user)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 font-bold mr-3">
                                            {{ strtoupper(substr($this->getUserName($user), 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ $this->getUserName($user) }}</div>
                                            <div class="text-sm text-gray-500">{{ $this->getUserIdentifier($user) }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <span class="px-3 py-1 text-xs bg-blue-100 text-blue-700 rounded-full font-semibold">
                                            {{ $user->actions->count() }}
                                        </span>
                                        @if($user->actions->count() > 0)
                                            <span class="ml-2 text-xs text-gray-500">
                                                {{ $user->actions->take(2)->pluck('slug')->implode(', ') }}
                                                @if($user->actions->count() > 2) ... @endif
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <span class="px-3 py-1 text-xs bg-green-100 text-green-700 rounded-full font-semibold">
                                            {{ $user->roles->count() }}
                                        </span>
                                        @if($user->roles->count() > 0)
                                            <span class="ml-2 text-xs text-gray-500">
                                                {{ $user->roles->take(2)->pluck('slug')->implode(', ') }}
                                                @if($user->roles->count() > 2) ... @endif
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <span class="px-3 py-1 text-xs bg-purple-100 text-purple-700 rounded-full font-semibold">
                                            {{ $user->groups->count() }}
                                        </span>
                                        @if($user->groups->count() > 0)
                                            <span class="ml-2 text-xs text-gray-500">
                                                {{ $user->groups->take(2)->pluck('slug')->implode(', ') }}
                                                @if($user->groups->count() > 2) ... @endif
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <button wire:click="editPermissions('{{ $user->id }}')" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                                        <i class="fas fa-edit mr-2"></i> Управление
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-users text-4xl mb-3 text-gray-300"></i>
                                    <p>Пользователи не найдены</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $users->links() }}
            </div>
        </div>
    @endif

    <!-- Permissions Modal -->
    @if($showPermissionsModal && $selectedUserId)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-y-auto">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-6xl mx-4 my-8 max-h-[90vh] flex flex-col">
                <div class="flex justify-between items-center p-6 border-b flex-shrink-0">
                    <h3 class="text-xl font-bold text-gray-800">
                        <i class="fas fa-user-shield mr-2 text-indigo-600"></i>
                        Управление разрешениями
                    </h3>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 text-2xl">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form wire:submit.prevent="savePermissions" class="flex-1 overflow-y-auto">
                    <div class="p-6">
                        <div class="grid grid-cols-3 gap-6">
                            <!-- Actions -->
                            <div class="bg-blue-50 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="font-bold text-blue-900 flex items-center">
                                        <i class="fas fa-bolt mr-2"></i>
                                        Actions
                                    </h4>
                                    <span class="px-2 py-1 bg-blue-200 text-blue-800 text-xs rounded-full font-bold">
                                        {{ count($userActions) }}
                                    </span>
                                </div>
                                <div class="space-y-2 max-h-96 overflow-y-auto">
                                    @foreach($availableActions as $action)
                                        <label class="flex items-center space-x-2 cursor-pointer hover:bg-blue-100 p-2 rounded transition">
                                            <input type="checkbox" wire:model="userActions" value="{{ $action->id }}" class="rounded text-blue-600 focus:ring-blue-500">
                                            <div class="flex-1">
                                                <span class="text-sm font-mono text-gray-800">{{ $action->slug }}</span>
                                                <p class="text-xs text-gray-600">{{ Str::limit($action->description, 30) }}</p>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Roles -->
                            <div class="bg-green-50 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="font-bold text-green-900 flex items-center">
                                        <i class="fas fa-user-tag mr-2"></i>
                                        Roles
                                    </h4>
                                    <span class="px-2 py-1 bg-green-200 text-green-800 text-xs rounded-full font-bold">
                                        {{ count($userRoles) }}
                                    </span>
                                </div>
                                <div class="space-y-2 max-h-96 overflow-y-auto">
                                    @foreach($availableRoles as $role)
                                        <label class="flex items-center space-x-2 cursor-pointer hover:bg-green-100 p-2 rounded transition">
                                            <input type="checkbox" wire:model="userRoles" value="{{ $role->id }}" class="rounded text-green-600 focus:ring-green-500">
                                            <div class="flex-1">
                                                <span class="text-sm font-mono text-gray-800">{{ $role->slug }}</span>
                                                <p class="text-xs text-gray-600">{{ $role->actions->count() }} actions</p>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Groups -->
                            <div class="bg-purple-50 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="font-bold text-purple-900 flex items-center">
                                        <i class="fas fa-users mr-2"></i>
                                        Groups
                                    </h4>
                                    <span class="px-2 py-1 bg-purple-200 text-purple-800 text-xs rounded-full font-bold">
                                        {{ count($userGroups) }}
                                    </span>
                                </div>
                                <div class="space-y-2 max-h-96 overflow-y-auto">
                                    @foreach($availableGroups as $group)
                                        <label class="flex items-center space-x-2 cursor-pointer hover:bg-purple-100 p-2 rounded transition">
                                            <input type="checkbox" wire:model="userGroups" value="{{ $group->id }}" class="rounded text-purple-600 focus:ring-purple-500">
                                            <div class="flex-1">
                                                <span class="text-sm font-mono text-gray-800">{{ $group->slug }}</span>
                                                <p class="text-xs text-gray-600">
                                                    {{ $group->roles->count() }} roles, {{ $group->actions->count() }} actions
                                                </p>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 p-6 border-t bg-gray-50 flex-shrink-0">
                        <button type="button" wire:click="closeModal" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition font-medium">
                            Отмена
                        </button>
                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium flex items-center">
                            <i class="fas fa-save mr-2"></i>
                            Сохранить
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

