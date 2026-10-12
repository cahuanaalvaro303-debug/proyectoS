<script setup>
    import { onMounted, ref, watch } from 'vue'
    import { useAuth } from '../composables/useAuth'
    import api from '../services/api'
    import { useRouter } from 'vue-router'
    import { useDisplay } from 'vuetify'

    const { usuario, limpiarUsuario, restaurarSesion } = useAuth()
    const router = useRouter()
    const display = useDisplay()
    const drawer = ref(display.mdAndUp.value)

    onMounted(()=>restaurarSesion())

    const permisos = (codigo) => {
        if (!usuario.value)
            return false

        return usuario.value.permisos.some(p => p.codigo === codigo)
    }

    const cerrarSesion = async () => {
        try {
            await api.post('/logout')
            localStorage.removeItem('token')
            limpiarUsuario()
            router.replace('/')
        } catch (error) {
            console.error(error.response?.data || error)
            alert('Error al cerrar sesión')
        }
    }

    watch(() => display.mdAndUp.value, (isDesktop) => {
    drawer.value = isDesktop
    })

</script>

<template>
    <v-app>
        <v-app-bar>
                  <v-app-bar-nav-icon
        v-if="display.smAndDown.value"
        @click="drawer = !drawer"
      />
            <v-app-bar-title class="font-weight-bold">SISTEMA DE MONITOREO</v-app-bar-title>
            <v-spacer />
            <span v-if="usuario"
                class="mr-5">
                {{ usuario.nombre }}
            </span>
            <v-btn @click="cerrarSesion"
                    color="error"
                    variant="outlined"
                    class="mr-3">
                Cerrar Sesión
            </v-btn>
        </v-app-bar>

        <v-navigation-drawer
        v-model="drawer"
        :permanent="display.mdAndUp.value"
        :temporary="display.smAndDown.value"
        width="280"
        >
            <v-list>

                <v-list-item
                    v-if="permisos('dashboard')"
                    title="Dashboard"
                    to="/menu"
                    prepend-icon="mdi-view-dashboard"></v-list-item>

                    <v-list-item
                    v-if="permisos('usuarios')"
                    title="Gestión de Usuarios"
                    to="/menu/usuarios"
                    prepend-icon="mdi-account-cog"
                    class="text-wrap"></v-list-item>

                    <v-list-group 
                    v-if="permisos('tipos_vehiculo') || permisos('vehiculos') || permisos('asignaciones')"
                    value="VehiculosGroup">
                        <template v-slot:activator="{ props }">
                            <v-list-item
                            v-bind="props"
                            prepend-icon="mdi-car-cog"
                            title="Gestión de Vehículos"
                            location="right"></v-list-item>
                        </template>

                        <v-list-item
                        v-if="permisos('tipos_vehiculo')"
                        title="Tipos de Vehiculo"
                        to="/menu/tipos_vehiculo"
                        prepend-icon="mdi-car-multiple"></v-list-item>

                        <v-list-item 
                        v-if="permisos('vehiculos')"
                        title="Vehículos"
                        to="/menu/vehiculos"
                        prepend-icon="mdi-car"></v-list-item>

                        <v-list-item
                        v-if="permisos('asignaciones')"
                        title="Asignaciones de Vehículos"
                        to="/menu/asignaciones"
                        prepend-icon="mdi-account-arrow-right"></v-list-item>
                    </v-list-group>

                    <v-list-group
                    v-if="permisos('ubicaciones')"
                    value="MonitoreGroup">
                        <template v-slot:activator="{ props }">
                            <v-list-item
                            v-bind="props"
                            prepend-icon="mdi-monitor-dashboard"
                            title="Gestión de Monitoreo"></v-list-item>
                        </template>

                        <v-list-item
                        title="Mapa"
                        prepend-icon="mdi-map-marker-radius"
                        to="/menu/ubicaciones"></v-list-item>

                        <v-list-item
                        title="Historial"
                        prepend-icon="mdi-map-marker-path"
                        to="/menu/historial"></v-list-item>
                    </v-list-group>

                    <v-list-item
                    v-if="permisos('auditoria')"
                    title="Bitacora de Auditoria"
                    prepend-icon="mdi-file-clock-outline"
                    to="/menu/auditoria"></v-list-item>

                    <v-list-item
                    title="Cerrar Sesión"
                    prepend-icon="mdi-logout"
                    color="error"
                    :active="true"
                    @click="cerrarSesion"></v-list-item>
                     
            </v-list>
        </v-navigation-drawer>

        <v-main>
            <router-view />
        </v-main>
    </v-app>
</template>

<style scoped>
    :deep(.v-list-item-title) {
        white-space: normal;
        overflow: visible;
        text-overflow: clip;
    }
</style>