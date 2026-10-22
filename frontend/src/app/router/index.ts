import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', redirect: '/live' },
    {
      path: '/live',
      name: 'live-list',
      component: () => import('@/features/live/views/public/LiveListView.vue'),
    },
    {
      path: '/live/:id(\\d+)',
      name: 'live-detail',
      component: () => import('@/features/live/views/public/LiveDetailView.vue'),
    },
    {
      path: '/admin/login',
      name: 'admin-login',
      component: () => import('@/views/auth/AdminLoginView.vue'),
    },
    {
      path: '/admin/live/matches',
      name: 'admin-match-list',
      component: () => import('@/features/live/views/admin/MatchListView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/admin/live/matches/:id(\\d+)',
      name: 'admin-match-detail',
      component: () => import('@/features/live/views/admin/MatchDetailAdminView.vue'),
      meta: { requiresAuth: true },
    },
    { path: '/:pathMatch(.*)*', redirect: '/live' },
  ],
})

router.beforeEach((to) => {
  if (to.meta.requiresAuth) {
    const auth = useAuthStore()
    if (!auth.isLoggedIn) {
      return { path: '/admin/login', query: { redirect: to.fullPath } }
    }
  }
  return true
})

export default router
