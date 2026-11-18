// Dirección de cada microservicio y token del middleware
const SERV = { veh: 'http://127.0.0.1:8001', cli: 'http://127.0.0.1:8002', res: 'http://127.0.0.1:8003' };
const TOKEN = 'Bearer 12345';
const MENU = { inicio: 'Inicio', vehiculos: 'Vehículos', clientes: 'Clientes', reservas: 'Reservas', disponibilidad: 'Disponibilidad', historial: 'Historial' };
const TITULOS = { inicio: 'Bienvenido a la calle del alquiler', vehiculos: 'Vehículos', clientes: 'Clientes', reservas: 'Reservas y devoluciones', disponibilidad: 'Disponibilidad por fechas', historial: 'Historial de alquileres' };

const $ = (s) => document.querySelector(s);
const e = (t) => String(t ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const pesos = (n) => '$' + Number(n).toLocaleString('es-CO');
const hoy = () => new Date().toLocaleDateString('en-CA');
const vacio = (t) => `<p class="vacio">${t}</p>`;

// Todas las llamadas a los microservicios pasan por aquí: manda el token y traduce los códigos HTTP a errores
async function api(base, ruta, metodo = 'GET', cuerpo = null) {
    let r;
    try {
        r = await fetch(SERV[base] + ruta, { method: metodo, headers: { Authorization: TOKEN, 'Content-Type': 'application/json' }, body: cuerpo ? JSON.stringify(cuerpo) : undefined });
    } catch (x) {
        throw new Error('No se pudo conectar con el servicio. ¿Están encendidos los 3 servicios?');
    }
    const datos = await r.json().catch(() => null);
    if (!r.ok) { throw new Error((datos && datos.error) || 'Error ' + r.status); }
    return datos;
}

let temporizador;
function avisar(texto, error = false) {
    const a = $('#aviso');
    a.textContent = texto;
    a.className = error ? 'error' : 'aviso';
    a.hidden = false;
    clearTimeout(temporizador);
    temporizador = setTimeout(() => (a.hidden = true), 5000);
}

function ficha(v, extra = '', i = 0) {
    const moto = String(v.categoria || '').toLowerCase() === 'moto' ? ' moto' : '';
    return `<article class="ficha" style="--i:${i};--tono:${(v.id * 67) % 360}"><div class="foto"><div class="auto${moto}"><b class="rueda r1"></b><b class="rueda r2"></b></div></div>` +
        `<div><h3>${e(v.marca + ' ' + v.modelo)}</h3><p>${e(v.anio)} · ${e(v.categoria)}</p><span class="precio">${pesos(v.tarifa_diaria)} / día</span>${extra}</div>` +
        `<span class="sello ${e(v.estado)}">${e(v.estado)}</span></article>`;
}
const opciones = (lista, texto, todos) => (todos ? '<option value="0">Todos</option>' : '') + lista.map((x) => `<option value="${x.id}">${e(texto(x))}</option>`).join('');
const mapa = (lista) => Object.fromEntries(lista.map((x) => [x.id, x]));
const dias = (ini, fin) => (Date.parse(fin) - Date.parse(ini)) / 86400000 + 1;

// Cada vista pide sus datos a los servicios y se dibuja
const vistas = {
    async inicio() {
        const v = await api('veh', '/vehiculos');
        const por = (est) => v.filter((x) => x.estado === est);
        $('#c-libres').textContent = por('disponible').length;
        $('#c-alq').textContent = por('alquilado').length;
        $('#c-mant').textContent = por('mantenimiento').length;
        $('#cat-inicio').innerHTML = por('disponible').map((x, i) => ficha(x, '', i)).join('') || vacio('No hay vehículos disponibles. Registra uno en «Vehículos».');
    },
    async vehiculos() {
        const v = await api('veh', '/vehiculos');
        $('#cat-veh').innerHTML = v.map((x, i) => ficha(x, `<button class="suave" data-mant="${x.id}" data-estado="${x.estado}">Mantenimiento on/off</button>`, i)).join('') || vacio('Aún no hay vehículos.');
    },
    async clientes() {
        const c = await api('cli', '/clientes');
        $('#tb-cli').innerHTML = c.map((x) => `<tr><td>${e(x.nombre)}</td><td>${e(x.telefono)}</td><td>${e(x.correo)}</td><td>${e(x.numero_licencia)}</td></tr>`).join('');
    },
    async reservas() {
        const [v, c, r] = await Promise.all([api('veh', '/vehiculos'), api('cli', '/clientes'), api('res', '/reservas?estado=activa')]);
        const mv = mapa(v), mc = mapa(c);
        $('#s-veh').innerHTML = opciones(v.filter((x) => x.estado !== 'mantenimiento'), (x) => x.marca + ' ' + x.modelo);
        $('#s-cli').innerHTML = opciones(c, (x) => x.nombre);
        if (!$('#r-ini').value) { $('#r-ini').value = $('#r-fin').value = hoy(); }
        $('#tb-res').innerHTML = r.map((x) => `<tr><td>${e(mv[x.vehiculo_id]?.marca)} ${e(mv[x.vehiculo_id]?.modelo)}</td><td>${e(mc[x.cliente_id]?.nombre)}</td><td>${e(x.fecha_inicio)} a ${e(x.fecha_fin)}</td>` +
            `<td>${pesos(dias(x.fecha_inicio, x.fecha_fin) * (mv[x.vehiculo_id]?.tarifa_diaria || 0))}</td><td><button class="suave" data-dev="${x.id}">Devolver</button> <button class="suave" data-can="${x.id}">Cancelar</button></td></tr>`).join('');
    },
    async disponibilidad() {
        if (!$('#d-ini').value) { $('#d-ini').value = $('#d-fin').value = hoy(); }
    },
    async historial() {
        const [v, c] = await Promise.all([api('veh', '/vehiculos'), api('cli', '/clientes')]);
        $('#h-veh').innerHTML = opciones(v, (x) => x.marca + ' ' + x.modelo, true);
        $('#h-cli').innerHTML = opciones(c, (x) => x.nombre, true);
        await consultarHistorial();
    },
};

async function consultarHistorial() {
    const [v, c] = await Promise.all([api('veh', '/vehiculos'), api('cli', '/clientes')]);
    const mv = mapa(v), mc = mapa(c);
    const q = new URLSearchParams();
    if ($('#h-veh').value > 0) { q.set('vehiculo_id', $('#h-veh').value); }
    if ($('#h-cli').value > 0) { q.set('cliente_id', $('#h-cli').value); }
    const r = await api('res', '/reservas?' + q);
    $('#tb-his').innerHTML = r.map((x) => `<tr><td>${e(mv[x.vehiculo_id]?.marca)} ${e(mv[x.vehiculo_id]?.modelo)}</td><td>${e(mc[x.cliente_id]?.nombre)}</td><td>${e(x.fecha_inicio)} a ${e(x.fecha_fin)}</td>` +
        `<td>${pesos(dias(x.fecha_inicio, x.fecha_fin) * (mv[x.vehiculo_id]?.tarifa_diaria || 0))}</td><td>${e(x.estado)}</td></tr>`).join('');
}

// Enrutador: la parte de la dirección después de # decide qué vista se muestra
async function ir() {
    const hash = (location.hash || '#inicio').slice(1);
    const nombre = MENU[hash] ? hash : 'inicio';
    document.querySelectorAll('.vista').forEach((s) => s.classList.toggle('activa', s.id === 'v-' + nombre));
    document.querySelectorAll('#menu a').forEach((a) => a.classList.toggle('activo', a.getAttribute('href') === '#' + nombre));
    $('#titulo').textContent = TITULOS[nombre];
    try { await vistas[nombre](); } catch (x) { avisar(x.message, true); }
}

// Envío de formularios: cada uno llama al microservicio correspondiente (POST) y refresca la vista
function formulario(id, accion, limpiar = true) {
    $(id).addEventListener('submit', async (ev) => {
        ev.preventDefault();
        try {
            await accion(Object.fromEntries(new FormData(ev.target)));
            if (limpiar) { ev.target.reset(); }
            await ir();
        } catch (x) { avisar(x.message, true); }
    });
}
formulario('#f-veh', async (d) => { await api('veh', '/vehiculos', 'POST', { ...d, anio: +d.anio }); avisar('Vehículo registrado.'); });
formulario('#f-cli', async (d) => { await api('cli', '/clientes', 'POST', d); avisar('Cliente registrado.'); });
formulario('#f-res', async (d) => { await api('res', '/reservas', 'POST', { ...d, cliente_id: +d.cliente_id, vehiculo_id: +d.vehiculo_id }); avisar('Reserva creada.'); }, false);
$('#f-disp').addEventListener('submit', async (ev) => {
    ev.preventDefault();
    try {
        const libres = await api('res', `/disponibilidad?desde=${$('#d-ini').value}&hasta=${$('#d-fin').value}`);
        $('#cat-disp').innerHTML = libres.map((x, i) => ficha(x, '', i)).join('') || vacio('No hay vehículos libres en esas fechas.');
    } catch (x) { avisar(x.message, true); }
});
$('#f-his').addEventListener('submit', (ev) => { ev.preventDefault(); consultarHistorial().catch((x) => avisar(x.message, true)); });

// Botones que se dibujan dentro de las fichas y tablas (delegación de eventos)
document.addEventListener('click', async (ev) => {
    const b = ev.target.closest('button[data-mant],button[data-dev],button[data-can]');
    if (!b) { return; }
    try {
        if (b.dataset.mant) {
            if (b.dataset.estado === 'alquilado') { throw new Error('Un vehículo alquilado no puede pasar a mantenimiento.'); }
            await api('veh', `/vehiculos/${b.dataset.mant}/estado`, 'PUT', { estado: b.dataset.estado === 'mantenimiento' ? 'disponible' : 'mantenimiento' });
            avisar('Estado actualizado.');
        } else {
            const dev = !!b.dataset.dev;
            await api('res', `/reservas/${b.dataset.dev || b.dataset.can}/${dev ? 'devolver' : 'cancelar'}`, 'PUT');
            avisar(dev ? 'Vehículo devuelto: reserva completada.' : 'Reserva cancelada.');
        }
        await ir();
    } catch (x) { avisar(x.message, true); }
});

// Fondo y menú
const colores = ['#ff8a2b', '#ff5fa2', '#b57bff', '#ffd90f', '#33c9ff', '#8cdb3a', '#e8412f'];
$('#ciudad').innerHTML = Array.from({ length: 24 }, (_, k) => `<i style="--w:${70 + (k * 37) % 50}px;--h:${90 + (k * 53) % 110}px;--c:${colores[k % 7]}"></i>`).join('');
$('#menu').innerHTML = Object.entries(MENU).map(([k, t]) => `<a href="#${k}">${t}</a>`).join('');
addEventListener('hashchange', ir);
ir();
