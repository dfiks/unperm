import axios from 'axios'
import { useNotificationStore } from '../stores/notification'

const client = axios.create({
  baseURL: '/unperm/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest'
  }
})

// Request interceptor
client.interceptors.request.use(
  (config) => {
    // Add CSRF token if available
    const token = document.querySelector('meta[name="csrf-token"]')?.content
    if (token) {
      config.headers['X-CSRF-TOKEN'] = token
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor
client.interceptors.response.use(
  (response) => {
    return response
  },
  (error) => {
    const notification = useNotificationStore()
    
    if (error.response) {
      const message = error.response.data?.message || 'Произошла ошибка'
      notification.error(message)
    } else if (error.request) {
      notification.error('Сервер не отвечает')
    } else {
      notification.error('Ошибка запроса')
    }
    
    return Promise.reject(error)
  }
)

export default client

