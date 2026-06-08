from contextlib import contextmanager
from datetime import date, datetime, timedelta
import hashlib

import pymysql
from flask import Flask, flash, redirect, render_template, request, url_for, session

DIAS_SEMANA = {
    'Lunes': 0, 'Martes': 1, 'Miércoles': 2,
    'Jueves': 3, 'Viernes': 4, 'Sábado': 5,
}

DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'balance_final',
    'port': 3306,
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.Cursor,
}


def proxima_fecha_por_dia(dia_semana: str, desde=None) -> date:
    desde = desde or date.today()
    objetivo = DIAS_SEMANA.get(dia_semana)
    if objetivo is None:
        return desde
    dias = (objetivo - desde.weekday()) % 7
    return desde + timedelta(days=dias)


def fmt_fecha(val):
    if hasattr(val, 'strftime'):
        return val.strftime('%Y-%m-%d')
    return str(val)[:10] if val else '—'


def fmt_hora(val):
    if hasattr(val, 'strftime'):
        return val.strftime('%H:%M')
    s = str(val)
    return s[:5] if len(s) >= 5 else s


@contextmanager
def get_db():
    conn = pymysql.connect(**DB_CONFIG)
    try:
        yield conn
        conn.commit()
    except Exception:
        conn.rollback()
        raise
    finally:
        conn.close()


def ensure_schema():
    """Crea tablas que puedan faltar en balance_final."""
    with get_db() as conn:
        cur = conn.cursor()
        cur.execute("""
            CREATE TABLE IF NOT EXISTS asistencia_coaches (
                id INT AUTO_INCREMENT PRIMARY KEY,
                coach_id INT NOT NULL,
                fecha DATE NOT NULL,
                hora_entrada TIME NOT NULL,
                id_horario INT NULL,
                notas VARCHAR(255) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_coach_fecha (coach_id, fecha),
                FOREIGN KEY (coach_id) REFERENCES coaches(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        """)
        try:
            cur.execute("""
                ALTER TABLE alumnas
                ADD COLUMN clases_restantes INT DEFAULT 0 AFTER paquete_id
            """)
        except pymysql.err.OperationalError as e:
            if e.args[0] != 1060:
                raise


app = Flask(__name__)
app.secret_key = 'balance_barre_2026'

ensure_schema()


@app.before_request
def check_auth():
    if request.endpoint in ('login_external', 'static'):
        return
    if not session.get('coach_id'):
        return redirect('http://localhost/balancebarre/login.php')


@app.route('/login_external')
def login_external():
    telefono = request.args.get('telefono')
    token = request.args.get('token')
    
    if not telefono or not token:
        flash('Acceso denegado: faltan parámetros.', 'alerta')
        return redirect('http://localhost/balancebarre/login.php')
        
    secret = "balance_barre_secret_key_2026"
    today = date.today().isoformat()
    
    expected_token = hashlib.sha256(f"{telefono}{secret}{today}".encode('utf-8')).hexdigest()
    
    if token != expected_token:
        flash('Acceso denegado: token inválido.', 'alerta')
        return redirect('http://localhost/balancebarre/login.php')
        
    with get_db() as conn:
        cur = conn.cursor()
        cur.execute("SELECT id, nombre, apellidos FROM coaches WHERE telefono = %s AND activo = 1", (telefono,))
        coach = cur.fetchone()
        
    if coach:
        session['coach_id'] = coach[0]
        session['coach_nombre'] = f"{coach[1]} {coach[2]}"
        return redirect(url_for('index'))
    else:
        flash('Coach no encontrado.', 'alerta')
        return redirect('http://localhost/balancebarre/login.php')


@app.route('/logout')
def logout():
    session.clear()
    return redirect('http://localhost/balancebarre/login.php')


