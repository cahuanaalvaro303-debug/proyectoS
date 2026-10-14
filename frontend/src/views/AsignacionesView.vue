<script setup>
import api from '../services/api';
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';

const cargando = ref(false)
const error = ref('')
const router = useRouter()
const dialogNuevo = ref(false)
const usuarios = ref([])
const vehiculos = ref([])
const usuarioSeleccionado = ref('')
const vehiculoSeleccionado = ref('')
const fechaInicio = ref('')
const fechaFin = ref('')
const observacionesEntrega = ref('')
const observacionesDev = ref('')
const activo = ref(true)

const requerido = value => !!value || 'Este campo es requerido' 

/*const headers = [
    {title: 'Usuario', key: 'placa', align: 'center', headerProps: {class: 'font-weight-bold'}},
    {title: 'Vehiculo', key: 'marca', align: 'center', headerProps: {class: 'font-weight-bold'}},
    {title: 'Fecha Inicio', key: 'modelo', align: 'center', headerProps: {class: 'font-weight-bold'}},
    {title: 'Fecha Fin', key: 'anio', align: 'center', headerProps: {class: 'font-weight-bold'}},
    {title: 'Observaciones entrega', key: 'color', align: 'center', headerProps: {class: 'font-weight-bold'}},
    {title: 'Observaciones devolución', key: 'tipo_vehiculo.nombre', align: 'center', headerProps: {class: 'font-weight-bold'}},
    {title: 'Activo', key: 'activo', align: 'center', headerProps: {class: 'font-weight-bold'}},
    {title: 'Acciones', key: 'acciones', align: 'center', headerProps: {class: 'font-weight-bold'}}
]*/

const cargarUsuarios = async () => {
        cargando.value = true
        error.value = null

        try {
            const respuesta = await api.get('/usuarios')

            usuarios.value = respuesta.data.data

            console.log('Lista de usuarios', respuesta.data)
        } catch (err) {
            error.value = err.response?.data?.message || 
                'No se pudieron cargar los usuarios'
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

function limpiar() {
    dialogNuevo.value = false
    return
}
//onMounted(()=>{})
</script>

<template>
    <v-container>
        <v-row>
            <v-col>
                <v-card>
                    <v-card-title class="d-flex align-center w-100 font-weight-bold ma-5">
                        ASIGNACIONES DE VEHÍCULOS
                        <v-spacer></v-spacer>
                        <v-btn
                        prepend-icon="mdi-plus"
                        color="success mr-5"
                        @click="dialogNuevo = true; cargarUsuarios(); cargarVehiculos() ">NUEVA ASIGNACIÓN</v-btn>
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
                        @click="">Editar Asignación</v-btn>
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
        @submit="">
            <v-card>
                <v-card-title class="align-center d-flex w-100 font-weight-bold ma-3">
                    NUEVA ASIGNACIÓN
                    <v-spacer></v-spacer>
                    <v-btn
                    icon="mdi-close"
                    class="mr-2"
                    @click="limpiar()"></v-btn>
                </v-card-title>

                <v-card-text>
                    <v-select
                    label="Usuario"
                    :items="usuarios"
                    :item-title="item => `${item.nombre} ${item.apellido}`"
                    item-value="id"
                    clearable
                    v-model="usuarioSeleccionado"
                    :rules="[requerido]"></v-select>
                    <v-select
                    label="Vehículo"
                    :items="vehiculos"
                    :item-title="item => `${item.tipo_vehiculo.nombre} - ${item.placa}`"
                    item-value="id"
                    clearable
                    v-model="vehiculoSeleccionado"
                    :rules="[requerido]"></v-select>
                    <v-text-field
                    label="Modelo" 
                    v-model="observacionesEntrega"
                    :rules="[requerido]"></v-text-field>
                    
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

</template>