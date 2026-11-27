
import 'dart:async';
import 'dart:collection';

import 'package:flutter/foundation.dart';
import 'package:geolocator/geolocator.dart';

import 'package:permission_handler/permission_handler.dart';

import 'ubicacion_service.dart';
import 'ubicacion_api_service.dart';
import 'ubicacion_pendiente_service.dart';

class SeguimientoService {
  static const Duration intervalo = Duration(seconds: 15);

  final UbicacionService _ubicacionService = UbicacionService();
  final UbicacionApiService _ubicacionApiService =
      UbicacionApiService();
  final UbicacionPendienteService _pendienteService =
      UbicacionPendienteService();

  StreamSubscription<Position>? _suscripcion;

  final Queue<Position> _colaPosiciones = Queue<Position>();

  bool _procesando = false;
  int? _asignacionId;

  bool get activo => _suscripcion != null;

  Future<void> iniciar({
    required int asignacionId,
    required void Function(Position posicion) onEnviado,
    required void Function(String mensaje) onError,
    required void Function() onNoAutorizado,
    required void Function(String mensaje) onAsignacionNoDisponible,
  }) async {
    if (activo) return;

    _asignacionId = asignacionId;

    try {
      if (defaultTargetPlatform == TargetPlatform.android) {
        final permisoNotificaciones =
            await Permission.notification.request();

        if (!permisoNotificaciones.isGranted) {
          throw Exception(
            'No se concedió el permiso de notificaciones. '
            'Actívalo desde los ajustes de Android.',
          );
        }
      }

      await _ubicacionService.verificarPermisos();

      final LocationSettings configuracion;

      if (defaultTargetPlatform == TargetPlatform.android) {
        configuracion = AndroidSettings(
          accuracy: LocationAccuracy.high,
          distanceFilter: 0,
          intervalDuration: intervalo,
          foregroundNotificationConfig:
              const ForegroundNotificationConfig(
            notificationTitle: 'Monitoreo de vehículo activo',
            notificationText:
                'La aplicación está registrando la ubicación del vehículo.',
            notificationChannelName: 'Monitoreo GPS',
            enableWakeLock: true,
            setOngoing: true,
          ),
        );
      } else {
        configuracion = const LocationSettings(
          accuracy: LocationAccuracy.high,
          distanceFilter: 0,
        );
      }

      final flujoUbicaciones = Geolocator.getPositionStream(
        locationSettings: configuracion,
      );

      _suscripcion = flujoUbicaciones.listen(
        (Position posicion) {
          _colaPosiciones.addLast(posicion);

          _procesarCola(
            onEnviado: onEnviado,
            onError: onError,
            onNoAutorizado: onNoAutorizado,
            onAsignacionNoDisponible: onAsignacionNoDisponible,
          );
        },
        onError: (error) {
          onError(
            'Error al recibir ubicaciones GPS: $error',
          );
        },
      );
    } catch (e) {
      _asignacionId = null;

      onError(
        e.toString().replaceFirst('Exception: ', ''),
      );
    }
  }

  Future<void> _procesarCola({
    required void Function(Position posicion) onEnviado,
    required void Function(String mensaje) onError,
    required void Function() onNoAutorizado,
    required void Function(String mensaje) onAsignacionNoDisponible,
  }) async {
    if (_procesando) return;

    _procesando = true;

    try {
      while (_colaPosiciones.isNotEmpty && activo) {
        final posicion = _colaPosiciones.removeFirst();

        final asignacionId = _asignacionId;

        if (asignacionId == null) {
          detener();
          return;
        }

        final datos = _ubicacionApiService.prepararDatos(posicion);

        await _pendienteService.guardar(
          datos,
          asignacionId: asignacionId,
        );

        final sincronizado = await _sincronizarPendientes(
          onError: onError,
          onNoAutorizado: onNoAutorizado,
          onAsignacionNoDisponible: onAsignacionNoDisponible,
        );

        if (sincronizado && activo) {
          onEnviado(posicion);
        }
      }
    } catch (e) {
      onError(
        e.toString().replaceFirst('Exception: ', ''),
      );
    } finally {
      _procesando = false;

      if (_colaPosiciones.isNotEmpty && activo) {
        _procesarCola(
          onEnviado: onEnviado,
          onError: onError,
          onNoAutorizado: onNoAutorizado,
          onAsignacionNoDisponible: onAsignacionNoDisponible,
        );
      }
    }
  }

  Future<bool> _sincronizarPendientes({
    required void Function(String mensaje) onError,
    required void Function() onNoAutorizado,
    required void Function(String mensaje) onAsignacionNoDisponible,
  }) async {
    while (activo) {
      final registro = await _pendienteService.obtenerPrimera();

      if (registro == null) {
        return true;
      }

      final id = registro['id'] as int;
      final datos = registro['datos'] as Map<String, dynamic>;

      try {
        await _ubicacionApiService.enviarDatos(datos);

        // Eliminar el registro únicamente después del envío exitoso.
        await _pendienteService.eliminar(id);
      } on UbicacionNoAutorizadaException {
        detener();
        onNoAutorizado();
        return false;
      } on SesionExpiradaException catch (e) {
        detener();
        onError(e.toString());
        return false;
      } on AsignacionNoDisponibleException catch (e) {
        // El usuario ya no tiene una asignación válida.
        // Se detiene el GPS y se conserva el registro pendiente.
        detener();
        onAsignacionNoDisponible(e.toString());
        return false;
      } on DatosUbicacionRechazadosException catch (e) {
        await _pendienteService.eliminar(id);
        onError(e.toString());
      } catch (e) {
        // Si falla la conexión, conservar el registro.
        onError(
          'No se pudieron enviar las ubicaciones pendientes. '
          'Se intentará nuevamente: '
          '${e.toString().replaceFirst('Exception: ', '')}',
        );

        return false;
      }
    }

    return false;
  }

  void detener() {
    final suscripcion = _suscripcion;

    _suscripcion = null;
    _asignacionId = null;
    _colaPosiciones.clear();

    if (suscripcion != null) {
      unawaited(suscripcion.cancel());
    }
  }

  void dispose() {
    detener();
  }
}
