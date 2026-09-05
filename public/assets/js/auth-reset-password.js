$(document).ready(function() {
    $('#form-reset-password').on('submit', function(e) {
        e.preventDefault();

        const token = $('#reset-token').val();
        const password = $('#reset-password').val().trim();
        const passwordConfirm = $('#reset-password-confirm').val().trim();
        let isValid = true;

        $('.input-custom').removeClass('input-error');
        $('#error-password, #error-password-confirm').addClass('d-none').text('');
        $('#reset-alert').addClass('d-none');

        if (password === '') {
            $('#reset-password').addClass('input-error');
            $('#error-password').text('La contraseña es obligatoria.').removeClass('d-none');
            isValid = false;
        } else {
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;
            if (!passwordRegex.test(password)) {
                $('#reset-password').addClass('input-error');
                $('#error-password').text('Debe tener mín. 8 caracteres, 1 mayúscula, 1 minúscula, 1 número y 1 carácter especial.').removeClass('d-none');
                isValid = false;
            }
        }

        if (passwordConfirm === '') {
            $('#reset-password-confirm').addClass('input-error');
            $('#error-password-confirm').text('Debes confirmar tu contraseña.').removeClass('d-none');
            isValid = false;
        } else if (password !== passwordConfirm) {
            $('#reset-password-confirm').addClass('input-error');
            $('#error-password-confirm').text('Las contraseñas no coinciden.').removeClass('d-none');
            isValid = false;
        }

        if (!isValid) return;

        const $btn = $('.btn-login');
        const originalContent = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa-solid fa-circle-notch fa-spin"></i> <span>Actualizando...</span>');

        $.ajax({
            url: API_URL + '/auth/reset-password',
            type: 'POST',
            contentType: 'application/x-www-form-urlencoded',
            dataType: 'json',
            data: {
                token: token,
                password: password,
                password_confirm: passwordConfirm
            },
            success: function(res) {
                $btn.html('<i class="fa-solid fa-check"></i> <span>¡Contraseña actualizada!</span>');
                $btn.css('background', 'linear-gradient(135deg, #28a745 0%, #218838 100%)');

                showSuccessAlert({
                    title: '¡Contraseña actualizada!',
                    text: res.message || 'Ya puedes iniciar sesión con tu nueva contraseña.',
                    confirmButtonText: 'Ir al Login',
                    onConfirm: () => {
                        window.location.href = API_URL + '/login';
                    }
                });
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html(originalContent);
                $btn.css('background', '');

                let errorMsg = 'Error al actualizar la contraseña.';
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
                    title: 'Error',
                    html: errorMsg,
                    confirmButtonText: 'Entendido'
                });
            }
        });
    });

    $('#reset-password, #reset-password-confirm').on('input', function() {
        $(this).removeClass('input-error');
        $('#error-password, #error-password-confirm').addClass('d-none');
    });
});