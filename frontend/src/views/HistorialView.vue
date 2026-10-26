
<script setup>
import { onMounted, onBeforeUnmount, ref } from 'vue'
import L from 'leaflet'
import api from '../services/api'

const contenedorMapa = ref(null)
const vehiculoSeleccionado = ref(null)
const fecha_inicio = ref('')
const fecha_fin = ref('')
const vehiculos = ref([])
const cargando = ref(false)
const error = ref(null)

let mapa = null

const requerido = value => !!value || 'Este campo es requerido' 

onMounted(() => {
  mapa = L.map(contenedorMapa.value).setView(
    [-17.9647, -67.1060],
    13
  )

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 19,
  }).addTo(mapa)

  L.marker([-17.9647, -67.1060]).addTo(mapa)
    .bindPopup(`
    <strong>Motocicleta ABC-123</strong>
    <br>
    Modelo: Honda XR150
    <br>
    Estado: Activo
  `)
  cargarVehiculos()
})

const cargarVehiculos = async () => {
  cargando.value = true
  error.value = null

  try {
    const respuesta = await api.get('/vehiculos')
    vehiculos.value = respuesta.data
  } catch (err) {
    error.value = err.response?.data?.message ||
      'Error al cargar vehiculos'
    alert(error.value)
  } finally {
    cargando.value = false
  }
}

onBeforeUnmount(() => {
  if (mapa) {
    mapa.remove()
    mapa = null
  }

})
</script>

<template>
    <v-container>
        <v-row>
            <v-col>
                <v-card>
                    <v-card-title class="d-flex align-center w-100 font-weight-bold ma-5">
                        HISTORIAL DE MONITOREO
                    </v-card-title>
                    <v-card-subtitle class="d-flex align-center mr-6 ml-6">

                        <v-autocomplete
                          label="Vehículo"
                          :items="vehiculos"
                          :item-title="item => `${item.tipo_vehiculo?.nombre ?? 'Sin tipo'} ${item.placa}`"
                          item-value="id"
                          :filter-keys="['raw.placa', 'raw.tipo_vehiculo.nombre']"
                          clearable
                          v-model="vehiculoSeleccionado"
                          :rules="[requerido]"
                        ></v-autocomplete>
                        <v-spacer></v-spacer>
                        <v-date-input
                        label="Fecha Inicio"
                        v-model="fecha_inicio"
                        prepend-inner-icon="mdi-calendar"
                        clearable
                        :rules="[requerido]"></v-date-input>
                        <v-spacer></v-spacer>
                        <v-date-input
                          label="Fecha Fin"
                          v-model="fecha_fin"
                          prepend-inner-icon="mdi-calendar"
                          clearable
                          :rules="[requerido]"></v-date-input>
                        <v-spacer></v-spacer>
                        <v-btn
                          append-icon="mdi-magnify"
                          variant="outlined">
                          Buscar
                        </v-btn>
                    </v-card-subtitle>
                    <v-divider></v-divider>
                    <v-card-text>
                        <div ref="contenedorMapa" class="mapa"></div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
  
</template>

<style scoped>
.mapa {
  width: 100%;
  height: 800px;
  border-radius: 8px;
}
</style>