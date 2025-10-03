<template>
  <div class="fixed top-4 right-4 z-50 space-y-2">
    <transition-group name="notification">
      <div
        v-for="notification in notifications"
        :key="notification.id"
        class="px-6 py-4 rounded-lg shadow-lg max-w-md animate-slide-in"
        :class="notificationClasses(notification.type)"
      >
        <div class="flex items-center">
          <i :class="[notificationIcon(notification.type), 'text-xl mr-3']"></i>
          <span class="font-medium">{{ notification.message }}</span>
          <button
            @click="removeNotification(notification.id)"
            class="ml-auto text-current opacity-50 hover:opacity-100 transition"
          >
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>
    </transition-group>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useNotificationStore } from '../../stores/notification'

const notificationStore = useNotificationStore()
const notifications = computed(() => notificationStore.notifications)

const notificationClasses = (type) => {
  const classes = {
    success: 'bg-green-50 border-l-4 border-green-500 text-green-800',
    error: 'bg-red-50 border-l-4 border-red-500 text-red-800',
    warning: 'bg-yellow-50 border-l-4 border-yellow-500 text-yellow-800',
    info: 'bg-blue-50 border-l-4 border-blue-500 text-blue-800',
  }
  return classes[type] || classes.info
}

const notificationIcon = (type) => {
  const icons = {
    success: 'fas fa-check-circle text-green-500',
    error: 'fas fa-exclamation-circle text-red-500',
    warning: 'fas fa-exclamation-triangle text-yellow-500',
    info: 'fas fa-info-circle text-blue-500',
  }
  return icons[type] || icons.info
}

const removeNotification = (id) => {
  notificationStore.remove(id)
}
</script>

<style scoped>
.notification-enter-active,
.notification-leave-active {
  transition: all 0.3s ease;
}

.notification-enter-from {
  opacity: 0;
  transform: translateX(100px);
}

.notification-leave-to {
  opacity: 0;
  transform: translateX(100px);
}
</style>

