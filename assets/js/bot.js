class Bullet {
    constructor(x, y, angle, speed, damage, color, fromBot = false) {
        this.x       = x;
        this.y       = y;
        this.angle   = angle;
        this.speed   = speed;
        this.damage  = damage;
        this.color   = color;
        this.fromBot = fromBot;
        this.radius  = 5;
        this.active  = true;
        this.vx      = Math.cos(angle) * speed;
        this.vy      = Math.sin(angle) * speed;
        this.trail   = [];
    }

    update() {
        this.trail.push({ x: this.x, y: this.y });
        if (this.trail.length > 6) this.trail.shift();
        this.x += this.vx;
        this.y += this.vy;
    }

    deactivate() { this.active = false; }

    draw(ctx) {
        if (!this.active) return;
        this.trail.forEach((p, i) => {
            ctx.globalAlpha = (i / this.trail.length) * 0.3;
            ctx.fillStyle   = this.color;
            ctx.beginPath();
            ctx.arc(p.x, p.y, this.radius * 0.5, 0, Math.PI * 2);
            ctx.fill();
        });
        ctx.globalAlpha = 1;
        ctx.fillStyle   = this.color;
        ctx.shadowColor = this.color;
        ctx.shadowBlur  = 10;
        ctx.beginPath();
        ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
        ctx.fill();
        ctx.shadowBlur = 0;
    }
}

class Bot {
    constructor(x, y, platform, type, round) {
        this.type      = type;     // 'runner' or 'shooter'
        this.platform  = platform;
        this.x         = x;
        this.y         = y;
        this.alive     = true;
        this.hitFlash  = 0;
        this.bullets   = [];
        this.round     = round;
        this.shootCooldown = Utils.randomInt(20, 60); // stagger initial shots
        this.angle     = 0;

        // size — runner smaller, shooter bigger
        if (type === 'runner') {
            this.radius = 16;
            this.w      = 32;
            this.h      = 48;
        } else {
            this.radius = 22;
            this.w      = 44;
            this.h      = 64;
        }

        const roundMult = 1 + (round - 1) * 0.18;

        if (type === 'runner') {
            // fast movement, shoots only horizontally (facing direction)
            this.hp        = Math.floor(30 * roundMult);
            this.maxHp     = this.hp;
            this.speed     = Math.min(3.8, 2.0 * roundMult);
            this.shootRate = Math.max(80, 130 - (round - 1) * 10);
            this.damage    = 8;
            this.bulletSpd = 5.5;
        } else {
            // slow movement, shoots in all directions toward player, slow fire rate
            this.hp        = Math.floor(70 * roundMult);
            this.maxHp     = this.hp;
            this.speed     = Math.min(0.8, 0.4 * roundMult);
            this.shootRate = Math.max(55, 90 - (round - 1) * 8);
            this.damage    = 18;
            this.bulletSpd = 6.5;
        }

        this.patrolDir    = Math.random() > 0.5 ? 1 : -1;
        this.patrolTimer  = 0;
        this.patrolChange = Utils.randomInt(70, 180);
        this.facingRight  = this.patrolDir > 0;
    }

    update(player, canvasW) {
        if (!this.alive) return;

        const p      = this.platform;
        const pLeft  = p.x + this.radius + 2;
        const pRight = p.x + p.w - this.radius - 2;

        // patrol
        this.patrolTimer++;
        if (this.patrolTimer >= this.patrolChange) {
            this.patrolDir    = -this.patrolDir;
            this.patrolTimer  = 0;
            this.patrolChange = Utils.randomInt(70, 180);
        }

        this.x += this.patrolDir * this.speed;

        // clamp to platform — never fall off
        if (this.x <= pLeft)  { this.x = pLeft;  this.patrolDir =  1; }
        if (this.x >= pRight) { this.x = pRight; this.patrolDir = -1; }

        // always sit on top of assigned platform
        this.y = p.y - this.radius;

        // facing
        this.facingRight = this.patrolDir > 0;

        // angle to player for shooter bot
        this.angle = Utils.angleBetween(this, player);

        // shooting
        this.shootCooldown--;
        if (this.shootCooldown <= 0) {
            this.fireBullet(player);
            this.shootCooldown = this.shootRate;
        }

        this.bullets.forEach(b => b.update());
        this.bullets = this.bullets.filter(b => b.active);

        if (this.hitFlash > 0) this.hitFlash--;
    }

