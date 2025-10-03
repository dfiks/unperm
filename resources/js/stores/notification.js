import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useNotificationStore = defineStore('notification', () => {
  const notifications = ref([])
  let nextId = 0

  function add(message, type = 'success', duration = 3000) {
    const id = nextId++
    notifications.value.push({
      id,
      message,
      type,
    })

    if (duration > 0) {
      setTimeout(() => remove(id), duration)
    }

    return id
  }

  function remove(id) {
    const index = notifications.value.findIndex(n => n.id === id)
    if (index > -1) {
      notifications.value.splice(index, 1)
    }
  }

  function success(message, duration) {
    return add(message, 'success', duration)
  }

  function error(message, duration) {
    return add(message, 'error', duration)
  }

  function warning(message, duration) {
    return add(message, 'warning', duration)
  }

  function info(message, duration) {
    return add(message, 'info', duration)
  }

  return {
    notifications,
    add,
    remove,
    success,
    error,
    warning,
    info
  }
})

