
<script setup>
import { onMounted, onBeforeUnmount, ref } from 'vue'
import L from 'leaflet'
import api from '../services/api'


const cargando = ref(false)
const error = ref('')
const contenedorMapa = ref(null)

let mapa = null
let capaMarcadores = null
const marcadores = new Map()

const ubicaciones = ref([])

const ultimasUbicaciones = async () => {
  cargando.value = true
  error.value = ''

  try {
    const respuesta = await api.get('/ubicaciones')
    ubicaciones.value = respuesta.data.data
    console.log('Datos', respuesta.data.data)
    sincronizarUbicaciones(ubicaciones.value)
    ajustarMapaVehiculos()
  } catch(err) {
    error.value = err.response?.data?.message || 
      'No se pudo cargar ubicaciones'
    alert(error.value)
  } finally {
    cargando.value = false
  }
}

function ajustarMapaVehiculos() {
  if(!mapa || marcadores.size === 0) {
    return 
  }

  const grupo = L.featureGroup([...marcadores.values()])
  const limites = grupo.getBounds()

  mapa.fitBounds(limites, {
    padding: [30,30],
    maxZoom: 16
  })
}

function popupUbicacion(u) {
  return `
    <strong>Placa: ${u.placa}</strong><br>
    Velocidad: ${u.velocidad ?? 0} km/h<br>
    Conductor: ${u.usuario_nombre ?? ''} ${u.usuario_apellido ?? ''}<br>
    Estado: ${u.vehiculo_activo ? 'Activo' : 'Inactivo'}
  `
}

function crearMarcador(u) {
  const marcador = L.marker([u.latitud, u.longitud])
    .bindPopup(popupUbicacion(u))
    .addTo(capaMarcadores)
  marcadores.set(u.vehiculo_id, marcador)
}

function eliminarMarcador(id) {
  const m = marcadores.get(id)
  if (m) {
    capaMarcadores.removeLayer(m)
    marcadores.delete(id)
  }
}

function sincronizarUbicaciones(listaUbicaciones) {
  const idsRecibidos = new Set()

  listaUbicaciones.forEach((ubicacion) => {
    idsRecibidos.add(ubicacion.vehiculo_id)

    const marcador = marcadores.get(ubicacion.vehiculo_id)

    if (!marcador) {
      crearMarcador(ubicacion)
      return
    }

    marcador.setLatLng([
      ubicacion.latitud,
      ubicacion.longitud
    ])

    marcador.setPopupContent(popupUbicacion(ubicacion))
  })

  for (const id of marcadores.keys()) {
    if (!idsRecibidos.has(id)) {
      eliminarMarcador(id)
    }
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

  ultimasUbicaciones()
  sincronizarUbicaciones(ubicaciones.value)
  ajustarMapaVehiculos()
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
                        MONITOREO
                    </v-card-title>
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