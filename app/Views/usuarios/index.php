<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Gestión de Usuarios<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 text-primary"><i class="fa-solid fa-users me-2"></i>Gestión de Usuarios</h5>
        <button class="btn btn-primary btn-sm" onclick="modalCrearUsuario()">
            <i class="fa-solid fa-user-plus me-1"></i> Agregar Usuario
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tbl-usuarios" class="table table-striped table-hover align-middle w-100">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL CREAR / EDITAR USUARIO -->
<div class="modal fade" id="modalUsuario" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalUsuarioTitle">Agregar Usuario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-usuario">
                <div class="modal-body">
                    <input type="hidden" id="usr-id">
                    <div class="mb-3">
                        <label class="form-label">Nombre Completo</label>
                        <input type="text" id="usr-nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" id="usr-email" class="form-control" required>
                    </div>
                    <div class="mb-3" id="group-pass">
                        <label class="form-label">Contraseña</label>
                        <input type="password" id="usr-pass" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rol del Usuario</label>
                        <select id="usr-rol" class="form-select" required>
                            <option value="1">Administrador</option>
                            <option value="2">Coleccionista</option>
                            <option value="3">Lector</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btn-guardar-usr">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    let dataTableUsers;

    $(document).ready(function() {
        dataTableUsers = $('#tbl-usuarios').DataTable({
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
                    data: 'email'
                },
                {
                    data: 'rol',
                    render: function(data) {
                        return `<span class="badge bg-info text-dark">${data || 'Sin Rol'}</span>`;
                    }
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        return `
                            <button class="btn btn-sm btn-warning me-1" onclick="modalEditarUsuario(${row.id}, '${escapeHtml(row.nombre)}', '${escapeHtml(row.email)}')">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="eliminarUsuario(${row.id})">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        `;
                    }
                }
            ]
        });

        cargarUsuarios();
    });

    function cargarUsuarios() {
        authAjax({
            url: API_URL + '/usuarios',
            type: 'GET',
            success: function(res) {
                const usuarios = res.data ? res.data : res;
                dataTableUsers.clear().rows.add(usuarios).draw();
            }
        });
    }

    function modalCrearUsuario() {
        $('#form-usuario')[0].reset();
        $('#usr-id').val('');
        $('#modalUsuarioTitle').text('Agregar Usuario');
        $('#usr-pass').attr('required', true);
        $('#group-pass').show();
        $('#modalUsuario').modal('show');
    }

    function modalEditarUsuario(id, nombre, email) {
        $('#usr-id').val(id);
        $('#usr-nombre').val(nombre);
        $('#usr-email').val(email);
        $('#modalUsuarioTitle').text('Editar Usuario');
        $('#usr-pass').removeAttr('required');
        $('#group-pass').hide(); // La contraseña se cambia en su sección específica
        $('#modalUsuario').modal('show');
    }

    $('#form-usuario').on('submit', function(e) {
        e.preventDefault();
        const id = $('#usr-id').val();
        const isEdit = id !== '';

        const payload = {
            nombre: $('#usr-nombre').val(),
            email: $('#usr-email').val(),
            role_id: $('#usr-rol').val()
        };

        if (!isEdit) {
            payload.password = $('#usr-pass').val();
        }

        authAjax({
            url: isEdit ? `${API_URL}/usuarios/${id}` : `${API_URL}/usuarios`,
            type: isEdit ? 'PUT' : 'POST',
            data: JSON.stringify(payload),
            success: function() {
                $('#modalUsuario').modal('hide');
                cargarUsuarios();
            }
        });
    });

    function eliminarUsuario(id) {
        if (confirm('¿Desea eliminar este usuario?')) {
            authAjax({
                url: `${API_URL}/usuarios/${id}`,
                type: 'DELETE',
                success: function() {
                    cargarUsuarios();
                }
            });
        }
    }

    // Utilidad para evitar errores de sintaxis al inyectar comillas en los botones
    function escapeHtml(text) {
        if (!text) return '';
        return text.replace(/'/g, "&#39;").replace(/"/g, "&quot;");
    }
</script>
<?= $this->endSection() ?>