import 'dart:convert';

import 'package:path/path.dart' as path;
import 'package:sqflite/sqflite.dart';

class UbicacionPendienteService {
  static Database? _database;
  static Future<Database>? _databaseFuture;

  Future<Database> get _db async {
    if (_database != null) {
      return _database!;
    }

    _databaseFuture ??= _inicializarBaseDatos();
    _database = await _databaseFuture!;

    return _database!;
  }

  Future<Database> _inicializarBaseDatos() async {
    final ruta = path.join(
      await getDatabasesPath(),
      'movil_gps.db',
    );

    return openDatabase(
      ruta,
      version: 2,
      onCreate: (db, version) async {
        await db.execute('''
          CREATE TABLE ubicaciones_pendientes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            datos TEXT NOT NULL,
            creado_en TEXT NOT NULL,
            asignacion_id INTEGER
          )
        ''');

        await _crearTablaRevision(db);
      },
      onUpgrade: (db, versionAnterior, versionNueva) async {
        if (versionAnterior < 2) {
          await db.execute('''
            ALTER TABLE ubicaciones_pendientes
            ADD COLUMN asignacion_id INTEGER
          ''');

          await _crearTablaRevision(db);
        }
      },
    );
  }

  Future<void> _crearTablaRevision(Database db) async {
    await db.execute('''
      CREATE TABLE IF NOT EXISTS ubicaciones_revision (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ubicacion_original_id INTEGER,
        datos TEXT NOT NULL,
        creado_en TEXT NOT NULL,
        asignacion_id INTEGER,
        motivo TEXT NOT NULL,
        revisado_en TEXT NOT NULL
      )
    ''');
  }

  Future<int> guardar(
    Map<String, dynamic> ubicacion, {
    required int asignacionId,
  }) async {
    final db = await _db;

    return db.insert(
      'ubicaciones_pendientes',
      {
        'datos': jsonEncode(ubicacion),
        'creado_en': DateTime.now().toUtc().toIso8601String(),
        'asignacion_id': asignacionId,
      },
    );
  }

  Future<Map<String, dynamic>?> obtenerPrimera() async {
    final db = await _db;

    final registros = await db.query(
      'ubicaciones_pendientes',
      orderBy: 'id ASC',
      limit: 1,
    );

    if (registros.isEmpty) {
      return null;
    }

    final registro = registros.first;

    return {
      'id': registro['id'] as int,
      'datos': Map<String, dynamic>.from(
        jsonDecode(registro['datos'] as String),
      ),
      'asignacion_id': registro['asignacion_id'] as int?,
    };
  }

  Future<void> eliminar(int id) async {
    final db = await _db;

    await db.delete(
      'ubicaciones_pendientes',
      where: 'id = ?',
      whereArgs: [id],
    );
  }

  // Conserva una ubicación que no debe enviarse al vehículo actual.
  Future<void> moverARevision(
    int id, {
    required String motivo,
  }) async {
    final db = await _db;

    await db.transaction((txn) async {
      final registros = await txn.query(
        'ubicaciones_pendientes',
        where: 'id = ?',
        whereArgs: [id],
        limit: 1,
      );

      if (registros.isEmpty) return;

      final registro = registros.first;

      await txn.insert(
        'ubicaciones_revision',
        {
          'ubicacion_original_id': registro['id'],
          'datos': registro['datos'],
          'creado_en': registro['creado_en'],
          'asignacion_id': registro['asignacion_id'],
          'motivo': motivo,
          'revisado_en': DateTime.now().toUtc().toIso8601String(),
        },
      );

      await txn.delete(
        'ubicaciones_pendientes',
        where: 'id = ?',
        whereArgs: [id],
      );
    });
  }

  Future<int> cantidadPendientes() async {
    final db = await _db;

    final resultado = await db.rawQuery(
      'SELECT COUNT(*) FROM ubicaciones_pendientes',
    );

    return Sqflite.firstIntValue(resultado) ?? 0;
  }

  Future<int> cantidadEnRevision() async {
    final db = await _db;

    final resultado = await db.rawQuery(
      'SELECT COUNT(*) FROM ubicaciones_revision',
    );

    return Sqflite.firstIntValue(resultado) ?? 0;
  }
}