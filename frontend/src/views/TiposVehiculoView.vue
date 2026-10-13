<script setup>
import api from '../services/api';
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';

const tiposVehiculos = ref([])
const cargando = ref(false)
const error = ref('')
const dialogNuevo = ref(false)
const dialogEditInfo = ref(false)
const nombre = ref('')
const descripcion = ref('')
const activo = ref(true)
const idTipo = ref('')

const formulariNuevo = ref(null)
const formularioEdit = ref(null)

const requerido = value => !!value || 'Este campo es requerido'
const router = useRouter()

const headers = [
    {title: 'Nombre', key: 'nombre', align: 'center', headerProps: {class: 'font-weight-bold'}},
    {title: 'Descripción', key: 'descripcion', align: 'center', headerProps: {class: 'font-weight-bold'}},
    {title: 'Estado', key: 'activo', align:'center', headerProps: {class: 'font-weight-bold'}},
    {title: 'Acciones',key: 'acciones', align: 'center', headerProps: {class: 'font-weight-bold'}}
]

function limpiar() {
    dialogNuevo.value = false
    dialogEditInfo.value = false
    nombre.value = ''
    descripcion.value = ''
    idTipo.value = ''
}

const cargarTiposVehiculos = async () => {
    cargando.value = true
    error.value = null

    try {
        const respuesta = await api.get('/tipos_vehiculos')
        tiposVehiculos.value = respuesta.data
    } catch (err) {
        error.value = err.response?.data?.message ||
            'Error al cargar tipos de vehículos'
    } finally {
        cargando.value = false
    }
}

const cargarTipo = (item) => {
    nombre.value = item.nombre,
    descripcion.value = item.descripcion,
    activo.value = item.activo,
    idTipo.value = item.id
}

const guardarNuevoTipo = async () => {

    const { valid } = await formulariNuevo.value.validate()

    if(!valid)
        return

    cargando.value = true
    error.value = null
    if (!descripcion.value){
        descripcion.value = 'Sin descripción'
    }
    const datos = {
        nombre: nombre.value,
        descripcion: descripcion.value,
        activo: activo.value
    }
    try {
        await api.post('/tipos_vehiculos', datos)
        router.push('/menu/tipos_vehiculo')
        limpiar()
    } catch(err) {
        error.value = err.response?.data?.message || 
            'Error al guardar nuevo tipo de vehículo'
        alert(error.value)
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

    if (!descripcion.value) {
        descripcion.value = 'Sin descripción'
    }
    const datos = {
        nombre: nombre.value,
        descripcion: descripcion.value,
        activo: activo.value
    }

    try {
        await api.put(`/tipos_vehiculos/${idTipo.value}`, datos)
        router.push('/menu/tipos_vehiculo')
        limpiar()
    } catch (err) {
        error.value = err.response?.data?.message ||
            'Error al guardar cambios'
        alert(error.value)
    }
    finally {
        cargando.value = false
    }
}

onMounted(() => {cargarTiposVehiculos()})
</script>

<template>
    <v-container>
        <v-row>
            <v-col>
                <v-card>
                    <v-card-title class="d-flex align-center w-100 font-weight-bold ma-5">
                        TIPOS DE VEHÍCULOS
                        <v-spacer></v-spacer>
                        <v-btn
                        prepend-icon="mdi-plus"
                        color="success mr-5"
                        @click="dialogNuevo = true">CREAR NUEVO TIPO DE VEHÍCULO</v-btn>
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-card-text>
                        <v-data-table
                        :headers="headers"
                        :items="tiposVehiculos">
                            <template #item.activo="{ item }">
                                <span v-if="item.activo">Activo</span>
                                <span v-else>Inactivo</span>
                            </template>
                            <template #item.acciones="{ item }">
                                <v-btn
                                prepend-icon="mdi-pencil"
                                color="error"
                                :loading="cargando"
                                @click="dialogEditInfo = true; cargarTipo(item)">
                                    Editar Información
                                </v-btn>
                            </template>
                        </v-data-table>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
    <v-dialog
    max-width="450"
    v-model="dialogNuevo">
        <v-form
            ref="formularioNuevo"
            @submit="guardarNuevoTipo();">
            <v-card>
                <v-card-title class="d-flex align-center w-100 font-weight-bold ma-2">
                    NUEVO TIPO DE VEHÍCULO
                    <v-spacer></v-spacer>
                    <v-btn
                    icon="mdi-close"
                    @click="limpiar()"></v-btn>
                </v-card-title>

                <v-card-text>
                    <v-text-field
                    label="Nombre"
                    v-model="nombre"
                    :rules="[requerido]"></v-text-field>

                    <v-text-field
                    label="Descripción"
                    v-model="descripcion"
                    :rules="[requerido]"></v-text-field>

                    <v-switch
                    :label="activo ? 'Tipo de Vehículo: Activo' : 'Tipo de Vehículo: Inactivo'"
                    :color="activo ? 'success' : 'error'"
                    v-model="activo"></v-switch>
                </v-card-text>

                <v-card-actions>
                    <v-btn
                    prepend-icon="mdi-plus"
                    color="success"
                    variant="outlined"
                    :loading="cargando"
                    type="submit">Guardar</v-btn>
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
    v-model="dialogEditInfo"
    max-width="500">
        <v-form
        ref="formularioEdit"
        @submit="guardarEditInfo();">
            <v-card>
                <v-card-title class="d-flex align-center w-100 font-weight-bold ma-2">
                    EDITAR TIPO DE VEHÍCULO
                    <v-spacer></v-spacer>
                    <v-btn
                    icon="mdi-close"
                    @click="limpiar()"></v-btn>
                </v-card-title>

                <v-card-text>
                    <v-text-field
                    label="Nombre"
                    v-model="nombre"
                    :rules="[requerido]"></v-text-field>
                    <v-text-field
                    label="Descripción"
                    v-model="descripcion"
                    :rules="[requerido]"></v-text-field>

                    <v-switch
                    :label="activo ? 'Tipo Vehículo: Activo' : 'Tipo Vehículo: Inactivo'"
                    :color="activo ? 'success' : 'error'"
                    v-model="activo"></v-switch>
                </v-card-text>

                <v-card-actions>
                    <v-btn
                    prepend-icon="mdi-content-save"
                    color="success"
                    variant="outlined"
                    type="submit"
                    :loading="cargando"
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