import api from './api'

export async function cerrarSesion() {
    await api.post('/logout')

    localStorage.removeItem('token')
}