    fireBullet(player) {
        let angle;
        const color = this.type === 'runner' ? '#E24B4A' : '#EF9F27';

        if (this.type === 'runner') {
            // shoots only horizontally in facing direction
            angle = this.facingRight ? 0 : Math.PI;
        } else {
            // shoots directly at player from any angle
            angle = Utils.angleBetween(this, player);
        }

        this.bullets.push(new Bullet(
            this.x,
            this.y - this.radius * 0.2,
            angle,
            this.bulletSpd,
            this.damage,
            color,
            true
        ));
    }

    takeDamage(amount) {
        this.hp       -= amount;
        this.hitFlash  = 8;
        if (this.hp <= 0) { this.hp = 0; this.alive = false; }
    }

    draw(ctx, imgRunner, imgShooter) {
        if (!this.alive) return;

        this.bullets.forEach(b => b.draw(ctx));

        const img = this.type === 'runner' ? imgRunner : imgShooter;
        const r   = this.radius;

        ctx.save();
        ctx.translate(this.x, this.y);

        // image faces LEFT — flip for right
        if (this.facingRight) ctx.scale(-1, 1);

        if (this.hitFlash > 0) {
            ctx.filter      = 'brightness(8)';
            ctx.globalAlpha = 0.7;
        }

        if (img && img.complete && img.naturalWidth > 0) {
            const drawW = r * 4;
            const drawH = r * 5.5;
            ctx.drawImage(img, -drawW / 2, -drawH * 0.75, drawW, drawH);
        } else {
            const color     = this.type === 'runner' ? '#E24B4A' : '#EF9F27';
            ctx.fillStyle   = color;
            ctx.shadowColor = color;
            ctx.shadowBlur  = 10;
            ctx.fillRect(-r, -r * 1.8, r * 2, r * 3);
            ctx.shadowBlur  = 0;
            ctx.fillStyle   = '#ffffff';
            ctx.font        = `bold ${r * 0.75}px monospace`;
            ctx.textAlign   = 'center';
            ctx.fillText(this.type === 'runner' ? 'R' : 'S', 0, r * 0.3);
        }

        ctx.filter      = 'none';
        ctx.globalAlpha = 1;
        ctx.restore();

        this.drawHpBar(ctx);

        // shooter bot aim indicator
        if (this.type === 'shooter') {
            ctx.save();
            ctx.strokeStyle = 'rgba(239,159,39,0.2)';
            ctx.lineWidth   = 1;
            ctx.setLineDash([3, 6]);
            ctx.beginPath();
            ctx.moveTo(this.x, this.y);
            ctx.lineTo(
                this.x + Math.cos(this.angle) * 100,
                this.y + Math.sin(this.angle) * 100
            );
            ctx.stroke();
            ctx.setLineDash([]);

            // dot at end
            ctx.fillStyle   = 'rgba(239,159,39,0.6)';
            ctx.shadowColor = '#EF9F27';
            ctx.shadowBlur  = 5;
            ctx.beginPath();
            ctx.arc(
                this.x + Math.cos(this.angle) * 100,
                this.y + Math.sin(this.angle) * 100,
                3, 0, Math.PI * 2
            );
            ctx.fill();
            ctx.shadowBlur = 0;
            ctx.restore();
        }
    }

    drawHpBar(ctx) {
        const bw  = this.type === 'shooter' ? 44 : 32;
        const bh  = 4;
        const bx  = this.x - bw / 2;
        const by  = this.y - this.radius * 2 - 12;
        const pct = this.hp / this.maxHp;

        ctx.fillStyle = 'rgba(0,0,0,0.55)';
        ctx.fillRect(bx, by, bw, bh);

        ctx.fillStyle = pct > 0.5 ? '#1D9E75' : pct > 0.25 ? '#EF9F27' : '#E24B4A';
        ctx.fillRect(bx, by, bw * pct, bh);

        ctx.fillStyle = this.type === 'runner'
            ? 'rgba(226,75,74,0.65)'
            : 'rgba(239,159,39,0.65)';
        ctx.font      = '8px monospace';
        ctx.textAlign = 'center';
        ctx.fillText(
            this.type === 'runner' ? 'RUNNER' : 'SHOOTER',
            this.x, by - 3
        );
    }
}