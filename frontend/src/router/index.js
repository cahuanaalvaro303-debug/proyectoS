import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '../views/LoginView.vue'
import DashboardView from '../views/DashboardView.vue'
import Layout from '../layouts/Layout.vue'
import UsuariosView from '../views/UsuariosView.vue'
import AsignacionesView from '../views/AsignacionesView.vue'
import AuditoriaView from '../views/AuditoriaView.vue'
import HistorialView from '../views/HistorialView.vue'
import MonitoreoView from '../views/MonitoreoView.vue'
import TiposVehiculoView from '../views/TiposVehiculoView.vue'
import VehiculosView from '../views/VehiculosView.vue'

const routes = [

    {
        path: '/',
        name: 'Login',
        component: LoginView
    },
    {
        path: '/menu',
        component: Layout,
        children: [
            {
                path: '',
                name: 'Dashboard',
                component: DashboardView
            },
            {
                path: 'usuarios',
                name: 'Usuarios',
                component: UsuariosView
            },
            {
                path: 'tipos_vehiculo',
                name: 'TiposVehiculo',
                component: TiposVehiculoView
            },
            {
                path: 'vehiculos',
                name: 'Vehiculos',
                component: VehiculosView
            },
            {
                path: 'asignaciones',
                name: 'AsignacionesVehiculos',
                component: AsignacionesView
            },
            {
                path: 'ubicaciones',
                name: 'Ubicaciones',
                component: MonitoreoView
            },
            {
                path: 'historial',
                name: 'Historial',
                component: HistorialView
            },
            {
                path: 'auditoria',
                name: 'Auditoria',
                component: AuditoriaView
            }
        ]
    },
    
    
]


const router = createRouter({
    history: createWebHistory(),
    routes
})

router.beforeEach((to) => {
    const token = localStorage.getItem('token')

    if(to.meta.requiresAuth && !token ) {
        return '/'
    }
    return true
})

export default router