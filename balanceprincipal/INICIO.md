# Balance Studio — Panel de empleados (balanceprincipal)

## Base de datos

Ambas apps usan **`balance_final`** en MySQL (Laragon).

Si falta la tabla `reservaciones` o la columna `clases_restantes`, ejecuta en phpMyAdmin:

`../balancebarre/database.sql`

## Cómo iniciar el panel

```bash
cd aplicacion
python app.py
```

## URL del panel (empleados)

**http://127.0.0.1:5000/**

| Ruta | Uso |
|------|-----|
| http://127.0.0.1:5000/ | Gestión de alumnas |
| http://127.0.0.1:5000/agenda | Horarios e inscritas |
| http://127.0.0.1:5000/recepcion | Check-in |

## URL del portal clientes (balancebarre)

Con Laragon, carpeta en `www` o `scratch`:

**http://localhost/balancebarre/**

(o la URL que Laragon muestre para esa carpeta, por ejemplo `http://balancebarre.test/`)

| Página | URL típica |
|--------|------------|
| Inicio | http://localhost/balancebarre/index.php |
| Clases (registro + elegir días) | http://localhost/balancebarre/registro.php |
| Horarios (solo consulta) | http://localhost/balancebarre/horarios.php |
