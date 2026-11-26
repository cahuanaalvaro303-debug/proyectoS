
import 'dart:convert';

import 'package:material_ui/material_ui.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:geolocator/geolocator.dart';

import '../utils/api_config.dart';
import '../services/ubicacion_service.dart';
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
  bool obteniendoUbicacion = false;

  String? error;
  String? errorUbicacion;

  Position? posicion;

  final UbicacionService _ubicacionService = UbicacionService();

  @override
  void initState() {
    super.initState();
    obtenerUsuario();
  }

  // Consultar información del usuario autenticado
  Future<void> obtenerUsuario() async {

    setState(() {
      cargando = true;
      error = null;
    });

    try {

      final preferencias = await SharedPreferences.getInstance();
      final token = preferencias.getString('token');

      if (token == null) {
        throw Exception('No se encontró el token de autenticación');
      }

      final respuesta = await http.get(
        Uri.parse('${ApiConfig.baseUrl}/usuario/movil'),
        headers: {
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
      );

      if (respuesta.statusCode == 200) {

        final datos = jsonDecode(respuesta.body);

        if (datos is! Map) {
          throw Exception('Respuesta del servidor no válida');
        }

        if (!mounted) return;

        setState(() {
          usuario = Map<String, dynamic>.from(datos);
          cargando = false;
        });

      } else if (respuesta.statusCode == 401) {

        throw Exception('La sesión ha expirado. Inicia sesión nuevamente');

      } else {

        throw Exception(
          'Error al consultar información (${respuesta.statusCode})',
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

  // Cerrar sesión
  Future<void> cerrarSesion() async {

    if (cerrandoSesion) return;

    setState(() {
      cerrandoSesion = true;
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
        );

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
          builder: (_) => LoginScreen(),
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
    }
  }

  // Comprobar si el usuario tiene permiso para enviar ubicaciones
  bool get tienePermiso {

    final permisos = usuario?['permisos'];

    if (permisos is! List) return false;

    return permisos.any((permiso) =>
        permiso is Map &&
        permiso['codigo'] == 'enviar_ubicacion'
    );
  }

  // Obtener la asignación activa
  Map<String, dynamic>? get asignacionActiva {

    final asignacion = usuario?['asignacion_activa'];

    if (asignacion is! Map) return null;

    if (asignacion['activo'] != true) return null;

    return Map<String, dynamic>.from(asignacion);
  }

  // Obtener vehículo asignado
  Map<String, dynamic>? get vehiculoAsignado {

    final vehiculo = asignacionActiva?['vehiculo'];

    if (vehiculo is! Map) return null;

    return Map<String, dynamic>.from(vehiculo);
  }

  // Obtener ubicación GPS
  Future<void> obtenerUbicacion() async {

    setState(() {
      obteniendoUbicacion = true;
      errorUbicacion = null;
    });

    try {

      final resultado =
          await _ubicacionService.obtenerUbicacionActual();

      if (!mounted) return;

      setState(() {
        posicion = resultado;
        obteniendoUbicacion = false;
      });

    } catch (e) {

      if (!mounted) return;

      setState(() {
        errorUbicacion =
            e.toString().replaceFirst('Exception: ', '');
        obteniendoUbicacion = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {

    return Scaffold(

      appBar: AppBar(
        title: const Text(
          'Mi cuenta',
          style: TextStyle(fontWeight: FontWeight.w600),
        ),
        centerTitle: false,
        actions: [

          IconButton(
            tooltip: 'Cerrar sesión',
            onPressed: cerrandoSesion ? null : cerrarSesion,
            icon: cerrandoSesion
                ? const SizedBox(
                    width: 22,
                    height: 22,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                    ),
                  )
                : const Icon(Icons.logout),
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

  // Pantalla de error al consultar el usuario
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

  // Encabezado con información del usuario
  Widget _construirEncabezado() {

    final nombre = usuario?['nombre'] ?? '';
    final apellido = usuario?['apellido'] ?? '';

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [

        const Text(
          'Bienvenido',
          style: TextStyle(
            color: Colors.grey,
            fontSize: 14,
          ),
        ),

        const SizedBox(height: 4),

        Text(
          '$nombre $apellido',
          style: const TextStyle(
            fontSize: 22,
            fontWeight: FontWeight.bold,
          ),
        ),

      ],
    );
  }

  // Pantalla para usuarios sin autorización
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
                  size: 55,
                  color: Colors.grey,
                ),

                const SizedBox(height: 16),

                const Text(
                  'Sin autorización',
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
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

  // Pantalla para usuarios autorizados sin vehículo
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
                  size: 55,
                  color: Colors.grey,
                ),

                const SizedBox(height: 16),

                const Text(
                  'Sin vehículo asignado',
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                  ),
                ),

                const SizedBox(height: 8),

                const Text(
                  'Actualmente no tienes una asignación '
                  'de vehículo activa.',
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

  // Panel principal para usuarios autorizados
  Widget _construirPanelPrincipal() {

    final vehiculo = vehiculoAsignado!;

    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [

          _construirEncabezado(),

          const SizedBox(height: 28),

          _tituloSeccion('Vehículo asignado'),

          const SizedBox(height: 12),

          _construirTarjetaVehiculo(vehiculo),

          const SizedBox(height: 28),

          _tituloSeccion('Estado del seguimiento'),

          const SizedBox(height: 12),

          _construirTarjetaEstado(),

          const SizedBox(height: 28),

          _tituloSeccion('Prueba de ubicación GPS'),

          const SizedBox(height: 12),

          _construirTarjetaUbicacion(),

        ],
      ),
    );
  }

  // Título de cada sección
  Widget _tituloSeccion(String titulo) {

    return Text(
      titulo,
      style: const TextStyle(
        fontSize: 17,
        fontWeight: FontWeight.w600,
      ),
    );
  }

  // Tarjeta de información del vehículo
  Widget _construirTarjetaVehiculo(
    Map<String, dynamic> vehiculo,
  ) {

    return Card(
      elevation: 0,
      color: Colors.white,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: const BorderSide(color: Color(0xFFE5E5E5)),
      ),
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [

            const Icon(
              Icons.directions_car_outlined,
              size: 30,
              color: Colors.black87,
            ),

            const SizedBox(height: 14),

            Text(
              vehiculo['placa']?.toString() ?? '--',
              style: const TextStyle(
                fontSize: 23,
                fontWeight: FontWeight.bold,
              ),
            ),

            const SizedBox(height: 4),

            Text(
              '${vehiculo['marca'] ?? ''} '
              '${vehiculo['modelo'] ?? ''}',
              style: const TextStyle(
                color: Colors.grey,
                fontSize: 14,
              ),
            ),

            const Divider(height: 28),

            _filaDato('Año', vehiculo['anio']),

            _filaDato('Color', vehiculo['color']),

          ],
        ),
      ),
    );
  }

  // Tarjeta de estado del seguimiento
  Widget _construirTarjetaEstado() {

    return Card(
      elevation: 0,
      color: Colors.white,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: const BorderSide(color: Color(0xFFE5E5E5)),
      ),
      child: const Padding(
        padding: EdgeInsets.all(18),
        child: Column(
          children: [

            _FilaEstado(
              titulo: 'Seguimiento',
              valor: 'Pendiente',
            ),

            Divider(height: 24),

            _FilaEstado(
              titulo: 'GPS',
              valor: 'Sin iniciar',
            ),

            Divider(height: 24),

            _FilaEstado(
              titulo: 'Internet',
              valor: 'Sin verificar',
            ),

            Divider(height: 24),

            _FilaEstado(
              titulo: 'Último envío',
              valor: '--',
            ),

          ],
        ),
      ),
    );
  }

  // Tarjeta para probar la ubicación GPS
  Widget _construirTarjetaUbicacion() {

    return Card(
      elevation: 0,
      color: Colors.white,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: const BorderSide(color: Color(0xFFE5E5E5)),
      ),
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [

            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: obteniendoUbicacion
                    ? null
                    : obtenerUbicacion,
                icon: const Icon(Icons.location_searching),
                label: const Text('Obtener ubicación actual'),
              ),
            ),

            if (obteniendoUbicacion) ...[

              const SizedBox(height: 16),

              const Center(
                child: CircularProgressIndicator(),
              ),

            ],

            if (errorUbicacion != null) ...[

              const SizedBox(height: 16),

              Text(
                errorUbicacion!,
                style: const TextStyle(
                  color: Colors.red,
                ),
              ),

            ],

            if (posicion != null) ...[

              const SizedBox(height: 20),

              const Divider(),

              _filaDato(
                'Latitud',
                posicion!.latitude.toString(),
              ),

              _filaDato(
                'Longitud',
                posicion!.longitude.toString(),
              ),

              _filaDato(
                'Velocidad',
                '${(posicion!.speed * 3.6).toStringAsFixed(2)} km/h',
              ),

              _filaDato(
                'Rumbo',
                '${posicion!.heading.toStringAsFixed(2)}°',
              ),

              _filaDato(
                'Precisión',
                '${posicion!.accuracy.toStringAsFixed(2)} metros',
              ),

              _filaDato(
                'Fecha de captura',
                posicion!.timestamp.toLocal().toString(),
              ),

            ],

          ],
        ),
      ),
    );
  }

  // Fila de información
  Widget _filaDato(String titulo, dynamic valor) {

    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [

          Text(
            titulo,
            style: const TextStyle(
              color: Colors.grey,
              fontSize: 14,
            ),
          ),

          Flexible(
            child: Text(
              valor?.toString() ?? '--',
              textAlign: TextAlign.end,
              style: const TextStyle(
                fontWeight: FontWeight.w500,
              ),
            ),
          ),

        ],
      ),
    );
  }
}

// Componente para mostrar los indicadores del seguimiento
class _FilaEstado extends StatelessWidget {

  final String titulo;
  final String valor;

  const _FilaEstado({
    required this.titulo,
    required this.valor,
  });

  @override
  Widget build(BuildContext context) {

    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [

        Text(
          titulo,
          style: const TextStyle(
            color: Colors.grey,
            fontSize: 14,
          ),
        ),

        Text(
          valor,
          style: const TextStyle(
            fontWeight: FontWeight.w500,
          ),
        ),

      ],
    );
  }
}
