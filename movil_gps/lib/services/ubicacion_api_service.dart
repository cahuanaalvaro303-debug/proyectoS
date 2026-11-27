import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:geolocator/geolocator.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import '../utils/api_config.dart';

class UbicacionNoAutorizadaException implements Exception {
  final String mensaje;
  UbicacionNoAutorizadaException(this.mensaje);

  @override
  String toString() => mensaje;
}

class SesionExpiradaException implements Exception {
  final String mensaje;
  SesionExpiradaException(this.mensaje);

  @override
  String toString() => mensaje;
}

class DatosUbicacionRechazadosException implements Exception {
  final String mensaje;
  DatosUbicacionRechazadosException(this.mensaje);

  @override
  String toString() => mensaje;
}

class AsignacionNoDisponibleException implements Exception {
  final String mensaje;
  AsignacionNoDisponibleException(this.mensaje);

  @override
  String toString() => mensaje;
}

class UbicacionApiService {
  Map<String, dynamic> prepararDatos(Position posicion) {
    final velocidadKmh = posicion.speed * 3.6;

    return {
      'latitud': posicion.latitude,
      'longitud': posicion.longitude,
      'velocidad': velocidadKmh.isFinite
          ? velocidadKmh.clamp(0.0, 1000.0)
          : 0.0,
      'direccion': posicion.heading.isFinite
          ? posicion.heading
          : 0.0,
      'precision': posicion.accuracy,
      'registrado_en': posicion.timestamp.toUtc().toIso8601String(),
    };
  }

  Future<void> enviarUbicacion(Position posicion) async {
    final datos = prepararDatos(posicion);
    await enviarDatos(datos);
  }

  Future<void> enviarDatos(Map<String, dynamic> datos) async {
    final preferencias = await SharedPreferences.getInstance();
    final token = preferencias.getString('token');

    if (token == null || token.isEmpty) {
      throw SesionExpiradaException(
        'No se encontró el token de autenticación.',
      );
    }

    final url = Uri.parse('${ApiConfig.baseUrl}/ubicaciones');

    final respuesta = await http.post(
      url,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
      body: jsonEncode(datos),
    ).timeout(const Duration(seconds: 20));

    if (respuesta.statusCode == 200 ||
        respuesta.statusCode == 201) {
      debugPrint('Ubicación enviada correctamente');
      return;
    }

    if (respuesta.statusCode == 401) {
      throw SesionExpiradaException(
        'La sesión expiró. Inicia sesión nuevamente.',
      );
    }

    if (respuesta.statusCode == 403) {
      throw UbicacionNoAutorizadaException(
        'No tienes autorización para enviar ubicaciones.',
      );
    }

    if (respuesta.statusCode == 422) {
      String mensaje = respuesta.body;

      try {
        final cuerpo = jsonDecode(respuesta.body);

        if (cuerpo is Map && cuerpo['message'] is String) {
          mensaje = cuerpo['message'] as String;
        }
      } catch (_) {
        // Se conserva el cuerpo original si no es JSON válido.
      }

      const mensajesAsignacion = [
        'No tienes un vehículo asignado actualmente.',
        'Existe más de una asignación activa. Debe revisarse.',
        'El vehículo asignado no está activo.',
      ];

      if (mensajesAsignacion.contains(mensaje)) {
        throw AsignacionNoDisponibleException(mensaje);
      }

      throw DatosUbicacionRechazadosException(
        'Laravel rechazó los datos enviados: $mensaje',
      );
    }

    throw Exception(
      'Error al enviar ubicación. '
      'Código HTTP: ${respuesta.statusCode}. '
      'Respuesta: ${respuesta.body}',
    );
  }
}