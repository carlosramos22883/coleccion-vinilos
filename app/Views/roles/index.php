<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Gestión de Roles<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 text-primary"><i class="fa-solid fa-user-shield me-2"></i>Gestión de Roles y Permisos</h5>
        <button class="btn btn-primary btn-sm" onclick="modalCrearRol()">
            <i class="fa-solid fa-shield-plus me-1"></i> Nuevo Rol
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tbl-roles" class="table table-striped table-hover align-middle w-100">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nombre del Rol</th>
                        <th>Descripción</th>
                        <th>Permisos Asignados</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL CREAR / EDITAR ROL -->
<div class="modal fade" id="modalRol" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalRolTitle">Nuevo Rol</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-rol">
                <div class="modal-body">
                    <input type="hidden" id="rol-id">
                    <div class="mb-3">
                        <label class="form-label">Nombre del Rol</label>
                        <input type="text" id="rol-nombre" class="form-control" required placeholder="Ej. Editor">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea id="rol-descripcion" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Rol</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    let dataTableRoles;

    $(document).ready(function() {
        dataTableRoles = $('#tbl-roles').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            columns: [{
                    data: 'id'
                },
                {
                    data: 'nombre'
                },
                {
                    data: 'descripcion'
                },
                {
                    data: 'permisos',
                    render: function(data) {
                        if (!data || data.length === 0) return '<em class="text-muted">Sin permisos</em>';
                        return data.map(p => `<span class="badge bg-secondary me-1">${p.clave || p.nombre}</span>`).join('');
                    }
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        return `
                            <button class="btn btn-sm btn-warning me-1" onclick="modalEditarRol(${row.id}, '${row.nombre}', '${row.descripcion || ''}')">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="eliminarRol(${row.id})">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        `;
                    }
                }
            ]
        });

        cargarRoles();
    });

    function cargarRoles() {
        authAjax({
            url: API_URL + '/roles',
            type: 'GET',
            success: function(res) {
                dataTableRoles.clear().rows.add(res.data || res).draw();
            }
        });
    }

    function modalCrearRol() {
        $('#form-rol')[0].reset();
        $('#rol-id').val('');
        $('#modalRolTitle').text('Nuevo Rol');
        $('#modalRol').modal('show');
    }

    function modalEditarRol(id, nombre, descripcion) {
        $('#rol-id').val(id);
        $('#rol-nombre').val(nombre);
        $('#rol-descripcion').val(descripcion);
        $('#modalRolTitle').text('Editar Rol');
        $('#modalRol').modal('show');
    }

    $('#form-rol').on('submit', function(e) {
        e.preventDefault();
        const id = $('#rol-id').val();
        const isEdit = id !== '';

        const payload = {
            nombre: $('#rol-nombre').val(),
            descripcion: $('#rol-descripcion').val()
        };

        authAjax({
            url: isEdit ? `${API_URL}/roles/${id}` : `${API_URL}/roles`,
            type: isEdit ? 'PUT' : 'POST',
            data: JSON.stringify(payload),
            success: function() {
                $('#modalRol').modal('hide');
                cargarRoles();
            }
        });
    });

    function eliminarRol(id) {
        if (confirm('¿Desea eliminar este rol?')) {
            authAjax({
                url: `${API_URL}/roles/${id}`,
                type: 'DELETE',
                success: function() {
                    cargarRoles();
                }
            });
        }
    }
</script>
<?= $this->endSection() ?>