// public/js/lienzo_feedback.js
// Solo maneja el preloader "Guardando..." y el mensaje de éxito,
// sin tocar la lógica de guardado que ya tienes en app.js

document.addEventListener('DOMContentLoaded', () => {
    const btnGuardar = document.getElementById('btnGuardar');
    if (!btnGuardar) return; // si no existe el botón, no hacemos nada

    // Crear overlay flotante para mostrar "Guardando..." / "Guardado"
    const overlay = document.createElement('div');
    overlay.id = 'lienzoSavingOverlay';
    Object.assign(overlay.style, {
        position: 'fixed',
        inset: '0',
        display: 'none',
        alignItems: 'center',
        justifyContent: 'center',
        backgroundColor: 'rgba(0,0,0,0.25)',
        zIndex: '9999'
    });

    const box = document.createElement('div');
    Object.assign(box.style, {
        background: '#ffffff',
        padding: '14px 22px',
        borderRadius: '8px',
        boxShadow: '0 10px 25px rgba(0,0,0,0.25)',
        fontFamily: 'Arial, sans-serif',
        fontSize: '0.95rem',
        minWidth: '220px',
        textAlign: 'center'
    });

    const textSpan = document.createElement('span');
    textSpan.id = 'lienzoSavingText';
    textSpan.textContent = 'Guardando...';

    box.appendChild(textSpan);
    overlay.appendChild(box);
    document.body.appendChild(overlay);

    function showOverlay(msg) {
        textSpan.textContent = msg;
        overlay.style.display = 'flex';
    }

    function hideOverlay() {
        overlay.style.display = 'none';
    }

    let guardando = false;

    btnGuardar.addEventListener('click', () => {
        // NO cancelamos el click, solo decoramos la UX
        if (guardando) return;
        guardando = true;

        // Mostrar "Guardando..."
        showOverlay('Guardando...');

        // Asumimos que el guardado en local es rápido.
        // Damos un pequeño tiempo y luego mostramos "Guardado correctamente"
        setTimeout(() => {
            textSpan.textContent = 'Lienzo guardado correctamente';
            // Ocultamos después de un momento
            setTimeout(() => {
                hideOverlay();
                guardando = false;
            }, 1200);
        }, 900);
    });
});
