/**
 * Balance Studio — Main Application JavaScript
 */

// ── Toast Notifications ──
function showToast(message, type = 'success') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const icons = { success: '✓', error: '✕', info: 'ℹ' };
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `<span>${icons[type] || ''}</span> ${message}`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'fadeOut 0.3s ease forwards';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

// ── API Helper ──
async function api(url, method = 'GET', data = null) {
    const options = {
        method,
        headers: { 'Content-Type': 'application/json' }
    };
    if (data) options.body = JSON.stringify(data);

    try {
        const response = await fetch(url, options);
        const result = await response.json();
        if (!response.ok) {
            throw new Error(result.error || 'Error en la solicitud');
        }
        return result;
    } catch (error) {
        showToast(error.message, 'error');
        throw error;
    }
}

// ── Modal Helpers ──
function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.add('active');
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.remove('active');
}

// Close modal on overlay click
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
    }
});

// Close modal on Escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
    }
});

// ── Format Helpers ──
function formatMoney(amount) {
    return '$' + parseFloat(amount).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    return dateStr;
}

function getBadgeClass(estatus) {
    const map = {
        'Activa': 'badge-active',
        'Inactiva': 'badge-inactive',
        'Pendiente': 'badge-pending'
    };
    return map[estatus] || 'badge-inactive';
}

// =============================================
// MÓDULO: ALUMNAS (Registro de Clases)
// =============================================
const AlumnasModule = {
    async init() {
        this.form = document.getElementById('formAlumna');
        this.tableBody = document.getElementById('alumnas-tbody');
        this.statTotal = document.getElementById('stat-total');

        if (!this.form && !this.statTotal) return;

        if (this.form) {
            this.form.addEventListener('submit', (e) => this.handleSubmit(e));
            await this.loadPaquetes();
        }
        
        await this.loadAlumnas(); // Para cargar estadísticas
    },

    async loadPaquetes() {
        try {
            const paquetes = await api('api/paquetes.php');
            const select = document.getElementById('paquete_id');
            if (!select) return;
            select.innerHTML = '<option value="">Seleccionar plan...</option>';
            paquetes.forEach(p => {
                select.innerHTML += `<option value="${p.id}">${p.nombre} — ${formatMoney(p.precio)}</option>`;
            });
        } catch (e) { /* silent */ }
    },

    async loadAlumnas() {
        try {
            const alumnas = await api('api/alumnas.php');
            if (this.tableBody) this.renderTable(alumnas);
            this.updateStats(alumnas);
        } catch (e) { /* silent */ }
    },

    renderTable(alumnas) {
        if (!this.tableBody) return;

        if (alumnas.length === 0) {
            this.tableBody.innerHTML = `
                <tr><td colspan="8">
                    <div class="empty-state">
                        <div class="empty-state-icon">📋</div>
                        <div class="empty-state-text">No hay registros aún</div>
                    </div>
                </td></tr>`;
            return;
        }

        this.tableBody.innerHTML = alumnas.map(a => `
            <tr>
                <td><strong>${a.nombre} ${a.apellidos}</strong></td>
                <td>${a.telefono}</td>
                <td>${a.paquete_nombre || '—'}</td>
                <td>${formatMoney(a.monto)}</td>
                <td>${formatDate(a.fecha_vencimiento)}</td>
                <td>${a.lesion || '—'}</td>
                <td><span class="badge ${getBadgeClass(a.estatus)}">${a.estatus}</span></td>
                <td>
                    <div style="display:flex;gap:6px;">
                        <button class="btn btn-outline btn-sm btn-icon" onclick="AlumnasModule.edit(${a.id})" title="Editar">✎</button>
                    </div>
                </td>
            </tr>
        `).join('');
    },

    updateStats(alumnas) {
        if (this.statTotal) this.statTotal.textContent = alumnas.length;
    },

    async handleSubmit(e) {
        e.preventDefault();
        const formData = new FormData(this.form);
        const data = Object.fromEntries(formData);

        try {
            await api('api/alumnas.php', 'POST', data);
            showToast('¡Registro exitoso! Nos pondremos en contacto contigo pronto.');
            this.form.reset();
            await this.loadAlumnas();
        } catch (e) { /* handled in api() */ }
    }
};

// =============================================
// MÓDULO: PAQUETES
// =============================================
const PaquetesModule = {
    async init() {
        this.form = document.getElementById('formPaquete');
        this.grid = document.getElementById('paquetes-grid');
        this.tableBody = document.getElementById('paquetes-tbody');

        if (!this.form && !this.grid && !this.tableBody) return;

        if (this.form) this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        await this.loadPaquetes();
    },

    async loadPaquetes() {
        try {
            const paquetes = await api('api/paquetes.php');
            if (this.grid) this.renderCards(paquetes);
            if (this.tableBody) this.renderTable(paquetes);
        } catch (e) { /* silent */ }
    },

    renderCards(paquetes) {
        if (!this.grid) return;
        this.grid.innerHTML = paquetes.map(p => `
            <div class="package-card">
                <div class="package-name">${p.nombre}</div>
                <div class="package-price"><span class="currency">$</span>${parseFloat(p.precio).toLocaleString('es-MX')}</div>
                <div class="package-detail">${p.clases_incluidas > 0 ? p.clases_incluidas + ' clases' : 'Ilimitadas'}</div>
                <div class="package-detail">${p.duracion_dias} días</div>
                <div class="package-detail text-muted">${p.descripcion || ''}</div>
                <div class="package-actions">
                    <a href="registro.php?paquete=${p.id}" class="btn btn-outline btn-sm">Elegir Plan</a>
                </div>
            </div>
        `).join('');
    },

    renderTable(paquetes) {
        if (!this.tableBody) return;
        this.tableBody.innerHTML = paquetes.map(p => `
            <tr>
                <td><strong>${p.nombre}</strong></td>
                <td>${p.descripcion || '—'}</td>
                <td>${formatMoney(p.precio)}</td>
                <td>${p.clases_incluidas > 0 ? p.clases_incluidas : 'Ilimitadas'}</td>
                <td>${p.duracion_dias} días</td>
            </tr>
        `).join('');
    }
};

