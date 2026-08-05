document.addEventListener('DOMContentLoaded', () => {
    const cookieDialog = document.getElementById('cookieDialog');
    const cookieMainView = document.getElementById('cookieMainView');
    const cookieCustomizeView = document.getElementById('cookieCustomizeView');

    const btnAccept = document.getElementById('btnAccept');
    const btnDeny = document.getElementById('btnDeny');
    const btnCustomize = document.getElementById('btnCustomize');
    const btnSavePreferences = document.getElementById('btnSavePreferences');
    const btnBackToMain = document.getElementById('btnBackToMain');

    const checkAnalytics = document.getElementById('checkAnalytics');
    const checkAds = document.getElementById('checkAds');

    const cookieConsent = sessionStorage.getItem('cookieConsent');

    // 1. Comprobar estado inicial: Si ya hay consentimiento, no se abre el modal
    if (!cookieConsent) {
        cookieDialog.showModal();
    } else {
        aplicarConsentimientoGuardado(cookieConsent);
    }

    // 2. Botón Aceptar todas
    btnAccept.addEventListener('click', () => {
        const preferences = { analytics: true, ads: true };
        guardarYCerrar(preferences, 'accepted_all');
    });

    // 2. Botón Rechazar (excepto técnicas) - No guarda el estado para que vuelva a salir
    btnDeny.addEventListener('click', () => {
        const preferences = { analytics: false, ads: false };
        evaluarScripts(preferences);
        cookieDialog.close();
    });

    // 4. Botón Personalizar (Cambia de vista dentro del modal)
    btnCustomize.addEventListener('click', () => {
        cookieMainView.style.display = 'none';
        cookieCustomizeView.style.display = 'block';
    });

    btnBackToMain.addEventListener('click', () => {
        cookieCustomizeView.style.display = 'none';
        cookieMainView.style.display = 'block';
    });

    // 5. Botón Guardar preferencias personalizadas
    btnSavePreferences.addEventListener('click', () => {
        const preferences = {
            analytics: checkAnalytics.checked,
            ads: checkAds.checked
        };
        guardarYCerrar(preferences, 'customized');
    });

    // Función centralizada para guardar preferencias, cerrar y evitar que reaparezca
    function guardarYCerrar(preferences, tipo) {
        sessionStorage.setItem('cookieConsent', tipo);
        sessionStorage.setItem('cookiePreferences', JSON.stringify(preferences));
        cookieDialog.close();
        evaluarScripts(preferences);
    }

    // Aplicar según lo que ya estuviera guardado en la sesión
    function aplicarConsentimientoGuardado(tipo) {
        if (tipo === 'accepted_all') {
            evaluarScripts({ analytics: true, ads: true });
        } else if (tipo === 'customized') {
            const preferences = JSON.parse(sessionStorage.getItem('cookiePreferences') || '{}');
            evaluarScripts(preferences);
        }
    }

    function evaluarScripts(preferences) {
        if (preferences.analytics) {
            iniciarCookiesAnaliticas();
        }
        if (preferences.ads) {
            iniciarCookiesPublicitarias();
        }
    }

    function iniciarCookiesAnaliticas() {
        console.log('Google Analytics / Scripts analíticos inicializados.');
    }

    function iniciarCookiesPublicitarias() {
        console.log('Pixels de Publicidad / Scripts de marketing inicializados.');
    }
});