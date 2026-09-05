$(document).ready(function() {
    $('#form-register').on('submit', function(e) {
        e.preventDefault();

        const nombre = $('#reg-nombre').val().trim();
        const email = $('#reg-email').val().trim();
        const password = $('#reg-password').val().trim();
        const passwordConfirm = $('#reg-password-confirm').val().trim();
        let isValid = true;

        // Limpiar errores previos
        $('.input-custom').removeClass('input-error');
        $('#error-nombre, #error-email, #error-password, #error-password-confirm').addClass('d-none').text('');

        // Validaciones...
        if (nombre === '') {
            $('#reg-nombre').addClass('input-error');
            $('#error-nombre').text('El nombre completo es obligatorio.').removeClass('d-none');
            isValid = false;
        } else if (nombre.length < 2) {
            $('#reg-nombre').addClass('input-error');
            $('#error-nombre').text('El nombre debe tener al menos 2 caracteres.').removeClass('d-none');
            isValid = false;
        }

        // 2. Validar Correo
        if (email === '') {
            $('#reg-email').addClass('input-error');
            $('#error-email').text('El correo electrónico es obligatorio.').removeClass('d-none');
            isValid = false;
        } else {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                $('#reg-email').addClass('input-error');
                $('#error-email').text('Ingresa un formato de correo válido.').removeClass('d-none');
                isValid = false;
            }
        }

        // 3. Validar Contraseña
        if (password === '') {
            $('#reg-password').addClass('input-error');
            $('#error-password').text('La contraseña es obligatoria.').removeClass('d-none');
            isValid = false;
        } else {
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;
            if (!passwordRegex.test(password)) {
                $('#reg-password').addClass('input-error');
                $('#error-password').text('Debe tener mín. 8 caracteres, 1 mayúscula, 1 minúscula, 1 número y 1 carácter especial.').removeClass('d-none');
                isValid = false;
            }
        }

        // 4. Validar Confirmación de Contraseña
        if (passwordConfirm === '') {
            $('#reg-password-confirm').addClass('input-error');
            $('#error-password-confirm').text('Debes confirmar tu contraseña.').removeClass('d-none');
            isValid = false;
        } else if (password !== passwordConfirm) {
            $('#reg-password-confirm').addClass('input-error');
            $('#error-password-confirm').text('Las contraseñas no coinciden.').removeClass('d-none');
            isValid = false;
        }

        if (!isValid) return;

        // Animación de carga en el botón
        const $btn = $('.btn-login');
        const originalContent = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa-solid fa-circle-notch fa-spin"></i> <span>Registrando...</span>');

        const payload = { nombre, email, password };

        $.ajax({
            url: API_URL + '/auth/register',
            type: 'POST',
            contentType: 'application/json',
            dataType: 'json',
            data: JSON.stringify(payload),
            success: function(res) {
                $btn.html('<i class="fa-solid fa-check"></i> <span>¡Registro exitoso!</span>');
                $btn.css('background', 'linear-gradient(135deg, #28a745 0%, #218838 100%)');

                // ¡AHORA ASÍ DE FÁCIL!
                showSuccessAlert({
                    title: '¡Registro exitoso!',
                    text: 'Tu cuenta ha sido creada correctamente. Por favor, revisa tu correo para verificar tu cuenta.',
                    confirmButtonText: 'Ir al Login',
                    onConfirm: () => {
                        window.location.href = API_URL + '/login';
                    }
                });
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html(originalContent);
                $btn.css('background', '');

                let errorMsg = 'Error al registrar el usuario.';
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.messages) {
                        errorMsg = typeof xhr.responseJSON.messages === 'string' ? 
                            xhr.responseJSON.messages : 
                            Object.values(xhr.responseJSON.messages).join('<br>');
                    } else if (xhr.responseJSON.error) {
                        errorMsg = xhr.responseJSON.error;
                    }
                }

                // ¡TAMBIÉN ASÍ DE FÁCIL!
                showErrorAlert({
                    title: 'Error al registrar',
                    html: errorMsg
                });
            }
        });
    });

    // Limpiar errores al escribir
    $('#reg-nombre, #reg-email, #reg-password, #reg-password-confirm').on('input', function() {
        $(this).removeClass('input-error');
        $('#error-nombre, #error-email, #error-password, #error-password-confirm').addClass('d-none');
    });
});