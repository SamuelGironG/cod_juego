document.addEventListener('DOMContentLoaded', () => {
    const nombre = document.getElementById('nombre');
    const correo = document.getElementById('correo');
    const contrasena = document.getElementById('contrasena');

    const errNombre = document.getElementById('err-nombre');
    const errCorreo = document.getElementById('err-correo');
    const errPassword = document.getElementById('err-password');

    // Validación para el nombre de usuario (si existe en la página)
    if (nombre && errNombre) {
        nombre.addEventListener('input', () => {
            const valor = nombre.value.trim();
            if (valor.length > 0 && /\s/.test(valor)) {
                errNombre.innerText = "El nombre de usuario no debe contener espacios.";
            } else {
                errNombre.innerText = "";
            }
        });
    }

    // Validación para el correo electrónico (si existe en la página)
    if (correo && errCorreo) {
        correo.addEventListener('input', () => {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (correo.value.length > 0 && !emailPattern.test(correo.value.trim())) {
                errCorreo.innerText = "Ingresa un correo electrónico válido.";
            } else {
                errCorreo.innerText = "";
            }
        });
    }

    // Validación para la contraseña (si existe en la página)
    if (contrasena && errPassword) {
        contrasena.addEventListener('input', () => {
            if (contrasena.value.length > 0 && contrasena.value.length < 8) {
                errPassword.innerText = "La contraseña debe tener al menos 8 caracteres.";
            } else {
                errPassword.innerText = "";
            }
        });
    }
});
document.addEventListener('DOMContentLoaded', () => {
    const cookieDialog = document.getElementById('cookieDialog');
    const btnAccept = document.getElementById('btnAccept');
    const btnDeny = document.getElementById('btnDeny');
    const btnCustomize = document.getElementById('btnCustomize');

    const cookieConsent = sessionStorage.getItem('cookieConsent');

    // Muestra el diálogo solo si el usuario no ha tomado ninguna decisión previa
    if (!cookieConsent) {
        cookieDialog.showModal();
    } else if (cookieConsent === 'accepted') {
        iniciarCookiesAnaliticas();
    }

    // Botón Aceptar
    btnAccept.addEventListener('click', () => {
        sessionStorage.setItem('cookieConsent', 'accepted');
        cookieDialog.close();
        iniciarCookiesAnaliticas();
    });

    // Botón Rechazar (Ya no es obligatorio; guarda el estado y cierra el diálogo)
    btnDeny.addEventListener('click', (e) => {
        e.preventDefault();
        sessionStorage.setItem('cookieConsent', 'denied');
        cookieDialog.close();
    });

    // Botón Personalizar
    btnCustomize.addEventListener('click', () => {
        alert('Aquí se abriría el panel de configuración detallado de cookies.');
    });

    function iniciarCookiesAnaliticas() {
        console.log('Consentimiento otorgado: Scripts de análisis inicializados.');
    }
});