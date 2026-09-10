<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Catálogo de Vinilos<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="vinilos-page">
    <!-- Header -->
    <div class="page-header">
        <h1>
            <i class="fa-solid fa-record-vinyl"></i>
            Catálogo de Vinilos
        </h1>
    </div>

    <!-- Tarjeta Principal -->
    <div class="data-card">
        <!-- Toolbar -->
        <div class="table-toolbar">
            <div class="table-search">
                <input type="text" id="search" placeholder="Buscar por título, artista o género...">
                <i class="fa-solid fa-search"></i>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="table-per-page">
                    <select id="per-page">
                        <option value="10">10 por página</option>
                        <option value="25">25 por página</option>
                        <option value="50">50 por página</option>
                        <option value="100">100 por página</option>
                    </select>
                </div>
                <!-- Botón Agregar (SOLO si tiene vinilos.create) -->
                <?php if (in_array('vinilos.create', $permisos)): ?>
                    <button class="btn btn-primary-custom" onclick="modalCrearVinilo()">
                        <i class="fa-solid fa-plus me-1"></i> Agregar Vinilo
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tabla -->
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Artista</th>
                        <th>Año</th>
                        <th>Género</th>
                        <th>Formato</th>
                        <th>Estado</th>
                        <th class="text-end">Precio</th>
                        <!-- En la tabla, columnas de acciones -->
                        <?php if (in_array('vinilos.edit', $permisos) || in_array('vinilos.delete', $permisos)): ?>
                            <th class="text-end">Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody id="table-body">
                    <!-- Se carga vía AJAX -->
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div id="pagination" class="table-pagination">
            <!-- Se carga vía AJAX -->
        </div>
    </div>
</div>

<!-- MODAL CREAR / EDITAR VINILO -->
<div class="modal fade" id="modalVinilo" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="modalViniloTitle">
                    <i class="fa-solid fa-record-vinyl me-2 text-primary"></i>
                    <span id="modal-title-text">Agregar Vinilo</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-vinilo" novalidate>
                <div class="modal-body">
                    <input type="hidden" id="vinilo-id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Título del Álbum *</label>
                            <input type="text" id="vinilo-titulo" class="form-control" maxlength="150" required placeholder="Ej: Nevermind">
                            <div class="invalid-feedback">El título es obligatorio.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Artista / Banda *</label>
                            <input type="text" id="vinilo-artista" class="form-control" maxlength="150" required placeholder="Ej: Nirvana">
                            <div class="invalid-feedback">El artista es obligatorio.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Año de Lanzamiento</label>
                            <input type="number" id="vinilo-anio" class="form-control" min="1800" max="2100" placeholder="1991">
                            <div class="invalid-feedback">El año debe estar entre 1800 y 2100.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Género</label>
                            <input type="text" id="vinilo-genero" class="form-control" placeholder="Ej: Rock, Grunge">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Formato *</label>
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
                            </select>
                            <div class="invalid-feedback">Seleccione un formato.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Estado de Conservación *</label>
                            <select id="vinilo-estado" class="form-select" required>
                                <option value="">Seleccione...</option>
                                <option value="M">M (Mint)</option>
                                <option value="NM">NM (Near Mint)</option>
                                <option value="EX">EX (Excellent)</option>
                                <option value="VG+">VG+ (Very Good Plus)</option>
                                <option value="VG">VG (Very Good)</option>
                                <option value="G">G (Good)</option>
                                <option value="F">F (Fair)</option>
                                <option value="P">P (Poor)</option>
                            </select>
                            <div class="invalid-feedback">Seleccione el estado de conservación.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Precio ($)</label>
                            <input type="number"
                                step="0.01"
                                min="0"
                                max="4000000"
                                id="vinilo-precio"
                                class="form-control"
                                placeholder="0.00"
                                oninput="validarPrecio(this)">
                            <div class="invalid-feedback">El precio debe estar entre $0.00 y $4,000,000.00 con máximo 2 decimales.</div>
                            <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">
                                <i class="fa-solid fa-circle-info me-1"></i>Máximo: $4,000,000.00 (récord histórico)
                            </small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">
                        <i class="fa-solid fa-times me-1"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary-custom">
                        <i class="fa-solid fa-save me-1"></i>
                        <span id="btn-save-text">Guardar Vinilo</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    // Hacemos los permisos disponibles globalmente para vinilos.js
    const USER_PERMISSIONS = <?= json_encode($permisos ?? []) ?>;
</script>
<script src="<?= base_url('assets/js/vinilos.js') ?>"></script>
<?= $this->endSection() ?>