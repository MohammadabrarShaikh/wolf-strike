const Utils = {

    distanceBetween(a, b) {
        const dx = b.x - a.x;
        const dy = b.y - a.y;
        return Math.sqrt(dx * dx + dy * dy);
    },

    angleBetween(from, to) {
        return Math.atan2(to.y - from.y, to.x - from.x);
    },

    circleCollision(a, b) {
        return Utils.distanceBetween(a, b) < (a.radius + b.radius);
    },

    clamp(val, min, max) {
        return Math.max(min, Math.min(max, val));
    },

    randomBetween(min, max) {
        return Math.random() * (max - min) + min;
    },

    randomInt(min, max) {
        return Math.floor(Math.random() * (max - min + 1)) + min;
    },

    // 5 platform levels all visible on screen
    // level 1 = ground, levels 2-5 = floating platforms at fixed heights
    generatePlatforms(canvasW, canvasH, round) {
        const platforms = [];
        const groundH   = 22;

        // level 1 — ground full width
        platforms.push({
            id:       0,
            level:    1,
            x:        0,
            y:        canvasH - groundH,
            w:        canvasW,
            h:        groundH,
            isGround: true,
        });

        // levels 2-5 — fixed vertical positions, random horizontal segments
        const topMargin    = 70;
        const usableH      = canvasH - groundH - topMargin - 40;
        const levelSpacing = usableH / 4;

        for (let lvl = 2; lvl <= 5; lvl++) {
            const levelIndex = lvl - 2; // 0,1,2,3
            const baseY      = canvasH - groundH - 60 - levelIndex * levelSpacing;

            // 2 platform segments per level, randomly placed horizontally
            const numSegs = 2;
            const segW    = Utils.randomBetween(canvasW * 0.28, canvasW * 0.38);
            const totalW  = numSegs * segW;
            const gap     = (canvasW - totalW) / (numSegs + 1);

            for (let s = 0; s < numSegs; s++) {
                const jitter = Utils.randomBetween(-30, 30);
                const x      = gap + s * (segW + gap) + jitter;
                const y      = baseY + Utils.randomBetween(-20, 20);

                platforms.push({
                    id:       lvl * 10 + s,
                    level:    lvl,
                    x:        Utils.clamp(x, 20, canvasW - segW - 20),
                    y:        Utils.clamp(y, topMargin, canvasH - groundH - 80),
                    w:        segW,
                    h:        16,
                    isGround: false,
                });
            }
        }

        return platforms;
    },

    getBackgroundTheme(round) {
        const themes = {
            1: {
                sky:    '#06061a',
                far:    { color: '#0d0d2e' },
                mid:    { color: '#0a0a22' },
                accent: '#7F77DD',
            },
            2: {
                sky:    '#130a1a',
                far:    { color: '#1a0a1a' },
                mid:    { color: '#120810' },
                accent: '#E24B4A',
            },
            3: {
                sky:    '#1a0e06',
                far:    { color: '#1a1006' },
                mid:    { color: '#130c04' },
                accent: '#EF9F27',
            },
            4: {
                sky:    '#050510',
                far:    { color: '#080818' },
                mid:    { color: '#060610' },
                accent: '#5DCAA5',
            },
            5: {
                sky:    '#1a0505',
                far:    { color: '#1a0808' },
                mid:    { color: '#120404' },
                accent: '#E24B4A',
            },
        };
        return themes[round] || themes[1];
    },

    lerp(a, b, t) {
        return a + (b - a) * t;
    },
};