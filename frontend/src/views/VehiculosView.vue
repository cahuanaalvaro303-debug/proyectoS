<script setup>
import api from '../services/api';
import { onMounted, ref } from 'vue';
import {  useRouter } from 'vue-router';

const error = ref('')
const cargando = ref(false)
const vehiculos = ref([])
const tipos = ref([])
const dialogNuevo = ref(false)
const dialogEdit = ref(false)
const placa = ref('')
const marca = ref('')
const modelo = ref('')
const anio = ref('')
const color = ref('')
const activo = ref(true)
const tipoSeleccionado = ref('')
const idVehiculo = ref('')

const formularioNuevo = ref(null)
const formularioEdit = ref(null)
const requerido = value => !!value || 'Este campo es requerido' 

const router = useRouter()

const headers = [
    {title: 'Placa', key: 'placa', align: 'center', headerProps: {class: 'font-weight-bold'}},
    {title: 'Marca', key: 'marca', align: 'center', headerProps: {class: 'font-weight-bold'}},
    {title: 'Modelo', key: 'modelo', align: 'center', headerProps: {class: 'font-weight-bold'}},
    {title: 'Año', key: 'anio', align: 'center', headerProps: {class: 'font-weight-bold'}},
    {title: 'Color', key: 'color', align: 'center', headerProps: {class: 'font-weight-bold'}},
    {title: 'Tipo de Vehículo', key: 'tipo_vehiculo.nombre', align: 'center', headerProps: {class: 'font-weight-bold'}},
    {title: 'Activo', key: 'activo', align: 'center', headerProps: {class: 'font-weight-bold'}},
    {title: 'Acciones', key: 'acciones', align: 'center', headerProps: {class: 'font-weight-bold'}}
]

function limpiar() {
    dialogNuevo.value = false
    dialogEdit.value = false
    placa.value = ''
    marca.value = ''
    modelo.value = ''
    anio.value = ''
    color.value = ''
    tipoSeleccionado.value = ''
    activo.value = true
    return
}

const cargarTipos = async () => {
    cargando.value = true
    error.value = null
    try {
        const respuesta = await api.get('/tipos_vehiculos')
        tipos.value = respuesta.data
    } catch (err) {
        error.value = err.response?.data?.message || 
            'No se pudo cargar los tipos de vehículos'
        alert(error.value)
    } finally {
        cargando.value = false
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
    } finally{
        cargando.value = false
    }
}

const cargarVehiculo = (item) => {
    placa.value = item.placa
    marca.value = item.marca
    modelo.value = item.modelo
    anio.value = item.anio
    color.value = item.color
    activo.value = item.activo
    tipoSeleccionado.value = item.tipo_vehiculo_id
    idVehiculo.value = item.id
    return
}

const guardarNuevoVehiculo = async () => {

    const { valid } = await formularioNuevo.value.validate()
    if(!valid)
        return
    cargando.value = true
    error.value = null

    const datos = {
        placa: placa.value,
        marca: marca.value,
        modelo: modelo.value,
        anio: anio.value,
        color: color.value,
        tipo_vehiculo_id: tipoSeleccionado.value,
        activo: activo.value
    }
    
    try {
        await api.post('/vehiculos', datos)
        limpiar()
        router.push('/menu/vehiculos')
    } catch (err) {
        error.value = err.response?.data?.message || 
            'Error al guardar nuevo vehículo'
    } finally {
        cargando.value = false
    }
}

const guardarEditInfo = async () => {

    const { valid } = await formularioEdit.value.validate()

    if(!valid)
        return
    cargando.value = true
    error.value = null
     const datos = {
        placa: placa.value,
        marca: marca.value,
        modelo: modelo.value,
        anio: anio.value,
        color: color.value,
        tipo_vehiculo_id: tipoSeleccionado.value,
        activo: activo.value}
    try {
        await api.put(`/vehiculos/${idVehiculo.value}`, datos)
        limpiar()
        router.push('/menu/vehiculos')
    } catch (err) {
        error.value = err.response?.data?.message ||
            'No se pudo guardar los cambios'
        alert(error.value)
    } finally {
        cargando.value = false
    }
}
onMounted(()=>{cargarVehiculos()})
</script>

