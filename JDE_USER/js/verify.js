/**
 * verify.js — Email Verification Page Logic
 * Handles the countdown redirect and confetti animation.
 */

document.addEventListener('DOMContentLoaded', () => {
    const countEl = document.getElementById('timer-count');
    const ctaBtn = document.getElementById('cta-btn');
    const canvas = document.getElementById('confetti-canvas');

    /* ── Countdown Redirect ── */
    if (countEl) {
        let count = 5;
        const timer = setInterval(() => {
            count--;
            countEl.textContent = count;
            if (count <= 0) {
                clearInterval(timer);
                window.location.href = 'login.php';
            }
        }, 1000);

        if (ctaBtn) {
            ctaBtn.addEventListener('click', () => clearInterval(timer));
        }
    }

    /* ── Confetti Burst ── */
    if (canvas) {
        const ctx = canvas.getContext('2d');
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        const COLORS = ['#D6A347', '#f0c040', '#ffffff', '#012B43', '#10b981', '#34d399'];
        const pieces = Array.from({ length: 130 }, () => ({
            x: Math.random() * canvas.width,
            y: Math.random() * canvas.height - canvas.height,
            w: 8 + Math.random() * 8,
            h: 4 + Math.random() * 6,
            color: COLORS[Math.floor(Math.random() * COLORS.length)],
            rot: Math.random() * Math.PI * 2,
            spin: (Math.random() - 0.5) * 0.14,
            vy: 2 + Math.random() * 3.5,
            vx: (Math.random() - 0.5) * 2.5,
            opacity: 0.9
        }));

        function draw() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            pieces.forEach(p => {
                p.y += p.vy;
                p.x += p.vx;
                p.rot += p.spin;
                p.opacity = Math.max(0, p.opacity - 0.003);
                ctx.save();
                ctx.globalAlpha = p.opacity;
                ctx.translate(p.x + p.w / 2, p.y + p.h / 2);
                ctx.rotate(p.rot);
                ctx.fillStyle = p.color;
                ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
                ctx.restore();
            });
            if (pieces.some(p => p.opacity > 0)) {
                requestAnimationFrame(draw);
            } else {
                canvas.remove();
            }
        }
        draw();

        window.addEventListener('resize', () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        });
    }
});