// =============================================
// MÓDULO: HORARIOS / CALENDARIO
// =============================================
const HorariosModule = {
    coaches: [],
    horarios: [],
    filterCoachId: null,
    timeSlots: ['07:00','08:00','09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00'],
    dias: ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'],

    async init() {
        this.grid = document.getElementById('schedule-grid');
        this.form = document.getElementById('formHorario');
        this.filterBar = document.getElementById('coach-filters');

        if (!this.grid && !this.filterBar) return;

        if (this.form) {
            this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        }

        await this.loadCoaches();
        await this.loadHorarios();
    },

    async loadCoaches() {
        try {
            this.coaches = await api('api/coaches.php');
            this.renderFilters();
            this.populateCoachSelect();
        } catch (e) { /* silent */ }
    },

    async loadHorarios() {
        try {
            this.horarios = await api('api/horarios.php');
            this.renderCalendar();
        } catch (e) { /* silent */ }
    },

    renderFilters() {
        if (!this.filterBar) return;

        let html = `<button class="filter-chip ${!this.filterCoachId ? 'active' : ''}" onclick="HorariosModule.setFilter(null)">Todos</button>`;
        this.coaches.forEach((c, i) => {
            html += `<button class="filter-chip ${this.filterCoachId == c.id ? 'active' : ''}" onclick="HorariosModule.setFilter(${c.id})">${c.nombre} ${c.apellidos}</button>`;
        });
        this.filterBar.innerHTML = html;
    },

    populateCoachSelect() {
        const select = document.getElementById('hor_coach_id');
        if (!select) return;
        select.innerHTML = '<option value="">Seleccionar coach...</option>';
        this.coaches.forEach(c => {
            select.innerHTML += `<option value="${c.id}">${c.nombre} ${c.apellidos}</option>`;
        });
    },

    setFilter(coachId) {
        this.filterCoachId = coachId;
        this.renderFilters();
        this.renderCalendar();
    },

    renderCalendar() {
        if (!this.grid) return;

        let filtered = this.horarios;
        if (this.filterCoachId) {
            filtered = this.horarios.filter(h => h.coach_id == this.filterCoachId);
        }

        const usedSlots = new Set();
        filtered.forEach(h => {
            const hour = h.hora_inicio.substring(0, 5);
            usedSlots.add(hour);
        });

        const slotsToShow = this.timeSlots.filter(s => usedSlots.has(s));
        const displaySlots = slotsToShow.length > 0 ? slotsToShow : ['07:00','09:00','18:00'];
        const colors = ['', 'brown-class', 'gray-class'];
        
        let html = '<div class="schedule-header"></div>';
        this.dias.forEach(d => {
            html += `<div class="schedule-header">${d}</div>`;
        });

        displaySlots.forEach(slot => {
            html += `<div class="schedule-time">${slot}</div>`;
            this.dias.forEach(dia => {
                const classes = filtered.filter(h => h.dia_semana === dia && h.hora_inicio.substring(0, 5) === slot);
                html += '<div class="schedule-cell">';
                classes.forEach(c => {
                    const coachIdx = this.coaches.findIndex(co => co.id == c.coach_id);
                    const colorClass = colors[coachIdx % colors.length] || '';
                    html += `
                        <div class="schedule-class ${colorClass}" onclick="HorariosModule.editHorario(${c.id})">
                            ${c.tipo_clase}
                            <div class="class-coach">${c.coach_nombre || ''}</div>
                        </div>`;
                });
                html += '</div>';
            });
        });

        this.grid.innerHTML = html;
    },

    editHorario(id) {
        // En la vista de cliente no se edita, pero mantenemos la firma para evitar errores
        console.log("Consulta de clase ID:", id);
    }
};

// =============================================
// MÓDULO: COACHES
// =============================================
const CoachesModule = {
    async init() {
        this.form = document.getElementById('formCoach');
        this.tableBody = document.getElementById('coaches-tbody');
        if (!this.form && !this.tableBody) return;
        await this.loadCoaches();
    },

    async loadCoaches() {
        try {
            const coaches = await api('api/coaches.php');
            if (this.tableBody) this.renderTable(coaches);
        } catch (e) { /* silent */ }
    },

    renderTable(coaches) {
        if (!this.tableBody) return;
        this.tableBody.innerHTML = coaches.map(c => `
            <tr>
                <td><strong>${c.nombre} ${c.apellidos}</strong></td>
                <td>${c.especialidad || '—'}</td>
                <td>${c.telefono || '—'}</td>
            </tr>
        `).join('');
    }
};

// ── Initialize on page load ──
document.addEventListener('DOMContentLoaded', () => {
    AlumnasModule.init();
    PaquetesModule.init();
    HorariosModule.init();
    CoachesModule.init();
});
