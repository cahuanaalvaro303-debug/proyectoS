<script setup>
    import api from '../services/api';
    import { ref, onMounted } from 'vue';
    import { useRouter } from 'vue-router';


    const router = useRouter()

    const cargando = ref(false)
    const error = ref(null)
    const usuarios = ref([])
    const dialogNuevo = ref(false)
    const dialogEditInfo = ref(false)
    const dialogEditPerm = ref(false)

    const nombre = ref('')
    const apellido = ref('')
    const ci = ref('')
    const telefono = ref('')
    const email = ref('')
    const password = ref('12345678')
    const activo = ref(true)
    const usuarioId = ref('')
    const requerido = value => !!value || 'Este campo es obligatorio'

    const formularioNuevo = ref(null)
    const formularioEditInfo = ref (null)

    const permisosSeleccionados = ref([])

    const itemsPermisos = ref([])

    const headers = [
        {title: 'Nombres', key: 'nombre', align: 'center', headerProps: {class: 'font-weight-bold'}},
        {title: 'Apellidos', key: 'apellido', align: 'center', headerProps: {class: 'font-weight-bold'}},
        {title: 'C.I.', key: 'ci', align: 'center', headerProps: {class: 'font-weight-bold'}},
        {title: 'Teléfono', key: 'telefono', align: 'center', headerProps: {class: 'font-weight-bold'}},
        {title: 'Correo Electrónico', key: 'email', align: 'center', headerProps: {class: 'font-weight-bold'}},
        {title: 'Estado', key: 'activo', align: 'center', headerProps: {class: 'font-weight-bold'}},
        {title: 'Acciones', key: 'acciones', align: 'center', headerProps: {class: 'font-weight-bold'}}
    ]

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

    const cargarPermisos = async () => {
        cargando.value = true
        error.value = null

        try {
            const respuesta = await api.get('/permisos')
            itemsPermisos.value = respuesta.data
        } catch (err){
            error.value = err.response?.data?.message || 
                'No se pudieron cargar los permisos'
            alert(error.value)
        } finally {
            cargando.value = false
        }
    }

    const cargarUsuario = (item) => {
        nombre.value = item.nombre
        apellido.value = item.apellido
        ci.value = item.ci
        telefono.value = item.telefono
        email.value = item.email
        activo.value = item.activo
        usuarioId.value = item.id 
        permisosSeleccionados.value = item.permisos.map(
        permiso => Number(permiso.id)
    )
        return 
    }

    const guardarNuevoUsuario = async () => {

        const { valid } = await formularioNuevo.value.validate()

        if(!valid){
            return
        }
        cargando.value = true
        error.value = null
        
        const datos = {
            nombre: nombre.value,
            apellido: apellido.value,
            ci: ci.value,
            telefono: telefono.value,
            email: email.value,
            password: password.value,
            activo: activo.value, 
            permisos: permisosSeleccionados.value
        }

        try {
            await api.post('/registro', datos)
            permisosSeleccionados.value = []
            limpiar()
            await cargarUsuarios()
        } catch (err) {
            error.value = err.response?.data?.message || 
                'Error al guardar nuevo usuario'
            alert(error.value)
        } finally {
            cargando.value = false
        }
    }

    const guardarEditUsuario = async () => {

        const { valid } = await formularioEditInfo.value.validate()

        if (!valid)
            return
        cargando.value = true
        error.value = null
            const datos = {
            nombre: nombre.value,
            apellido: apellido.value,
            ci: ci.value,
            telefono: telefono.value,
            email: email.value,
            activo: activo.value, 
        }
        try {
            await api.patch(`/usuarios/${usuarioId.value}`, datos)
            limpiar()
            await cargarUsuarios()
        } catch (err) {
            error.value = err.response?.data?.message || 
                'Error al guardar cambios de usuario'
            alert(error.value)
        } finally {
            cargando.value = false
        }
    }
    
    const guardarEditPermisos = async () =>{

        cargando.value = true
        error.value = null
        try {
            await api.patch(`/usuarios/${usuarioId.value}/permisos`, {permisos: permisosSeleccionados.value})
            permisosSeleccionados.value = []
            await cargarUsuarios()
        } catch (err) {
            error.value = err.response?.data?.message ||
                'Error al editar permisos'
            alert(error.value)
        } finally {
            cargando.value = false
        }
    }

    const guardarEditPassword = async () => {
        cargando.value = true
        error.value = null
        try {
            await api.patch(`/usuarios/${usuarioId.value}/password`, {
                password: '12345678'
            })
            alert('Contraseña restablecida correctamente')
            limpiar()
            await cargarUsuarios()

        } catch (err) {
            error.value = err.response?.data?.message || 
                'Error al Restaurar la Contraseña'
            alert(error.value)
        } finally {
            cargando.value = false
        }
    }

    function limpiar() {
        nombre.value = ''
        apellido.value = ''
        ci.value = ''
        telefono.value = '' 
        email.value = ''
        activo.value = true
        permisosSeleccionados.value = []
        dialogNuevo.value = false
        dialogEditInfo.value = false
        dialogEditPerm.value = false
        return
    }



    onMounted(() => {cargarUsuarios() })

</script>

