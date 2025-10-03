<template>
  <div class="flex-1 flex flex-col">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200">
      <div class="px-8 py-6">
        <h2 class="text-3xl font-bold text-gray-900">Dashboard</h2>
        <p class="text-gray-500 text-sm mt-1">Обзор системы разрешений</p>
      </div>
    </header>

    <!-- Content -->
    <div class="flex-1 p-8 overflow-y-auto custom-scrollbar">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Stats Cards -->
        <div 
          v-for="stat in stats" 
          :key="stat.label"
          class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-600 text-sm">{{ stat.label }}</p>
              <p class="text-3xl font-bold text-gray-900 mt-2">{{ stat.value }}</p>
            </div>
            <div :class="['w-14 h-14 rounded-full flex items-center justify-center', stat.bgColor]">
              <i :class="[stat.icon, 'text-2xl', stat.iconColor]"></i>
            </div>
          </div>
          <div class="mt-4 flex items-center text-sm">
            <span :class="stat.trend === 'up' ? 'text-green-600' : 'text-red-600'" class="font-semibold">
              <i :class="stat.trend === 'up' ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"></i>
              {{ stat.change }}
            </span>
            <span class="text-gray-500 ml-2">за последний месяц</span>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
          <h3 class="text-xl font-bold text-gray-900 mb-4">Быстрые действия</h3>
          <div class="space-y-3">
            <router-link
              v-for="action in quickActions"
              :key="action.path"
              :to="action.path"
              class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-indigo-50 transition group"
            >
              <div :class="['w-10 h-10 rounded-lg flex items-center justify-center', action.bgColor]">
                <i :class="[action.icon, action.iconColor]"></i>
              </div>
              <div class="ml-4 flex-1">
                <p class="font-semibold text-gray-900 group-hover:text-indigo-600">{{ action.label }}</p>
                <p class="text-sm text-gray-500">{{ action.description }}</p>
              </div>
              <i class="fas fa-arrow-right text-gray-400 group-hover:text-indigo-600"></i>
            </router-link>
          </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
          <h3 class="text-xl font-bold text-gray-900 mb-4">Последняя активность</h3>
          <div class="space-y-4">
            <div v-for="activity in recentActivity" :key="activity.id" class="flex items-start">
              <div class="w-2 h-2 bg-indigo-600 rounded-full mt-2"></div>
              <div class="ml-4">
                <p class="text-gray-900 font-medium">{{ activity.text }}</p>
                <p class="text-sm text-gray-500">{{ activity.time }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const stats = ref([
  { label: 'Actions', value: '0', icon: 'fas fa-bolt', bgColor: 'bg-blue-100', iconColor: 'text-blue-600', trend: 'up', change: '+12%' },
  { label: 'Roles', value: '0', icon: 'fas fa-user-tag', bgColor: 'bg-purple-100', iconColor: 'text-purple-600', trend: 'up', change: '+8%' },
  { label: 'Groups', value: '0', icon: 'fas fa-users', bgColor: 'bg-green-100', iconColor: 'text-green-600', trend: 'up', change: '+5%' },
  { label: 'Users', value: '0', icon: 'fas fa-user-shield', bgColor: 'bg-orange-100', iconColor: 'text-orange-600', trend: 'down', change: '-2%' },
])

const quickActions = [
  { path: '/actions', label: 'Управление Actions', description: 'Создание и настройка действий', icon: 'fas fa-bolt', bgColor: 'bg-blue-100', iconColor: 'text-blue-600' },
  { path: '/roles', label: 'Управление Roles', description: 'Настройка ролей пользователей', icon: 'fas fa-user-tag', bgColor: 'bg-purple-100', iconColor: 'text-purple-600' },
  { path: '/users', label: 'Права пользователей', description: 'Назначение прав пользователям', icon: 'fas fa-user-shield', bgColor: 'bg-green-100', iconColor: 'text-green-600' },
  { path: '/resources', label: 'Resource Permissions', description: 'Управление правами на ресурсы', icon: 'fas fa-folder-open', bgColor: 'bg-orange-100', iconColor: 'text-orange-600' },
]

const recentActivity = ref([
  { id: 1, text: 'Добавлен новый action "users.create"', time: '5 минут назад' },
  { id: 2, text: 'Роль "Admin" обновлена', time: '1 час назад' },
  { id: 3, text: 'Создана группа "Managers"', time: '3 часа назад' },
  { id: 4, text: 'Пользователю назначены права', time: '1 день назад' },
])

onMounted(() => {
  // Load stats from API
  console.log('Dashboard mounted')
})
</script>

