import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/',
    redirect: '/dashboard'
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: () => import('../views/Dashboard.vue'),
    meta: { title: 'Dashboard' }
  },
  {
    path: '/actions',
    name: 'Actions',
    component: () => import('../views/Actions.vue'),
    meta: { title: 'Actions' }
  },
  {
    path: '/roles',
    name: 'Roles',
    component: () => import('../views/Roles.vue'),
    meta: { title: 'Roles' }
  },
  {
    path: '/groups',
    name: 'Groups',
    component: () => import('../views/Groups.vue'),
    meta: { title: 'Groups' }
  },
  {
    path: '/users',
    name: 'Users',
    component: () => import('../views/Users.vue'),
    meta: { title: 'User Permissions' }
  },
  {
    path: '/resources',
    name: 'Resources',
    component: () => import('../views/Resources.vue'),
    meta: { title: 'Resource Permissions' }
  }
]

const router = createRouter({
  history: createWebHistory('/unperm'),
  routes
})

router.beforeEach((to, from, next) => {
  document.title = to.meta.title ? `${to.meta.title} - UnPerm` : 'UnPerm'
  next()
})

export default router