<template>
    <v-container>
        <v-row>
            <v-col>
                <v-card>
                    <v-card-title class="d-flex align-center w-100 font-weight-bold ma-5">
                            GESTIÓN DE USUARIOS
                            <v-spacer></v-spacer>
                            <v-btn 
                            prepend-icon="mdi-account-plus"
                            color="success mr-5"
                            :loading="cargando"
                            @click="dialogNuevo = true;  cargarPermisos()">REGISTRAR NUEVO USUARIO</v-btn>
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-card-text class="mt-2">
                        <v-data-table
                        :headers="headers"
                        :items="usuarios">
                            <template #item.activo="{ item }">
                                <span v-if="item.activo">
                                    Activo
                                </span>
                                <span v-else>
                                    Inactivo
                                </span>
                            </template>
                            <template #item.acciones="{ item }">
                                <v-btn 
                                    prepend-icon="mdi-pencil"
                                    color="error"
                                    @click="dialogEditInfo = true; cargarUsuario(item); cargarPermisos()">Información</v-btn>
                                <v-btn
                                    prepend-icon="mdi-shield-key"
                                    color="warning"
                                    @click="dialogEditPerm = true; cargarUsuario(item); cargarPermisos()">Permisos</v-btn>
                                <v-btn
                                prepend-icon="mdi-key"
                                color="primary"
                                @click="cargarUsuario(item); guardarEditPassword(); limpiar() ">
                                    Restaurar Contraseña
                                </v-btn>
                            </template>
                        </v-data-table>
                    </v-card-text>

                    <v-card-actions>

                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>
    </v-container>

    <v-dialog v-model="dialogNuevo"
              max-width="500">
        <v-form 
        ref="formularioNuevo"
        @submit.prevent="guardarNuevoUsuario()">
            <v-card>
                <v-card-title class="d-flex align-center w-100 font-weight-bold ma-2">
                    NUEVO USUARIO
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
                    label="Apellido"
                    v-model="apellido"
                    :rules="[requerido]"/>

                    <v-text-field
                    label="C.I."
                    v-model="ci"
                    :rules="[requerido]"></v-text-field>

                    <v-text-field
                    label="Telefono/Celular"
                    v-model="telefono"
                    :rules="[requerido]"></v-text-field>

                    <v-text-field
                    label="Correo Electrónico"
                    v-model="email"
                    :rules="[requerido]"></v-text-field>

                    <v-text-field
                    label="Contraseña"
                    v-model="password"
                    disabled
                    prepend-inner-icon="mdi-lock"
                    hint="Clave genérica del sistema para nuevos usuarios"
                    persistent-hint></v-text-field>

                    <v-select
                    v-model="permisosSeleccionados"
                    label="Permisos"
                    :items="itemsPermisos"
                    item-title="nombre"
                    item-value="id"
                    multiple
                    chips
                    closable-chips
                    ></v-select>

                    <v-switch
                    :label="activo ? 'Usuario: Activo' : 'Usuario: Inactivo'"
                    :color="activo ? 'success' : 'error'"
                    v-model="activo"></v-switch>

                </v-card-text>

                <v-card-actions>
                    <v-btn
                    prepend-icon="mdi-account-plus"
                    color="success"
                    variant="outlined"
                    type="submit"
                    :loading="cargando">Guardar</v-btn>
                    <v-btn
                    prepend-icon="mdi-close"
                    color="error"
                    variant="outlined"
                    @click="limpiar()">Cancelar</v-btn>
                </v-card-actions>
            </v-card>
        </v-form>
    </v-dialog>

    <v-dialog v-model="dialogEditInfo"
              max-width="700">
        <v-form 
        ref="formularioEditInfo"
        @submit.prevent="guardarEditUsuario()">
            <v-card>
                <v-card-title class="d-flex align-center w-100 font-weight-bold">
                    EDITAR USUARIO
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
                    label="Apellido"
                    v-model="apellido"
                    :rules="[requerido]"/>

                    <v-text-field
                    label="C.I."
                    v-model="ci"
                    :rules="[requerido]"></v-text-field>

                    <v-text-field
                    label="Telefono/Celular"
                    v-model="telefono"
                    :rules="[requerido]"></v-text-field>

                    <v-text-field
                    label="Correo Electrónico"
                    v-model="email"
                    :rules="[requerido]"></v-text-field>

                    <v-switch
                    :label="activo ? 'Usuario: Activo' : 'Usuario: Inactivo'"
                    :color="activo ? 'success' : 'error'"
                    v-model="activo"></v-switch>

                </v-card-text>

                <v-card-actions>
                    <v-btn
                    prepend-icon="mdi-content-save"
                    color="success"
                    variant="outlined"
                    :loading="cargando"
                    type="submit">Guardar Cambios</v-btn>
                    <v-btn
                    prepend-icon="mdi-close"
                    color="error"
                    variant="outlined"
                    @click="limpiar()">Cancelar</v-btn>
                </v-card-actions>
            </v-card>
        </v-form>
    </v-dialog>

    <v-dialog v-model="dialogEditPerm"
              max-width="500">
        <v-form @submit.prevent="guardarEditPermisos(); limpiar()">
            <v-card>
                <v-card-title class="d-flex align-center w-100 font-weight-bold">
                    EDITAR PERMISOS
                    <v-spacer></v-spacer>
                    <v-btn
                    icon="mdi-close"
                    @click="limpiar()"></v-btn>
                </v-card-title>

                <v-card-text>
                    <v-checkbox 
                    v-for="permiso in itemsPermisos"
                    :key="permiso.id"
                    v-model="permisosSeleccionados"
                    :value="permiso.id"
                    :label="permiso.nombre"
                    hide-details
                    density="compact">
                    </v-checkbox>
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
                    @click="limpiar()"
                    >Cancelar</v-btn>
                </v-card-actions>
            </v-card>
        </v-form>
    </v-dialog>
</template>