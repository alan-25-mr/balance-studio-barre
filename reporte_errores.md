# Reporte de Errores Corregidos y Bitácora de Depuración

Este reporte consolida los errores críticos identificados, depurados y solucionados en la base de código del sistema **Balance Studio**. 

---

## 1. Error de Escape HTML/JSON en Atributo `onclick`
* **Módulos afectados:** `admin_agenda.php` y `admin_alumnas.php`
* **Síntoma:** Las celdas correspondientes a las fechas y horarios de clases en las tablas administrativas se distorsionaban mostrando código en texto plano o truncando el botón visual. Al dar clic en "Ver clases/fechas", el modal no se abría.
* **Causa raíz:** En PHP, la variable `$r['clases_txt']` o `$al['horarios_txt']` contenía etiquetas HTML `<br>` que separaban cada horario. Al serializarlas con `json_encode($r['clases_txt'])` y ponerlas directamente en el atributo HTML `onclick="..."`, las comillas y los signos `<` y `>` de las etiquetas de salto de línea rompían el parseador de HTML del navegador, arruinando el JS inline.
* **Solución y Depuración:**
  1. Se utilizó la consola de herramientas de desarrollo (F12 DevTools -> Console) donde se observaba un error `Uncaught SyntaxError: Unexpected token '<'`.
  2. Se configuró `json_encode()` utilizando las constantes de máscara de bits de PHP para escapar caracteres conflictivos en atributos HTML:
     ```php
     json_encode($al['horarios_txt'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)
     ```
  3. Se aisló el renderizado a un modal limpio (`modalHorarios` y `modalClases`) en lugar de mostrar los strings de fecha directamente sobre la grilla de la tabla.

---

## 2. Bug de Clases Incompletas (GROUP_CONCAT Distinct Bug)
* **Módulos afectados:** `admin_alumnas.php` (Consulta SQL principal de carga de alumnas).
* **Síntoma:** El sistema de alumnas informaba que una alumna con un paquete de 20 clases contratadas y programadas solo mostraba 3 elementos dentro de su desglose de "Ver horarios".
* **Causa raíz:** La subconsulta SQL utilizaba un `DISTINCT` para concatenar los horarios elegidos:
  ```sql
  GROUP_CONCAT(DISTINCT CONCAT(h.dia_semana, ' ', TIME_FORMAT(h.hora_inicio, '%H:%i')) ORDER BY ... SEPARATOR ' · ')
  ```
  Esto agrupaba y deduplicaba las clases que ocurrían en los mismos días y horas (por ejemplo, si tenía 20 clases en total, pero todas eran Lunes, Miércoles o Viernes a las 08:00 AM, el `DISTINCT` las reducía a solo 3 registros únicos de horario de semana, perdiendo la fecha real e individual de cada reservación).
* **Solución y Depuración:**
  1. Se eliminó la cláusula `DISTINCT` de la query para permitir duplicados semanales legítimos.
  2. Se modificó el `CONCAT` para incluir la fecha de la reservación específica (`fecha_clase`) y hacer legible cada clase individual.
  3. Se aumentó el límite temporal de buffer de MySQL para concatenaciones largas antes de ejecutar la consulta principal:
     ```php
     $pdo->exec("SET SESSION group_concat_max_len = 100000");
     ```
  4. Se reemplazó el separador tradicional ` · ` por un delimitador seguro (`||SPLIT||`) para evitar colisiones en el formateador del frontend.
  5. Se actualizó la lógica JS en `showHorariosModal()` para separar los elementos por el nuevo token:
     ```javascript
     const items = content.split('||SPLIT||').filter(l => l.trim());
     ```

---

## 3. Parse Error: Syntax Error, Unexpected Token `<` en `login.php`
* **Módulos afectados:** `login.php` (Línea 48).
* **Síntoma:** Al ingresar a la página de Login o Registro, se mostraba una pantalla en blanco con el mensaje de error:
  `Parse error: syntax error, unexpected token "<" in C:\laragon\www\balancebarre\login.php on line 48`
* **Causa raíz:** Se insertó un tag de apertura de PHP `<?php` adicional en la línea 48 dentro de un bloque que ya se encontraba en modo de ejecución PHP (iniciado en la línea 1).
* **Solución y Depuración:**
  1. Se abrió el archivo en el editor y se localizó la sección de consultas a la base de datos de coaches.
  2. Se eliminó el tag de apertura redundante y se verificó la correcta indentación.
  3. Se corrió el validador sintáctico en CLI para asegurar la validez del script:
     ```bash
     php -l login.php
     ```

---

## 4. Validación Visual de Formularios Fallida
* **Módulos afectados:** `login.php` y formularios de edición.
* **Síntoma:** Al enviar información errónea o incompleta, los formularios se enviaban de todos modos a la base de datos provocando errores SQL (llaves foráneas vacías o campos truncados).
* **Causa raíz:** El frontend carecía de un preventor de submit (`preventDefault`) efectivo que validara formatos de teléfono exactos y strings alfabéticos para nombres.
* **Solución y Depuración:**
  1. Se implementó una rutina de JS en el evento `submit` que revisa dinámicamente cada input.
  2. Al detectar un fallo (campo obligatorio vacío, teléfono $\neq$ 10 dígitos o letras no alfabéticas en nombres), se asigna la clase `.invalid-field` (que pinta un borde rojo de alerta) y detiene el flujo del submit informando al usuario.
