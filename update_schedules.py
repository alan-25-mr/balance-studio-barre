import pymysql

DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'balance_final',
    'port': 3306,
    'charset': 'utf8mb4',
}

try:
    conn = pymysql.connect(**DB_CONFIG)
    cur = conn.cursor()
    
    # Empty existing schedules
    cur.execute("TRUNCATE TABLE reservaciones") # Clear reservations to avoid FK issues
    cur.execute("DELETE FROM horarios")
    
    # 1. Coach Stéphanie (id = 1): Lunes a Viernes de 6:00 y 7:00 am, 5:00, 6:00, 7:00 y 8:00 pm
    days_steph = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes']
    slots_steph = [
        ('06:00:00', '07:00:00', 'Barré & Pilates'),
        ('07:00:00', '08:00:00', 'Barré & Pilates'),
        ('17:00:00', '18:00:00', 'Pilates & Funcional'),
        ('18:00:00', '19:00:00', 'Pilates & Funcional'),
        ('19:00:00', '20:00:00', 'Pilates & Funcional'),
        ('20:00:00', '21:00:00', 'Pilates & Funcional')
    ]
    
    for day in days_steph:
        for start, end, name in slots_steph:
            cur.execute("""
                INSERT INTO horarios (coach_id, dia_semana, hora_inicio, hora_fin, tipo_clase, capacidad)
                VALUES (%s, %s, %s, %s, %s, 15)
            """, (1, day, start, end, name))
            
    # 2. Coach Fátima (id = 2): Lunes, Miercoles y Viernes 8:00 am
    days_fati = ['Lunes', 'Miércoles', 'Viernes']
    slots_fati = [
        ('08:00:00', '09:00:00', 'Barré')
    ]
    for day in days_fati:
        for start, end, name in slots_fati:
            cur.execute("""
                INSERT INTO horarios (coach_id, dia_semana, hora_inicio, hora_fin, tipo_clase, capacidad)
                VALUES (%s, %s, %s, %s, %s, 15)
            """, (2, day, start, end, name))
            
    conn.commit()
    conn.close()
    print("Schedules updated successfully in database!")
except Exception as e:
    print("Error:", e)
