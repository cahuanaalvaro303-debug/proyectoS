<script setup>
import api from '../services/api';
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';

const cargando = ref(false)
const error = ref('')
const router = useRouter()
const dialogNuevo = ref(false)
const dialogEdit = ref(false)
const dialogDesasig = ref(false)
const usuarios = ref([])
const vehiculos = ref([])
const usuarioSeleccionado = ref(null)
const vehiculoSeleccionado = ref(null)
const fechaInicio = ref('')
const fechaFin = ref('')
const observacionesEntrega = ref('')
const observacionesDev = ref('')
const activo = ref(true)
const formularioNuevo = ref(null)
const formularioEdit = ref(null)
const formularioDesasig = ref(null)
const asignaciones = ref([])
const idAsignacion = ref('')
const asignacionSeleccionada = ref(null)

const requerido = value => !!value || 'Este campo es requerido' 

const headers = [
    {title: 'Usuario', key: 'usuario.nombre', align: 'center', headerProps: {class: 'font-weight-bold'}},
    {title: 'Vehiculo', key: 'vehiculo.tipo_vehiculo.nombre', align: 'center', headerProps: {class: 'font-weight-bold'}},
    {title: 'Fecha Inicio', key: 'fecha_inicio', align: 'center', headerProps: {class: 'font-weight-bold'}},
    {title: 'Fecha Fin', key: 'fecha_fin', align: 'center', headerProps: {class: 'font-weight-bold'}},
    {title: 'Observaciones entrega', key: 'observaciones_entrega', align: 'center', headerProps: {class: 'font-weight-bold'}},
    {title: 'Observaciones devolución', key: 'observaciones_devolucion', align: 'center', headerProps: {class: 'font-weight-bold'}},
    {title: 'Activo', key: 'activo', align: 'activo', headerProps: {class: 'font-weight-bold'}},
    {title: 'Acciones', key: 'acciones', align: 'center', headerProps: {class: 'font-weight-bold'}}
]

const cargarAsignaciones = async () => {
    cargando.value = true
    error.value = null

    try { 
        const respuesta = await api.get('/asignaciones')
        asignaciones.value = respuesta.data
        console.log('Lista de asignaciones', respuesta.data)
    }catch (err) {
        error.value = err.response?.data?.message || 
            'Error al cargar asignaciones'
        alert(error.value)
    } finally {
        cargando.value = false
    }
}