@app.route('/')
def index():
    with get_db() as conn:
        cur = conn.cursor()
        cur.execute("""
            SELECT a.id, a.nombre, a.apellidos,
                   COALESCE(a.clases_restantes, 0), a.telefono,
                   a.monto, a.fecha_vencimiento, a.estatus, a.lesion,
                   p.nombre AS paquete_nombre,
                   a.fecha_registro,
                   (SELECT GROUP_CONCAT(
                        CONCAT(h.dia_semana, ' ', DATE_FORMAT(h.hora_inicio, '%%H:%%i'),
                               ' (', DATE_FORMAT(r.fecha_clase, '%%d/%%m'), ')')
                        ORDER BY r.fecha_clase SEPARATOR ' · ')
                    FROM reservaciones r
                    INNER JOIN horarios h ON r.id_clase = h.id
                    WHERE r.id_alumna = a.id AND r.estatus = 'Confirmada') AS horarios_txt
            FROM alumnas a
            LEFT JOIN paquetes p ON a.paquete_id = p.id
            ORDER BY a.id DESC
        """)
        alumnas = cur.fetchall()

        cur.execute("SELECT id, nombre, precio, clases_incluidas FROM paquetes WHERE activo = 1")
        paquetes = cur.fetchall()

    return render_template('index.html', alumnas=alumnas, paquetes=paquetes)


@app.route('/activar/<int:alumna_id>', methods=['POST'])
def activar_alumna(alumna_id):
    with get_db() as conn:
        cur = conn.cursor()
        cur.execute("UPDATE alumnas SET estatus = 'Activa' WHERE id = %s", (alumna_id,))
    flash('Alumna activada correctamente.', 'exito')
    return redirect(url_for('index'))


@app.route('/add', methods=['POST'])
def add_alumna():
    nombre = request.form['nombre']
    apellidos = request.form['apellidos']
    telefono = request.form['telefono']
    paquete_id = request.form.get('paquete_id')
    lesion = request.form.get('lesion', '')
    fecha_nacimiento = request.form.get('fecha_nacimiento') or None
    estatus = request.form.get('estatus', 'Activa')

    clases = 0
    monto = 0.0
    vencimiento = None
    pkg_id = None

    if paquete_id and paquete_id != 'manual':
        pkg_id = paquete_id
        with get_db() as conn:
            cur = conn.cursor()
            cur.execute(
                "SELECT clases_incluidas, precio, duracion_dias FROM paquetes WHERE id = %s",
                (paquete_id,),
            )
            pkg = cur.fetchone()
            if pkg:
                clases = pkg[0]
                monto = pkg[1]
                cur.execute("SELECT DATE_ADD(CURDATE(), INTERVAL %s DAY)", (pkg[2],))
                vencimiento = cur.fetchone()[0]

    if request.form.get('clases_override'):
        clases = int(request.form['clases_override'])
    if request.form.get('monto_override'):
        monto = float(request.form['monto_override'])
    if request.form.get('vencimiento_override'):
        vencimiento = request.form['vencimiento_override']

    with get_db() as conn:
        cur = conn.cursor()
        cur.execute("""
            INSERT INTO alumnas (
                nombre, apellidos, fecha_nacimiento, telefono, paquete_id,
                clases_restantes, lesion, fecha_registro, fecha_vencimiento, monto, estatus
            ) VALUES (%s, %s, %s, %s, %s, %s, %s, CURDATE(), %s, %s, %s)
        """, (
            nombre, apellidos, fecha_nacimiento, telefono, pkg_id,
            clases, lesion, vencimiento, monto, estatus,
        ))

    return redirect(url_for('index'))


