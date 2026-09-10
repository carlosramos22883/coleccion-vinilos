/**
 * Perfil de Usuario - Viniloteca
 * Manejo de avatar con Croppie y formularios
 */
(function() {
    'use strict';

    let croppie = null;
    const modalCrop = new bootstrap.Modal(document.getElementById('modal-crop-image'));

    document.getElementById('avatar-upload').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        if (!file.type.match('image.*')) {
            showErrorAlert({ title: 'Error', text: 'Por favor selecciona una imagen válida.' });
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            showErrorAlert({ title: 'Error', text: 'La imagen no debe superar los 5MB.' });
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            modalCrop.show();
            setTimeout(() => { initCroppie(e.target.result); }, 300);
        };
        reader.readAsDataURL(file);
    });

    function initCroppie(imageBase64) {
        if (croppie) croppie.destroy();
        const container = document.getElementById('crop-image-container');
        croppie = new Croppie(container, {
            viewport: { width: 300, height: 300, type: 'circle' },
            boundary: { width: 400, height: 400 },
            enableZoom: true, showZoomer: true, enableOrientation: false, mouseWheelZoom: true, enableResize: false
        });
        croppie.bind({ url: imageBase64, orientation: 1 });
    }

    document.getElementById('cancel-crop').addEventListener('click', function() {
        if (croppie) { croppie.destroy(); croppie = null; }
        modalCrop.hide();
        document.getElementById('avatar-upload').value = '';
    });

    document.getElementById('save-crop').addEventListener('click', function() {
        if (!croppie) return;
        croppie.result({ type: 'base64', size: { width: 400, height: 400 }, format: 'png', quality: 0.9 })
        .then(function(base64) { uploadAvatar(base64); });
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
                if (croppie) { croppie.destroy(); croppie = null; }
                modalCrop.hide();
                showSuccessAlert({ title: '¡Éxito!', text: 'Foto de perfil actualizada correctamente.' });
            } else {
                showErrorAlert({ title: 'Error', text: data.message || 'Error al subir la imagen.' });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorAlert({ title: 'Error', text: 'Error de conexión al subir la imagen.' });
        });
    }

    function updateAvatarEverywhere(avatarUrl) {
        const topbarAvatar = document.getElementById('user-avatar');
        const sidebarAvatar = document.getElementById('sidebar-user-avatar');
        if (topbarAvatar) topbarAvatar.src = avatarUrl;
        if (sidebarAvatar) sidebarAvatar.src = avatarUrl;
    }

    document.getElementById('profile-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const nombre = document.getElementById('nombre').value.trim();
        const email = document.getElementById('email').value.trim();
        let hasErrors = false;

        document.querySelectorAll('.invalid-feedback').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.form-control').forEach(el => el.classList.remove('is-invalid'));

        if (nombre.length < 3) { showError('nombre', 'El nombre debe tener al menos 3 caracteres'); hasErrors = true; }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showError('email', 'Ingresa un correo electrónico válido'); hasErrors = true; }

        if (!hasErrors) submitProfileForm(this, nombre, email);
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

        fetch(API_URL + 'perfil/check-email', {
            method: 'POST',
            headers: { 'Authorization': 'Bearer ' + localStorage.getItem('jwt_token'), 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: email, user_id: userId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.exists) {
                showError('email', 'Este correo electrónico ya está en uso por otro usuario');
                return;
            }
            return fetch(form.action, { method: 'POST', body: formData, headers: { 'Authorization': 'Bearer ' + localStorage.getItem('jwt_token') } }).then(res => res.json());
        })
        .then(data => {
            if (!data) return;
            if (data.status === 200) {
                const userNameEl = document.getElementById('user-name');
                const sidebarUserNameEl = document.getElementById('sidebar-user-name');
                if (userNameEl) userNameEl.textContent = nombre;
                if (sidebarUserNameEl) sidebarUserNameEl.textContent = nombre;
                
                if (data.message && data.message.includes('correo de verificación')) {
                    localStorage.removeItem('jwt_token');
                    sessionStorage.clear();
                    showSuccessAlert({
                        title: '¡Correo actualizado!',
                        text: 'Debido al cambio de correo, debes verificar tu nueva dirección. Revisa tu bandeja de entrada.',
                        onConfirm: () => { window.location.href = API_URL + 'login'; }
                    });
                } else {
                    showSuccessAlert({ title: '¡Éxito!', text: data.message || 'Perfil actualizado correctamente.' });
                }
            } else {
                showErrorAlert({ title: 'Error', text: data.error || 'Error al actualizar el perfil.' });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorAlert({ title: 'Error', text: 'Error de conexión al actualizar el perfil.' });
        });
    }

    document.getElementById('form-change-password').addEventListener('submit', function(e) {
        e.preventDefault();
        clearPasswordErrors();
        
        const passwordActual = this.querySelector('[name="password_actual"]').value;
        const passwordNuevo = this.querySelector('[name="password_nuevo"]').value;
        const passwordConfirm = this.querySelector('[name="password_confirm"]').value;
        let hasErrors = false;

        if (!passwordActual) { showFieldError('password_actual', 'La contraseña actual es obligatoria'); hasErrors = true; }
        if (!passwordNuevo) { showFieldError('password_nuevo', 'La nueva contraseña es obligatoria'); hasErrors = true; }
        else {
            const passwordErrors = validatePasswordStrength(passwordNuevo);
            if (passwordErrors.length > 0) { showFieldError('password_nuevo', passwordErrors.join('<br>')); hasErrors = true; }
        }
        if (passwordNuevo !== passwordConfirm) { showFieldError('password_confirm', 'Las contraseñas no coinciden'); hasErrors = true; }

        if (hasErrors) return;

        const formData = new FormData(this);
        fetch(this.action, { method: 'POST', body: formData, headers: { 'Authorization': 'Bearer ' + localStorage.getItem('jwt_token') } })
        .then(response => response.json())
        .then(data => {
            if (data.status === 200) {
                showSuccessAlert({ title: '¡Éxito!', text: data.message || 'Contraseña actualizada exitosamente' });
                bootstrap.Modal.getInstance(document.getElementById('modal-change-password')).hide();
                this.reset();
                clearPasswordErrors();
            } else {
                if (data.error) showErrorAlert({ title: 'Error', text: data.error });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorAlert({ title: 'Error', text: 'Error de conexión al cambiar la contraseña.' });
        });
    });

    document.getElementById('modal-change-password').addEventListener('hidden.bs.modal', function() {
        const form = document.getElementById('form-change-password');
        form.reset();
        clearPasswordErrors();
    });

    ['password_actual', 'password_nuevo', 'password_confirm'].forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', function() {
                this.classList.remove('is-invalid');
                const feedback = this.parentElement.querySelector('.invalid-feedback');
                if (feedback) { feedback.classList.remove('show'); feedback.textContent = ''; }
            });
        }
    });

    function showFieldError(fieldId, message) {
        const field = document.getElementById(fieldId);
        if (!field) return;
        field.classList.add('is-invalid');
        const feedback = field.parentElement.querySelector('.invalid-feedback');
        if (feedback) { feedback.innerHTML = message; feedback.classList.add('show'); }
    }

    function clearPasswordErrors() {
        ['password_actual', 'password_nuevo', 'password_confirm'].forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.classList.remove('is-invalid');
                const feedback = field.parentElement.querySelector('.invalid-feedback');
                if (feedback) { feedback.classList.remove('show'); feedback.textContent = ''; }
            }
        });
    }

    function validatePasswordStrength(password) {
        const errors = [];
        if (password.length < 8) errors.push('La contraseña debe tener al menos 8 caracteres');
        if (!/[A-Z]/.test(password)) errors.push('Debe contener al menos una letra mayúscula');
        if (!/[a-z]/.test(password)) errors.push('Debe contener al menos una letra minúscula');
        if (!/[0-9]/.test(password)) errors.push('Debe contener al menos un número');
        if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) errors.push('Debe contener al menos un carácter especial');
        return errors;
    }

    document.getElementById('form-delete-account').addEventListener('submit', function(e) {
        e.preventDefault();
        clearDeleteErrors();
        
        const password = document.getElementById('delete-password').value;
        if (!password) { showDeleteError('delete-password', 'La contraseña es obligatoria'); return; }

        showConfirmAlert({
            title: '¿Eliminar cuenta permanentemente?',
            text: 'Esta acción no se puede deshacer. Todos tus datos serán eliminados.',
            confirmButtonText: 'Sí, eliminar cuenta',
            cancelButtonText: 'Cancelar',
            onConfirm: () => {
                const formData = new FormData(this);
                fetch(this.action, { method: 'POST', body: formData, headers: { 'Authorization': 'Bearer ' + localStorage.getItem('jwt_token') } })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 200) {
                        localStorage.removeItem('jwt_token');
                        sessionStorage.clear();
                        showSuccessAlert({
                            title: 'Cuenta eliminada',
                            text: 'Tu cuenta ha sido eliminada correctamente. Serás redirigido al login.',
                            onConfirm: () => { window.location.href = API_URL + 'login'; }
                        });
                    } else {
                        showDeleteError('delete-password', data.error || 'Error al eliminar la cuenta');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorAlert({ title: 'Error', text: 'Error de conexión al eliminar la cuenta.' });
                });
            }
        });
    });

    const deletePasswordField = document.getElementById('delete-password');
    if (deletePasswordField) {
        deletePasswordField.addEventListener('input', function() {
            this.classList.remove('is-invalid');
            const feedback = this.parentElement.querySelector('.invalid-feedback');
            if (feedback) { feedback.classList.remove('show'); feedback.textContent = ''; }
        });
    }

    document.getElementById('modal-delete-account').addEventListener('hidden.bs.modal', function() {
        const form = document.getElementById('form-delete-account');
        form.reset();
        clearDeleteErrors();
    });

    function showDeleteError(fieldId, message) {
        const field = document.getElementById(fieldId);
        if (!field) return;
        field.classList.add('is-invalid');
        const feedback = field.parentElement.querySelector('.invalid-feedback');
        if (feedback) { feedback.textContent = message; feedback.classList.add('show'); }
    }

    function clearDeleteErrors() {
        const field = document.getElementById('delete-password');
        if (field) {
            field.classList.remove('is-invalid');
            const feedback = field.parentElement.querySelector('.invalid-feedback');
            if (feedback) { feedback.classList.remove('show'); feedback.textContent = ''; }
        }
    }    
})();