<template>
    <v-container>
        <v-row>
            <v-col>
                <v-card>
                    <v-card-title class="d-flex align-center w-100 font-weight-bold ma-5">
                        VEHÍCULOS
                        <v-spacer></v-spacer>
                        <v-btn
                        prepend-icon="mdi-plus"
                        color="success mr-5"
                        @click="dialogNuevo = true; cargarTipos()">NUEVO VEHÍCULO</v-btn>
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-card-text>
                        <v-data-table
                        :headers="headers"
                        :items="vehiculos">
                    <template #item.acciones="{ item }">
                        <v-btn
                        prepend-icon="mdi-pencil"
                        color="error"
                        @click="dialogEdit = true; cargarVehiculo(item);
                            cargarTipos()">Editar Información</v-btn>
                    </template>
                    <template #item.activo="{ item }">
                        <span v-if="item.activo">Activo</span>
                        <span v-else>Inactivo</span>
                    </template>
                    </v-data-table>
                    </v-card-text>

                </v-card>
            </v-col>
        </v-row>
    </v-container>
    <v-dialog
    v-model="dialogNuevo"
    max-width="450">
        <v-form
        ref="formularioNuevo"
        @submit="guardarNuevoVehiculo()">
            <v-card>
                <v-card-title class="align-center d-flex w-100 font-weight-bold ma-3">
                    NUEVO VEHÍCULO
                    <v-spacer></v-spacer>
                    <v-btn
                    icon="mdi-close"
                    class="mr-2"
                    @click="limpiar()"></v-btn>
                </v-card-title>

                <v-card-text>
                    <v-text-field
                    label="Placa" 
                    v-model="placa"
                    :rules="[requerido]"></v-text-field>
                    <v-text-field
                    label="Marca" 
                    v-model="marca"
                    :rules="[requerido]"></v-text-field>
                    <v-text-field
                    label="Modelo" 
                    v-model="modelo"
                    :rules="[requerido]"></v-text-field>
                    <v-text-field
                    label="Año" 
                    v-model="anio"
                    :rules="[requerido]"></v-text-field>
                    <v-text-field
                    label="Color" 
                    v-model="color"
                    :rules="[requerido]"></v-text-field>

                    <v-select
                    label="Tipo de Vehículo"
                    :items="tipos"
                    item-title="nombre"
                    item-value="id"
                    clearable
                    v-model="tipoSeleccionado"
                    :rules="[requerido]"></v-select>

                    <v-switch
                    :label="activo ? 'Vehículo: activo' : 'Vehículo: inactivo'"
                    :color="activo ? 'success' : 'error'"
                    v-model="activo"></v-switch>
                </v-card-text>

                <v-card-actions>
                    <v-btn 
                    prepend-icon="mdi-plus"
                    color="success"
                    type="submit"
                    variant="outlined"
                    >Guardar</v-btn>
                    <v-btn 
                    prepend-icon="mdi-close"
                    color="error"
                    variant="outlined"
                    @click="limpiar()">Cancelar</v-btn>
                </v-card-actions>
            </v-card>
        </v-form>
    </v-dialog>

    <v-dialog
    v-model="dialogEdit"
    max-width="500">
        <v-form
        ref="formularioEdit"
        @submit="guardarEditInfo()">
            <v-card>
                <v-card-title class="align-center d-flex w-100 font-weight-bold ma-3">
                    EDITAR VEHÍCULO
                    <v-spacer></v-spacer>
                    <v-btn
                    icon="mdi-close"
                    class="mr-2"
                    @click="limpiar()"></v-btn>
                </v-card-title>

                <v-card-text>
                    <v-text-field
                    label="Placa" 
                    v-model="placa"
                    :rules="[requerido]"></v-text-field>
                    <v-text-field
                    label="Marca" 
                    v-model="marca"
                    :rules="[requerido]"></v-text-field>
                    <v-text-field
                    label="Modelo" 
                    v-model="modelo"
                    :rules="[requerido]"></v-text-field>
                    <v-text-field
                    label="Año" 
                    v-model="anio"
                    :rules="[requerido]"></v-text-field>
                    <v-text-field
                    label="Color" 
                    v-model="color"
                    :rules="[requerido]"></v-text-field>

                    <v-select
                    label="Tipo de Vehículo"
                    :items="tipos"
                    item-title="nombre"
                    item-value="id"
                    clearable
                    v-model="tipoSeleccionado"
                    :rules="[requerido]"></v-select>

                    <v-switch
                    :label="activo ? 'Vehículo: activo' : 'Vehículo: inactivo'"
                    :color="activo ? 'success' : 'error'"
                    v-model="activo"></v-switch>
                </v-card-text>

                <v-card-actions>
                    <v-btn 
                    prepend-icon="mdi-plus"
                    color="success"
                    type="submit"
                    variant="outlined"
                    >Guardar Cambios</v-btn>
                    <v-btn 
                    prepend-icon="mdi-close"
                    color="error"
                    variant="outlined"
                    @click="limpiar()">Cancelar</v-btn>
                </v-card-actions>
            </v-card>
        </v-form>
    </v-dialog>
</template>