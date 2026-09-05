$(document).ready(function() {
    // Ocultar la sección de reenvío al cargar
    $('.resend-verification-section').hide();

    // ==========================================
    // MANEJO DE PARÁMETROS URL
    // ==========================================
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.get('verified') === '1') {
        showSuccessAlert({
            title: '¡Cuenta verificada!',
            text: 'Tu correo ha sido verificado correctamente. Ya puedes iniciar sesión.',
            timer: 4000,
            showConfirmButton: false,
        });
    }

    if (urlParams.get('error') === 'token_invalido') {
        showErrorAlert({
            title: 'Token inválido',
            text: 'El enlace de verificación no es válido.',
            confirmButtonText: 'Entendido'
        });
    }

    if (urlParams.get('error') === 'token_expirado') {
        const email = urlParams.get('email') || '';
        $('#login-email').val(email);
        showWarningAlert({
            title: 'Enlace expirado',
            text: 'El enlace de verificación ha expirado. Solicita un nuevo correo de verificación.',
            confirmButtonText: 'Entendido'
        });
        // Mostrar botón de reenvío con el email ya llenado
        $('.resend-verification-section').slideDown(300);
    }

    if (urlParams.get('error') === 'token_faltante') {
        showErrorAlert({
            title: 'Error',
            text: 'No se proporcionó un token de verificación.',
            confirmButtonText: 'Entendido'
        });
    }

    // Nuevos casos de error para recuperación de contraseña
    if (urlParams.get('error') === 'reset_token_faltante') {
        showErrorAlert({
            title: 'Error',
            text: 'No se proporcionó un token de recuperación.',
            confirmButtonText: 'Entendido'
        });
    }

    if (urlParams.get('error') === 'reset_token_invalido') {
        showErrorAlert({
            title: 'Token inválido',
            text: 'El enlace de recuperación no es válido o ya fue usado.',
            confirmButtonText: 'Entendido'
        });
    }

    if (urlParams.get('error') === 'reset_token_expirado') {
        showWarningAlert({
            title: 'Enlace expirado',
            text: 'El enlace de recuperación ha expirado. Solicita un nuevo enlace desde "¿Olvidaste tu contraseña?".',
            confirmButtonText: 'Entendido'
        });
    }

    // ==========================================
    // FORMULARIO DE LOGIN
    // ==========================================
    $('#form-login').on('submit', function(e) {
        e.preventDefault();

        const email = $('#login-email').val().trim();
        const password = $('#login-password').val().trim();
        let isValid = true;

        $('.input-custom').removeClass('input-error');
        $('#error-email, #error-password').addClass('d-none').text('');
        $('#login-alert').addClass('d-none');

        if (email === '') {
            $('#login-email').addClass('input-error');
            $('#error-email').text('El correo electrónico es obligatorio.').removeClass('d-none');
            isValid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            $('#login-email').addClass('input-error');
            $('#error-email').text('Ingresa un formato de correo válido.').removeClass('d-none');
            isValid = false;
        }

        if (password === '') {
            $('#login-password').addClass('input-error');
            $('#error-password').text('La contraseña es obligatoria.').removeClass('d-none');
            isValid = false;
        }

        if (!isValid) return;

        const $btn = $('.btn-login');
        const originalContent = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa-solid fa-circle-notch fa-spin"></i> <span>Ingresando...</span>');

        $.ajax({
            url: API_URL + '/auth/login',
            type: 'POST',
            contentType: 'application/json',
            dataType: 'json',
            data: JSON.stringify({ email, password }),
            success: function(res) {
                localStorage.setItem('jwt_token', res.token);
                $btn.html('<i class="fa-solid fa-check"></i> <span>¡Bienvenido!</span>');
                $btn.css('background', 'linear-gradient(135deg, #28a745 0%, #218838 100%)');
                
                setTimeout(() => {
                    window.location.href = API_URL + '/dashboard';
                }, 800);
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html(originalContent);
                $btn.css('background', '');

                // Caso especial: cuenta no verificada
                if (xhr.status === 403 && xhr.responseJSON) {
                    const userEmail = xhr.responseJSON.email || email;
                    const tokenExpirado = xhr.responseJSON.token_expirado || false;
                    
                    const mensaje = tokenExpirado 
                        ? `Tu cuenta aún no ha sido verificada y el enlace ha expirado.<br><br><strong>Email:</strong> ${userEmail}<br><br>¿Deseas que te enviemos un nuevo enlace de verificación?`
                        : `Tu cuenta aún no ha sido verificada.<br><br><strong>Email:</strong> ${userEmail}<br><br>¿Deseas que te enviemos un nuevo enlace de verificación?`;

                    showConfirmAlert({
                        title: 'Cuenta no verificada',
                        html: mensaje,
                        confirmButtonText: 'Sí, reenviar correo',
                        cancelButtonText: 'No, cancelar',
                        onConfirm: () => {
                            reenviarVerificacion(userEmail);
                        },
                        onCancel: () => {
                            // Si cancela, mostrar el botón en la UI
                            $('#login-email').val(userEmail);
                            $('.resend-verification-section').slideDown(300);
                        }
                    });
                    return;
                }

                let errorMsg = 'Credenciales incorrectas o error en el servidor';
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.messages) {
                        errorMsg = typeof xhr.responseJSON.messages === 'string' ? 
                            xhr.responseJSON.messages : 
                            Object.values(xhr.responseJSON.messages).join('<br>');
                    } else if (xhr.responseJSON.error) {
                        errorMsg = xhr.responseJSON.error;
                    }
                }

                showErrorAlert({
                    title: 'Error al iniciar sesión',
                    html: errorMsg,
                    confirmButtonText: 'Intentar de nuevo'
                });
            }
        });
    });

    // ==========================================
    // REENVÍO DE CORREO DE VERIFICACIÓN
    // ==========================================
    $('#btn-resend-verification').on('click', function() {
        const email = $('#login-email').val().trim();
        
        if (!email) {
            showWarningAlert({
                title: 'Correo requerido',
                text: 'Por favor ingresa tu correo electrónico primero.',
                confirmButtonText: 'Entendido'
            });
            $('#login-email').focus();
            return;
        }

        reenviarVerificacion(email);
    });

    function reenviarVerificacion(email) {
        const $btn = $('#btn-resend-verification');
        const originalText = $btn.html();
        
        $btn.prop('disabled', true).html('<i class="fa-solid fa-circle-notch fa-spin"></i> Enviando...');

        $.ajax({
            url: API_URL + '/auth/resend-verification',
            type: 'POST',
            contentType: 'application/json',
            dataType: 'json',
            data: JSON.stringify({ email: email }),
            success: function(res) {
                $btn.prop('disabled', false).html(originalText);
                
                showSuccessAlert({
                    title: '¡Correo enviado!',
                    text: res.message || 'Revisa tu bandeja de entrada (y spam).',
                    timer: 5000,
                    showConfirmButton: false,
                });
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html(originalText);
                
                let errorMsg = 'Error al reenviar el correo.';
                if (xhr.responseJSON && xhr.responseJSON.messages) {
                    errorMsg = typeof xhr.responseJSON.messages === 'string' ? 
                        xhr.responseJSON.messages : 
                        Object.values(xhr.responseJSON.messages).join('<br>');
                }

                showErrorAlert({
                    title: 'Error',
                    html: errorMsg,
                    confirmButtonText: 'Intentar de nuevo'
                });
            }
        });
    }

    // Limpiar errores al escribir
    $('#login-email, #login-password').on('input', function() {
        $(this).removeClass('input-error');
        $('#error-email, #error-password').addClass('d-none');
    });



    // ==========================================
    // MODAL OLVIDÉ MI CONTRASEÑA
    // ==========================================
    $('#form-forgot-password').on('submit', function(e) {
        e.preventDefault();

        const email = $('#forgot-email').val().trim();
        let isValid = true;

        $('#forgot-email').removeClass('input-error');
        $('#error-forgot-email').addClass('d-none').text('');
        $('#forgot-alert').addClass('d-none');

        if (email === '') {
            $('#forgot-email').addClass('input-error');
            $('#error-forgot-email').text('El correo electrónico es obligatorio.').removeClass('d-none');
            isValid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            $('#forgot-email').addClass('input-error');
            $('#error-forgot-email').text('Ingresa un formato de correo válido.').removeClass('d-none');
            isValid = false;
        }

        if (!isValid) return;

        const $btn = $(this).find('button[type="submit"]');
        const originalContent = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa-solid fa-circle-notch fa-spin"></i> <span>Enviando...</span>');

        $.ajax({
            url: API_URL + '/auth/forgot-password',
            type: 'POST',
            contentType: 'application/json',
            dataType: 'json',
            data: JSON.stringify({ email: email }),
            success: function(res) {
                $btn.prop('disabled', false).html(originalContent);

                // Personalizar mensaje según el tipo de correo enviado
                const titulo = res.tipo === 'verificacion' 
                    ? '¡Correo de verificación enviado!' 
                    : '¡Correo de recuperación enviado!';
                
                const texto = res.tipo === 'verificacion'
                    ? 'Tu cuenta aún no estaba verificada. Te enviamos un enlace para verificar tu correo. Revisa tu bandeja de entrada (y spam).'
                    : (res.message || 'Revisa tu bandeja de entrada (y spam) para restablecer tu contraseña.');

                showSuccessAlert({
                    title: titulo,
                    text: texto,
                    confirmButtonText: 'Entendido',
                    onConfirm: () => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('modalForgotPassword'));
                        modal.hide();
                        $('#form-forgot-password')[0].reset();
                    }
                });
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html(originalContent);

                let errorMsg = 'Error al enviar el correo.';
                if (xhr.responseJSON && xhr.responseJSON.messages) {
                    errorMsg = typeof xhr.responseJSON.messages === 'string' ? 
                        xhr.responseJSON.messages : 
                        Object.values(xhr.responseJSON.messages).join('<br>');
                }

                $('#forgot-alert .alert-message').text(errorMsg);
                $('#forgot-alert').removeClass('d-none');
            }
        });
    });

    // Limpiar errores al escribir en el modal
    $('#forgot-email').on('input', function() {
        $(this).removeClass('input-error');
        $('#error-forgot-email').addClass('d-none');
        $('#forgot-alert').addClass('d-none');
    });

});