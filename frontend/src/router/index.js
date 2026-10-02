import { createRouter, createWebHistory } from 'vue-router'
import HelloWorld from '../components/HelloWorld.vue'
import LoginView from '../views/LoginView.vue'

const routes = [
    {
        path: '/',
        name: 'hello',
        component: HelloWorld
    },
    {
        path: '/login',
        name: 'login',
        component: LoginView
    }
]


const router = createRouter({
    history: createWebHistory(),
    routes
})

router.beforeEach((to) => {
    const token = localStorage.getItem('token')

    if(to.meta.requiresAuth && !token ) {
        return '/login'
    }
    return true
})

export default router