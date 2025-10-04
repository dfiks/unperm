<div class="bg-white rounded-2xl shadow-sm p-8">
    <!-- Header -->
    <div class="mb-8">
        <h3 class="text-2xl font-bold text-gray-800">Управление разрешениями пользователей</h3>
        <p class="text-gray-500 mt-1">Назначение Actions, Roles и Groups пользователям</p>
    </div>

    <!-- Model Selector -->
    @if(count($availableModels) > 0)
        <div class="mb-6 bg-gradient-to-r from-purple-50 to-indigo-50 border border-purple-200 rounded-xl p-5">
            <label class="block text-sm font-semibold text-gray-700 mb-3">
                <i class="fas fa-database mr-2 text-purple-600"></i>
                Выберите модель пользователя
            </label>
            <select wire:model.live="selectedUserModel" wire:change="changeModel" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 transition-smooth bg-white">
                @foreach($availableModels as $modelClass => $modelInfo)
                    <option value="{{ $modelClass }}">
                        {{ $modelInfo['name'] }} ({{ $modelInfo['table'] }})
                    </option>
                @endforeach
            </select>
            <p class="text-xs text-gray-600 mt-2">
                <i class="fas fa-info-circle mr-1"></i>
                Найдено моделей с HasPermissions trait: {{ count($availableModels) }}
            </p>
        </div>
    @else
        <div class="mb-6 bg-white border border-gray-200 rounded-xl p-8 text-center">
            <i class="fas fa-exclamation-triangle text-5xl text-gray-300 mb-4"></i>
            <h4 class="text-lg font-bold text-gray-800 mb-2">Модели не найдены</h4>
            <p class="text-gray-600 mb-4">
                Не найдено ни одной модели, использующей HasPermissions trait.
                Добавьте trait к вашей модели User:
            </p>
            <code class="block mt-4 bg-gray-50 p-4 rounded-xl text-left text-sm text-gray-700 border border-gray-200">
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
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        <!-- Search -->
        <div class="mb-6">
            <input wire:model.live="search" type="text" placeholder="Поиск по имени или email..." 
                   class="w-full px-5 py-3.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-gray-50 transition-smooth">
        </div>

        <!-- Users Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Пользователь</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Roles</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Groups</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($users as $user)
                            <tr class="hover:bg-gray-50 transition-smooth">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="w-11 h-11 bg-gradient-to-br from-purple-400 to-indigo-500 rounded-xl flex items-center justify-center text-white font-bold mr-3 shadow-md">
                                            {{ strtoupper(substr($this->getUserName($user), 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-semibold text-gray-800">{{ $this->getUserName($user) }}</div>
                                            <div class="text-sm text-gray-500">{{ $this->getUserIdentifier($user) }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <span class="px-3 py-1.5 text-xs bg-indigo-50 text-indigo-700 rounded-lg font-semibold">
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
                                        <span class="px-3 py-1.5 text-xs bg-purple-50 text-purple-700 rounded-lg font-semibold">
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
                                        <span class="px-3 py-1.5 text-xs bg-pink-50 text-pink-700 rounded-lg font-semibold">
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
                                    <button wire:click="editPermissions('{{ $user->getKey() }}')" class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-purple-500 to-indigo-600 hover:from-purple-600 hover:to-indigo-700 text-white text-sm font-semibold rounded-xl transition-smooth shadow-md">
                                        <i class="fas fa-edit mr-2"></i> Управление
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <i class="fas fa-users text-5xl mb-4 text-gray-300"></i>
                                    <p class="text-gray-500 font-medium">Пользователи не найдены</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                {{ $users->links() }}
            </div>
        </div>
    @endif

    <!-- Permissions Modal -->
    @if($showPermissionsModal && $selectedUserId)
        <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50 overflow-y-auto p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-6xl my-8 max-h-[90vh] flex flex-col">
                <div class="flex justify-between items-center p-6 border-b border-gray-200 flex-shrink-0">
                    <h3 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-user-shield mr-2 text-purple-600"></i>
                        Управление разрешениями
                    </h3>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 text-2xl transition-smooth">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form wire:submit.prevent="savePermissions" class="flex-1 overflow-y-auto">
                    <div class="p-6">
                        <div class="grid grid-cols-3 gap-6">
                            <!-- Actions -->
                            <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl p-5 border border-indigo-200">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="font-bold text-gray-800 flex items-center">
                                        <i class="fas fa-bolt mr-2 text-indigo-600"></i>
                                        Actions
                                    </h4>
                                    <span class="px-3 py-1 bg-white text-indigo-700 text-xs rounded-lg font-bold shadow-sm">
                                        {{ count($userActions) }}
                                    </span>
                                </div>
                                <div class="space-y-2 max-h-96 overflow-y-auto">
                                    @foreach($availableActions as $action)
                                        <label class="flex items-center space-x-3 cursor-pointer hover:bg-white p-3 rounded-lg transition-smooth">
                                            <input type="checkbox" wire:model="userActions" value="{{ $action->id }}" class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500">
                                            <div class="flex-1">
                                                <span class="text-sm font-medium text-gray-700">{{ $action->slug }}</span>
                                                <p class="text-xs text-gray-500">{{ Str::limit($action->description, 30) }}</p>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Roles -->
                            <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-5 border border-purple-200">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="font-bold text-gray-800 flex items-center">
                                        <i class="fas fa-user-tag mr-2 text-purple-600"></i>
                                        Roles
                                    </h4>
                                    <span class="px-3 py-1 bg-white text-purple-700 text-xs rounded-lg font-bold shadow-sm">
                                        {{ count($userRoles) }}
                                    </span>
                                </div>
                                <div class="space-y-2 max-h-96 overflow-y-auto">
                                    @foreach($availableRoles as $role)
                                        <label class="flex items-center space-x-3 cursor-pointer hover:bg-white p-3 rounded-lg transition-smooth">
                                            <input type="checkbox" wire:model="userRoles" value="{{ $role->id }}" class="w-4 h-4 rounded text-purple-600 focus:ring-purple-500">
                                            <div class="flex-1">
                                                <span class="text-sm font-medium text-gray-700">{{ $role->slug }}</span>
                                                <p class="text-xs text-gray-500">{{ $role->actions->count() }} actions</p>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Groups -->
                            <div class="bg-gradient-to-br from-pink-50 to-rose-50 rounded-xl p-5 border border-pink-200">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="font-bold text-gray-800 flex items-center">
                                        <i class="fas fa-users mr-2 text-pink-600"></i>
                                        Groups
                                    </h4>
                                    <span class="px-3 py-1 bg-white text-pink-700 text-xs rounded-lg font-bold shadow-sm">
                                        {{ count($userGroups) }}
                                    </span>
                                </div>
                                <div class="space-y-2 max-h-96 overflow-y-auto">
                                    @foreach($availableGroups as $group)
                                        <label class="flex items-center space-x-3 cursor-pointer hover:bg-white p-3 rounded-lg transition-smooth">
                                            <input type="checkbox" wire:model="userGroups" value="{{ $group->id }}" class="w-4 h-4 rounded text-pink-600 focus:ring-pink-500">
                                            <div class="flex-1">
                                                <span class="text-sm font-medium text-gray-700">{{ $group->slug }}</span>
                                                <p class="text-xs text-gray-500">
                                                    {{ $group->roles->count() }} roles, {{ $group->actions->count() }} actions
                                                </p>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 p-6 border-t border-gray-200 bg-gray-50 flex-shrink-0">
                        <button type="button" wire:click="closeModal" class="px-6 py-3 border border-gray-300 rounded-xl text-gray-700 hover:bg-white transition-smooth font-medium">
                            Отмена
                        </button>
                        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-purple-500 to-indigo-600 text-white rounded-xl hover:from-purple-600 hover:to-indigo-700 transition-smooth font-semibold flex items-center shadow-lg">
                            <i class="fas fa-save mr-2"></i>
                            Сохранить
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
