
import 'dart:convert';

import 'package:material_ui/material_ui.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:geolocator/geolocator.dart';

import '../utils/api_config.dart';
import '../services/seguimiento_service.dart';
import '../services/ubicacion_pendiente_service.dart';
import 'login_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  Map<String, dynamic>? usuario;

  bool cargando = true;
  bool cerrandoSesion = false;
  bool seguimientoActivo = false;

  String? error;
  String? errorSeguimiento;

  Position? posicion;
  DateTime? ultimoEnvio;

  String estadoGPS = 'Esperando';
  String estadoServidor = 'Sin confirmar';

  int ubicacionesPendientes = 0;

  final SeguimientoService _seguimientoService =
      SeguimientoService();

  final UbicacionPendienteService _pendienteService =
      UbicacionPendienteService();

  @override
  void initState() {
    super.initState();

    actualizarPendientes();
    obtenerUsuario();
  }

  @override
  void dispose() {
    _seguimientoService.dispose();
    super.dispose();
  }

  // Consultar información del usuario autenticado.
  Future<void> obtenerUsuario() async {
    if (!mounted) return;

    setState(() {
      cargando = true;
      error = null;
    });

    try {
      final preferencias = await SharedPreferences.getInstance();
      final token = preferencias.getString('token');

      if (token == null || token.isEmpty) {
        throw Exception(
          'No se encontró el token de autenticación',
        );
      }

      final respuesta = await http.get(
        Uri.parse('${ApiConfig.baseUrl}/usuario/movil'),
        headers: {
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
      ).timeout(const Duration(seconds: 20));

      if (respuesta.statusCode == 200) {
        final datos = jsonDecode(respuesta.body);

        if (datos is! Map) {
          throw Exception('Respuesta del servidor no válida');
        }

        if (!mounted) return;

        setState(() {
          usuario = Map<String, dynamic>.from(datos);
          cargando = false;
          errorSeguimiento = null;
        });

        await actualizarPendientes();

        if (tienePermiso && vehiculoAsignado != null) {
          iniciarSeguimiento();
        } else {
          _seguimientoService.detener();

          setState(() {
            seguimientoActivo = false;
            estadoGPS = 'Detenido';
            estadoServidor = 'Sin asignación';
          });
        }
      } else if (respuesta.statusCode == 401) {
        throw Exception(
          'La sesión ha expirado. Inicia sesión nuevamente',
        );
      } else {
        throw Exception(
          'Error al consultar información '
          '(${respuesta.statusCode})',
        );
      }
    } catch (e) {
      if (!mounted) return;

      setState(() {
        error = e.toString().replaceFirst('Exception: ', '');
        cargando = false;
      });
    }
  }

  // Consultar cantidad de ubicaciones pendientes.
  Future<void> actualizarPendientes() async {
    try {
      final cantidad =
          await _pendienteService.cantidadPendientes();

      if (!mounted) return;

      setState(() {
        ubicacionesPendientes = cantidad;
      });
    } catch (e) {
      debugPrint('Error al consultar SQLite: $e');
    }
  }

  // Iniciar seguimiento automático.
  Future<void> iniciarSeguimiento() async {
    if (_seguimientoService.activo || !mounted) return;

    final asignacionId = asignacionActiva?['id'];

    if (asignacionId is! int) {
      setState(() {
        seguimientoActivo = false;
        estadoGPS = 'Detenido';
        estadoServidor = 'Sin asignación';
        errorSeguimiento =
            'No se pudo identificar la asignación activa.';
      });
      return;
    }

    setState(() {
      seguimientoActivo = true;
      errorSeguimiento = null;
      estadoGPS = 'Obteniendo ubicación';
      estadoServidor = 'Verificando';
    });

    await _seguimientoService.iniciar(
      asignacionId: asignacionId,

      onEnviado: (nuevaPosicion) {
        if (!mounted) return;

        setState(() {
          posicion = nuevaPosicion;
          ultimoEnvio = DateTime.now();

          seguimientoActivo = true;
          estadoGPS = 'Disponible';
          estadoServidor = 'Conectado';

          errorSeguimiento = null;
        });

        actualizarPendientes();
      },

      onError: (mensaje) {
        if (!mounted) return;

        setState(() {
          errorSeguimiento = mensaje;
          estadoServidor = 'Sin respuesta';
        });

        actualizarPendientes();
      },

      onNoAutorizado: () {
        if (!mounted) return;

        setState(() {
          seguimientoActivo = false;
          estadoServidor = 'No autorizado';

          errorSeguimiento =
              'El servidor rechazó el envío de ubicaciones. '
              'El seguimiento se ha detenido.';
        });

        actualizarPendientes();
      },

      onAsignacionNoDisponible: (mensaje) {
        if (!mounted) return;

        setState(() {
          seguimientoActivo = false;
          estadoGPS = 'Detenido';
          estadoServidor = 'Sin asignación';

          if (usuario != null) {
            usuario = {
              ...usuario!,
              'asignacion_activa': null,
            };
          }

          errorSeguimiento = mensaje;
        });

        actualizarPendientes();
      },
    );
  }

  // Cerrar sesión.
  Future<void> cerrarSesion() async {
    if (cerrandoSesion) return;

    setState(() {
      cerrandoSesion = true;
    });

    _seguimientoService.detener();

    setState(() {
      seguimientoActivo = false;
    });

    try {
      final preferencias = await SharedPreferences.getInstance();
      final token = preferencias.getString('token');

      if (token != null) {
        final respuesta = await http.post(
          Uri.parse('${ApiConfig.baseUrl}/logout'),
          headers: {
            'Accept': 'application/json',
            'Authorization': 'Bearer $token',
          },
        ).timeout(const Duration(seconds: 20));

        if (respuesta.statusCode != 200 &&
            respuesta.statusCode != 204 &&
            respuesta.statusCode != 401) {
          throw Exception(
            'No se pudo cerrar la sesión en el servidor '
            '(${respuesta.statusCode})',
          );
        }
      }

      await preferencias.remove('token');

      if (!mounted) return;

      Navigator.of(context).pushAndRemoveUntil(
        MaterialPageRoute(
          builder: (_) => const LoginScreen(),
        ),
        (route) => false,
      );
    } catch (e) {
      if (!mounted) return;

      setState(() {
        cerrandoSesion = false;
      });

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            'Error al cerrar sesión: '
            '${e.toString().replaceFirst('Exception: ', '')}',
          ),
        ),
      );

      if (tienePermiso && vehiculoAsignado != null) {
        iniciarSeguimiento();
      }
    }
  }

  // Comprobar permiso.
  bool get tienePermiso {
    final permisos = usuario?['permisos'];

    if (permisos is! List) return false;

    return permisos.any((permiso) =>
        permiso is Map &&
        permiso['codigo'] == 'enviar_ubicacion');
  }

  // Obtener asignación activa.
  Map<String, dynamic>? get asignacionActiva {
    final asignacion = usuario?['asignacion_activa'];

    if (asignacion is! Map) return null;

    if (asignacion['activo'] != true) return null;

    return Map<String, dynamic>.from(asignacion);
  }

  // Obtener vehículo asignado.
  Map<String, dynamic>? get vehiculoAsignado {
    final vehiculo = asignacionActiva?['vehiculo'];

    if (vehiculo is! Map) return null;

    return Map<String, dynamic>.from(vehiculo);
  }

  // Formatear fecha y hora.
  String formatearFecha(DateTime? fecha) {
    if (fecha == null) return '--';

    final local = fecha.toLocal();

    final dia = local.day.toString().padLeft(2, '0');
    final mes = local.month.toString().padLeft(2, '0');
    final anio = local.year;

    final hora = local.hour.toString().padLeft(2, '0');
    final minuto = local.minute.toString().padLeft(2, '0');
    final segundo = local.second.toString().padLeft(2, '0');

    return '$dia/$mes/$anio $hora:$minuto:$segundo';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF7F8FA),

      appBar: AppBar(
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.white,
        elevation: 0,
        title: const Text(
          'Mi cuenta',
          style: TextStyle(
            fontWeight: FontWeight.w600,
            fontSize: 19,
          ),
        ),
        centerTitle: false,
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 12),
            child: TextButton.icon(
              onPressed: cerrandoSesion ? null : cerrarSesion,
              icon: cerrandoSesion
                  ? const SizedBox(
                      width: 17,
                      height: 17,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                      ),
                    )
                  : const Icon(
                      Icons.logout,
                      size: 19,
                    ),
              label: Text(
                cerrandoSesion
                    ? 'Cerrando...'
                    : 'Cerrar sesión',
              ),
              style: TextButton.styleFrom(
                foregroundColor: const Color(0xFF424B57),
                textStyle: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ),
        ],
      ),

      body: cargando
          ? const Center(
              child: CircularProgressIndicator(),
            )
          : error != null
              ? _construirError()
              : !tienePermiso
                  ? _construirSinPermiso()
                  : vehiculoAsignado == null
                      ? _construirSinVehiculo()
                      : _construirPanelPrincipal(),
    );
  }

  Widget _construirError() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(
              Icons.error_outline,
              size: 48,
              color: Colors.grey,
            ),
            const SizedBox(height: 16),
            Text(
              error!,
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 20),
            OutlinedButton.icon(
              onPressed: obtenerUsuario,
              icon: const Icon(Icons.refresh),
              label: const Text('Reintentar'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _construirEncabezado() {
    final nombre = usuario?['nombre'] ?? '';
    final apellido = usuario?['apellido'] ?? '';

    return Row(
      children: [
        Container(
          width: 48,
          height: 48,
          decoration: BoxDecoration(
            color: const Color(0xFFECEFF3),
            borderRadius: BorderRadius.circular(14),
          ),
          child: const Icon(
            Icons.person_outline,
            size: 27,
            color: Color(0xFF596575),
          ),
        ),
        const SizedBox(width: 14),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'Bienvenido',
                style: TextStyle(
                  color: Colors.grey,
                  fontSize: 13,
                ),
              ),
              const SizedBox(height: 3),
              Text(
                '$nombre $apellido',
                style: const TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _construirSinPermiso() {
    return Padding(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _construirEncabezado(),
          const SizedBox(height: 40),
          Center(
            child: Column(
              children: [
                const Icon(
                  Icons.lock_outline,
                  size: 48,
                  color: Color(0xFF7A8491),
                ),
                const SizedBox(height: 16),
                const Text(
                  'Sin autorización',
                  style: TextStyle(
                    fontSize: 19,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 8),
                const Text(
                  'No tienes permiso para enviar ubicaciones. '
                  'Comunícate con el administrador del sistema.',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    color: Colors.grey,
                    fontSize: 14,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _construirSinVehiculo() {
    return Padding(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _construirEncabezado(),
          const SizedBox(height: 40),
          Center(
            child: Column(
              children: [
                const Icon(
                  Icons.directions_car_outlined,
                  size: 48,
                  color: Color(0xFF7A8491),
                ),
                const SizedBox(height: 16),
                const Text(
                  'Sin vehículo asignado',
                  style: TextStyle(
                    fontSize: 19,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  errorSeguimiento ??
                      'Actualmente no tienes una asignación '
                      'de vehículo activa.',
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    color: Colors.grey,
                    fontSize: 14,
                  ),
                ),
                const SizedBox(height: 20),
                OutlinedButton.icon(
                  onPressed: obtenerUsuario,
                  icon: const Icon(Icons.refresh),
                  label: const Text('Actualizar información'),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _construirPanelPrincipal() {
    final vehiculo = vehiculoAsignado!;

    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _construirEncabezado(),
          const SizedBox(height: 28),
          _tituloSeccion(
            'Vehículo asignado',
            Icons.directions_car_outlined,
          ),
          const SizedBox(height: 12),
          _construirTarjetaVehiculo(vehiculo),
          const SizedBox(height: 26),
          _tituloSeccion(
            'Estado del seguimiento',
            Icons.radar,
          ),
          const SizedBox(height: 12),
          _construirTarjetaEstado(),
          const SizedBox(height: 26),
          _tituloSeccion(
            'Última ubicación enviada',
            Icons.location_on_outlined,
          ),
          const SizedBox(height: 12),
          _construirTarjetaUbicacion(),
          const SizedBox(height: 24),
        ],
      ),
    );
  }

  Widget _tituloSeccion(String titulo, IconData icono) {
    return Row(
      children: [
        Icon(
          icono,
          size: 19,
          color: const Color(0xFF596575),
        ),
        const SizedBox(width: 9),
        Text(
          titulo,
          style: const TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w600,
          ),
        ),
      ],
    );
  }

  Widget _construirTarjetaVehiculo(
    Map<String, dynamic> vehiculo,
  ) {
    return _tarjeta(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: const Color(0xFFECEFF3),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(
                  Icons.directions_car_outlined,
                  size: 25,
                  color: Color(0xFF596575),
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      vehiculo['placa']?.toString() ?? '--',
                      style: const TextStyle(
                        fontSize: 21,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      '${vehiculo['marca'] ?? ''} '
                      '${vehiculo['modelo'] ?? ''}',
                      style: const TextStyle(
                        color: Colors.grey,
                        fontSize: 14,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const Divider(height: 28),
          _filaDato(
            'Año',
            vehiculo['anio'],
            icono: Icons.calendar_today_outlined,
          ),
          _filaDato(
            'Color',
            vehiculo['color'],
            icono: Icons.palette_outlined,
          ),
        ],
      ),
    );
  }

  Widget _construirTarjetaEstado() {
    return _tarjeta(
      child: Column(
        children: [
          _FilaEstado(
            titulo: 'Seguimiento',
            valor: seguimientoActivo ? 'Activo' : 'Detenido',
            icono: Icons.radar,
            colorValor: seguimientoActivo
                ? const Color(0xFF25845A)
                : const Color(0xFF7A8491),
          ),
          const Divider(height: 24),
          _FilaEstado(
            titulo: 'GPS',
            valor: estadoGPS,
            icono: Icons.gps_fixed,
          ),
          const Divider(height: 24),
          _FilaEstado(
            titulo: 'Servidor',
            valor: estadoServidor,
            icono: Icons.cloud_outlined,
            colorValor: estadoServidor == 'Conectado'
                ? const Color(0xFF25845A)
                : null,
          ),
          const Divider(height: 24),
          _FilaEstado(
            titulo: 'Ubicaciones pendientes',
            valor: ubicacionesPendientes.toString(),
            icono: Icons.cloud_upload_outlined,
            colorValor: ubicacionesPendientes > 0
                ? const Color(0xFFB7791F)
                : const Color(0xFF25845A),
          ),
          const Divider(height: 24),
          _FilaEstado(
            titulo: 'Último envío exitoso',
            valor: formatearFecha(ultimoEnvio),
            icono: Icons.schedule,
          ),
          const Divider(height: 24),
          const _FilaEstado(
            titulo: 'Intervalo de captura',
            valor: '15 segundos',
            icono: Icons.timer_outlined,
          ),
        ],
      ),
    );
  }

  Widget _construirTarjetaUbicacion() {
    return _tarjeta(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (posicion == null) ...[
            const Center(
              child: Column(
                children: [
                  Icon(
                    Icons.location_searching,
                    size: 35,
                    color: Color(0xFF7A8491),
                  ),
                  SizedBox(height: 12),
                  Text(
                    'Esperando la primera ubicación...',
                    style: TextStyle(
                      color: Colors.grey,
                    ),
                  ),
                ],
              ),
            ),
          ] else ...[
            _filaDato(
              'Latitud',
              posicion!.latitude.toStringAsFixed(6),
              icono: Icons.north,
            ),
            _filaDato(
              'Longitud',
              posicion!.longitude.toStringAsFixed(6),
              icono: Icons.east,
            ),
            _filaDato(
              'Velocidad',
              '${(posicion!.speed * 3.6).toStringAsFixed(2)} km/h',
              icono: Icons.speed,
            ),
            _filaDato(
              'Rumbo',
              '${posicion!.heading.toStringAsFixed(2)}°',
              icono: Icons.explore_outlined,
            ),
            _filaDato(
              'Precisión',
              '${posicion!.accuracy.toStringAsFixed(2)} metros',
              icono: Icons.my_location,
            ),
            _filaDato(
              'Fecha de captura',
              formatearFecha(posicion!.timestamp),
              icono: Icons.calendar_month_outlined,
            ),
          ],

          if (errorSeguimiento != null) ...[
            const Divider(height: 24),
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Icon(
                  Icons.info_outline,
                  color: Color(0xFFB7791F),
                  size: 19,
                ),
                const SizedBox(width: 9),
                Expanded(
                  child: Text(
                    errorSeguimiento!,
                    style: const TextStyle(
                      color: Color(0xFF8A641F),
                      fontSize: 13,
                    ),
                  ),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }

  Widget _tarjeta({required Widget child}) {
    return Card(
      elevation: 0,
      color: Colors.white,
      margin: EdgeInsets.zero,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: const BorderSide(
          color: Color(0xFFE5E8EC),
        ),
      ),
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: child,
      ),
    );
  }

  Widget _filaDato(
    String titulo,
    dynamic valor, {
    IconData? icono,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 13),
      child: Row(
        children: [
          if (icono != null) ...[
            Icon(
              icono,
              size: 17,
              color: const Color(0xFF84909D),
            ),
            const SizedBox(width: 9),
          ],
          Expanded(
            child: Text(
              titulo,
              style: const TextStyle(
                color: Colors.grey,
                fontSize: 13,
              ),
            ),
          ),
          Flexible(
            child: Text(
              valor?.toString() ?? '--',
              textAlign: TextAlign.end,
              style: const TextStyle(
                fontWeight: FontWeight.w500,
                fontSize: 13,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _FilaEstado extends StatelessWidget {
  final String titulo;
  final String valor;
  final IconData icono;
  final Color? colorValor;

  const _FilaEstado({
    required this.titulo,
    required this.valor,
    required this.icono,
    this.colorValor,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Icon(
          icono,
          size: 19,
          color: const Color(0xFF84909D),
        ),
        const SizedBox(width: 11),
        Expanded(
          child: Text(
            titulo,
            style: const TextStyle(
              color: Colors.grey,
              fontSize: 13,
            ),
          ),
        ),
        Flexible(
          child: Text(
            valor,
            textAlign: TextAlign.end,
            style: TextStyle(
              fontWeight: FontWeight.w600,
              fontSize: 13,
              color: colorValor ?? const Color(0xFF343B44),
            ),
          ),
        ),
      ],
    );
  }
}