@app.route('/agenda')
def agenda():
    with get_db() as conn:
        cur = conn.cursor()

        cur.execute("""
            SELECT c.id, c.nombre, c.apellidos, c.especialidad
            FROM coaches c
            WHERE c.activo = 1
            ORDER BY c.nombre
        """)
        coaches = cur.fetchall()

        cur.execute("""
            SELECT h.id, h.coach_id, h.dia_semana, h.hora_inicio, h.hora_fin,
                   h.tipo_clase, h.capacidad,
                   CONCAT(c.nombre, ' ', c.apellidos) AS coach_nombre
            FROM horarios h
            INNER JOIN coaches c ON h.coach_id = c.id
            WHERE h.activo = 1
            ORDER BY c.nombre, FIELD(h.dia_semana,
                'Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'), h.hora_inicio
        """)
        horarios_rows = cur.fetchall()

        cur.execute("""
            SELECT r.id_clase, r.fecha_clase,
                   CONCAT(a.nombre, ' ', a.apellidos) AS alumna,
                   a.estatus, a.telefono, p.nombre AS paquete
            FROM reservaciones r
            INNER JOIN alumnas a ON r.id_alumna = a.id
            LEFT JOIN paquetes p ON a.paquete_id = p.id
            WHERE r.estatus = 'Confirmada'
            ORDER BY r.fecha_clase ASC
        """)
        reservas_raw = cur.fetchall()

        inscritas = {}
        for id_clase, fecha_clase, alumna, estatus, telefono, paquete in reservas_raw:
            inscritas.setdefault(id_clase, []).append({
                'nombre': alumna,
                'fecha': fmt_fecha(fecha_clase),
                'estatus': estatus,
                'telefono': telefono,
                'paquete': paquete or '—',
            })

        coaches_agenda = []
        for coach in coaches:
            cid, nombre, apellidos, especialidad = coach
            clases_coach = []
            for row in horarios_rows:
                if row[1] != cid:
                    continue
                hid = row[0]
                clases_coach.append({
                    'id': hid,
                    'dia': row[2],
                    'hora_inicio': fmt_hora(row[3]),
                    'hora_fin': fmt_hora(row[4]),
                    'tipo': row[5],
                    'capacidad': row[6],
                    'inscritas': inscritas.get(hid, []),
                    'ocupados': len(inscritas.get(hid, [])),
                })
            coaches_agenda.append({
                'id': cid,
                'nombre': f'{nombre} {apellidos}',
                'especialidad': especialidad,
                'clases': clases_coach,
            })

        cur.execute("""
            SELECT a.id, CONCAT(a.nombre, ' ', a.apellidos), a.telefono, a.estatus, p.nombre
            FROM alumnas a
            LEFT JOIN paquetes p ON a.paquete_id = p.id
            WHERE a.estatus IN ('Activa', 'Pendiente')
            ORDER BY a.nombre
        """)
        alumnas = cur.fetchall()

        cur.execute("""
            SELECT COUNT(*) FROM alumnas WHERE estatus = 'Pendiente'
        """)
        pendientes_count = cur.fetchone()[0]

    return render_template(
        'agenda.html',
        coaches_agenda=coaches_agenda,
        alumnas=alumnas,
        pendientes_count=pendientes_count,
    )


@app.route('/reservar', methods=['POST'])
def reservar():
    id_clase = request.form['id_clase']
    id_alumna = request.form['id_alumna']
    fecha_clase = request.form.get('fecha_clase')

    with get_db() as conn:
        cur = conn.cursor()
        cur.execute("SELECT capacidad, dia_semana FROM horarios WHERE id = %s", (id_clase,))
        horario = cur.fetchone()
        if not horario:
            flash('Horario no encontrado.', 'alerta')
            return redirect(url_for('agenda'))

        capacidad = horario[0] if horario[0] else 15
        if not fecha_clase:
            fecha_clase = proxima_fecha_por_dia(horario[1]).isoformat()

        cur.execute("""
            SELECT COUNT(*) FROM reservaciones
            WHERE id_clase = %s AND fecha_clase = %s AND estatus = 'Confirmada'
        """, (id_clase, fecha_clase))
        if cur.fetchone()[0] < capacidad:
            cur.execute(
                "SELECT COALESCE(clases_restantes,0), estatus FROM alumnas WHERE id = %s",
                (id_alumna,),
            )
            alumna_row = cur.fetchone()
            if alumna_row and (alumna_row[0] > 0 or alumna_row[1] == 'Pendiente'):
                cur.execute("""
                    INSERT INTO reservaciones (id_clase, id_alumna, fecha_clase, estatus)
                    VALUES (%s, %s, %s, 'Confirmada')
                """, (id_clase, id_alumna, fecha_clase))
                if alumna_row[1] == 'Activa' and alumna_row[0] > 0:
                    cur.execute(
                        "UPDATE alumnas SET clases_restantes = clases_restantes - 1 WHERE id = %s",
                        (id_alumna,),
                    )
                flash(f'Reservación confirmada para el {fecha_clase}.', 'exito')
            else:
                flash('La alumna no tiene clases restantes.', 'alerta')
        else:
            flash('La clase ya se encuentra llena para esa fecha.', 'alerta')

    return redirect(url_for('agenda'))


def inicio_fin_semana(ref=None):
    ref = ref or date.today()
    lunes = ref - timedelta(days=ref.weekday())
    domingo = lunes + timedelta(days=6)
    return lunes, domingo


