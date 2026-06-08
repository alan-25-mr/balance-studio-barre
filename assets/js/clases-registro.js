/**
 * Registro de clases — selector visual tipo horario semanal (registro.php)
 * Versión 3.0 — Muestra la grilla de horario igual que horarios.php
 */
(function () {
    'use strict';

    const timeSlots = ['06:00','07:00','08:00','09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00'];
    const dias = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];

    function proximaFecha(diaSemana) {
        const map = { Lunes: 1, Martes: 2, Miércoles: 3, Jueves: 4, Viernes: 5, Sábado: 6 };
        const t = map[diaSemana];
        if (!t) return '';
        const d = new Date();
        d.setHours(12, 0, 0, 0);
        const cur = d.getDay() === 0 ? 7 : d.getDay();
        let diff = t - cur;
        if (diff < 0) diff += 7;
        d.setDate(d.getDate() + diff);
        return d.toISOString().slice(0, 10);
    }

    async function fetchJson(url, method, body) {
        const opts = { method: method || 'GET', headers: {} };
        if (body) {
            opts.method = 'POST';
            opts.headers['Content-Type'] = 'application/json';
            opts.body = JSON.stringify(body);
        }
        const res = await fetch(url, opts);
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Error de conexión');
        return data;
    }

    const ClasesRegistro = {
        paquetes: [],
        horarios: [],
        coaches: [],
        paqueteSeleccionado: null,
        modoDias: 'manual',
        seleccionManual: [],
        seleccionAuto: [],

        async init() {
            const panel = document.getElementById('panel-elegir-dias');
            if (!panel) return;

            this.paqueteSelect = document.getElementById('paquete_id');
            this.hintEl = document.getElementById('booking-hint');
            this.listEl = document.getElementById('selected-classes-list');
            this.progresoEl = document.getElementById('progreso-clases');
            this.gridEl = document.getElementById('clases-disponibles');
            this.btnSubmit = document.getElementById('btn-submit-alumna');
            this.coachFilter = document.getElementById('filtro_coach');
            this.horaFilter = document.getElementById('filtro_hora');

            await this.cargarDatos();

            this.paqueteSelect.addEventListener('change', () => this.onPaqueteChange());

            if (this.coachFilter) {
                this.coachFilter.addEventListener('change', () => {
                    this.seleccionManual = [];
                    this.onPaqueteChange();
                });
            }

            if (this.horaFilter) {
                this.horaFilter.addEventListener('change', () => {
                    this.seleccionManual = [];
                    this.onPaqueteChange();
                });
            }

            if (this.horarios.length) {
                this.renderScheduleGrid();
                this.hintEl.textContent = 'Selecciona un plan arriba y luego toca las clases en el horario.';
            }

            const paqueteUrl = new URLSearchParams(location.search).get('paquete');
            if (paqueteUrl) {
                this.paqueteSelect.value = paqueteUrl;
                this.onPaqueteChange();
            }
        },

        async cargarDatos() {
            try {
                const [paquetes, horarios, coaches] = await Promise.all([
                    fetchJson('api/paquetes.php'),
                    fetchJson('api/horarios.php'),
                    fetchJson('api/coaches.php')
                ]);
                this.paquetes = Array.isArray(paquetes) ? paquetes : [];
                this.horarios = Array.isArray(horarios) ? horarios : [];
                this.coaches = Array.isArray(coaches) ? coaches : [];

                // Extract unique hour start times
                const hoursSet = new Set();
                this.horarios.forEach(h => {
                    if (h.hora_inicio) {
                        hoursSet.add(h.hora_inicio.substring(0, 5));
                    }
                });
                this.availableTimeSlots = Array.from(hoursSet).sort();

                this.paqueteSelect.innerHTML = '<option value="">— Seleccionar plan —</option>';
                this.paquetes.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.id;
                    opt.textContent = `${p.nombre} — $${parseFloat(p.precio).toFixed(0)} (${p.clases_incluidas} clases)`;
                    this.paqueteSelect.appendChild(opt);
                });

                if (this.horarios.length === 0) {
                    this.gridEl.innerHTML = '<p class="aviso-plan" style="grid-column:1/-1;">No hay horarios en el sistema. Contacta al estudio.</p>';
                }
            } catch (err) {
                this.hintEl.textContent = 'Error al cargar: ' + err.message;
                this.gridEl.innerHTML = '<p class="aviso-plan" style="grid-column:1/-1;color:#c00;">No se pudieron cargar los horarios. Verifica que MySQL esté activo.</p>';
                if (typeof showToast === 'function') showToast(err.message, 'error');
            }
        },

        onPaqueteChange() {
            const id = this.paqueteSelect.value;
            this.paqueteSeleccionado = this.paquetes.find(p => String(p.id) === String(id)) || null;
            this.seleccionManual = [];
            this.seleccionAuto = [];

            const panel = document.getElementById('panel-elegir-dias');
            if (this.paqueteSeleccionado) {
                panel.classList.remove('sin-plan');
            } else {
                panel.classList.add('sin-plan');
                this.hintEl.textContent = 'Selecciona un plan en el paso 1 para poder elegir tus clases.';
                if (this.horarios.length) this.renderScheduleGrid();
                else this.gridEl.innerHTML = '<p class="aviso-plan">Cargando horarios...</p>';
                this.actualizarUI();
                return;
            }

            const n = parseInt(this.paqueteSeleccionado.clases_incluidas, 10);
            this.hintEl.textContent = `Toca cada clase en el horario para elegir ${n} clase(s). Las seleccionadas se marcan en verde.`;
            this.renderScheduleGrid();
            this.actualizarUI();
        },

        getSeleccion() {
            return this.seleccionManual;
        },

        toggleHorario(h) {
            if (!this.paqueteSeleccionado) return;

            const fecha = proximaFecha(h.dia_semana);
            const idx = this.seleccionManual.findIndex(
                s => String(s.id_clase) === String(h.id) && s.fecha_clase === fecha
            );

            if (idx >= 0) {
                this.seleccionManual.splice(idx, 1);
            } else {
                const max = parseInt(this.paqueteSeleccionado.clases_incluidas, 10);
                if (this.seleccionManual.length >= max) {
                    if (typeof showToast === 'function') {
                        showToast(`Tu plan permite ${max} clase(s). Quita una para cambiar.`, 'info');
                    }
                    return;
                }
                this.seleccionManual.push({
                    id_clase: h.id,
                    fecha_clase: fecha,
                    dia_semana: h.dia_semana,
                    hora_inicio: (h.hora_inicio || '').substring(0, 5),
                    tipo_clase: h.tipo_clase,
                    coach_nombre: h.coach_nombre || ''
                });
            }
            this.renderScheduleGrid();
            this.actualizarUI();
        },

        /**
         * Render the schedule grid exactly like horarios.php
         * with selectable classes for booking.
         */
        renderScheduleGrid() {
            const sel = this.getSeleccion();
            const tienePlan = !!this.paqueteSeleccionado;
            const max = tienePlan ? parseInt(this.paqueteSeleccionado.clases_incluidas, 10) : 0;
            const manual = tienePlan;
            const lleno = manual && sel.length >= max;

            if (!this.horarios.length) {
                this.gridEl.innerHTML = '<p class="aviso-plan">Sin horarios disponibles.</p>';
                return;
            }

            // Apply coach filter
            const coachVal = this.coachFilter ? this.coachFilter.value : 'todos';
            // Apply hour filter
            const horaVal = this.horaFilter ? this.horaFilter.value : 'todos';

            let filtered = [...this.horarios];
            if (coachVal !== 'todos') {
                filtered = filtered.filter(h => String(h.coach_id) === String(coachVal));
            }
            if (horaVal !== 'todos') {
                filtered = filtered.filter(h => (h.hora_inicio || '').substring(0, 5) === horaVal);
            }

            if (filtered.length === 0) {
                this.gridEl.innerHTML = '<p class="aviso-plan" style="grid-column:1/-1;">No hay clases disponibles con los filtros seleccionados.</p>';
                return;
            }

            // Determine which time slots have classes
            const usedSlots = new Set();
            filtered.forEach(h => {
                const hour = h.hora_inicio.substring(0, 5);
                usedSlots.add(hour);
            });
            const slotsToShow = timeSlots.filter(s => usedSlots.has(s));
            if (slotsToShow.length === 0) {
                this.gridEl.innerHTML = '<p class="aviso-plan" style="grid-column:1/-1;">No hay clases disponibles con los filtros seleccionados.</p>';
                return;
            }

            // Color mapping for coaches
            const colors = ['', 'brown-class', 'gray-class'];

            // Build the grid HTML
            let html = '<div class="schedule-header"></div>';
            dias.forEach(d => {
                html += `<div class="schedule-header">${d}</div>`;
            });

            slotsToShow.forEach(slot => {
                html += `<div class="schedule-time">${slot}</div>`;
                dias.forEach(dia => {
                    const classes = filtered.filter(h => h.dia_semana === dia && h.hora_inicio.substring(0, 5) === slot);
                    html += '<div class="schedule-cell">';
                    classes.forEach(c => {
                        const coachIdx = this.coaches.findIndex(co => co.id == c.coach_id);
                        const colorClass = colors[coachIdx % colors.length] || '';
                        const fecha = proximaFecha(c.dia_semana);
                        const elegida = sel.some(
                            s => String(s.id_clase) === String(c.id) && s.fecha_clase === fecha
                        );
                        const deshab = !tienePlan || (manual && lleno && !elegida);
                        const selectable = tienePlan && manual && !deshab;

                        const extraClasses = [
                            colorClass,
                            selectable ? 'selectable' : '',
                            elegida ? 'selected' : '',
                            deshab ? 'disabled-class' : ''
                        ].filter(Boolean).join(' ');

                        html += `
                            <div class="schedule-class ${extraClasses}"
                                 data-id="${c.id}" role="button" tabindex="0">
                                ${c.tipo_clase}
                                <div class="class-coach">${c.coach_nombre || ''}</div>
                                ${elegida ? '<div class="class-coach" style="color:#fff;font-weight:700;margin-top:2px;">✓ Seleccionada</div>' : ''}
                            </div>
                        `;
                    });
                    html += '</div>';
                });
            });

            this.gridEl.innerHTML = html;

            // Add click handlers for manual mode
            if (manual && tienePlan) {
                this.gridEl.querySelectorAll('.schedule-class.selectable, .schedule-class.selected').forEach(el => {
                    const id = parseInt(el.getAttribute('data-id'), 10);
                    const h = this.horarios.find(x => x.id == id);
                    if (h) {
                        el.addEventListener('click', () => this.toggleHorario(h));
                    }
                });
            }
        },

        actualizarUI() {
            const sel = this.getSeleccion();
            const n = this.paqueteSeleccionado
                ? parseInt(this.paqueteSeleccionado.clases_incluidas, 10)
                : 0;

            if (this.progresoEl) {
                this.progresoEl.textContent = this.paqueteSeleccionado
                    ? `${sel.length} de ${n} clase(s) elegidas`
                    : '';
            }

            if (this.listEl) {
                if (!sel.length) {
                    this.listEl.innerHTML = '';
                } else {
                    this.listEl.innerHTML = sel.map(s => `
                        <li><strong>${s.tipo_clase || 'Clase'}</strong> — ${s.dia_semana} ${s.hora_inicio}
                        con ${s.coach_nombre || ''} · ${s.fecha_clase}</li>
                    `).join('');
                }
            }

            if (this.btnSubmit) {
                this.btnSubmit.disabled = !(this.paqueteSeleccionado && sel.length === n);
            }
        },

        validarYPreparar(data) {
            if (!this.paqueteSeleccionado) {
                if (typeof showToast === 'function') showToast('Selecciona un plan', 'error');
                return null;
            }
            const sel = this.getSeleccion();
            const n = parseInt(this.paqueteSeleccionado.clases_incluidas, 10);
            if (sel.length !== n) {
                if (typeof showToast === 'function') {
                    showToast(`Debes elegir ${n} clase(s). Llevas ${sel.length}.`, 'error');
                }
                return null;
            }
            data.reservaciones = sel.map(s => ({
                id_clase: s.id_clase,
                fecha_clase: s.fecha_clase
            }));
            return data;
        }
    };

    window.ClasesRegistro = ClasesRegistro;

    document.addEventListener('DOMContentLoaded', () => ClasesRegistro.init());
})();
