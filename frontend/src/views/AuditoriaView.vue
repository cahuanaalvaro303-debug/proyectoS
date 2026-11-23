<script setup>
    import { ref, computed, onMounted } from 'vue';
    import api from '../services/api';

    const cargando = ref(false)
    const error = ref('')
    const registros = ref([])
    const usuarios = ref([])
    const usuarioSeleccionado = ref('')
    const registroBuscado = ref('')
    const fechaDesde = ref('')
    const fechaHasta = ref('')


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

    const usuariosOpciones = computed(() => [
        { id: '', nombre: 'Todos' },
        ...usuarios.value.map(usuario => ({
            id: usuario.id,
            nombre: `${usuario.nombre} ${usuario.apellido}`
        }))
    ])

    const cargarRegistros = async (params = {}) => {
        
        cargando.value = true
        error.value = ''

        try {
            const respuesta = await api.get('/audits', { params })
            registros.value = respuesta.data
            console.log('Registros de auditoria',respuesta.data)
        } catch (err) {
            error.value = err.response?.data?.message || 
                'Error al cargar registros'
            alert(error.value)
        } finally {
            cargando.value = false
        }
    }

    const cargarUsuarios = async () => {
        cargando.value = true
        error.value = ''

        try {
            const respuesta = await api.get('/usuarios')
            usuarios.value = respuesta.data.data
            console.log('Lista de usuarios', respuesta.data)
        } catch (err) {
            error.value = err.response?.data?.message ||
                'Error al cargar usuarios'
            alert(error.value)
        } finally {
            cargando.value = false
        }
    }

    const buscar = async () => {
        if (fechaDesde.value && fechaHasta.value && fechaDesde.value > fechaHasta.value) {
            error.value = 'La fecha desde no puede ser posterior a la fecha hasta'
            alert(error.value)
            return
        }

        const params = {}

        if (usuarioSeleccionado.value)
            params.user_id = usuarioSeleccionado.value
        if (registroBuscado.value.trim())
            params.registro = registroBuscado.value.trim()
        if (fechaDesde.value)
            params.from = fechaDesde.value
        if (fechaHasta.value)
            params.to = fechaHasta.value

        await cargarRegistros(params)
    }

    const limpiarFiltros = () => {
        usuarioSeleccionado.value = ''
        registroBuscado.value = ''
        fechaDesde.value = ''
        fechaHasta.value = ''
        error.value = ''
        cargarRegistros()
    }

    const formatearValores = (valores) => {
        if (!valores || typeof valores !== 'object') 
            return '---'
        const campos = Object.entries(valores)
        if (!campos.length) 
            return '---'

        return campos.map(([campo, valor]) => {
            if (Array.isArray(valor)) {
                const detalle = valor.map(item =>
                    item && typeof item === 'object'
                        ? (item.nombre ?? item.placa ?? JSON.stringify(item))
                        : item
                ).join(', ')
                return `${campo}: ${detalle}`
            }
            if (valor !== null && typeof valor === 'object')
                return `${campo}: ${JSON.stringify(valor)}`
            return `${campo}: ${valor ?? '—'}`
        }).join(' | ')
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

    onMounted(()=>{
        cargarUsuarios()
        cargarRegistros()
    })

</script>

<template>

    <v-container>
        <v-row>
            <v-col>
                <v-card>
                    <v-card-title class="d-flex align-center w-100 font-weight-bold ma-5">
                        AUDITORIA
                    </v-card-title>
                    <v-card-subtitle class="d-flex align-center flex-wrap ga-4 mr-6 ml-6">
                        <v-autocomplete
                        label="Usuario"
                        :items="usuariosOpciones"
                        item-title="nombre"
                        item-value="id"
                        v-model="usuarioSeleccionado"
                        placeholder="Buscar usuario"
                        prepend-inner-icon="mdi-account-search"
                        clearable
                        no-data-text="No se encontraron usuarios"
                        style="max-width: 260px;"
                        @click:clear="usuarioSeleccionado = ''">
                        </v-autocomplete>

                        <v-text-field
                        label="Registro afectado"
                        v-model="registroBuscado"
                        placeholder="Placa, nombre, CI o ID"
                        prepend-inner-icon="mdi-magnify"
                        clearable
                        style="max-width: 260px;">
                        </v-text-field>

                        <v-text-field
                        label="Fecha desde"
                        v-model="fechaDesde"
                        type="date"
                        :max="fechaHasta"
                        clearable
                        style="max-width: 180px;">
                        </v-text-field>

                        <v-text-field
                        label="Fecha hasta"
                        v-model="fechaHasta"
                        type="date"
                        :min="fechaDesde"
                        clearable
                        style="max-width: 180px;">
                        </v-text-field>

                        <v-btn
                        prepend-icon="mdi-magnify"
                        variant="outlined"
                        @click="buscar()">
                            Buscar
                        </v-btn>

                        <v-btn
                        prepend-icon="mdi-eraser"
                        variant="text"
                        @click="limpiarFiltros()">
                            Limpiar
                        </v-btn>
                    </v-card-subtitle>
                    <v-divider></v-divider>
                    <v-card-text>
                        <v-data-table
                        :headers="headers"
                        :items="registros">
                    
                            <template #item.user.nombre="{ item }">
                                {{ item.user?.nombre ?? 'Sistema' }} {{ item.user?.apellido ?? '' }}
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
                            <template #item.old_values="{ item }">
                                {{ formatearValores(item.old_values) }}
                            </template>
                            <template #item.new_values="{ item }">
                                {{ formatearValores(item.new_values) }}
                            </template>
                        </v-data-table>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </v-container>

</template>