// public/js/app.js

(function () {
    const cfg = window.LIENZO_CONFIG || {};
    const familiaId = cfg.familiaId || 0;

    const canvasInner = document.getElementById('canvasInner');
    const templatesBar = document.getElementById('templatesBar');
    const btnGuardar = document.getElementById('btnGuardarLienzo');
    const toastMsg = document.getElementById('toastMsg');

    if (!canvasInner || !templatesBar || !btnGuardar) {
        console.error('Editor de lienzo: elementos base no encontrados.');
        return;
    }

    // ============================
    // UTILIDAD PARA TOAST MENSAJES
    // ============================
    function showToast(msg, tipo = 'info') {
        if (!toastMsg) return;
        toastMsg.classList.remove('d-none', 'alert-info', 'alert-success', 'alert-danger', 'alert-warning');
        let clase = 'alert-info';
        if (tipo === 'ok') clase = 'alert-success';
        else if (tipo === 'error') clase = 'alert-danger';
        else if (tipo === 'warn') clase = 'alert-warning';
        toastMsg.classList.add(clase);
        toastMsg.textContent = msg;
        setTimeout(() => {
            toastMsg.classList.add('d-none');
        }, 4000);
    }

    // ======================================
    // DEFINICIÓN DE PLANTILLAS (10 layouts)
    // ======================================
    const TEMPLATES = [
        // P1: 4 fotos iguales (2x2, sin margen).
        {
            id: 1, nombre: 'P1',
            outerMargin: 0,
            slots: [
                { x: 0, y: 0,   w: 50, h: 50 },
                { x: 50, y: 0,  w: 50, h: 50 },
                { x: 0, y: 50,  w: 50, h: 50 },
                { x: 50, y: 50, w: 50, h: 50 }
            ]
        },
        // P2: margen completo; 4 fotos en rejilla interna.
        {
            id: 2, nombre: 'P2',
            outerMargin: 6,
            slots: [
                { x: 0,  y: 0,  w: 50, h: 50 },
                { x: 50, y: 0,  w: 50, h: 50 },
                { x: 0,  y: 50, w: 50, h: 50 },
                { x: 50, y: 50, w: 50, h: 50 }
            ]
        },
        // P3: 5 fotos, una grande izquierda y 4 en cuadrícula derecha.
        {
            id: 3, nombre: 'P3',
            outerMargin: 0,
            slots: [
                { x: 0,  y: 0,   w: 45, h: 100 }, // grande
                { x: 55, y: 0,   w: 45, h: 50 },
                { x: 55, y: 50,  w: 22.5, h: 50 },
                { x: 77.5, y: 50, w: 22.5, h: 25 },
                { x: 77.5, y: 75, w: 22.5, h: 25 }
            ]
        },
        // P4: margen superior/inferior, 4 fotos verticales iguales
        {
            id: 4, nombre: 'P4',
            outerMargin: 8,
            slots: [
                { x: 0,  y: 0, w: 25, h: 100 },
                { x: 25, y: 0, w: 25, h: 100 },
                { x: 50, y: 0, w: 25, h: 100 },
                { x: 75, y: 0, w: 25, h: 100 }
            ]
        },
        // P5: 6 fotos (2 filas x 3 columnas)
        {
            id: 5, nombre: 'P5',
            outerMargin: 0,
            slots: [
                { x: 0,     y: 0,   w: 33.33, h: 50 },
                { x: 33.33, y: 0,   w: 33.33, h: 50 },
                { x: 66.66, y: 0,   w: 33.34, h: 50 },
                { x: 0,     y: 50,  w: 33.33, h: 50 },
                { x: 33.33, y: 50,  w: 33.33, h: 50 },
                { x: 66.66, y: 50,  w: 33.34, h: 50 }
            ]
        },
        // P6: margen completo, 6 fotos (3x2) internas
        {
            id: 6, nombre: 'P6',
            outerMargin: 7,
            slots: [
                { x: 0,     y: 0,   w: 33.33, h: 50 },
                { x: 33.33, y: 0,   w: 33.33, h: 50 },
                { x: 66.66, y: 0,   w: 33.34, h: 50 },
                { x: 0,     y: 50,  w: 33.33, h: 50 },
                { x: 33.33, y: 50,  w: 33.33, h: 50 },
                { x: 66.66, y: 50,  w: 33.34, h: 50 }
            ]
        },
        // P7: 7 fotos (una grande arriba y 6 pequeñas abajo)
        {
            id: 7, nombre: 'P7',
            outerMargin: 0,
            slots: [
                { x: 0,    y: 0,  w: 100,   h: 45 },    // grande
                { x: 0,    y: 55, w: 16.66, h: 45 },
                { x: 16.66,y: 55, w: 16.66, h: 45 },
                { x: 33.32,y: 55, w: 16.66, h: 45 },
                { x: 49.98,y: 55, w: 16.66, h: 45 },
                { x: 66.64,y: 55, w: 16.66, h: 45 },
                { x: 83.30,y: 55, w: 16.70, h: 45 }
            ]
        },
        // P8: 8 fotos (4x2 sin margen)
        {
            id: 8, nombre: 'P8',
            outerMargin: 0,
            slots: [
                { x: 0,   y: 0,   w: 25, h: 50 },
                { x: 25,  y: 0,   w: 25, h: 50 },
                { x: 50,  y: 0,   w: 25, h: 50 },
                { x: 75,  y: 0,   w: 25, h: 50 },
                { x: 0,   y: 50,  w: 25, h: 50 },
                { x: 25,  y: 50,  w: 25, h: 50 },
                { x: 50,  y: 50,  w: 25, h: 50 },
                { x: 75,  y: 50,  w: 25, h: 50 }
            ]
        },
        // P9: 5 fotos (2 grandes + 3 pequeñas)
        {
            id: 9, nombre: 'P9',
            outerMargin: 0,
            slots: [
                { x: 0,     y: 0,   w: 50,    h: 60 },
                { x: 50,    y: 0,   w: 50,    h: 60 },
                { x: 0,     y: 60,  w: 33.33, h: 40 },
                { x: 33.33, y: 60,  w: 33.33, h: 40 },
                { x: 66.66, y: 60,  w: 33.34, h: 40 }
            ]
        },
        // P10: margen completo + mezcla
        {
            id: 10, nombre: 'P10',
            outerMargin: 7,
            slots: [
                { x: 0,   y: 0,   w: 40, h: 60 },
                { x: 40, y: 0,   w: 60, h: 30 },
                { x: 40, y: 30,  w: 30, h: 30 },
                { x: 70, y: 30,  w: 30, h: 30 },
                { x: 0,   y: 60, w: 25, h: 40 },
                { x: 25,  y: 60, w: 25, h: 40 },
                { x: 50,  y: 60, w: 25, h: 40 },
                { x: 75,  y: 60, w: 25, h: 40 }
            ]
        }
    ];

    let currentTemplateId = 1;
    let slotState = []; // {slotId, scale, offsetX, offsetY, imageSrc}

    // ========================================
    // CONSTRUIR BARRA DE PLANTILLAS (THUMBS)
    // ========================================
    function buildTemplateThumbs() {
        const existing = templatesBar.querySelectorAll('.template-thumb');
        existing.forEach(e => e.remove());

        TEMPLATES.forEach(t => {
            const thumb = document.createElement('div');
            thumb.className = 'template-thumb';
            thumb.dataset.templateId = t.id;

            const inner = document.createElement('div');
            inner.className = 'template-thumb-inner';

            // margen blanco para ciertas plantillas
            if (t.outerMargin > 0) {
                const margin = document.createElement('div');
                margin.className = 'thumb-margin';
                inner.appendChild(margin);
            }

            t.slots.forEach((s) => {
                const slotDiv = document.createElement('div');
                slotDiv.className = 'thumb-slot';
                const margin = t.outerMargin;
                const baseLeft = margin + (s.x * (100 - 2 * margin) / 100);
                const baseTop  = margin + (s.y * (100 - 2 * margin) / 100);
                const baseW    = (s.w * (100 - 2 * margin) / 100);
                const baseH    = (s.h * (100 - 2 * margin) / 100);
                slotDiv.style.left   = baseLeft + '%';
                slotDiv.style.top    = baseTop + '%';
                slotDiv.style.width  = baseW + '%';
                slotDiv.style.height = baseH + '%';
                inner.appendChild(slotDiv);
            });

            thumb.appendChild(inner);
            templatesBar.appendChild(thumb);

            thumb.addEventListener('click', () => {
                setCurrentTemplate(t.id);
            });
        });

        // seleccionar por defecto la primera
        setCurrentTemplate(currentTemplateId);
    }

    // ======================
    // RENDER DE UN TEMPLADO
    // ======================
    function setCurrentTemplate(id) {
        currentTemplateId = id;

        // marcar selección en thumbs
        templatesBar.querySelectorAll('.template-thumb').forEach(el => {
            const tId = parseInt(el.dataset.templateId, 10);
            el.classList.toggle('selected', tId === id);
        });

        const tpl = TEMPLATES.find(x => x.id === id);
        if (!tpl) return;

        slotState = [];

        // limpiar lienzo
        Array.from(canvasInner.querySelectorAll('.photo-slot')).forEach(el => el.remove());

        const outerMargin = tpl.outerMargin; // porcentaje
        tpl.slots.forEach((s, idx) => {
            const slot = document.createElement('div');
            slot.className = 'photo-slot';
            slot.dataset.slotId = String(idx);

            const left = outerMargin + (s.x * (100 - 2 * outerMargin) / 100);
            const top  = outerMargin + (s.y * (100 - 2 * outerMargin) / 100);
            const w    = (s.w * (100 - 2 * outerMargin) / 100);
            const h    = (s.h * (100 - 2 * outerMargin) / 100);

            slot.style.left   = left + '%';
            slot.style.top    = top + '%';
            slot.style.width  = w + '%';
            slot.style.height = h + '%';

            const inner = document.createElement('div');
            inner.className = 'photo-slot-inner';

            const img = document.createElement('img');
            img.className = 'slot-image';
            img.draggable = false;

            const controls = document.createElement('div');
            controls.className = 'slot-controls';

            const btnMinus = document.createElement('button');
            btnMinus.className = 'slot-btn';
            btnMinus.textContent = '−';
            btnMinus.dataset.title = 'Zoom -';

            const btnPlus = document.createElement('button');
            btnPlus.className = 'slot-btn';
            btnPlus.textContent = '+';
            btnPlus.dataset.title = 'Zoom +';

            const btnFolder = document.createElement('button');
            btnFolder.className = 'slot-btn';
            btnFolder.textContent = '📁';
            btnFolder.dataset.title = 'Cargar foto';

            controls.appendChild(btnMinus);
            controls.appendChild(btnPlus);
            controls.appendChild(btnFolder);

            inner.appendChild(img);
            inner.appendChild(controls);
            slot.appendChild(inner);

            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.accept = 'image/*';
            fileInput.className = 'hidden-input';
            slot.appendChild(fileInput);

            canvasInner.appendChild(slot);

            // estado inicial
            slotState.push({
                slotId: idx,
                scale: 1,
                offsetX: 0,
                offsetY: 0,
                imageSrc: null
            });

            btnPlus.addEventListener('click', (ev) => {
                ev.stopPropagation();
                changeZoom(idx, 1.1);
            });
            btnMinus.addEventListener('click', (ev) => {
                ev.stopPropagation();
                changeZoom(idx, 1 / 1.1);
            });
            btnFolder.addEventListener('click', (ev) => {
                ev.stopPropagation();
                fileInput.click();
            });

            fileInput.addEventListener('change', () => {
                if (fileInput.files && fileInput.files[0]) {
                    loadImageIntoSlot(idx, fileInput.files[0], img);
                }
            });

            // drag & drop directo
            slot.addEventListener('dragover', (ev) => {
                ev.preventDefault();
                slot.style.outline = '2px dashed #00bcd4';
            });
            slot.addEventListener('dragleave', () => {
                slot.style.outline = 'none';
            });
            slot.addEventListener('drop', (ev) => {
                ev.preventDefault();
                slot.style.outline = 'none';
                const file = ev.dataTransfer.files && ev.dataTransfer.files[0];
                if (file && file.type.startsWith('image/')) {
                    loadImageIntoSlot(idx, file, img);
                }
            });

            // arrastrar imagen dentro
            enableDragInside(img, idx);
        });
    }

    // =========================
    // CARGAR IMAGEN EN UN SLOT
    // =========================
    function loadImageIntoSlot(slotId, file, imgElement) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const src = e.target.result;
            imgElement.onload = function () {
                const st = slotState.find(s => s.slotId === slotId);
                if (!st) return;
                const slot = imgElement.closest('.photo-slot');
                if (!slot) return;

                const slotW = slot.clientWidth;
                const slotH = slot.clientHeight;
                const imgW  = imgElement.naturalWidth;
                const imgH  = imgElement.naturalHeight;

                if (imgW && imgH && slotW && slotH) {
                    // escala para cubrir al menos ~90% del slot
                    const scaleFit = 0.9 * Math.max(slotW / imgW, slotH / imgH);
                    st.scale = scaleFit;
                } else {
                    st.scale = 1;
                }

                st.offsetX = 0;
                st.offsetY = 0;
                st.imageSrc = src;
                applyTransform(imgElement, st);
            };
            imgElement.src = src;
        };
        reader.readAsDataURL(file);
    }

    // =====================
    // ZOOM EN UN SLOT
    // =====================
    function changeZoom(slotId, factor) {
        const st = slotState.find(s => s.slotId === slotId);
        if (!st) return;
        st.scale *= factor;
        // sin límite superior; solo mínimo para no desaparecer
        if (st.scale < 0.1) st.scale = 0.1;

        const imgElement = canvasInner.querySelector('.photo-slot[data-slot-id="' + slotId + '"] .slot-image');
        if (imgElement) {
            applyTransform(imgElement, st);
        }
    }

    // =====================
    // ARRASTRAR DENTRO SLOT
    // =====================
    function enableDragInside(img, slotId) {
        let dragging = false;
        let startX = 0, startY = 0;
        let originX = 0, originY = 0;

        img.addEventListener('mousedown', (ev) => {
            if (!img.src) return;
            dragging = true;
            startX = ev.clientX;
            startY = ev.clientY;
            const st = slotState.find(s => s.slotId === slotId);
            originX = st ? st.offsetX : 0;
            originY = st ? st.offsetY : 0;
            ev.preventDefault();
        });

        window.addEventListener('mousemove', (ev) => {
            if (!dragging) return;
            const dx = ev.clientX - startX;
            const dy = ev.clientY - startY;
            const st = slotState.find(s => s.slotId === slotId);
            if (!st) return;
            st.offsetX = originX + dx;
            st.offsetY = originY + dy;
            applyTransform(img, st);
        });

        window.addEventListener('mouseup', () => {
            dragging = false;
        });
    }

    function applyTransform(img, st) {
        img.style.transform =
            'translate(-50%, -50%) translate(' + st.offsetX + 'px,' + st.offsetY + 'px) scale(' + st.scale + ')';
    }

    // ==========================
    // CARGAR LIENZO EXISTENTE
    // ==========================
    function cargarLienzoServidor() {
        if (!familiaId) {
            console.warn('No hay familiaId en sesión');
            return;
        }
        fetch('../process/lienzo_process.php?usuario_familia_id=' + encodeURIComponent(familiaId), {
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        })
            .then(r => r.json())
            .then(j => {
                if (!j.ok) {
                    console.warn('No se pudo cargar lienzo:', j.message);
                    return;
                }
                if (!j.lienzo) {
                    // nada guardado aún
                    return;
                }
                const lienzo = j.lienzo;
                const tplId = lienzo.plantilla_id || currentTemplateId;
                setCurrentTemplate(tplId);
                if (lienzo.datos_lienzo && lienzo.datos_lienzo.slots) {
                    slotState = lienzo.datos_lienzo.slots.map(s => ({
                        slotId: s.slotId,
                        scale: s.scale || 1,
                        offsetX: s.offsetX || 0,
                        offsetY: s.offsetY || 0,
                        imageSrc: s.imageSrc || null
                    }));
                    // aplicar a DOM
                    slotState.forEach(st => {
                        const slot = canvasInner.querySelector('.photo-slot[data-slot-id="' + st.slotId + '"]');
                        if (!slot) return;
                        const img = slot.querySelector('.slot-image');
                        if (st.imageSrc) {
                            img.src = st.imageSrc;
                            // cuando cargue la imagen, aplicar transform
                            img.onload = function () {
                                applyTransform(img, st);
                            };
                        } else {
                            applyTransform(img, st);
                        }
                    });
                }
            })
            .catch(err => {
                console.error(err);
            });
    }

    // ============
    // GUARDAR
    // ============
    function guardarLienzo() {
        if (!familiaId) {
            showToast('No se reconoce la sesión de familia. Vuelva a iniciar sesión.', 'error');
            return;
        }
debugger;
        const payload = {
            usuario_familia_id: familiaId,
            plantilla_id: currentTemplateId,
            nombre: 'Lienzo principal',
            datos_lienzo: {
                plantilla_id: currentTemplateId,
                slots: slotState
            }
        };

        fetch('../process/lienzo_process.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json;charset=utf-8'
            },
            body: JSON.stringify(payload)
        })
            .then(r => r.json())
            .then(j => {
                if (j.ok) {
                    showToast('Lienzo guardado correctamente.', 'ok');
                } else {
                    showToast(j.message || 'No se pudo guardar el lienzo.', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Error de comunicación con el servidor.', 'error');
            });
    }

    // ======================
    // INICIALIZACIÓN
    // ======================
    document.addEventListener('DOMContentLoaded', () => {
        buildTemplateThumbs();           // crea las 10 plantillas
        cargarLienzoServidor();          // intenta recuperar desde BD
        btnGuardar.addEventListener('click', (e) => {
            e.preventDefault();
            guardarLienzo();
        });
    });

})();
