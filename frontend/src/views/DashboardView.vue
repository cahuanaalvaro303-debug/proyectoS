<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'
import { useAuth } from '../composables/useAuth'

const router = useRouter()
const { usuario } = useAuth()

const cargando = ref(false)
const errorIndicadores = ref('')
const mensaje = ref('')
const indicadores = ref({
    total_vehiculos: 0,
    usuarios_activos: 0,
    asignaciones_activas: 0,
    vehiculos_activos: 0
})

const tarjetas = [
    {
        clave: 'total_vehiculos',
        titulo: 'Total de vehículos',
        detalle: 'Registrados en el sistema',
        icono: 'mdi-car',
        color: 'primary'
    },
    {
        clave: 'usuarios_activos',
        titulo: 'Usuarios activos',
        detalle: 'Con acceso al sistema',
        icono: 'mdi-account-check',
        color: 'success'
    },
    {
        clave: 'asignaciones_activas',
        titulo: 'Asignaciones activas',
        detalle: 'Vigentes actualmente',
        icono: 'mdi-account-arrow-right',
        color: 'warning'
    },
    {
        clave: 'vehiculos_activos',
        titulo: 'Vehículos activos',
        detalle: 'Disponibles en la flota',
        icono: 'mdi-car-connected',
        color: 'info'
    }
]

const cargarIndicadores = async () => {
    cargando.value = true
    errorIndicadores.value = ''

    try {
        const respuesta = await api.get('/dashboard')
        indicadores.value = respuesta.data
    } catch (error) {
        errorIndicadores.value = 'No se pudieron cargar los indicadores.'
    } finally {
        cargando.value = false
    }
}

const irAMonitoreo = async () => {
    mensaje.value = ''

    if (usuario.value && !usuario.value.permisos.some(p => p.codigo === 'ubicaciones')) {
        mensaje.value = 'No cuenta con autorización para acceder a Monitoreo.'
        return
    }

    try {
        await api.get('/dashboard/monitoreo')
        router.push('/menu/ubicaciones')
    } catch (error) {
        mensaje.value = 'No cuenta con autorización para acceder a Monitoreo.'
    }
}

onMounted(() => cargarIndicadores())
</script>

<template>
    <v-container>
        <v-card  class="pa-6 mb-6" flat>
            <div class="d-flex align-center">
                <v-avatar size="56" color="white" class="mr-4">
                    <v-icon icon="mdi-view-dashboard" color="primary" size="32"></v-icon>
                </v-avatar>

                <div>
                    <h1 class="dashboard-titulo">DASHBOARD</h1>
                    <div class="text-body-2 text-white">
                        Resumen general de la flota, usuarios y asignaciones del sistema.
                    </div>
                </div>
            </div>
        </v-card>

        <v-alert v-if="errorIndicadores" type="error" class="mb-4">
            {{ errorIndicadores }}
        </v-alert>

        <v-row>
            <v-col v-for="tarjeta in tarjetas" :key="tarjeta.clave" cols="12" sm="6" md="3">
                <v-card class="tarjeta pa-5" :loading="cargando">
                    <div class="d-flex justify-space-between align-start">
                        <div>
                            <div class="text-caption font-weight-bold text-uppercase text-medium-emphasis">
                                {{ tarjeta.titulo }}
                            </div>
                            <div class="text-h3 font-weight-bold mt-3">
                                {{ indicadores[tarjeta.clave] }}
                            </div>
                        </div>

                        <v-avatar :color="tarjeta.color" size="48">
                            <v-icon :icon="tarjeta.icono" color="white" size="26"></v-icon>
                        </v-avatar>
                    </div>

                    <v-divider class="my-4"></v-divider>

                    <div class="text-caption text-medium-emphasis">
                        {{ tarjeta.detalle }}
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <v-row class="mt-2">
            <v-col cols="12">
                <v-card class="pa-6">
                    <div class="d-flex align-center flex-wrap">
                        <v-avatar color="info" size="56" class="mr-4">
                            <v-icon icon="mdi-map-marker-radius" color="white" size="30"></v-icon>
                        </v-avatar>

                        <div class="mr-auto">
                            <div class="text-h6 font-weight-bold">Acceso al módulo de Monitoreo</div>
                            <div class="text-body-2 text-medium-emphasis">
                                Consulta la ubicación actual de los vehículos en el mapa.
                            </div>
                        </div>

                        <v-btn color="primary" prepend-icon="mdi-map-marker" @click="irAMonitoreo">
                            Ir a Monitoreo
                        </v-btn>
                    </div>

                    <v-alert v-if="mensaje" type="warning" class="mt-4">
                        {{ mensaje }}
                    </v-alert>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

<style scoped>
.dashboard-titulo {
  font-size: 2rem !important;
  line-height: 1.2 !important;
  letter-spacing: 1px;
  margin: 0 !important;
  color: #fff !important;
}

.tarjeta {
  border: 1px solid rgba(0, 0, 0, 0.06);
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.tarjeta:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 18px rgba(0, 0, 0, 0.12) !important;
}
</style>
