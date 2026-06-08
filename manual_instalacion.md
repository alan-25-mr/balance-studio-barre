# Manual de Instalación Paso a Paso — Balance Studio

Este manual contiene instrucciones sencillas y muy detalladas para que cualquier usuario pueda instalar y configurar el sistema **Balance Studio** en su computadora con Windows utilizando **Laragon**.

---

## Paso 1: Descargar Laragon
1. Abre tu navegador web (Google Chrome, Edge, Firefox, etc.).
2. Entra al sitio web oficial de Laragon: [https://laragon.org/download/](https://laragon.org/download/).
3. Haz clic en el botón de descarga del paquete **Laragon Wamp (con PHP 8.1 y MySQL 8.0)**.
4. Espera a que la descarga del archivo `laragon-wamp.exe` termine por completo.

---

## Paso 2: Instalar Laragon en Windows
1. Busca el archivo descargado `laragon-wamp.exe` en tu carpeta de Descargas y haz doble clic sobre él para abrirlo.
2. Si Windows te pide permisos de administrador ("¿Quieres permitir que esta aplicación haga cambios en el dispositivo?"), haz clic en **Sí**.
3. Selecciona tu idioma (por defecto Español/English) y haz clic en **OK**.
4. Haz clic en **Siguiente** (Next).
5. Selecciona la carpeta de instalación. **Se recomienda dejar la ruta por defecto:** `C:\laragon`. Haz clic en **Siguiente**.
6. En la pantalla de opciones adicionales, deja las siguientes casillas activadas:
   - *Run Laragon when Windows starts* (Opcional, iniciar al encender la PC).
   - *Auto-virtual hosts* (Muy importante, crea dominios locales automáticamente).
7. Haz clic en **Siguiente** y luego en **Instalar**.
8. Espera a que la barra de progreso llegue al 100%. Al finalizar, haz clic en **Terminar** (Finish).

---

## Paso 3: Copiar los Archivos del Sistema
1. Abre el Explorador de Archivos de Windows.
2. Dirígete a la carpeta donde se instaló Laragon: `C:\laragon\www\`.
3. Crea una nueva carpeta dentro de `www` llamada **`balancebarre`** (todo en minúsculas y sin espacios).
4. Copia todos los archivos y carpetas del código fuente de **Balance Studio** y pégalos dentro de esa carpeta recién creada.
5. La ruta final exacta de los archivos debe ser: `C:\laragon\www\balancebarre\`. Asegúrate de que los archivos principales como `index.php` y `login.php` estén directamente en esa carpeta (no dentro de otra subcarpeta).

---

## Paso 4: Iniciar los Servicios en Laragon
1. Abre la aplicación Laragon en tu computadora (puedes buscar "Laragon" en el menú de inicio de Windows).
2. Se abrirá una pequeña ventana gris. Haz clic en el botón que dice **Iniciar Todo** (Start All) en la esquina inferior izquierda.
3. Verás que los textos de Apache y MySQL cambian mostrando números de puerto (como Apache: `80` y MySQL: `3306`). Esto indica que el servidor local está activo.

---

## Paso 5: Crear e Importar la Base de Datos
1. En la ventana de Laragon, haz clic en el botón **Base de Datos** (Database) que está en la parte inferior.
2. Se abrirá automáticamente un programa llamado **HeidiSQL** (el administrador de base de datos integrado).
3. Aparecerá una ventana de conexión. En la esquina inferior izquierda, haz clic en el botón **Abrir** (Open) (no es necesario escribir ninguna contraseña, la contraseña por defecto en Laragon está en blanco).
4. Estarás dentro del panel de base de datos:
   - En la barra lateral izquierda, haz clic derecho sobre el primer elemento (suele decir `127.0.0.1` o `Unnamed`).
   - Selecciona la opción **Crear nuevo** -> **Base de datos**.
   - En el cuadro de texto que aparece, escribe el nombre: `balance_final`.
   - Haz clic en **Aceptar**.
5. Selecciona la base de datos `balance_final` que acabas de crear haciendo clic izquierdo sobre ella en la lista de la izquierda.
6. Ve al menú de arriba en HeidiSQL y haz clic en **Archivo** -> **Cargar archivo SQL...**.
7. Se abrirá una ventana para elegir un archivo. Busca el script que copiaste en el paso 3:
   `C:\laragon\www\balancebarre\database_complete.sql` y haz clic en Abrir.
8. HeidiSQL cargará el archivo. Para ejecutar todas las instrucciones del script, busca en la barra de herramientas de arriba un botón con un triángulo azul (icono de **Play** o ejecutar) o simplemente presiona la tecla **`F9`** en tu teclado.
9. Espera unos segundos. Verás que en el panel izquierdo, al desplegar `balance_final`, aparecen las tablas creadas (`alumnas`, `coaches`, `horarios`, etc.). Ya puedes cerrar HeidiSQL.

---

## Paso 6: Configurar el Archivo de Base de Datos PHP
1. Abre el Explorador de Archivos y ve a la carpeta del proyecto:
   `C:\laragon\www\balancebarre\config\`
2. Haz clic derecho sobre el archivo `database.php` y ábrelo con el **Bloc de Notas** o cualquier editor de código (como VS Code).
3. Asegúrate de que las credenciales de conexión sean exactamente las siguientes:
   - `$host = 'localhost';`
   - `$db = 'balance_final';`
   - `$user = 'root';`
   - `$pass = '';` (comillas simples vacías, sin espacios).
4. Si haces algún cambio, guarda el archivo (`Ctrl + G` en Bloc de Notas) y ciérralo.

---

## Paso 7: Entrar al Sistema desde tu Navegador
1. Abre tu navegador web preferido.
2. Escribe en la barra de direcciones la siguiente dirección:
   [http://localhost/balancebarre/](http://localhost/balancebarre/)
3. Presiona Enter. Verás la página de inicio (Landing Page) de **Balance Studio**.

---

## Paso 8: Probar los Inicios de Sesión (Datos de Prueba)
Para confirmar que todo funciona correctamente, intenta iniciar sesión con las siguientes cuentas de prueba:

### A. Para ingresar como Coach (Administrador):
1. Entra a: [http://localhost/balancebarre/login.php](http://localhost/balancebarre/login.php)
2. En la pestaña "Ingresar", escribe los siguientes datos:
   - **ID, Nombre o Teléfono:** `101`
   - **Contraseña:** `steph123`
3. Haz clic en **Entrar**. Te redirigirá al Panel de Administración de Alumnas de la Coach Fany.

### B. Para ingresar como Alumna (Cliente):
1. Cierra sesión en el botón "Cerrar Sesión" en la esquina superior derecha.
2. Ve a [http://localhost/balancebarre/login.php](http://localhost/balancebarre/login.php) e ingresa con:
   - **ID, Nombre o Teléfono:** `201`
   - **Contraseña:** `alumna123`
3. Haz clic en **Entrar**. Te redirigirá al panel del cliente donde verás las clases asignadas y el calendario para agendar.
