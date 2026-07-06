/**
 * The "Share" dialog (see components/share.blade.php). A single Alpine
 * component drives the modal for every page type: it renders the QR code for
 * the current URL client-side, copies text to the clipboard with feedback, and
 * exposes the native system share sheet on devices that support it.
 *
 * QR generation is deliberately client-side: the code only ever encodes the
 * page's own URL (already known in the browser), so there is nothing to cache
 * or index server-side, and drawing to a canvas lets us overlay a business
 * logo without compositing images on the backend.
 */

import qrcode from 'qrcode-generator';

function shareDialog(config) {
    return {
        open: false,
        // Which field was last copied, so only its button shows "Copied".
        copied: null,
        // Web Share API is mostly a mobile capability; the button hides without it.
        canShare: false,
        // Bound in init() so the same reference can be removed again.
        onKeydown: null,

        init() {
            this.canShare = typeof navigator !== 'undefined' && typeof navigator.share === 'function';
            this.onKeydown = (event) => {
                if (event.key === 'Escape') this.hide();
            };
        },

        show() {
            this.open = true;
            document.addEventListener('keydown', this.onKeydown);
            // Wait for x-show to reveal the canvas before measuring/drawing it.
            this.$nextTick(() => this.renderQr());
        },

        hide() {
            this.open = false;
            this.copied = null;
            document.removeEventListener('keydown', this.onKeydown);
        },

        renderQr() {
            const canvas = this.$refs.qr;
            if (!canvas) return;

            // Type 0 = smallest version that fits the data; level H tolerates
            // ~30% occlusion, leaving room for the centred logo overlay.
            const qr = qrcode(0, 'H');
            qr.addData(config.url);
            qr.make();

            const count = qr.getModuleCount();
            const margin = 2; // quiet zone, in modules
            const cell = 8; // device px per module — scaled down responsively via CSS
            const size = (count + margin * 2) * cell;

            canvas.width = size;
            canvas.height = size;

            const ctx = canvas.getContext('2d');
            // Force a white field regardless of light/dark theme so scanners
            // always see the required contrast.
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, size, size);
            ctx.fillStyle = '#000000';
            for (let row = 0; row < count; row++) {
                for (let col = 0; col < count; col++) {
                    if (!qr.isDark(row, col)) continue;
                    ctx.fillRect((col + margin) * cell, (row + margin) * cell, cell, cell);
                }
            }
        },

        copy(text, key) {
            const done = () => {
                this.copied = key;
                setTimeout(() => {
                    if (this.copied === key) this.copied = null;
                }, 1500);
            };

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(done).catch(() => this.fallbackCopy(text, done));
            } else {
                this.fallbackCopy(text, done);
            }
        },

        // Older/insecure-context browsers without the async clipboard API.
        fallbackCopy(text, done) {
            const area = document.createElement('textarea');
            area.value = text;
            area.setAttribute('readonly', '');
            area.style.position = 'absolute';
            area.style.left = '-9999px';
            document.body.appendChild(area);
            area.select();
            try {
                document.execCommand('copy');
                done();
            } catch (error) {
                /* give up silently — the value is still visible to copy by hand */
            }
            document.body.removeChild(area);
        },

        nativeShare() {
            if (!this.canShare) return;
            navigator.share({ title: config.title, url: config.url }).catch(() => {
                /* user dismissed the share sheet — nothing to do */
            });
        },
    };
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('shareDialog', shareDialog);
});
