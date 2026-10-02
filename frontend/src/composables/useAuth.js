import { ref } from 'vue'
import api from '../services/api'

const usuario = ref(null)

export function useAuth() {
    const restaurarSesion = async () => {
        const token = localStorage.getItem('token')
        
        if (!token) {
            return
        } 

        try {
            const respuesta = await api.get('/user')
            usuario.value = respuesta.data

        } catch (error) {
            localStorage.removeItem('token')
            usuario.value = null

            console.error('Sesión no válida', error)
        } 
    } 

    const limpiarUsuario = () => {
        usuario.value = null
    }

    return {
        usuario,
        restaurarSesion, 
        limpiarUsuario
    }
}