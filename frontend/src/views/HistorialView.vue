
<script setup>
import { onMounted, onBeforeUnmount, ref } from 'vue'
import L from 'leaflet'

const contenedorMapa = ref(null)

let mapa = null

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
                    <v-card-title>
                        HISTORIAL DE MONITOREO
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