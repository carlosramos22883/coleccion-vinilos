/**
 * Perfil de Usuario - Viniloteca
 * Manejo de avatar con Croppie y formularios
 */

(function() {
    'use strict';

    let croppie = null;
    const modalCrop = new bootstrap.Modal(document.getElementById('modal-crop-image'));

    /* ==========================================
       1. MANEJO DE CROPPIE (AVATAR)
       ========================================== */
    document.getElementById('avatar-upload').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        if (!file.type.match('image.*')) {
            showAlert('error', 'Error', 'Por favor selecciona una imagen válida.');
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            showAlert('error', 'Error', 'La imagen no debe superar los 5MB.');
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            modalCrop.show();
            setTimeout(() => {
                initCroppie(e.target.result);
            }, 300);
        };
        reader.readAsDataURL(file);
    });

    function initCroppie(imageBase64) {
        if (croppie) {
            croppie.destroy();
        }

        const container = document.getElementById('crop-image-container');
        croppie = new Croppie(container, {
            viewport: { width: 300, height: 300, type: 'circle' },
            boundary: { width: 400, height: 400 },
            enableZoom: true,
            showZoomer: true,
            enableOrientation: false,
            mouseWheelZoom: true,
            enableResize: false
        });

        croppie.bind({ url: imageBase64, orientation: 1 });
    }

    document.getElementById('cancel-crop').addEventListener('click', function() {
        if (croppie) {
            croppie.destroy();
            croppie = null;
        }
        modalCrop.hide();
        document.getElementById('avatar-upload').value = '';
    });

    document.getElementById('save-crop').addEventListener('click', function() {
        if (!croppie) return;

        croppie.result({
            type: 'base64',
            size: { width: 400, height: 400 },
            format: 'png',
            quality: 0.9
        }).then(function(base64) {
            uploadAvatar(base64);
        });
    });

    function uploadAvatar(base64Image) {
        const formData = new FormData();
        formData.append('image', base64Image);

        fetch(API_URL + 'perfil/upload-avatar', {
            method: 'POST',
            headers: { 'Authorization': 'Bearer ' + localStorage.getItem('jwt_token') },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('avatar-preview').src = data.avatar_url;
                updateAvatarEverywhere(data.avatar_url);
                
                if (croppie) {
                    croppie.destroy();
                    croppie = null;
                }
                modalCrop.hide();
                showAlert('success', '¡Éxito!', 'Foto de perfil actualizada correctamente.');
            } else {
                showAlert('error', 'Error', data.message || 'Error al subir la imagen.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'Error', 'Error de conexión al subir la imagen.');
        });
    }

    function updateAvatarEverywhere(avatarUrl) {
        const topbarAvatar = document.getElementById('user-avatar');
        const sidebarAvatar = document.getElementById('sidebar-user-avatar');
        if (topbarAvatar) topbarAvatar.src = avatarUrl;
        if (sidebarAvatar) sidebarAvatar.src = avatarUrl;
    }

    /* ==========================================
       2. FORMULARIO DE ACTUALIZACIÓN DE PERFIL
       ========================================== */
    document.getElementById('profile-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const nombre = document.getElementById('nombre').value.trim();
        const email = document.getElementById('email').value.trim();
        let hasErrors = false;

        document.querySelectorAll('.invalid-feedback').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.form-control').forEach(el => el.classList.remove('is-invalid'));

        if (nombre.length < 3) {
            showError('nombre', 'El nombre debe tener al menos 3 caracteres');
            hasErrors = true;
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showError('email', 'Ingresa un correo electrónico válido');
            hasErrors = true;
        }

        if (!hasErrors) {
            submitProfileForm(this, nombre, email);
        }
    });

    function showError(fieldId, message) {
        const field = document.getElementById(fieldId);
        field.classList.add('is-invalid');
        let errorDiv = field.parentElement.querySelector('.invalid-feedback');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            errorDiv.style.display = 'block';
            field.parentElement.appendChild(errorDiv);
        }
        errorDiv.textContent = message;
    }

    function submitProfileForm(form, nombre, email) {
        const formData = new FormData(form);
        const userId = form.dataset.userId;

        // 1. Verificar si el email ya existe
        fetch(API_URL + 'perfil/check-email', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('jwt_token'),
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email: email, user_id: userId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.exists) {
                showError('email', 'Este correo electrónico ya está en uso por otro usuario');
                return;
            }

            // 2. Si no existe, enviar la actualización
            return fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: { 'Authorization': 'Bearer ' + localStorage.getItem('jwt_token') }
            }).then(res => res.json());
        })
        .then(data => {
            if (!data) return; // Si el email existía, salimos temprano

            if (data.status === 200) {
                // Actualizar nombre en la interfaz
                const userNameEl = document.getElementById('user-name');
                const sidebarUserNameEl = document.getElementById('sidebar-user-name');
                if (userNameEl) userNameEl.textContent = nombre;
                if (sidebarUserNameEl) sidebarUserNameEl.textContent = nombre;
                
                // 3. VERIFICAR SI SE CAMBIÓ EL EMAIL (Mensaje del servidor)
                if (data.message && data.message.includes('correo de verificación')) {
                    // Limpiar sesión del cliente
                    localStorage.removeItem('jwt_token');
                    sessionStorage.clear();
                    
                    showAlert('success', '¡Correo actualizado!', 
                        'Debido al cambio de correo electrónico, debes verificar tu nueva dirección. ' +
                        'Te hemos enviado un correo de verificación. Por favor revisa tu bandeja de entrada e inicia sesión nuevamente.')
                    .then(() => {
                        window.location.href = API_URL + 'login';
                    });
                } else {
                    showAlert('success', '¡Éxito!', data.message || 'Perfil actualizado correctamente.');
                }
            } else {
                showAlert('error', 'Error', data.error || 'Error al actualizar el perfil.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'Error', 'Error de conexión al actualizar el perfil.');
        });
    }

    /* ==========================================
       3. FORMULARIO DE CAMBIO DE CONTRASEÑA
       ========================================== */
    document.getElementById('form-change-password').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const passwordActual = this.querySelector('[name="password_actual"]').value;
        const passwordNuevo = this.querySelector('[name="password_nuevo"]').value;
        const passwordConfirm = this.querySelector('[name="password_confirm"]').value;
        
        let errors = [];

        if (!passwordActual) errors.push('La contraseña actual es obligatoria');
        
        if (!passwordNuevo) {
            errors.push('La nueva contraseña es obligatoria');
        } else {
            errors = errors.concat(validatePasswordStrength(passwordNuevo));
        }

        if (passwordNuevo !== passwordConfirm) {
            errors.push('Las contraseñas no coinciden');
        }

        if (errors.length > 0) {
            showAlert('warning', 'Error de validación', errors.join('<br>'));
            return;
        }

        const formData = new FormData(this);
        
        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: { 'Authorization': 'Bearer ' + localStorage.getItem('jwt_token') }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 200) {
                showAlert('success', '¡Éxito!', data.message || 'Contraseña actualizada exitosamente');
                bootstrap.Modal.getInstance(document.getElementById('modal-change-password')).hide();
                this.reset();
            } else {
                showAlert('error', 'Error', data.error || 'Error al cambiar la contraseña.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'Error', 'Error de conexión al cambiar la contraseña.');
        });
    });

    /* ==========================================
       4. HELPERS
       ========================================== */
    function validatePasswordStrength(password) {
        const errors = [];
        if (password.length < 8) errors.push('La contraseña debe tener al menos 8 caracteres');
        if (!/[A-Z]/.test(password)) errors.push('Debe contener al menos una letra mayúscula');
        if (!/[a-z]/.test(password)) errors.push('Debe contener al menos una letra minúscula');
        if (!/[0-9]/.test(password)) errors.push('Debe contener al menos un número');
        if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) errors.push('Debe contener al menos un carácter especial');
        return errors;
    }

    function showAlert(icon, title, text) {
        if (typeof Swal !== 'undefined') {
            return Swal.fire({
                icon: icon,
                title: title,
                text: text,
                confirmButtonColor: '#F28C28'
            });
        } else {
            alert(title + ': ' + text);
        }
    }

})();