/**
 * Gestión de Vinilos - Viniloteca
 * CRUD completo con estilo Mis Gastos
 */
(function() {
    'use strict';

    let currentModal = null;
    let currentPage = 1;

    /**
     * Valida el campo de precio en tiempo real
     * - Solo permite 2 decimales
     * - Evita notación científica
     * - Limita a $4,000,000
     */
    window.validarPrecio = function(input) {
        let valor = input.value;
        
        // Eliminar notación científica (e, E)
        valor = valor.replace(/[eE]/g, '');
        
        // Permitir solo números y un punto decimal
        valor = valor.replace(/[^0-9.]/g, '');
        
        // Asegurar solo un punto decimal
        const partes = valor.split('.');
        if (partes.length > 2) {
            valor = partes[0] + '.' + partes.slice(1).join('');
        }
        
        // Limitar a 2 decimales
        if (partes.length === 2 && partes[1].length > 2) {
            valor = partes[0] + '.' + partes[1].substring(0, 2);
        }
        
        // Limitar valor máximo
        const numero = parseFloat(valor);
        if (numero > 4000000) {
            valor = '4000000.00';
        }
        
        // Actualizar el valor solo si cambió
        if (input.value !== valor) {
            input.value = valor;
        }
    };

    function cargarVinilos() {
        const searchTerm = $('#search').val() || '';
        const perPage = parseInt($('#per-page').val()) || 10;        

        authAjax({
            url: API_URL + 'api/vinilos',
            type: 'GET',
            data: { buscar: searchTerm, limit: perPage, page: currentPage },
            success: function(response) {
                if (response.status === 200 && response.data) {
                    renderTable(response.data);
                    renderPagination(response);
                } else {
                    showErrorAlert({ title: 'Error', text: 'No se pudieron cargar los vinilos.' });
                }
            },
            error: function(xhr) {
                console.error('Error al cargar vinilos:', xhr);
                showErrorAlert({ title: 'Error', text: 'No se pudieron cargar los vinilos.' });
            }
        });
    }

    function renderTable(vinilos) {
        let html = '';
        if (vinilos.length === 0) {
            html = `<tr><td colspan="8" class="text-center py-5 text-muted"><i class="fa-solid fa-record-vinyl fa-3x mb-3 d-block opacity-25"></i>No hay vinilos registrados</td></tr>`;
        } else {
            vinilos.forEach(vinilo => {
                const estadoClass = getEstadoClass(vinilo.estado_conservacion);
                const precio = parseFloat(vinilo.precio || 0).toFixed(2);
                
                // ✅ Truncar textos largos (máximo 50 caracteres)
                const tituloTruncado = truncateText(vinilo.titulo, 50);
                const artistaTruncado = truncateText(vinilo.artista, 50);
                
                const canEdit = typeof USER_PERMISSIONS !== 'undefined' && USER_PERMISSIONS.includes('vinilos.edit');
                const canDelete = typeof USER_PERMISSIONS !== 'undefined' && USER_PERMISSIONS.includes('vinilos.delete');

                let actionsHtml = '';
                if (canEdit || canDelete) {
                    actionsHtml += '<td class="text-end"><div class="table-row-actions">';
                    if (canEdit) {
                        actionsHtml += `<button class="btn-action btn-edit" onclick="modalEditarVinilo(${vinilo.id})" title="Editar"><i class="fa-solid fa-pen"></i></button>`;
                    }
                    if (canDelete) {
                        actionsHtml += `<button class="btn-action btn-delete" onclick="eliminarVinilo(${vinilo.id}, '${escapeHtml(vinilo.titulo)}')" title="Eliminar"><i class="fa-solid fa-trash"></i></button>`;
                    }
                    actionsHtml += '</div></td>';
                } else {
                    actionsHtml += '<td></td>';
                }
                
                html += `
                    <tr>
                        <td><span class="cell-truncated" 
                                data-full-text="${escapeHtml(vinilo.titulo)}">
                                ${truncateText(vinilo.titulo, 50)}</span></td>
                        <td><span class="cell-truncated" 
                                data-full-text="${escapeHtml(vinilo.artista)}">
                                ${truncateText(vinilo.artista, 50)}</span></td>
                        <td>${vinilo.anio_lanzamiento || '-'}</td>
                        <td>${escapeHtml(vinilo.genero) || '-'}</td>
                        <td>${escapeHtml(vinilo.formato)}</td>
                        <td><span class="badge-estado ${estadoClass}">${vinilo.estado_conservacion}</span></td>
                        <td class="cell-price">$${precio}</td>
                        ${actionsHtml}
                    </tr>
                `;
            });
        }
        $('#table-body').html(html);
    }

    // ✅ Función auxiliar para truncar texto
    function truncateText(text, maxLength) {
        if (!text) return '';
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    }

    function renderPagination(response) {
        const total = response.total || 0;
        const perPage = response.per_page || 10;
        const currentPage = response.page || 1;
        const pageCount = response.page_count || 1;
        
        let html = `
            <div class="pagination-info">Mostrando ${Math.min(total, perPage)} de ${total} registros</div>
            <nav><ul class="pagination">
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="cambiarPagina(${currentPage - 1}); return false;"><i class="fa-solid fa-chevron-left"></i></a>
                </li>`;
        
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(pageCount, startPage + 4);
        if (endPage - startPage < 4) startPage = Math.max(1, endPage - 4);
        
        for (let i = startPage; i <= endPage; i++) {
            html += `<li class="page-item ${i === currentPage ? 'active' : ''}"><a class="page-link" href="#" onclick="cambiarPagina(${i}); return false;">${i}</a></li>`;
        }
        
        html += `
                <li class="page-item ${currentPage === pageCount ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="cambiarPagina(${currentPage + 1}); return false;"><i class="fa-solid fa-chevron-right"></i></a>
                </li>
            </ul></nav>`;
        $('#pagination').html(html);
    }

    window.cambiarPagina = function(page) {
        if (page < 1) return;
        currentPage = page;
        cargarVinilos();
    };

    window.modalCrearVinilo = function() {
        $('#form-vinilo')[0].reset();
        $('#vinilo-id').val('');
        clearValidationErrors();
        $('#modal-title-text').text('Agregar Vinilo');
        $('#modalViniloTitle i').removeClass('fa-pen').addClass('fa-record-vinyl');
        $('#btn-save-text').text('Guardar Vinilo');
        
        currentModal = new bootstrap.Modal(document.getElementById('modalVinilo'), { backdrop: 'static', keyboard: false });
        currentModal.show();
    };

    window.modalEditarVinilo = function(id) {
        clearValidationErrors();
        authAjax({
            url: API_URL + 'api/vinilos/' + id,
            type: 'GET',
            success: function(vinilo) {
                if (vinilo.status === 200 || vinilo.id) {
                    const data = vinilo.data || vinilo;
                    $('#vinilo-id').val(data.id);
                    $('#vinilo-titulo').val(data.titulo);
                    $('#vinilo-artista').val(data.artista);
                    $('#vinilo-anio').val(data.anio_lanzamiento);
                    $('#vinilo-genero').val(data.genero);
                    $('#vinilo-formato').val(data.formato);
                    $('#vinilo-estado').val(data.estado_conservacion);
                    $('#vinilo-precio').val(data.precio);

                    $('#modal-title-text').text('Editar Vinilo');
                    $('#modalViniloTitle i').removeClass('fa-record-vinyl').addClass('fa-pen');
                    $('#btn-save-text').text('Guardar Cambios');

                    currentModal = new bootstrap.Modal(document.getElementById('modalVinilo'), { backdrop: 'static', keyboard: false });
                    currentModal.show();
                } else {
                    showErrorAlert({ title: 'Error', text: 'No se pudo cargar la información del vinilo.' });
                }
            },
            error: function(xhr) {
                console.error('Error al cargar vinilo:', xhr);
                showErrorAlert({ title: 'Error', text: 'No se pudo cargar la información del vinilo.' });
            }
        });
    };

    window.eliminarVinilo = function(id, titulo) {
        showConfirmAlert({
            title: '¿Eliminar vinilo?',
            html: `¿Estás seguro de eliminar "<strong>${titulo}</strong>"? Esta acción no se puede deshacer.`,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            onConfirm: () => {
                authAjax({
                    url: API_URL + 'api/vinilos/' + id,
                    type: 'DELETE',
                    success: function() {
                        showSuccessAlert({
                            title: '¡Eliminado!',
                            text: 'El vinilo ha sido eliminado correctamente.',
                            onConfirm: () => {
                                // ✅ VERIFICAR SI LA PÁGINA ACTUAL QUEDÓ VACÍA
                                verificarYAjustarPaginacion();
                            }
                        });
                    },
                    error: function(xhr) {
                        let errorMsg = 'No se pudo eliminar el vinilo.';
                        if (xhr.responseJSON && xhr.responseJSON.error) errorMsg = xhr.responseJSON.error;
                        showErrorAlert({ title: 'Error', text: errorMsg });
                    }
                });
            }
        });
    };

    // ✅ Función para ajustar paginación después de eliminar
    function verificarYAjustarPaginacion() {
        const perPage = parseInt($('#per-page').val()) || 10;
        const searchTerm = $('#search').val() || '';
        
        authAjax({
            url: API_URL + 'api/vinilos',
            type: 'GET',
            data: { buscar: searchTerm, limit: perPage, page: currentPage },
            success: function(response) {
                const dataEnPaginaActual = response.data || [];
                const pageCount = response.page_count || 1;
                
                // Si la página actual está vacía y no es la primera, ir a la anterior
                if (dataEnPaginaActual.length === 0 && currentPage > 1) {
                    currentPage = Math.max(1, currentPage - 1);
                }
                
                // Asegurar que no nos pasemos del total de páginas
                if (currentPage > pageCount) {
                    currentPage = pageCount;
                }
                
                cargarVinilos();
            }
        });
    }

    $('#form-vinilo').on('submit', function(e) {
        e.preventDefault();
        clearValidationErrors();

        let isValid = true;
        const titulo = $('#vinilo-titulo').val().trim();
        const artista = $('#vinilo-artista').val().trim();
        const formato = $('#vinilo-formato').val();
        const estado = $('#vinilo-estado').val();
        const precioInput = $('#vinilo-precio').val().trim();
        
        if (precioInput !== '') {
            const precioRegex = /^\d{1,8}(\.\d{1,2})?$/;
            const precio = parseFloat(precioInput);
            
            if (/[eE]/.test(precioInput)) {
                showFieldError('vinilo-precio', 'El precio no puede contener notación científica.');
                isValid = false;
            } else if (!precioRegex.test(precioInput)) {
                showFieldError('vinilo-precio', 'El precio debe tener máximo 2 decimales.');
                isValid = false;
            } else if (isNaN(precio) || precio < 0 || precio > 4000000) {
                showFieldError('vinilo-precio', 'El precio debe estar entre $0.00 y $4,000,000.00');
                isValid = false;
            }
        }
        
        if (!titulo) { showFieldError('vinilo-titulo', 'El título es obligatorio'); isValid = false; }
        if (!artista) { showFieldError('vinilo-artista', 'El artista es obligatorio'); isValid = false; }
        if (!formato) { showFieldError('vinilo-formato', 'Seleccione un formato'); isValid = false; }
        if (!estado) { showFieldError('vinilo-estado', 'Seleccione el estado de conservación'); isValid = false; }

        if (!isValid) return;

        const id = $('#vinilo-id').val();
        const esEdicion = !!id;
        const url = esEdicion ? API_URL + 'api/vinilos/' + id : API_URL + 'api/vinilos';
        const metodo = esEdicion ? 'PUT' : 'POST';

        const payload = {
            titulo: titulo,
            artista: artista,
            anio_lanzamiento: $('#vinilo-anio').val() || null,
            genero: $('#vinilo-genero').val(),
            formato: formato,
            estado_conservacion: estado,
            precio: precioInput !== '' ? parseFloat(precioInput).toFixed(2) : 0
        };

        const $btn = $(this).find('button[type="submit"]');
        const originalText = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Guardando...');

        authAjax({
            url: url,
            type: metodo,
            data: JSON.stringify(payload),
            success: function(response) {
                $btn.prop('disabled', false).html(originalText);
                
                showSuccessAlert({
                    title: '¡Éxito!',
                    text: esEdicion ? 'Vinilo actualizado correctamente.' : 'Vinilo creado correctamente.',
                    onConfirm: () => {
                        if (currentModal) currentModal.hide();
                        
                        // ✅ CALCULAR Y MOVER A LA PÁGINA CORRECTA
                        if (!esEdicion) {
                            // Es creación: calcular en qué página caería el nuevo registro
                            calcularPaginaParaNuevoRegistro();
                        } else {
                            // Es edición: recargar manteniendo página actual
                            cargarVinilos();
                        }
                    }
                });
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html(originalText);
                
                if (xhr.responseJSON && xhr.responseJSON.messages) {
                    showServerErrors(xhr.responseJSON.messages);
                    return;
                }
                let errorMsg = 'Error al guardar el vinilo.';
                if (xhr.responseJSON && xhr.responseJSON.error) errorMsg = xhr.responseJSON.error;
                showErrorAlert({ title: 'Error', text: errorMsg });
            }
        });
    });

    // ✅ Función para calcular página del nuevo registro
    function calcularPaginaParaNuevoRegistro() {
        const perPage = parseInt($('#per-page').val()) || 10;
        const searchTerm = $('#search').val() || '';
        
        // Obtener total actual
        authAjax({
            url: API_URL + 'api/vinilos',
            type: 'GET',
            data: { buscar: searchTerm, limit: 1, page: 1 },
            success: function(response) {
                const total = response.total || 0;
                const nuevaPagina = Math.ceil((total + 1) / perPage);
                
                currentPage = Math.max(1, nuevaPagina);
                cargarVinilos();
            }
        });
    }

    window.eliminarVinilo = function(id, titulo) {
        showConfirmAlert({
            title: '¿Eliminar vinilo?',
            html: `¿Estás seguro de eliminar "<strong>${titulo}</strong>"? Esta acción no se puede deshacer.`,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            onConfirm: () => {
                authAjax({
                    url: API_URL + 'api/vinilos/' + id,
                    type: 'DELETE',
                    success: function() {
                        showSuccessAlert({
                            title: '¡Eliminado!',
                            text: 'El vinilo ha sido eliminado correctamente.',
                            onConfirm: () => {
                                // ✅ VERIFICAR SI LA PÁGINA ACTUAL QUEDÓ VACÍA
                                verificarYAjustarPaginacion();
                            }
                        });
                    },
                    error: function(xhr) {
                        let errorMsg = 'No se pudo eliminar el vinilo.';
                        if (xhr.responseJSON && xhr.responseJSON.error) errorMsg = xhr.responseJSON.error;
                        showErrorAlert({ title: 'Error', text: errorMsg });
                    }
                });
            }
        });
    };

    // ✅ Función para ajustar paginación después de eliminar
    function verificarYAjustarPaginacion() {
        const perPage = parseInt($('#per-page').val()) || 10;
        const searchTerm = $('#search').val() || '';
        
        authAjax({
            url: API_URL + 'api/vinilos',
            type: 'GET',
            data: { buscar: searchTerm, limit: perPage, page: currentPage },
            success: function(response) {
                const dataEnPaginaActual = response.data || [];
                const pageCount = response.page_count || 1;
                
                // Si la página actual está vacía y no es la primera, ir a la anterior
                if (dataEnPaginaActual.length === 0 && currentPage > 1) {
                    currentPage = Math.max(1, currentPage - 1);
                }
                
                // Asegurar que no nos pasemos del total de páginas
                if (currentPage > pageCount) {
                    currentPage = pageCount;
                }
                
                cargarVinilos();
            }
        });
    }

    function showFieldError(fieldId, message) {
        const $field = $('#' + fieldId);
        $field.addClass('is-invalid');
        const $feedback = $field.next('.invalid-feedback');
        if ($feedback.length) $feedback.text(message).addClass('show');
    }

    function clearValidationErrors() {
        $('#form-vinilo .form-control, #form-vinilo .form-select').each(function() {
            $(this).removeClass('is-invalid');
            const $feedback = $(this).next('.invalid-feedback');
            if ($feedback.length) $feedback.removeClass('show').text('');
        });
    }

    function showServerErrors(errors) {
        const fieldMap = {
            'titulo': 'vinilo-titulo', 'artista': 'vinilo-artista', 'formato': 'vinilo-formato',
            'estado_conservacion': 'vinilo-estado', 'anio_lanzamiento': 'vinilo-anio', 'precio': 'vinilo-precio'
        };
        for (const [field, messages] of Object.entries(errors)) {
            const fieldId = fieldMap[field];
            if (fieldId) showFieldError(fieldId, Array.isArray(messages) ? messages[0] : messages);
        }
    }

    $('#form-vinilo').on('input', '.form-control, .form-select', function() {
        $(this).removeClass('is-invalid');
        const $feedback = $(this).next('.invalid-feedback');
        if ($feedback.length) $feedback.removeClass('show');
    });

    $('#search').on('input', function() {
        clearTimeout(window.searchTimeout);
        window.searchTimeout = setTimeout(() => { currentPage = 1; cargarVinilos(); }, 500);
    });

    $('#per-page').on('change', function() {
        currentPage = 1;
        cargarVinilos();
    });

    function getEstadoClass(estado) {
        const classes = { 'M': 'M', 'NM': 'NM', 'EX': 'EX', 'VG+': 'VG+', 'VG': 'VG', 'G': 'G', 'F': 'F', 'P': 'P' };
        return classes[estado] || '';
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    $(document).ready(function() { cargarVinilos(); });
})();