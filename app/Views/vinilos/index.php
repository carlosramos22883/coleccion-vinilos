<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Colección de Vinilos<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 text-primary"><i class="fa-solid fa-record-vinyl me-2"></i>Catálogo de Vinilos</h5>
        <button class="btn btn-primary btn-sm" onclick="modalCrearVinilo()">
            <i class="fa-solid fa-plus me-1"></i> Agregar Vinilo
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tabla-vinilos" class="table table-striped table-hover align-middle w-100">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Artista</th>
                        <th>Año</th>
                        <th>Formato</th>
                        <th>Estado</th>
                        <th>Precio</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL CREAR / EDITAR VINILO -->
<div class="modal fade" id="modalVinilo" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalViniloTitle">Agregar Vinilo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-vinilo">
                <div class="modal-body">
                    <input type="hidden" id="vinilo-id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Título del Álbum</label>
                            <input type="text" id="vinilo-titulo" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Artista / Banda</label>
                            <input type="text" id="vinilo-artista" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Año de Lanzamiento</label>
                            <input type="number" id="vinilo-anio-lanzamiento" class="form-control" min="1800" max="2100">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Género</label>
                            <input type="text" id="vinilo-genero" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Formato</label>
                            <select id="vinilo-formato" class="form-select" required>
                                <option value="">Seleccione...</option>
                                <option value="LP">LP</option>
                                <option value="Single">Single</option>
                                <option value="Maxi Single">Maxi Single</option>
                                <option value="EP">EP</option>
                                <option value="10 pulgadas">10 pulgadas</option>
                                <option value="78 RPM">78 RPM</option>
                                <option value="Picture Disc">Picture Disc</option>
                                <option value="Vinilo de Color">Vinilo de Color</option>
                                <option value="Flexi Disc">Flexi Disc</option>
                                <option value="Shaped Disc">Shaped Disc</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado de Conservación</label>
                            <select id="vinilo-estado-conservacion" class="form-select" required>
                                <option value="">Seleccione...</option>
                                <option value="M">M (Mint)</option>
                                <option value="NM">NM (Near Mint)</option>
                                <option value="EX">EX (Excellent)</option>
                                <option value="VG+">VG+ (Very Good Plus)</option>
                                <option value="VG">VG (Very Good)</option>
                                <option value="G">G (Good)</option>
                                <option value="F/P">F/P (Fair / Poor)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Precio ($)</label>
                            <input type="number" step="0.01" id="vinilo-precio" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Vinilo</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    let dataTableVinilos;

    $(document).ready(function() {
        dataTableVinilos = $('#tabla-vinilos').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            columns: [{
                    data: 'id'
                },
                {
                    data: 'titulo'
                },
                {
                    data: 'artista',
                    render: data => data || ''
                },
                {
                    data: 'anio_lanzamiento',
                    render: data => data || ''
                },
                {
                    data: 'formato',
                    render: data => data || ''
                },
                {
                    data: 'estado_conservacion',
                    render: data => data ? `<span class="badge bg-secondary">${data}</span>` : ''
                },
                {
                    data: 'precio',
                    render: data => `$${parseFloat(data || 0).toFixed(2)}`
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        return `
                            <button class="btn btn-sm btn-warning me-1" onclick="modalEditarVinilo(${row.id}, '${escapeHtml(row.titulo)}', '${escapeHtml(row.artista)}', '${row.anio_lanzamiento || ''}', '${escapeHtml(row.genero || '')}', '${row.formato || ''}', '${row.estado_conservacion || ''}', '${row.precio || 0}')">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="eliminarVinilo(${row.id})">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        `;
                    }
                }
            ]
        });

        cargarVinilos();

        // Enviar Formulario (Crear / Editar) utilizando authAjax
        $('#form-vinilo').on('submit', function(e) {
            e.preventDefault();

            const id = $('#vinilo-id').val();
            const esEdicion = !!id;
            const url = esEdicion ? `${API_URL}/vinilos/${id}` : `${API_URL}/vinilos`;
            const metodo = esEdicion ? 'PUT' : 'POST';

            const payload = {
                titulo: $('#vinilo-titulo').val(),
                artista: $('#vinilo-artista').val(),
                anio_lanzamiento: $('#vinilo-anio-lanzamiento').val(),
                genero: $('#vinilo-genero').val(),
                formato: $('#vinilo-formato').val(),
                estado_conservacion: $('#vinilo-estado-conservacion').val(),
                precio: $('#vinilo-precio').val()
            };

            authAjax({
                url: url,
                type: metodo,
                data: JSON.stringify(payload),
                success: function() {
                    const modalEl = document.getElementById('modalVinilo');
                    const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                    modalInstance.hide();
                    cargarVinilos();
                }
            });
        });
    });

    function cargarVinilos() {
        authAjax({
            url: `${API_URL}/vinilos`,
            type: 'GET',
            success: function(response) {
                const vinilos = response.data ? response.data : response;
                dataTableVinilos.clear().rows.add(vinilos || []).draw();
            }
        });
    }

    function modalCrearVinilo() {
        $('#form-vinilo')[0].reset();
        $('#vinilo-id').val('');
        $('#modalViniloTitle').text('Agregar Vinilo');

        const modalEl = document.getElementById('modalVinilo');
        const modalInstance = new bootstrap.Modal(modalEl);
        modalInstance.show();
    }

    function modalEditarVinilo(id, titulo, artista, anio, genero, formato, estado, precio) {
        $('#vinilo-id').val(id);
        $('#vinilo-titulo').val(titulo);
        $('#vinilo-artista').val(artista);
        $('#vinilo-anio-lanzamiento').val(anio);
        $('#vinilo-genero').val(genero);
        $('#vinilo-formato').val(formato);
        $('#vinilo-estado-conservacion').val(estado);
        $('#vinilo-precio').val(precio);

        $('#modalViniloTitle').text('Editar Vinilo');

        const modalEl = document.getElementById('modalVinilo');
        const modalInstance = new bootstrap.Modal(modalEl);
        modalInstance.show();
    }

    function eliminarVinilo(id) {
        if (!confirm('¿Estás seguro de eliminar este vinilo?')) return;

        authAjax({
            url: `${API_URL}/vinilos/${id}`,
            type: 'DELETE',
            success: function() {
                cargarVinilos();
            }
        });
    }

    // Utilidad pequeña para prevenir problemas con comillas en JavaScript inline
    function escapeHtml(text) {
        if (!text) return '';
        return text.replace(/'/g, "&#39;").replace(/"/g, "&quot;");
    }
</script>
<?= $this->endSection() ?>