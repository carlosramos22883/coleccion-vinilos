$(document).ready(function() {
    $('#form-register').on('submit', function(e) {
        e.preventDefault();

        const nombre = $('#reg-nombre').val().trim();
        const email = $('#reg-email').val().trim();
        const password = $('#reg-password').val().trim();
        const passwordConfirm = $('#reg-password-confirm').val().trim();
        let isValid = true;

        // Limpiar errores previos
        $('.form-control').removeClass('input-error');
        $('#error-nombre, #error-email, #error-password, #error-password-confirm').hide().text('');
        $('#register-alert').addClass('d-none');

        // 1. Validar Nombre
        if (nombre === '') {
            $('#reg-nombre').addClass('input-error');
            $('#error-nombre').text('El nombre completo es obligatorio.').show();
            isValid = false;
        }

        // 2. Validar Correo
        if (email === '') {
            $('#reg-email').addClass('input-error');
            $('#error-email').text('El correo electrónico es obligatorio.').show();
            isValid = false;
        } else {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                $('#reg-email').addClass('input-error');
                $('#error-email').text('Ingresa un formato de correo válido.').show();
                isValid = false;
            }
        }

        // 3. Validar Contraseña
        if (password === '') {
            $('#reg-password').addClass('input-error');
            $('#error-password').text('La contraseña es obligatoria.').show();
            isValid = false;
        } else {
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;
            if (!passwordRegex.test(password)) {
                $('#reg-password').addClass('input-error');
                $('#error-password').text('Debe tener mín. 8 caracteres, 1 mayúscula, 1 minúscula, 1 número y 1 carácter especial.').show();
                isValid = false;
            }
        }

        // 4. Validar Confirmación de Contraseña
        if (passwordConfirm === '') {
            $('#reg-password-confirm').addClass('input-error');
            $('#error-password-confirm').text('Debes confirmar tu contraseña.').show();
            isValid = false;
        } else if (password !== passwordConfirm) {
            $('#reg-password-confirm').addClass('input-error');
            $('#error-password-confirm').text('Las contraseñas no coinciden.').show();
            isValid = false;
        }

        if (!isValid) return;

        const payload = {
            nombre: nombre,
            email: email,
            password: password
        };

        $.ajax({
            url: API_URL + '/auth/register',
            type: 'POST',
            contentType: 'application/json',
            dataType: 'json',
            data: JSON.stringify(payload),
            success: function(res) {
                // SWEETALERT2 EN LUGAR DEL ALERT NATIVO
                Swal.fire({
                    icon: 'success',
                    title: '¡Registro exitoso!',
                    text: 'Tu cuenta ha sido creada correctamente. Por favor, inicia sesión.',
                    confirmButtonColor: '#0d6efd',
                    confirmButtonText: 'Ir al Login'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = API_URL + '/login';
                    }
                });
            },
            error: function(xhr) {
                let errorMsg = 'Error al registrar el usuario.';
                if (xhr.responseJSON && xhr.responseJSON.messages) {
                    errorMsg = typeof xhr.responseJSON.messages === 'string' ?
                        xhr.responseJSON.messages :
                        Object.values(xhr.responseJSON.messages).join('\n');
                }
                
                // Opcional: También podemos mostrar los errores de servidor con SweetAlert si prefieres
                $('#register-alert').text(errorMsg).removeClass('d-none');
            }
        });
    });
});