@app.route('/recepcion')
def recepcion():
    hoy = date.today()
    inicio_semana, fin_semana = inicio_fin_semana(hoy)

    logged_coach_id = session.get('coach_id')
    is_jefa = (logged_coach_id == 1)  # Stéphanie is ID 1 (Jefa)

    # Get requested coach_id, default to logged coach if not specified
    coach_id = request.args.get('coach_id', type=int)
    if not coach_id:
        coach_id = logged_coach_id

    # If they are NOT the jefa and try to access another coach's page, force it to themselves
    if not is_jefa and coach_id != logged_coach_id:
        coach_id = logged_coach_id

    with get_db() as conn:
        cur = conn.cursor()
        
        # Jefa can select both coaches; normal coaches only see themselves in the selector
        if is_jefa:
            cur.execute("""
                SELECT id, nombre, apellidos, especialidad
                FROM coaches WHERE activo = 1 ORDER BY nombre
            """)
        else:
            cur.execute("""
                SELECT id, nombre, apellidos, especialidad
                FROM coaches WHERE id = %s AND activo = 1
            """, (logged_coach_id,))
        coaches = cur.fetchall()

        coach_sel = None
        horarios_coach = []
        asistencias_semana = 0
        entro_hoy = False
        historial = []

        if coach_id:
            cur.execute(
                "SELECT id, nombre, apellidos, especialidad FROM coaches WHERE id = %s",
                (coach_id,),
            )
            coach_sel = cur.fetchone()

            cur.execute("""
                SELECT h.id, h.dia_semana, h.hora_inicio, h.hora_fin, h.tipo_clase,
                       (SELECT COUNT(*) FROM reservaciones r
                        WHERE r.id_clase = h.id AND r.estatus = 'Confirmada') AS alumnas_inscritas
                FROM horarios h
                WHERE h.coach_id = %s AND h.activo = 1
                ORDER BY FIELD(h.dia_semana,
                    'Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'), h.hora_inicio
            """, (coach_id,))
            horarios_coach = cur.fetchall()

            cur.execute("""
                SELECT COUNT(*) FROM asistencia_coaches
                WHERE coach_id = %s AND fecha >= %s AND fecha <= %s
            """, (coach_id, inicio_semana, fin_semana))
            asistencias_semana = cur.fetchone()[0]

            cur.execute("""
                SELECT 1 FROM asistencia_coaches
                WHERE coach_id = %s AND fecha = %s LIMIT 1
            """, (coach_id, hoy))
            entro_hoy = cur.fetchone() is not None

            cur.execute("""
                SELECT fecha, hora_entrada FROM asistencia_coaches
                WHERE coach_id = %s
                ORDER BY fecha DESC LIMIT 10
            """, (coach_id,))
            historial = cur.fetchall()

    clases_por_semana = len(horarios_coach) if coach_sel else 0
    clases_restantes = max(0, clases_por_semana - asistencias_semana) if coach_sel else 0

    return render_template(
        'recepcion.html',
        coaches=coaches,
        coach_sel=coach_sel,
        coach_id=coach_id,
        horarios_coach=horarios_coach,
        clases_por_semana=clases_por_semana,
        asistencias_semana=asistencias_semana,
        clases_restantes=clases_restantes,
        entro_hoy=entro_hoy,
        historial=historial,
        hoy=hoy.isoformat(),
        inicio_semana=inicio_semana.isoformat(),
        fin_semana=fin_semana.isoformat(),
    )


@app.route('/recepcion/entrada', methods=['POST'])
def recepcion_entrada():
    coach_id = request.form.get('coach_id')
    if not coach_id:
        flash('Selecciona un coach.', 'alerta')
        return redirect(url_for('recepcion'))

    hoy = date.today()
    ahora = datetime.now().strftime('%H:%M:%S')

    with get_db() as conn:
        cur = conn.cursor()
        cur.execute("""
            SELECT 1 FROM asistencia_coaches WHERE coach_id = %s AND fecha = %s
        """, (coach_id, hoy))
        if cur.fetchone():
            flash('Ya registraste tu entrada hoy.', 'alerta')
        else:
            cur.execute("""
                INSERT INTO asistencia_coaches (coach_id, fecha, hora_entrada)
                VALUES (%s, %s, %s)
            """, (coach_id, hoy, ahora))
            cur.execute("SELECT nombre FROM coaches WHERE id = %s", (coach_id,))
            nombre = cur.fetchone()[0]
            flash(f'Entrada registrada — {nombre}, {ahora[:5]} hrs.', 'exito')

    return redirect(url_for('recepcion', coach_id=coach_id))


if __name__ == '__main__':
    app.run(debug=True, host='127.0.0.1', port=5000)
