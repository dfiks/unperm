<template>
  <aside class="w-64 bg-gradient-to-b from-indigo-900 to-indigo-700 text-white m-[5px] rounded-[20px] flex flex-col">
    <!-- Logo -->
    <div class="p-6 border-b border-indigo-600">
      <h1 class="text-2xl font-bold flex items-center">
        <i class="fas fa-shield-alt mr-2"></i>
        UnPerm
      </h1>
      <p class="text-indigo-200 text-sm mt-1">Управление разрешениями</p>
    </div>
    
    <!-- Navigation -->
    <nav class="mt-4 flex-1 overflow-y-auto custom-scrollbar">
      <router-link
        v-for="item in menuItems"
        :key="item.path"
        :to="item.path"
        class="flex items-center px-6 py-3 mx-2 rounded-lg hover:bg-indigo-800 transition-all duration-200"
        :class="{ 'bg-indigo-800 shadow-lg': isActive(item.path) }"
      >
        <i :class="[item.icon, 'w-5']"></i>
        <span class="ml-3">{{ item.label }}</span>
      </router-link>
    </nav>
    
    <!-- Footer -->
    <div class="p-6 border-t border-indigo-600 text-sm text-indigo-200">
      <p class="font-semibold">Version 1.0.0</p>
      <p class="mt-1">© 2025 DFiks</p>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

const menuItems = [
  { path: '/dashboard', label: 'Dashboard', icon: 'fas fa-chart-line' },
  { path: '/actions', label: 'Actions', icon: 'fas fa-bolt' },
  { path: '/roles', label: 'Roles', icon: 'fas fa-user-tag' },
  { path: '/groups', label: 'Groups', icon: 'fas fa-users' },
  { path: '/users', label: 'Users', icon: 'fas fa-user-shield' },
  { path: '/resources', label: 'Resources', icon: 'fas fa-folder-open' },
]

const isActive = (path) => {
  return route.path === path || route.path.startsWith(path + '/')
}
</script>

