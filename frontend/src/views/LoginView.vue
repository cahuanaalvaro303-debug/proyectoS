<script setup>
    import { ref } from 'vue'
    import api from '../services/api'

    const email = ref('')
    const password = ref('')
    const error = ref('')
    const cargando = ref(false)

    const login = async () => {
        error.value = ''
        cargando.value = true

        try {
            const respuesta = await api.post('/login', {
                email: email.value,
                password: password.value
            })
            const token = respuesta.data.token

            localStorage.setItem('token', token)

            console.log('Login correcto')
            console.log('Token guardado: ', localStorage.getItem('token'))

        } catch (err) {
            console.error('Error de login', err)

            error.value = err.response?.data?.message || 
                'No se pudo iniciar sesión'

        } finally {
            cargando.value = false
        }
    }

</script>

<template>
    <v-container fluid class="fill-height">
        <v-row align="center" justify="center">
            <v-col cols="12" sm="8" md="5" lg="4">
                <v-card class="text-center pa-5">
                    <div class="pa-5">
                        <v-icon size="64" color="primary">mdi-shield-account</v-icon>
                        <h1>Iniciar Sesión</h1>
                        <p>Introducir credenciales</p>
                    </div>

                    <v-form class="pa-4">
                        <v-text-field label="email" v-model="email" class="" placeholder="ejemplo@ejemplo.com" type="email"></v-text-field>
                        <v-text-field label="password" v-model="password" class="" type="password"></v-text-field>
                    </v-form>

                    <v-btn color="primary" @submit.prevent="">Iniciar Sesión</v-btn>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>