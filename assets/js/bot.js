class Bot {
    constructor(x, y, round) {
        this.x = x;
        this.y = y;
        this.radius   = 18;
        this.angle    = 0;
        this.hp       = 40 + (round - 1) * 10;
        this.maxHp    = this.hp;
        this.speed    = 1.2 + (round - 1) * 0.35;
        this.state    = 'patrol';
        this.patrolAngle   = Utils.randomBetween(0, Math.PI * 2);
        this.patrolTimer   = 0;
        this.patrolChange  = Utils.randomInt(60, 180);
        this.shootCooldown = 0;
        this.shootRate     = Math.max(60, 100 - (round - 1) * 8);
        this.detectionRadius = 220;
        this.shootRadius     = 160;
        this.bullets  = [];
        this.alive    = true;
        this.hitFlash = 0;

        this.color = `hsl(${Utils.randomInt(0, 20)}, 80%, 45%)`;
    }

    update(player, obstacles, canvasW, canvasH) {
        if (!this.alive) return;

        const dist = Utils.distanceBetween(this, player);

        if (dist < this.shootRadius) {
            this.state = 'shoot';
        } else if (dist < this.detectionRadius) {
            this.state = 'chase';
        } else {
            this.state = 'patrol';
        }

        this.angle = Utils.angleBetween(this, player);

        switch (this.state) {

            case 'patrol':
                this.patrolTimer++;
                if (this.patrolTimer >= this.patrolChange) {
                    this.patrolAngle  = Utils.randomBetween(0, Math.PI * 2);
                    this.patrolTimer  = 0;
                    this.patrolChange = Utils.randomInt(60, 180);
                }
                this.moveInDirection(this.patrolAngle, this.speed * 0.6, obstacles, canvasW, canvasH);
                break;

            case 'chase':
                this.moveInDirection(this.angle, this.speed, obstacles, canvasW, canvasH);
                break;

            case 'shoot':
                this.moveInDirection(this.angle, this.speed * 0.3, obstacles, canvasW, canvasH);
                this.shootCooldown--;
                if (this.shootCooldown <= 0) {
                    this.fireBullet();
                    this.shootCooldown = this.shootRate;
                }
                break;
        }

        this.bullets.forEach(b => b.update());
        this.bullets = this.bullets.filter(b => b.active);

        if (this.hitFlash > 0) this.hitFlash--;
    }

    moveInDirection(angle, speed, obstacles, canvasW, canvasH) {
        const nx = this.x + Math.cos(angle) * speed;
        const ny = this.y + Math.sin(angle) * speed;

        const testX = { x: nx, y: this.y, radius: this.radius };
        const testY = { x: this.x, y: ny, radius: this.radius };

        const hitX = obstacles.some(o => Utils.rectCollision(testX, o));
        const hitY = obstacles.some(o => Utils.rectCollision(testY, o));

        if (!hitX) this.x = Utils.clamp(nx, this.radius, canvasW - this.radius);
        if (!hitY) this.y = Utils.clamp(ny, this.radius, canvasH - this.radius);
    }

    fireBullet() {
        this.bullets.push(new Bullet(
            this.x, this.y,
            this.angle,
            5, 10, '#E24B4A'
        ));
    }

    takeDamage(amount) {
        this.hp -= amount;
        this.hitFlash = 8;
        if (this.hp <= 0) {
            this.hp    = 0;
            this.alive = false;
        }
    }

    draw(ctx, img) {
        if (!this.alive) return;

        this.bullets.forEach(b => b.draw(ctx));

        ctx.save();
        ctx.translate(this.x, this.y);
        ctx.rotate(this.angle + Math.PI / 2);

        if (this.hitFlash > 0) {
            ctx.globalAlpha = 0.5;
        }

        if (img && img.complete) {
            ctx.drawImage(img, -this.radius, -this.radius, this.radius * 2, this.radius * 2);
        } else {
            ctx.fillStyle = this.hitFlash > 0 ? '#ffffff' : this.color;
            ctx.beginPath();
            ctx.arc(0, 0, this.radius, 0, Math.PI * 2);
            ctx.fill();
        }

        ctx.globalAlpha = 1;
        ctx.restore();

        this.drawHpBar(ctx);
    }

    drawHpBar(ctx) {
        const bw = 36, bh = 4;
        const bx = this.x - bw / 2;
        const by = this.y - this.radius - 10;
        const pct = this.hp / this.maxHp;

        ctx.fillStyle = 'rgba(0,0,0,0.5)';
        ctx.fillRect(bx, by, bw, bh);

        const color = pct > 0.5 ? '#1D9E75' : pct > 0.25 ? '#EF9F27' : '#E24B4A';
        ctx.fillStyle = color;
        ctx.fillRect(bx, by, bw * pct, bh);
    }
}

class Bullet {
    constructor(x, y, angle, speed, damage, color) {
        this.x      = x;
        this.y      = y;
        this.angle  = angle;
        this.speed  = speed;
        this.damage = damage;
        this.color  = color;
        this.radius = 5;
        this.active = true;
        this.vx     = Math.cos(angle) * speed;
        this.vy     = Math.sin(angle) * speed;
        this.trail  = [];
    }

    update() {
        this.trail.push({ x: this.x, y: this.y });
        if (this.trail.length > 6) this.trail.shift();
        this.x += this.vx;
        this.y += this.vy;
    }

    deactivate() {
        this.active = false;
    }

    draw(ctx) {
        if (!this.active) return;

        this.trail.forEach((p, i) => {
            const alpha = (i / this.trail.length) * 0.4;
            ctx.globalAlpha = alpha;
            ctx.fillStyle   = this.color;
            ctx.beginPath();
            ctx.arc(p.x, p.y, this.radius * 0.5, 0, Math.PI * 2);
            ctx.fill();
        });

        ctx.globalAlpha = 1;
        ctx.fillStyle   = this.color;
        ctx.shadowColor = this.color;
        ctx.shadowBlur  = 8;
        ctx.beginPath();
        ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
        ctx.fill();
        ctx.shadowBlur = 0;
    }
}