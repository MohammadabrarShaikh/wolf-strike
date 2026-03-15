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

    rectCollision(entity, obstacle) {
        return (
            entity.x - entity.radius < obstacle.x + obstacle.w &&
            entity.x + entity.radius > obstacle.x &&
            entity.y - entity.radius < obstacle.y + obstacle.h &&
            entity.y + entity.radius > obstacle.y
        );
    },

    clamp(value, min, max) {
        return Math.max(min, Math.min(max, value));
    },

    randomBetween(min, max) {
        return Math.random() * (max - min) + min;
    },

    randomInt(min, max) {
        return Math.floor(Math.random() * (max - min + 1)) + min;
    },

    randomEdgePosition(canvasW, canvasH, margin) {
        const side = Utils.randomInt(0, 3);
        switch (side) {
            case 0: return { x: Utils.randomBetween(margin, canvasW - margin), y: margin };
            case 1: return { x: canvasW - margin, y: Utils.randomBetween(margin, canvasH - margin) };
            case 2: return { x: Utils.randomBetween(margin, canvasW - margin), y: canvasH - margin };
            case 3: return { x: margin, y: Utils.randomBetween(margin, canvasH - margin) };
        }
    },

    generateObstacles(canvasW, canvasH, count) {
        const obstacles = [];
        const minW = 40, maxW = 100;
        const minH = 40, maxH = 100;
        const margin = 80;
        const centerSafe = 150;
        const cx = canvasW / 2;
        const cy = canvasH / 2;

        let attempts = 0;
        while (obstacles.length < count && attempts < 500) {
            attempts++;
            const w = Utils.randomInt(minW, maxW);
            const h = Utils.randomInt(minH, maxH);
            const x = Utils.randomInt(margin, canvasW - margin - w);
            const y = Utils.randomInt(margin, canvasH - margin - h);

            const tooCloseToCenter = (
                x < cx + centerSafe && x + w > cx - centerSafe &&
                y < cy + centerSafe && y + h > cy - centerSafe
            );

            if (tooCloseToCenter) continue;

            const overlaps = obstacles.some(o =>
                x < o.x + o.w + 20 &&
                x + w > o.x - 20 &&
                y < o.y + o.h + 20 &&
                y + h > o.y - 20
            );

            if (!overlaps) {
                obstacles.push({ x, y, w, h });
            }
        }
        return obstacles;
    }
};