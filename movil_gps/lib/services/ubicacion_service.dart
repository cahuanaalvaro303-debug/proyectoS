
import 'package:geolocator/geolocator.dart';

class UbicacionService {
  Future<void> verificarPermisos() async {
    final servicioHabilitado =
        await Geolocator.isLocationServiceEnabled();

    if (!servicioHabilitado) {
      throw Exception('El GPS está desactivado');
    }

    LocationPermission permiso =
        await Geolocator.checkPermission();

    if (permiso == LocationPermission.denied) {
      permiso = await Geolocator.requestPermission();
    }

    if (permiso == LocationPermission.denied) {
      throw Exception('No se concedió el permiso de ubicación');
    }

    if (permiso == LocationPermission.deniedForever) {
      throw Exception(
        'El permiso de ubicación está denegado permanentemente. '
        'Debes habilitarlo desde los ajustes de Android.',
      );
    }
  }

  Future<Position> obtenerUbicacionActual() async {
    await verificarPermisos();

    try {
      return await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
        ),
      );
    } catch (e) {
      throw Exception('No se pudo obtener la ubicación GPS: $e');
    }
  }
}
