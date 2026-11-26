
import 'package:geolocator/geolocator.dart';

class UbicacionService {

  Future<Position> obtenerUbicacionActual() async {

    // 1. Comprobar si el servicio de ubicación está activado.
    final servicioHabilitado =
        await Geolocator.isLocationServiceEnabled();

    if (!servicioHabilitado) {
      throw Exception('El GPS está desactivado');
    }

    // 2. Comprobar los permisos de ubicación.
    LocationPermission permiso =
        await Geolocator.checkPermission();

    // 3. Solicitar permiso si todavía no fue concedido.
    if (permiso == LocationPermission.denied) {
      permiso = await Geolocator.requestPermission();
    }

    // 4. Verificar si el usuario rechazó el permiso.
    if (permiso == LocationPermission.denied) {
      throw Exception('No se concedió el permiso de ubicación');
    }

    // 5. Verificar si el permiso fue denegado permanentemente.
    if (permiso == LocationPermission.deniedForever) {
      throw Exception(
        'El permiso de ubicación está denegado permanentemente. '
        'Debes habilitarlo desde los ajustes de Android.',
      );
    }

    // 6. Obtener la ubicación actual.
    final posicion = await Geolocator.getCurrentPosition(
      locationSettings: const LocationSettings(
        accuracy: LocationAccuracy.high,
      ),
    );

    return posicion;
  }
}
