$(document).ready(function() {
    $('#form-login').on('submit', function(e) {
        e.preventDefault();

        const email = $('#login-email').val().trim();
        const password = $('#login-password').val().trim();
        let isValid = true;

        // Limpiar errores previos
        $('.form-control').removeClass('input-error');
        $('#error-email, #error-password').hide().text('');
        $('#login-alert').addClass('d-none');

        // Validar Correo
        if (email === '') {
            $('#login-email').addClass('input-error');
            $('#error-email').text('El correo electrónico es obligatorio.').show();
            isValid = false;
        } else {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                $('#login-email').addClass('input-error');
                $('#error-email').text('Ingresa un formato de correo válido.').show();
                isValid = false;
            }
        }

        // Validar Contraseña
        if (password === '') {
            $('#login-password').addClass('input-error');
            $('#error-password').text('La contraseña es obligatoria.').show();
            isValid = false;
        }

        if (!isValid) return;

        // Petición AJAX
        $.ajax({
            url: API_URL + '/auth/login',
            type: 'POST',
            contentType: 'application/json',
            dataType: 'json',
            data: JSON.stringify({
                email: email,
                password: password
            }),
            success: function(res) {
                localStorage.setItem('jwt_token', res.token);
                window.location.href = API_URL + '/dashboard';
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON && xhr.responseJSON.messages ?
                    (xhr.responseJSON.messages.error || xhr.responseJSON.messages) :
                    'Credenciales incorrectas o error en el servidor';

                $('#login-alert').text(typeof errorMsg === 'string' ? errorMsg : 'Error al iniciar sesión').removeClass('d-none');
            }
        });
    });
});