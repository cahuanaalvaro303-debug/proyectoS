
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
const snackbar = ref(false)


let mapa = null
let capaMarcadores = null
const marcadores = new Map()

const ubicaciones = ref([]) 

const requerido = value => !!value || 'Este campo es requerido' 

function ajustarMapaVehiculos() {
  if(!mapa || marcadores.size === 0) {
    return
  }

  const grupo = L.featureGroup([...marcadores.values()])
  const limites = grupo.getBounds()

  mapa.fitBounds(limites, {
    padding: [30, 30],
    maxZoom: 16
  })
}

function popupUbicacion(u) {
  return `
    <strong>Placa: ${u.placa}</strong><br>
    Conductor: ${u.nombre} ${u.apellido}<br>
    Velocidad: ${u.velocidad}<br>
    Registrado en: ${u.recibido_en}
  `
}

function crearMarcador(u, clave) {
  let lat = u.latitud
  let lng = u.longitud
  // Aplicar pequeño offset si hay marcadores en las mismas coordenadas
  const mismosPuntos = [...marcadores.values()].filter(m => {
    const pos = m.getLatLng()
    return Math.abs(pos.lat - lat) < 0.000001 && Math.abs(pos.lng - lng) < 0.000001
  })
  if (mismosPuntos.length > 0) {
    const offset = 0.00005 * (mismosPuntos.length + 1)
    lat = lat + offset
    lng = lng + offset
  }
  const marcador = L.marker([lat, lng])
  .bindPopup(popupUbicacion(u))
  .addTo(capaMarcadores)
  marcadores.set(clave, marcador)
}

function eliminarMarcador(id) {
  const m = marcadores.get(id)
  if(m) {
    capaMarcadores.removeLayer(m)
    marcadores.delete(id)
  }
}

function sincronizarUbicaciones(listaUbicaciones) {
  const idsRecibidos = new Set()

  listaUbicaciones.forEach((ubicacion, index) => {
    const clave = ubicacion.id ?? `${ubicacion.vehiculo_id}-${ubicacion.usuario_id ?? ubicacion.conductor_id ?? ubicacion.user_id}-${ubicacion.recibido_en}-${index}`
    idsRecibidos.add(clave)

    const marcador = marcadores.get(clave)

    if (!marcador) {
      crearMarcador(ubicacion, clave)
      return
    }

    // Aplicar mismo offset si hay otros marcadores en las mismas coordenadas
    let lat = ubicacion.latitud
    let lng = ubicacion.longitud
    const mismosPuntos = [...marcadores.values()].filter(m => {
      const pos = m.getLatLng()
      return Math.abs(pos.lat - lat) < 0.000001 && Math.abs(pos.lng - lng) < 0.000001 && m !== marcador
    })
    if (mismosPuntos.length > 0) {
      const offset = 0.00005 * (mismosPuntos.length + 1)
      lat = lat + offset
      lng = lng + offset
    }

    marcador.setLatLng([lat, lng])

    marcador.setPopupContent(popupUbicacion(ubicacion))
  })

  // Eliminar marcadores que ya no están en la respuesta actual
  for (const [claveMarcador] of marcadores) {
    if (!idsRecibidos.has(claveMarcador)) {
      eliminarMarcador(claveMarcador)
    }
  }

}

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


const cargarUbicaciones = async () => {
  snackbar.value = false 
  error.value = null
  cargando.value = true
  ubicaciones.value = []
  capaMarcadores.clearLayers()
  marcadores.clear()

  try {
    const respuesta = await api.get('/ubicaciones/historial', {
      params: {
        vehiculo_id: vehiculoSeleccionado.value,
        fecha_inicio: fecha_inicio.value,
        fecha_fin: fecha_fin.value
      }
    })

    ubicaciones.value = respuesta.data.data
    console.log('Ubicaciones cargadas', respuesta.data.data)

    if(!Array.isArray(respuesta.data.data) || respuesta.data.data.length === 0) {
      snackbar.value = true
    }
    sincronizarUbicaciones(ubicaciones.value)
    ajustarMapaVehiculos()

  } catch (err) {
    error.value = err.response?.data?.message || 
      'No se pudo cargar las ubicaciones'
    alert(error.value)
  } finally {
    cargando.value = false 
  }
} 


onMounted(() => {
  mapa = L.map(contenedorMapa.value).setView(
    [-17.9647, -67.1060],
    13
  )

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 19,
  }).addTo(mapa)
  
   capaMarcadores = L.layerGroup().addTo(mapa)

  cargarVehiculos()
})

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

                        <v-text-field
                        label="Fecha Inicio"
                        v-model="fecha_inicio"
                        type="datetime-local"
                        clearable
                        :rules="[requerido]"></v-text-field>

                        <v-spacer></v-spacer>

                        <v-text-field
                        label="Fecha Fin"
                        v-model="fecha_fin"
                        type="datetime-local"
                        clearable
                        :rules="[requerido]"></v-text-field>
                        
                        <v-spacer></v-spacer>

                        <v-btn
                          append-icon="mdi-magnify"
                          variant="outlined"
                          @click="cargarUbicaciones()">
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
    <v-snackbar
    v-model="snackbar"
    :timeout="3000"
    location="center">No tiene ubicaciones registradas</v-snackbar>
  
</template>

<style scoped>
.mapa {
  width: 100%;
  height: 800px;
  border-radius: 8px;
}
</style>