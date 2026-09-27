/**
 * signature-pad.js
 *
 * Lienzo para firmar a mano alzada: dedo o lápiz en pantallas táctiles,
 * mouse en computador. Se usa en el formulario público (firma de quien
 * realizó la actividad) y en el modal de descarga del formato RA-F-33
 * (firma de quien la recibe).
 *
 * Usa Pointer Events, que unifican mouse, toque y lápiz. El lienzo tiene
 * `touch-action: none` en CSS para que firmar no desplace la página.
 *
 * El fondo queda transparente: el servidor recorta la imagen al trazo y
 * la estampa sobre la línea "FIRMA" del formato.
 *
 * Uso:
 *   const pad = new SignaturePad(canvas, { onChange: (empty) => ... });
 *   pad.isEmpty(); pad.clear(); pad.toDataURL();
 */
(() => {
    'use strict';

    if (window.SignaturePad) return;

    class SignaturePad {
        constructor(canvas, options = {}) {
            this.canvas = canvas;
            this.context = canvas.getContext('2d');
            this.onChange = options.onChange || (() => {});
            this.color = options.color || '#0f172a';
            this.width = options.width || 2.4;

            // Los trazos se guardan en coordenadas CSS para poder
            // redibujarlos si el lienzo cambia de tamaño (rotar el celular).
            this.strokes = [];
            this.current = null;

            this.handleDown = this.handleDown.bind(this);
            this.handleMove = this.handleMove.bind(this);
            this.handleUp = this.handleUp.bind(this);
            this.resize = this.resize.bind(this);

            canvas.addEventListener('pointerdown', this.handleDown);
            canvas.addEventListener('pointermove', this.handleMove);
            canvas.addEventListener('pointerup', this.handleUp);
            canvas.addEventListener('pointercancel', this.handleUp);
            canvas.addEventListener('pointerleave', this.handleUp);

            if (typeof ResizeObserver !== 'undefined') {
                this.observer = new ResizeObserver(this.resize);
                this.observer.observe(canvas);
            } else {
                window.addEventListener('resize', this.resize);
            }

            this.resize();
        }

        /**
         * Ajusta la resolución interna al tamaño en pantalla y a la
         * densidad de píxeles, para que el trazo no se vea borroso.
         */
        resize() {
            const rect = this.canvas.getBoundingClientRect();

            if (rect.width === 0 || rect.height === 0) return;

            const ratio = Math.max(window.devicePixelRatio || 1, 1);

            this.canvas.width = Math.round(rect.width * ratio);
            this.canvas.height = Math.round(rect.height * ratio);
            this.context.setTransform(ratio, 0, 0, ratio, 0, 0);
            this.redraw();
        }

        point(event) {
            const rect = this.canvas.getBoundingClientRect();

            return { x: event.clientX - rect.left, y: event.clientY - rect.top };
        }

        handleDown(event) {
            if (event.button !== undefined && event.button > 0) return;

            event.preventDefault();
            this.canvas.setPointerCapture?.(event.pointerId);

            this.current = [this.point(event)];
            this.strokes.push(this.current);
            this.drawDot(this.current[0]);
        }

        handleMove(event) {
            if (!this.current) return;

            event.preventDefault();

            // Algunos navegadores agrupan varios puntos por evento: usarlos
            // todos da un trazo más fiel en movimientos rápidos.
            const events = event.getCoalescedEvents ? event.getCoalescedEvents() : [event];

            events.forEach((item) => {
                const previous = this.current[this.current.length - 1];
                const next = this.point(item);

                this.current.push(next);
                this.drawSegment(previous, next);
            });
        }

        handleUp() {
            if (!this.current) return;

            this.current = null;
            this.onChange(this.isEmpty());
        }

        applyStyle() {
            this.context.strokeStyle = this.color;
            this.context.fillStyle = this.color;
            this.context.lineWidth = this.width;
            this.context.lineCap = 'round';
            this.context.lineJoin = 'round';
        }

        drawDot(point) {
            this.applyStyle();
            this.context.beginPath();
            this.context.arc(point.x, point.y, this.width / 2, 0, Math.PI * 2);
            this.context.fill();
        }

        drawSegment(from, to) {
            this.applyStyle();
            this.context.beginPath();
            this.context.moveTo(from.x, from.y);
            this.context.lineTo(to.x, to.y);
            this.context.stroke();
        }

        redraw() {
            const { width, height } = this.canvas;

            this.context.save();
            this.context.setTransform(1, 0, 0, 1, 0, 0);
            this.context.clearRect(0, 0, width, height);
            this.context.restore();

            this.strokes.forEach((stroke) => {
                this.drawDot(stroke[0]);

                for (let index = 1; index < stroke.length; index += 1) {
                    this.drawSegment(stroke[index - 1], stroke[index]);
                }
            });
        }

        clear() {
            this.strokes = [];
            this.current = null;
            this.redraw();
            this.onChange(true);
        }

        isEmpty() {
            return this.strokes.length === 0;
        }

        /**
         * PNG con fondo transparente, o cadena vacía si no hay firma.
         */
        toDataURL() {
            return this.isEmpty() ? '' : this.canvas.toDataURL('image/png');
        }
    }

    window.SignaturePad = SignaturePad;
})();
