<template>
  <div id="app" class="min-h-screen bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500">
    <div class="container mx-auto px-[30px] py-[30px] min-h-screen">
      <div class="bg-white rounded-[24px] shadow-2xl overflow-hidden min-h-[calc(100vh-60px)] flex">
        
        <!-- Sidebar -->
        <Sidebar v-if="!isAuthRoute" />
        
        <!-- Main Content -->
        <main class="flex-1 flex flex-col bg-gray-50">
          <router-view />
        </main>
      </div>
    </div>
    
    <!-- Toast Notifications -->
    <Notifications />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import Sidebar from './components/layout/Sidebar.vue'
import Notifications from './components/layout/Notifications.vue'

const route = useRoute()
const isAuthRoute = computed(() => route.path.startsWith('/auth'))
</script>

