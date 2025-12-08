// public/js/app.js
// Editor de lienzo VyR Producciones
// - Soporta plantillas P1..P10
// - Drag & drop de imágenes sobre cada marco
// - Botones + / - / carpeta en cada marco
// - Línea de doblez vertical discontinua
// - Guarda/carga usando process/lienzo_process.php

(function () {
    "use strict";

    // ==========================
    // CONFIGURACIÓN BÁSICA
    // ==========================

    const familiaId = (window.VYR_LIENZO && window.VYR_LIENZO.familiaId) ? window.VYR_LIENZO.familiaId : 0;
    const API_URL = "../process/lienzo_process.php";

    // Tamaño físico del lienzo (cm)
    const CM_WIDTH = 60.5;
    const CM_HEIGHT = 25.4;
    const ASPECT_RATIO = CM_WIDTH / CM_HEIGHT; // ~2.38

    // Conversión aproximada mm -> px (96 DPI aprox => 3.78 px/mm)
    const MM_TO_PX = 3.78;
    const SEPARACION_MM = 2;
    const SEPARACION_PX = SEPARACION_MM * MM_TO_PX; // ~7-8 px

    // ==========================
    // REFERENCIAS DOM
    // ==========================

    const canvas = document.getElementById("lienzoCanvas");
    const ctx = canvas.getContext("2d");
    const plantillasBar = document.getElementById("plantillasBar");
    const slotControlsContainer = document.getElementById("slotControlsContainer");
    const btnGuardar = document.getElementById("btnGuardarLienzo");
    const fileInputHidden = document.getElementById("fileInputHidden");

    if (!canvas || !ctx || !plantillasBar || !slotControlsContainer) {
        console.error("Editor de lienzo: elementos DOM no encontrados.");
        return;
    }

    // ==========================
    // DEFINICIÓN DE PLANTILLAS
    // ==========================

    // Cada plantilla define slots en coordenadas relativas [0..1] dentro del área útil.
    // hasMargin: indica si tiene margen blanco alrededor.
    const PLANTILLAS = [
        // P1: 4 fotos (2x2, sin margen)
        {
            id: 1, name: "P1", hasMargin: false,
            slots: [
                { x: 0,   y: 0,   w: 0.5, h: 0.5 },
                { x: 0.5, y: 0,   w: 0.5, h: 0.5 },
                { x: 0,   y: 0.5, w: 0.5, h: 0.5 },
                { x: 0.5, y: 0.5, w: 0.5, h: 0.5 }
            ]
        },
        // P2: 4 fotos con margen
        {
            id: 2, name: "P2", hasMargin: true,
            slots: [
                { x: 0,   y: 0,   w: 0.5, h: 0.5 },
                { x: 0.5, y: 0,   w: 0.5, h: 0.5 },
                { x: 0,   y: 0.5, w: 0.5, h: 0.5 },
                { x: 0.5, y: 0.5, w: 0.5, h: 0.5 }
            ]
        },
        // P3: 5 fotos (1 superior panorámica + 4 abajo)
        {
            id: 3, name: "P3", hasMargin: false,
            slots: [
                { x: 0,   y: 0,    w: 1.0, h: 0.4 },
                { x: 0,   y: 0.4,  w: 0.5, h: 0.3 },
                { x: 0.5, y: 0.4,  w: 0.5, h: 0.3 },
                { x: 0,   y: 0.7,  w: 0.5, h: 0.3 },
                { x: 0.5, y: 0.7,  w: 0.5, h: 0.3 }
            ]
        },
        // P4: 5 fotos con margen
        {
            id: 4, name: "P4", hasMargin: true,
            slots: [
                { x: 0,   y: 0,    w: 1.0, h: 0.4 },
                { x: 0,   y: 0.4,  w: 0.5, h: 0.3 },
                { x: 0.5, y: 0.4,  w: 0.5, h: 0.3 },
                { x: 0,   y: 0.7,  w: 0.5, h: 0.3 },
                { x: 0.5, y: 0.7,  w: 0.5, h: 0.3 }
            ]
        },
        // P5: 6 fotos (3x2 sin margen)
        {
            id: 5, name: "P5", hasMargin: false,
            slots: [
                { x: 0,    y: 0,   w: 1/3, h: 0.5 },
                { x: 1/3,  y: 0,   w: 1/3, h: 0.5 },
                { x: 2/3,  y: 0,   w: 1/3, h: 0.5 },
                { x: 0,    y: 0.5, w: 1/3, h: 0.5 },
                { x: 1/3,  y: 0.5, w: 1/3, h: 0.5 },
                { x: 2/3,  y: 0.5, w: 1/3, h: 0.5 }
            ]
        },
        // P6: 6 fotos con margen
        {
            id: 6, name: "P6", hasMargin: true,
            slots: [
                { x: 0,    y: 0,   w: 1/3, h: 0.5 },
                { x: 1/3,  y: 0,   w: 1/3, h: 0.5 },
                { x: 2/3,  y: 0,   w: 1/3, h: 0.5 },
                { x: 0,    y: 0.5, w: 1/3, h: 0.5 },
                { x: 1/3,  y: 0.5, w: 1/3, h: 0.5 },
                { x: 2/3,  y: 0.5, w: 1/3, h: 0.5 }
            ]
        },
        // P7: 7 fotos (2 arriba + 3 centro + 2 abajo)
        {
            id: 7, name: "P7", hasMargin: false,
            slots: [
                { x: 0,    y: 0,    w: 0.5, h: 0.33 },
                { x: 0.5,  y: 0,    w: 0.5, h: 0.33 },
                { x: 0,    y: 0.33, w: 1/3, h: 0.34 },
                { x: 1/3,  y: 0.33, w: 1/3, h: 0.34 },
                { x: 2/3,  y: 0.33, w: 1/3, h: 0.34 },
                { x: 0,    y: 0.67, w: 0.5, h: 0.33 },
                { x: 0.5,  y: 0.67, w: 0.5, h: 0.33 }
            ]
        },
        // P8: 7 fotos con otra distribución sin margen
        {
            id: 8, name: "P8", hasMargin: false,
            slots: [
                { x: 0,    y: 0,    w: 0.4, h: 0.5 },
                { x: 0.4,  y: 0,    w: 0.6, h: 0.25 },
                { x: 0.4,  y: 0.25, w: 0.6, h: 0.25 },
                { x: 0,    y: 0.5,  w: 0.33, h: 0.5 },
                { x: 0.33, y: 0.5,  w: 0.34, h: 0.5 },
                { x: 0.67, y: 0.5,  w: 0.33, h: 0.25 },
                { x: 0.67, y: 0.75, w: 0.33, h: 0.25 }
            ]
        },
        // P9: 8 fotos (4x2 sin margen)
        {
            id: 9, name: "P9", hasMargin: false,
            slots: (function(){
                const arr = [];
                for (let row=0; row<2; row++) {
                    for (let col=0; col<4; col++) {
                        arr.push({
                            x: col/4,
                            y: row/2,
                            w: 1/4,
                            h: 0.5
                        });
                    }
                }
                return arr;
            })()
        },
        // P10: 8 fotos con margen
        {
            id: 10, name: "P10", hasMargin: true,
            slots: (function(){
                const arr = [];
                for (let row=0; row<2; row++) {
                    for (let col=0; col<4; col++) {
                        arr.push({
                            x: col/4,
                            y: row/2,
                            w: 1/4,
                            h: 0.5
                        });
                    }
                }
                return arr;
            })()
        }
    ];

    // ==========================
    // ESTADO ACTUAL
    // ==========================

    const state = {
        plantillaId: 1,
        canvasWidth: 0,
        canvasHeight: 0,
        slots: [],       // se llena al seleccionar plantilla
        slotRects: [],   // posición real en px de cada slot
        draggingSlotIndex: null,
        dragStartX: 0,
        dragStartY: 0,
        dragStartOffsetX: 0,
        dragStartOffsetY: 0
    };

    // ==========================
    // UTILIDADES
    // ==========================

    function getPlantillaById(id) {
        return PLANTILLAS.find(p => p.id === id);
    }

    function resizeCanvasToContainer() {
        const wrapper = canvas.parentElement;
        if (!wrapper) return;

        const padding = 20; // espacio dentro del wrapper
        const maxWidth = wrapper.clientWidth - padding;
        const maxHeight = wrapper.clientHeight - padding;

        if (maxWidth <= 0 || maxHeight <= 0) return;

        // mantenemos relación 25.4 x 60.5
        let width = maxWidth;
        let height = width / ASPECT_RATIO;
        if (height > maxHeight) {
            height = maxHeight;
            width = height * ASPECT_RATIO;
        }

        canvas.width = Math.round(width);
        canvas.height = Math.round(height);

        state.canvasWidth = canvas.width;
        state.canvasHeight = canvas.height;

        // Re-dibujar
        updateSlotRects();
        drawCanvas();
        positionSlotControls();
    }

    function updateSlotRects() {
        const plantilla = getPlantillaById(state.plantillaId);
        if (!plantilla) return;
        const cw = state.canvasWidth;
        const ch = state.canvasHeight;

        let marginPx = plantilla.hasMargin ? SEPARACION_PX * 2 : 0; // margen alrededor
        const innerX = marginPx;
        const innerY = marginPx;
        const innerW = cw - marginPx * 2;
        const innerH = ch - marginPx * 2;

        state.slotRects = plantilla.slots.map(slot => {
            const x = innerX + slot.x * innerW;
            const y = innerY + slot.y * innerH;
            const w = slot.w * innerW;
            const h = slot.h * innerH;
            return { x, y, w, h };
        });

        // Si cambia el tamaño, mantener offsets relativos (no hacemos nada especial aquí, se verán algo diferentes al redimensionar)
    }

    function clearCanvas() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    }

    function drawCanvas() {
        clearCanvas();
        const cw = state.canvasWidth;
        const ch = state.canvasHeight;

        // Fondo gris
        ctx.fillStyle = "#4b5563";
        ctx.fillRect(0, 0, cw, ch);

        const plantilla = getPlantillaById(state.plantillaId);
        if (!plantilla) return;

        // Área blanca principal (con o sin margen)
        let marginPx = plantilla.hasMargin ? SEPARACION_PX * 2 : 0;
        ctx.fillStyle = "#ffffff";
        ctx.fillRect(marginPx, marginPx, cw - 2*marginPx, ch - 2*marginPx);

        // Línea de doblez vertical discontinua (solo en editor)
        ctx.save();
        ctx.setLineDash([10, 6]);
        ctx.strokeStyle = "#9ca3af";
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(cw / 2, marginPx);
        ctx.lineTo(cw / 2, ch - marginPx);
        ctx.stroke();
        ctx.restore();

        // Dibujar cada slot (marco blanco de separación y foto si existe)
        ctx.lineWidth = SEPARACION_PX;
        ctx.strokeStyle = "#ffffff";

        state.slotRects.forEach((rect, i) => {
            // Borde del slot para simular separación
            ctx.strokeRect(rect.x + SEPARACION_PX/2,
                           rect.y + SEPARACION_PX/2,
                           rect.w - SEPARACION_PX,
                           rect.h - SEPARACION_PX);

            const slot = state.slots[i];
            if (slot && slot.image) {
                // Dibujar imagen con escala y offset
                const img = slot.image;
                const scale = slot.scale;
                const offsetX = slot.offsetX;
                const offsetY = slot.offsetY;

                const imgW = img.width * scale;
                const imgH = img.height * scale;
                const centerX = rect.x + rect.w / 2 + offsetX;
                const centerY = rect.y + rect.h / 2 + offsetY;
                const drawX = centerX - imgW / 2;
                const drawY = centerY - imgH / 2;

                ctx.save();
                // Clip al rect del slot
                ctx.beginPath();
                ctx.rect(rect.x + SEPARACION_PX/2,
                         rect.y + SEPARACION_PX/2,
                         rect.w - SEPARACION_PX,
                         rect.h - SEPARACION_PX);
                ctx.clip();

                ctx.drawImage(img, drawX, drawY, imgW, imgH);

                ctx.restore();
            }
        });
    }

    function initStateForPlantilla(plantillaId) {
        const plantilla = getPlantillaById(plantillaId);
        if (!plantilla) return;

        const oldSlots = state.slots || [];
        const newSlots = [];

        for (let i = 0; i < plantilla.slots.length; i++) {
            if (oldSlots[i]) {
                newSlots.push(oldSlots[i]);
            } else {
                newSlots.push({
                    image: null,
                    imgSrc: null,
                    scale: 1,
                    offsetX: 0,
                    offsetY: 0
                });
            }
        }

        state.plantillaId = plantillaId;
        state.slots = newSlots;

        updateSlotRects();
        drawCanvas();
        buildSlotControls();
    }

    // ==========================
    // CONTROLES POR SLOT
    // ==========================

    function buildSlotControls() {
        slotControlsContainer.innerHTML = "";
        const rects = state.slotRects;

        rects.forEach((r, index) => {
            const div = document.createElement("div");
            div.className = "slot-controls";
            div.dataset.slotIndex = index;

            // Posicionar aprox. en esquina superior izquierda del slot
            div.style.left = (r.x + 8) + "px";
            div.style.top = (r.y + 8) + "px";

            const btnPlus = document.createElement("button");
            btnPlus.type = "button";
            btnPlus.className = "slot-btn slot-btn-plus";
            btnPlus.title = "Acercar";
            btnPlus.addEventListener("click", () => zoomSlot(index, 1.1));

            const btnMinus = document.createElement("button");
            btnMinus.type = "button";
            btnMinus.className = "slot-btn slot-btn-minus";
            btnMinus.title = "Alejar";
            btnMinus.addEventListener("click", () => zoomSlot(index, 0.9));

            const btnFolder = document.createElement("button");
            btnFolder.type = "button";
            btnFolder.className = "slot-btn slot-btn-folder";
            btnFolder.title = "Seleccionar imagen";
            btnFolder.addEventListener("click", () => openFileForSlot(index));

            div.appendChild(btnPlus);
            div.appendChild(btnMinus);
            div.appendChild(btnFolder);

            slotControlsContainer.appendChild(div);
        });
    }

    function positionSlotControls() {
        const rects = state.slotRects;
        const controls = slotControlsContainer.querySelectorAll(".slot-controls");
        controls.forEach(ctrl => {
            const idx = parseInt(ctrl.dataset.slotIndex, 10);
            const r = rects[idx];
            if (!r) return;
            ctrl.style.left = (r.x + 8) + "px";
            ctrl.style.top = (r.y + 8) + "px";
        });
    }

    function zoomSlot(index, factor) {
        const slot = state.slots[index];
        if (!slot || !slot.image) return;
        slot.scale *= factor;
        if (slot.scale < 0.1) slot.scale = 0.1;
        if (slot.scale > 10) slot.scale = 10;
        drawCanvas();
    }

    // ==========================
    // CARGA DE IMÁGENES
    // ==========================

    let currentSlotForFile = null;

    function openFileForSlot(index) {
        currentSlotForFile = index;
        fileInputHidden.value = "";
        fileInputHidden.click();
    }

    fileInputHidden.addEventListener("change", function (e) {
        const file = e.target.files && e.target.files[0];
        if (!file || currentSlotForFile === null) return;

        const reader = new FileReader();
        reader.onload = function (ev) {
            const img = new Image();
            img.onload = function () {
                setImageInSlot(currentSlotForFile, img, ev.target.result);
            };
            img.src = ev.target.result;
        };
        reader.readAsDataURL(file);
    });

    function setImageInSlot(index, img, src) {
        const rect = state.slotRects[index];
        if (!rect) return;

        // Calcular escala inicial para cubrir el slot (sin que ninguna dimensión sea menor que el marco)
        const scaleX = rect.w / img.width;
        const scaleY = rect.h / img.height;
        const scale = Math.max(scaleX, scaleY) * 1.1; // 10% más grande

        state.slots[index] = {
            image: img,
            imgSrc: src,
            scale: scale,
            offsetX: 0,
            offsetY: 0
        };

        drawCanvas();
    }

    // ==========================
    // DRAG & DROP DESDE DISCO
    // ==========================

    canvas.addEventListener("dragover", function (e) {
        e.preventDefault();
    });

    canvas.addEventListener("drop", function (e) {
        e.preventDefault();
        if (!e.dataTransfer || !e.dataTransfer.files || e.dataTransfer.files.length === 0) return;

        const rectCanvas = canvas.getBoundingClientRect();
        const x = e.clientX - rectCanvas.left;
        const y = e.clientY - rectCanvas.top;

        const slotIndex = findSlotIndexAtPoint(x, y);
        if (slotIndex === null) return;

        const file = e.dataTransfer.files[0];
        if (!file.type.startsWith("image/")) return;

        const reader = new FileReader();
        reader.onload = function (ev) {
            const img = new Image();
            img.onload = function () {
                setImageInSlot(slotIndex, img, ev.target.result);
            };
            img.src = ev.target.result;
        };
        reader.readAsDataURL(file);
    });

    function findSlotIndexAtPoint(x, y) {
        for (let i = 0; i < state.slotRects.length; i++) {
            const r = state.slotRects[i];
            if (x >= r.x && x <= r.x + r.w && y >= r.y && y <= r.y + r.h) {
                return i;
            }
        }
        return null;
    }

    // ==========================
    // MOVER IMAGEN DENTRO DEL SLOT
    // ==========================

    canvas.addEventListener("mousedown", function (e) {
        const rectCanvas = canvas.getBoundingClientRect();
        const x = e.clientX - rectCanvas.left;
        const y = e.clientY - rectCanvas.top;

        const idx = findSlotIndexAtPoint(x, y);
        if (idx === null) return;

        const slot = state.slots[idx];
        if (!slot || !slot.image) return;

        state.draggingSlotIndex = idx;
        state.dragStartX = x;
        state.dragStartY = y;
        state.dragStartOffsetX = slot.offsetX;
        state.dragStartOffsetY = slot.offsetY;
    });

    canvas.addEventListener("mousemove", function (e) {
        if (state.draggingSlotIndex === null) return;

        const rectCanvas = canvas.getBoundingClientRect();
        const x = e.clientX - rectCanvas.left;
        const y = e.clientY - rectCanvas.top;

        const dx = x - state.dragStartX;
        const dy = y - state.dragStartY;

        const slot = state.slots[state.draggingSlotIndex];
        slot.offsetX = state.dragStartOffsetX + dx;
        slot.offsetY = state.dragStartOffsetY + dy;

        drawCanvas();
    });

    canvas.addEventListener("mouseup", function () {
        state.draggingSlotIndex = null;
    });
    canvas.addEventListener("mouseleave", function () {
        state.draggingSlotIndex = null;
    });

    // ==========================
    // BARRA DE PLANTILLAS (miniaturas)
    // ==========================

    function buildPlantillasBar() {
        plantillasBar.innerHTML = "";
        PLANTILLAS.forEach(p => {
            const thumb = document.createElement("div");
            thumb.className = "plantilla-thumb";
            thumb.dataset.plantillaId = p.id;

            // Margen interno si corresponde
            let inner = thumb;
            if (p.hasMargin) {
                const marginDiv = document.createElement("div");
                marginDiv.className = "plantilla-thumb-inner-margin";
                thumb.appendChild(marginDiv);
                inner = marginDiv;
            }

            // Slots como pequeños bloques
            p.slots.forEach(s => {
                const slotDiv = document.createElement("div");
                slotDiv.className = "plantilla-thumb-slot";
                slotDiv.style.left = (s.x * 100) + "%";
                slotDiv.style.top = (s.y * 100) + "%";
                slotDiv.style.width = (s.w * 100) + "%";
                slotDiv.style.height = (s.h * 100) + "%";
                inner.appendChild(slotDiv);
            });

            thumb.addEventListener("click", () => {
                selectPlantilla(p.id);
            });

            plantillasBar.appendChild(thumb);
        });

        markSelectedPlantilla(state.plantillaId);
    }

    function markSelectedPlantilla(id) {
        const thumbs = plantillasBar.querySelectorAll(".plantilla-thumb");
        thumbs.forEach(t => {
            const pid = parseInt(t.dataset.plantillaId, 10);
            if (pid === id) t.classList.add("selected");
            else t.classList.remove("selected");
        });
    }

    function selectPlantilla(id) {
        initStateForPlantilla(id);
        markSelectedPlantilla(id);
    }

    // ==========================
    // GUARDAR / CARGAR LIENZO
    // ==========================

    function serializeLienzo() {
        const plantilla = getPlantillaById(state.plantillaId);
        if (!plantilla) return null;

        const datos = {
            plantilla_id: state.plantillaId,
            slots: state.slots.map((s, index) => {
                return {
                    index: index,
                    imgSrc: s.imgSrc || null,
                    scale: s.scale,
                    offsetX: s.offsetX,
                    offsetY: s.offsetY
                };
            })
        };
        return datos;
    }

    function guardarLienzo() {
        if (!familiaId) {
            alert("No se pudo identificar la familia. Inicie sesión nuevamente.");
            return;
        }

        const datosLienzo = serializeLienzo();
        if (!datosLienzo) {
            alert("No hay datos de lienzo para guardar.");
            return;
        }

        const payload = {
            usuario_familia_id: familiaId,
            plantilla_id: state.plantillaId,
            nombre: "Lienzo familia " + (window.VYR_LIENZO && window.VYR_LIENZO.nombreFamiliar ? window.VYR_LIENZO.nombreFamiliar : ""),
            datos_lienzo: datosLienzo
        };

        fetch(API_URL, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(j => {
            if (j.ok) {
                alert("Lienzo guardado correctamente.");
            } else {
                alert(j.message || "No se pudo guardar el lienzo.");
            }
        })
        .catch(() => {
            alert("Error al comunicarse con el servidor.");
        });
    }

    function cargarLienzo() {
        if (!familiaId) return;

        fetch(API_URL + "?usuario_familia_id=" + encodeURIComponent(familiaId))
            .then(r => r.json())
            .then(j => {
                if (!j.ok) return;
                if (!j.lienzo) {
                    // no hay lienzo guardado, se queda la plantilla por defecto
                    return;
                }

                const lienzo = j.lienzo;
                const plantillaId = lienzo.plantilla_id || lienzo.plantillaId || 1;
                const datos = lienzo.datos_lienzo || lienzo.datosLienzo || {};

                const plantilla = getPlantillaById(plantillaId) || getPlantillaById(1);
                state.plantillaId = plantilla.id;
                state.slots = [];

                const slotsSaved = (datos.slots || []);

                // Inicializar slots
                plantilla.slots.forEach((_, i) => {
                    const saved = slotsSaved.find(s => s.index === i);
                    if (saved && saved.imgSrc) {
                        const img = new Image();
                        img.onload = function () {
                            state.slots[i] = {
                                image: img,
                                imgSrc: saved.imgSrc,
                                scale: saved.scale || 1,
                                offsetX: saved.offsetX || 0,
                                offsetY: saved.offsetY || 0
                            };
                            drawCanvas();
                        };
                        img.src = saved.imgSrc;
                    } else {
                        state.slots[i] = {
                            image: null,
                            imgSrc: null,
                            scale: 1,
                            offsetX: 0,
                            offsetY: 0
                        };
                    }
                });

                updateSlotRects();
                drawCanvas();
                buildSlotControls();
                markSelectedPlantilla(state.plantillaId);
            })
            .catch(() => {
                console.warn("No se pudo cargar el lienzo guardado.");
            });
    }

    // ==========================
    // INIT
    // ==========================

    function init() {
        buildPlantillasBar();
        initStateForPlantilla(1); // P1 por defecto
        resizeCanvasToContainer();
        cargarLienzo();

        window.addEventListener("resize", () => {
            resizeCanvasToContainer();
        });

        if (btnGuardar) {
            btnGuardar.addEventListener("click", guardarLienzo);
        }
    }

    document.addEventListener("DOMContentLoaded", init);
})();
