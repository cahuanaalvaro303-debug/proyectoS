<script setup>
    import { ref, onMounted } from 'vue';
    import api from '../services/api';

    const cargando = ref(false)
    const error = ref('')
    const registros = ref([])


    const headers = [
        {title: 'Fecha y Hora', key: 'created_at', align: 'center', headerProps: {class: 'font-weight-bold'}},
        {title: 'Usuario', key: 'user.nombre', align: 'center', headerProps: {class: 'font-weight-bold'}},
        {title: 'Evento/Accion', key: 'event', align: 'center', headerProps: {class: 'font-weight-bold'}},
        {title: 'Modulo', key: 'auditable_type', align: 'center', headerProps: {class: 'font-weight-bold'}},
        {title: 'Registro', key: 'registro', align: 'center', headerProps: {class: 'font-weight-bold'}},
        {title: 'Valor Antiguo', key: 'old_values', align: 'center', headerProps: {class: 'font-weight-bold'}},
        {title: 'Valor Nuevo', key: 'new_values', align: 'center', headerProps: {class: 'font-weight-bold'}},
        {title: 'Dirección IP', key: 'ip_address', align: 'center', headerProps: {class: 'font-weight-bold'}}
    ]

    const cargarRegistros = async ()=> {
        cargando.value = true
        error.value = ''

        try {
            const respuesta = await api.get('/audits')
            registros.value = respuesta.data.data
            console.log('Registros de auditoria',respuesta.data.data)
        } catch (err) {
            error.value = err.response?.data?.message || 
                'Error al cargar registros'
            alert(error.value)
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
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(fecha)
    }

    onMounted(()=>{cargarRegistros()})

</script>

<template>

    <v-container>
        <v-row>
            <v-col>
                <v-card>
                    <v-card-title class="d-flex align-center w-100 font-weight-bold ma-5">
                        AUDITORIA
                    </v-card-title>
                    <v-card-subtitle>
                        
                    </v-card-subtitle>
                    <v-divider></v-divider>
                    <v-card-text>
                        <v-data-table
                        :headers="headers"
                        :items="registros">
                    
                            <template #item.user.nombre="{ item }">
                                {{ item.user.nombre }} {{ item.user.apellido }}
                            </template>
                            <template #item.created_at="{ item }">
                                {{ formatearFecha(item.created_at) }}
                            </template>
                            <template #item.event="{ item }">
                                <div v-if="item.event == 'updated'">Modificado</div>
                                <div v-else-if="item.event == 'created'">Creado</div>
                                <div v-else-if="item.event == 'delete'">Eliminado</div>
                                <div v-else-if="item.event == 'sync'">Asignacion/Desasignacion Permisos</div>
                                <div v-else>---</div>
                            </template>
                            <template #item.auditabe_type="{ item }">
                                <div v-if="item.auditable_type == 'App\Models\Usuario'">Usuario</div>
                                <div></div>
                                <div></div>
                                <div></div>
                            </template>
                        </v-data-table>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </v-container>

</template>