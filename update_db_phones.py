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
    
    # Actualizar credenciales de coaches (Teléfono y Contraseña SHA-256)
    cur.execute("""
        UPDATE coaches 
        SET telefono = '1111111111', 
            password = 'f3f81b4178264e7640cd3486879336ab4a582c4033093cccd03487c3c95a2b8e' 
        WHERE id = 1
    """)
    cur.execute("""
        UPDATE coaches 
        SET telefono = '2222222222', 
            password = '8a8ecb5079178266b93e4958be8c95c9fe0383baf713a563b16f88a157630ef3' 
        WHERE id = 2
    """)
    print("Coaches actualizadas con teléfonos y contraseñas por defecto.")
    
    # Hacer que la columna password de alumnas sea nullable por si acaso
    cur.execute("ALTER TABLE alumnas MODIFY COLUMN password VARCHAR(255) DEFAULT NULL")
    
    conn.commit()
    conn.close()
    print("¡Migración de teléfonos exitosa!")
except Exception as e:
    print("Error:", e)