const cargarUsuariosActivos = async () => {
        cargando.value = true
        error.value = null

        try {
            const respuesta = await api.get('/usuarios/activos')

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

const cargarVehiculosActivos = async () => {
    cargando.value = true
    error.value = null
    try {
        const respuesta = await api.get('/vehiculos/activos')
        vehiculos.value = respuesta.data
        
    } catch (err) {
        error.value = err.response?.data?.message ||
            'Error al cargar vehiculos'
        alert(error.value)
    } finally{
        cargando.value = false
    }
}

function cargarAsignacion(item){
    asignacionSeleccionada.value = item
    usuarioSeleccionado.value = item.usuario.id
    vehiculoSeleccionado.value = item.vehiculo.id
    fechaInicio.value = item.fecha_inicio
    fechaFin.value = item.fecha_fin
    observacionesEntrega.value = item.observaciones_entrega
    observacionesDev.value = item.observaciones_devolucion
    activo.value = item.activo
    idAsignacion.value = item.id
    return
}

function limpiar() {
    dialogNuevo.value = false
    dialogDesasig.value = false
    dialogEdit.value = false
    usuarioSeleccionado.value = null
    vehiculoSeleccionado.value = null
    observacionesEntrega.value = ''
    observacionesDev.value = ''
    fechaFin.value = ''
    fechaInicio.value = ''
    return
}

const guardarNuevaAsignacion = async () => {

    const { valid } = await formularioNuevo.value.validate()

    if( !valid )
        return

    cargando.value = true
    error.value = null

    const datos = {
        usuario_id: usuarioSeleccionado.value,
        vehiculo_id: vehiculoSeleccionado.value,
        fecha_inicio: fechaInicio.value,
        fecha_fin: null,
        observaciones_entrega: observacionesEntrega.value,
        observaciones_devolucion: null
    }

    try {
        await api.post('/asignaciones', datos)
        limpiar()
        router.push('/menu/asignaciones')
    } catch(err) {
        error.value = err.response?.data?.message || 
              'No se pudo guardar nueva asignación'
    } finally {
        cargando.value = false 
    }
}

const formatearFecha = (fechaRaw) =>{
    if(!fechaRaw) 
        return '---'
    const fecha = new Date(fechaRaw)

    return new Intl.DateTimeFormat('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    }).format(fecha)
}

const guardarEditAsig = async () => {

    const { valid } = await formularioEdit.value.validate()
    if(!valid)
        return
    cargando.value = true
    error.value = null

    const datos = {
        usuario_id: usuarioSeleccionado.value,
        vehiculo_id: vehiculoSeleccionado.value,
        observaciones_entrega: observacionesEntrega.value
    }
    try {
        await api.patch(`/asignaciones/${idAsignacion.value}`, datos)
        limpiar()
        router.push('/menu/asignaciones')
    } catch (err) {
        error.value = err.response?.data?.message || 
            'No se pudo actualizar datos'
        alert.value(error.value)
    } finally {
        cargando.value = false
    }
}
const guardarDesasignacion = async () => {

    const { valid } = await formularioDesasig.value.validate()

    if(!valid)
        return 
    cargando.value = true
    error.value = null
    const datos = {

        observaciones_devolucion: observacionesDev.value,
    }
    try {
        const respuesta = await api.patch(`/asignaciones/${idAsignacion.value}/desasignar`, datos)
        console.log('Respuesta', respuesta.data.message)
        //router.push('/menu/asignaciones')
        limpiar()
    } catch (err) {
        error.value = err.response?.data?.message || 
            'No se pudo realizar la desasignacion'
        alert(error.value)
    }  finally {
        cargando.value = false
    }
}

onMounted(()=>{cargarAsignaciones();})

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
                        @click="dialogNuevo = true; cargarUsuariosActivos(); cargarVehiculosActivos()">NUEVA ASIGNACIÓN</v-btn>
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-card-text>
                        <v-data-table
                        :headers="headers"
                        :items="asignaciones">
                    <template #item.acciones="{ item }">
                        <v-btn
                        prepend-icon="mdi-pencil"
                        color="error"
                        :disabled="!item.activo"
                        @click="dialogEdit = true; cargarUsuariosActivos(); cargarVehiculosActivos(); cargarAsignacion(item)">
                        Editar Información
                        </v-btn>
                        <v-btn
                        prepend-icon="mdi-car-off"
                        color="warning"
                        :disabled="!item.activo"
                        @click="dialogDesasig = true; cargarAsignacion(item)">
                            <span v-if="item.activo">Desasignar</span>
                            <span v-else>Desasignado</span>
                        </v-btn>
                    </template>

                    <template #item.usuario.nombre="{ item }">
                        {{ item.usuario.nombre }} {{ item.usuario.apellido }}
                    </template>

                    <template #item.vehiculo.tipo_vehiculo.nombre="{ item }">
                        {{ item.vehiculo.tipo_vehiculo.nombre }} - {{ item.vehiculo.placa }}
                    </template>

                    <template #item.fecha_inicio="{ item }">
                        {{ formatearFecha(item.fecha_inicio) }}
                    </template>

                    <template #item.fecha_fin="{ item }">
                        {{ formatearFecha(item.fecha_fin) }}
                    </template>

                    <template #item.observaciones_devolucion="{ item }">
                        {{ item['observaciones_devolucion'] || '---' }}
                    </template>

                    <template #item.observaciones_entrega="{ item }">
                        {{ item['observaciones_entrega'] || '---' }}
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
    max-width="450"
    >
        <v-form
        ref="formularioNuevo"
        @submit="guardarNuevaAsignacion">
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
                    :item-title="item => `${item.tipo_vehiculo?.nombre ?? 'Sin tipo'} ${item.placa}`"
                    item-value="id"
                    clearable
                    v-model="vehiculoSeleccionado"
                    :rules="[requerido]"></v-select> 

                    <v-text-field
                    label="Observaciones Entrega" 
                    v-model="observacionesEntrega"
                    :rules="[requerido]"></v-text-field>
                    
                    <v-date-input
                    label="Fecha de asignación"
                    v-model="fechaInicio"
                    prepend-inner-icon="mdi-calendar"
                    clearable
                    :rules="[requerido]"></v-date-input>

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
    max-width="450"
    >
        <v-form
        ref="formularioEdit"
        @submit="guardarEditAsig()">
            <v-card>
                <v-card-title class="align-center d-flex w-100 font-weight-bold ma-3">
                    EDITAR ASIGNACIÓN
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
                    :item-title="item => `${item.tipo_vehiculo?.nombre ?? 'Sin tipo'} ${item.placa}`"
                    item-value="id"
                    clearable
                    v-model="vehiculoSeleccionado"
                    :rules="[requerido]"></v-select> 

                    <v-text-field
                    label="Observaciones Entrega" 
                    v-model="observacionesEntrega"
                    :rules="[requerido]"></v-text-field>
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

    <v-dialog
    v-model="dialogDesasig"
    max-width="450"
    >
        <v-form
        ref="formularioDesasig"
        @submit="guardarDesasignacion()">
            <v-card>
                <v-card-title class="align-center d-flex w-100 font-weight-bold mt-3 mr-3 ml-3">
                    DESASIGNAR
                    <v-spacer></v-spacer>
                    <v-btn
                    icon="mdi-close"
                    class="mr-2"
                    @click="limpiar()"></v-btn>
                </v-card-title>
                
                <v-card-text>
                    
                    <div class="mb-3">
                        <div class="text-caption text-medium-emphasis">Usuario</div>
                        <div class="text-body-1">
                        {{ asignacionSeleccionada?.usuario?.nombre }} {{ asignacionSeleccionada?.usuario?.apellido }}
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="text-caption text-medium-emphasis">Vehículo</div>
                        <div class="text-body-1">
                        {{ asignacionSeleccionada?.vehiculo?.tipo_vehiculo?.nombre ?? '' }} - {{ asignacionSeleccionada?.vehiculo?.placa }}
                        </div>
                    </div>

                    <v-text-field
                    label="Observaciones Devolución" 
                    v-model="observacionesDev"
                    :rules="[requerido]"></v-text-field>
                    
                </v-card-text> 

                <v-card-actions>
                    <v-btn 
                    prepend-icon="mdi-plus"
                    color="success"
                    type="submit"
                    variant="outlined"
                    >Aceptar Desasignación</v-btn>
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