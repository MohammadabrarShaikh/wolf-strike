const canvas = document.getElementById('gameCanvas');
const ctx    = canvas.getContext('2d');

canvas.width  = window.innerWidth;
canvas.height = window.innerHeight;

window.addEventListener('resize', () => {
    canvas.width  = window.innerWidth;
    canvas.height = window.innerHeight;
    if (state.phase !== 'starting') {
        state.platforms = Utils.generatePlatforms(canvas.width, canvas.height, state.round);
    }
});

// ── agent config — no gravity differences, all agents jump same ──
const AGENT_CONFIG = {
    Scout:   { hp: 60,  speed: 5.2, bulletSpeed: 9,  damage: 25, gravity: 0.55, jumpForce: -13 },
    Hunter:  { hp: 100, speed: 3.8, bulletSpeed: 7,  damage: 20, gravity: 0.55, jumpForce: -13 },
    Alpha:   { hp: 150, speed: 2.4, bulletSpeed: 5,  damage: 35, gravity: 0.55, jumpForce: -13 },
    Phantom: { hp: 80,  speed: 5.5, bulletSpeed: 10, damage: 30, gravity: 0.55, jumpForce: -13 },
};

// bots per round — [runners, shooters]
const ROUND_BOTS = [
    { runners: 2, shooters: 0 }, // round 1 — super easy
    { runners: 3, shooters: 0 }, // round 2
    { runners: 3, shooters: 1 }, // round 3
    { runners: 3, shooters: 2 }, // round 4
    { runners: 3, shooters: 3 }, // round 5
];

const TIMER_SECS    = [70, 65, 60, 55, 50];
const SURVIVAL_BONUS = [500, 600, 750, 900, 1200];
const KILL_POINTS    = { runner: 100, shooter: 200 };
const ROUND_MULT     = [100, 120, 140, 160, 180];

const agentCfg = AGENT_CONFIG[PLAYER_AGENT] || AGENT_CONFIG.Hunter;

// ── images ──
const images = {};
function loadImage(key, src) {
    return new Promise(resolve => {
        const img    = new Image();
        img.onload   = () => { images[key] = img; resolve(); };
        img.onerror  = () => { images[key] = null; resolve(); };
        img.src      = src;
    });
}

// ── screen shake ──
let shakeIntensity = 0;
let shakeX = 0, shakeY = 0;

function triggerShake(amount) {
    shakeIntensity = Math.max(shakeIntensity, amount);
}

function updateShake() {
    if (shakeIntensity > 0.3) {
        shakeX          = Utils.randomBetween(-shakeIntensity, shakeIntensity);
        shakeY          = Utils.randomBetween(-shakeIntensity, shakeIntensity);
        shakeIntensity *= 0.82;
    } else {
        shakeX = 0; shakeY = 0; shakeIntensity = 0;
    }
}

// ── parallax ──
const parallaxLayers = [];
let scrollX = 0;

function buildParallax(round) {
    parallaxLayers.length = 0;
    const theme = Utils.getBackgroundTheme(round);

    const far = [];
    for (let i = 0; i < 16; i++) {
        far.push({
            x:     Utils.randomBetween(0, canvas.width),
            w:     Utils.randomBetween(35, 100),
            h:     Utils.randomBetween(70, 240),
            color: theme.far.color,
        });
    }
    parallaxLayers.push({ speed: 0.15, items: far });

    const mid = [];
    for (let i = 0; i < 10; i++) {
        mid.push({
            x:     Utils.randomBetween(0, canvas.width),
            w:     Utils.randomBetween(24, 60),
            h:     Utils.randomBetween(50, 160),
            color: theme.mid.color,
        });
    }
    parallaxLayers.push({ speed: 0.4, items: mid });
}

function drawBackground(round) {
    const theme = Utils.getBackgroundTheme(round);
    ctx.fillStyle = theme.sky;
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    drawAtmosphere(round, theme);

    parallaxLayers.forEach((layer, li) => {
        layer.items.forEach(b => {
            const px = ((b.x + scrollX * layer.speed) % canvas.width + canvas.width) % canvas.width;
            const py = canvas.height - b.h;
            ctx.fillStyle = b.color;
            ctx.fillRect(px, py, b.w, b.h);

            if (li === 1) {
                ctx.fillStyle = theme.accent + '44';
                for (let wy = py + 8; wy < py + b.h - 8; wy += 16) {
                    for (let wx = px + 5; wx < px + b.w - 5; wx += 12) {
                        if (Math.random() > 0.45) ctx.fillRect(wx, wy, 4, 6);
                    }
                }
            }
        });
    });
}

function drawAtmosphere(round, theme) {
    const t = performance.now() / 1000;

    if (round === 1) {
        for (let i = 0; i < 100; i++) {
            const sx       = (i * 137.5) % canvas.width;
            const sy       = (i * 97.3)  % (canvas.height * 0.6);
            const twinkle  = Math.sin(t * 2 + i) * 0.5 + 0.5;
            ctx.globalAlpha = twinkle * 0.65;
            ctx.fillStyle   = '#ffffff';
            ctx.fillRect(sx, sy, 1.5, 1.5);
        }
        ctx.globalAlpha = 1;
    }

    if (round === 2) {
        const pulse     = Math.sin(t * 4) * 0.5 + 0.5;
        ctx.fillStyle   = `rgba(226,75,74,${pulse * 0.07})`;
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        for (let i = 0; i < 5; i++) {
            const ax = (i / 5) * canvas.width + 50;
            ctx.beginPath();
            ctx.arc(ax, 32, 10 + pulse * 5, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(226,75,74,${pulse * 0.85})`;
            ctx.fill();
        }
    }

    if (round === 3) {
        const g = ctx.createLinearGradient(0, canvas.height * 0.55, 0, canvas.height);
        g.addColorStop(0, 'transparent');
        g.addColorStop(1, `rgba(239,159,39,${0.1 + Math.sin(t * 3) * 0.03})`);
        ctx.fillStyle = g;
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = '#EF9F27';
        for (let i = 0; i < 14; i++) {
            const sx        = ((i * 185 + t * 55) % canvas.width);
            const sy        = canvas.height - 18 - Math.abs(Math.sin(t * 3 + i)) * 60;
            ctx.globalAlpha = Math.abs(Math.sin(t * 4 + i)) * 0.7;
            ctx.fillRect(sx, sy, 2, 3);
        }
        ctx.globalAlpha = 1;
    }

    if (round === 4) {
        ctx.fillStyle = 'rgba(93,202,165,0.025)';
        for (let y = 0; y < canvas.height; y += 4) ctx.fillRect(0, y, canvas.width, 1);
        if (Math.random() > 0.93) {
            ctx.fillStyle = `rgba(93,202,165,${Math.random() * 0.13})`;
            ctx.fillRect(0, Utils.randomInt(0, canvas.height), canvas.width, Utils.randomInt(2, 9));
        }
        ctx.strokeStyle = 'rgba(93,202,165,0.12)';
        ctx.lineWidth   = 1;
        for (let i = 0; i < 3; i++) {
            const lx = ((i * 290 + t * 22) % canvas.width);
            ctx.beginPath();
            ctx.moveTo(lx, 0);
            ctx.lineTo(lx + 18, canvas.height);
            ctx.stroke();
        }
    }

    if (round === 5) {
        const c         = Math.sin(t * 6) * 0.5 + 0.5;
        ctx.fillStyle   = `rgba(226,75,74,${c * 0.09})`;
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        if (Math.random() > 0.96) {
            ctx.fillStyle = 'rgba(239,159,39,0.13)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
        }
        for (let i = 0; i < 10; i++) {
            const dx        = ((i * 170 + t * 42) % canvas.width);
            const dy        = ((t * 28  + i * 65)  % canvas.height);
            ctx.globalAlpha = 0.4;
            ctx.fillStyle   = '#E24B4A';
            ctx.fillRect(dx, dy, 3, 3);
        }
        ctx.globalAlpha = 1;
    }
}

// ── player ──
const player = {
    x:           0,
    y:           0,
    radius:      20,
    vx:          0,
    vy:          0,
    onGround:    false,
    hp:          agentCfg.hp,
    maxHp:       agentCfg.hp,
    gravity:     agentCfg.gravity,
    jumpForce:   agentCfg.jumpForce,
    speed:       agentCfg.speed,
    angle:       0,
    facingRight: true,
    walkFrame:   0,
    walkTimer:   0,
};

// ── input ──
const keys  = {};
const mouse = { x: 0, y: 0 };

canvas.addEventListener('mousemove', e => {
    const r = canvas.getBoundingClientRect();
    mouse.x = e.clientX - r.left;
    mouse.y = e.clientY - r.top;
});

canvas.addEventListener('click', () => {
    if (state.phase === 'playing') shootBullet();
});

window.addEventListener('keydown', e => {
    keys[e.code] = true;
    if (['Space', 'ArrowUp', 'KeyW'].includes(e.code)) e.preventDefault();
});
window.addEventListener('keyup', e => { keys[e.code] = false; });

// ── game state ──
const state = {
    round:         1,
    score:         0,
    totalKills:    0,
    phase:         'starting',
    timer:         0,
    timerMax:      0,
    platforms:     [],
    bots:          [],
    playerBullets: [],
    shootCooldown: 0,
    gameOver:      false,
    particles:     [],
};

// ── particles ──
function spawnParticles(x, y, color, count = 8) {
    for (let i = 0; i < count; i++) {
        state.particles.push({
            x, y,
            vx:     Utils.randomBetween(-4, 4),
            vy:     Utils.randomBetween(-7, -1),
            life:   1,
            decay:  Utils.randomBetween(0.025, 0.06),
            radius: Utils.randomBetween(2, 5),
            color,
        });
    }
}

function updateParticles() {
    state.particles.forEach(p => {
        p.x    += p.vx;
        p.y    += p.vy;
        p.vy   += 0.18;
        p.life -= p.decay;
    });
    state.particles = state.particles.filter(p => p.life > 0);
}

function drawParticles() {
    state.particles.forEach(p => {
        ctx.globalAlpha = p.life;
        ctx.fillStyle   = p.color;
        ctx.shadowColor = p.color;
        ctx.shadowBlur  = 4;
        ctx.beginPath();
        ctx.arc(p.x + shakeX, p.y + shakeY, p.radius, 0, Math.PI * 2);
        ctx.fill();
    });
    ctx.globalAlpha = 1;
    ctx.shadowBlur  = 0;
}

// ── shoot ──
function shootBullet() {
    if (state.shootCooldown > 0) return;
    const angle = Utils.angleBetween(player, mouse);
    state.playerBullets.push(new Bullet(
        player.x, player.y - 8,
        angle,
        agentCfg.bulletSpeed,
        agentCfg.damage,
        '#7F77DD', false
    ));
    state.shootCooldown = 12;
}

// ── player movement with solid platform collision ──
function movePlayer() {
    const moving = keys['KeyA'] || keys['ArrowLeft'] ||
                   keys['KeyD'] || keys['ArrowRight'];

    if (keys['KeyA'] || keys['ArrowLeft']) {
        player.vx        = -player.speed;
        player.facingRight = false;
        scrollX          -= player.speed * 0.25;
    } else if (keys['KeyD'] || keys['ArrowRight']) {
        player.vx        = player.speed;
        player.facingRight = true;
        scrollX          += player.speed * 0.25;
    } else {
        player.vx *= 0.75;
    }

    // jump — only when on ground
    if ((keys['KeyW'] || keys['ArrowUp'] || keys['Space']) && player.onGround) {
        player.vy       = player.jumpForce;
        player.onGround = false;
    }

    // walk animation
    if (moving && player.onGround) {
        player.walkTimer++;
        if (player.walkTimer > 8) {
            player.walkFrame  = (player.walkFrame + 1) % 2;
            player.walkTimer  = 0;
        }
    } else if (!moving) {
        player.walkFrame = 0;
        player.walkTimer = 0;
    }

    // apply gravity
    player.vy += player.gravity;

    // apply velocity
    player.x  += player.vx;
    player.y  += player.vy;

    // clamp horizontal to screen
    player.x = Utils.clamp(player.x, player.radius, canvas.width - player.radius);

    // ── solid platform collision — cannot pass through from any direction ──
    player.onGround = false;

    state.platforms.forEach(p => {
        const inXRange = player.x + player.radius > p.x &&
                         player.x - player.radius < p.x + p.w;

        if (!inXRange) return;

        const playerBottom = player.y + player.radius;
        const playerTop    = player.y - player.radius;
        const platTop      = p.y;
        const platBottom   = p.y + p.h;

        // landing on top — player falling down, feet near platform top
        if (player.vy >= 0 &&
            playerBottom >= platTop &&
            playerBottom <= platTop + player.vy + 12) {
            player.y        = platTop - player.radius;
            player.vy       = 0;
            player.onGround = true;
        }

        // hitting from below — player jumping up, head hits platform bottom
        if (player.vy < 0 &&
            playerTop <= platBottom &&
            playerTop >= platBottom + player.vy - 8) {
            player.y  = platBottom + player.radius;
            player.vy = 0; // stop upward movement — cannot jump through
        }
    });

    // world floor
    if (player.y + player.radius >= canvas.height) {
        player.y        = canvas.height - player.radius;
        player.vy       = 0;
        player.onGround = true;
    }

    player.angle = Utils.angleBetween(player, mouse);
}

// ── draw platforms ──
function drawPlatforms() {
    const theme = Utils.getBackgroundTheme(state.round);

    state.platforms.forEach(p => {
        const sx = p.x + shakeX;
        const sy = p.y + shakeY;

        if (p.isGround) {
            ctx.fillStyle = '#0b0b1c';
            ctx.fillRect(sx, sy, p.w, p.h);

            ctx.strokeStyle = theme.accent + '77';
            ctx.lineWidth   = 2;
            ctx.beginPath();
            ctx.moveTo(sx, sy);
            ctx.lineTo(sx + p.w, sy);
            ctx.stroke();
        } else {
            // platform body
            ctx.fillStyle = 'rgba(16,16,36,0.96)';
            ctx.fillRect(sx, sy, p.w, p.h);

            // glowing border
            ctx.strokeStyle = theme.accent + 'cc';
            ctx.lineWidth   = 1.5;
            ctx.strokeRect(sx, sy, p.w, p.h);

            // top glow strip
            const grd = ctx.createLinearGradient(sx, sy - 3, sx, sy + 5);
            grd.addColorStop(0, theme.accent + '55');
            grd.addColorStop(1, 'transparent');
            ctx.fillStyle = grd;
            ctx.fillRect(sx, sy - 3, p.w, 8);

            // level indicator
            ctx.fillStyle = theme.accent + '66';
            ctx.font      = 'bold 9px monospace';
            ctx.textAlign = 'center';
            ctx.fillText(`LVL ${p.level}`, sx + p.w / 2, sy + p.h - 2);
        }
    });
}

// ── draw player ──
function drawPlayer() {
    const sx = player.x + shakeX;
    const sy = player.y + shakeY;
    const r  = player.radius;

    ctx.save();
    ctx.translate(sx, sy);

    // image faces LEFT — flip to face RIGHT
    if (player.facingRight) ctx.scale(-1, 1);

    // walk bob
    const bob = player.onGround && player.walkFrame === 1 ? 2 : 0;
    ctx.translate(0, bob);

    const imgKey = `player_${PLAYER_AGENT.toLowerCase()}`;
    const img    = images[imgKey];

    if (img && img.complete && img.naturalWidth > 0) {
        const drawW = r * 4.5;
        const drawH = r * 6.2;
        ctx.drawImage(img, -drawW / 2, -drawH * 0.8, drawW, drawH);
    } else {
        ctx.fillStyle   = '#7F77DD';
        ctx.shadowColor = '#7F77DD';
        ctx.shadowBlur  = 14;
        ctx.fillRect(-r * 1.2, -r * 2.1, r * 2.4, r * 3.8);
        ctx.shadowBlur  = 0;
        ctx.fillStyle   = '#ffffff';
        ctx.fillRect(-r * 0.6, -r * 2.1, r * 1.2, r * 1.1);
        ctx.fillStyle   = '#ffffff';
        ctx.font        = `bold ${r * 0.6}px monospace`;
        ctx.textAlign   = 'center';
        ctx.fillText(PLAYER_AGENT.charAt(0), 0, r * 0.8);
    }

    ctx.restore();

    // aim line — thin dashed from player to mouse
    ctx.strokeStyle = 'rgba(127,119,221,0.28)';
    ctx.lineWidth   = 1;
    ctx.setLineDash([3, 6]);
    ctx.beginPath();
    ctx.moveTo(sx, sy - r * 0.3);
    ctx.lineTo(
        sx + Math.cos(player.angle) * 70,
        sy - r * 0.3 + Math.sin(player.angle) * 70
    );
    ctx.stroke();
    ctx.setLineDash([]);
}

// ── round management ──
function startRound(round) {
    const botConfig = ROUND_BOTS[round - 1];
    const timerSecs = TIMER_SECS[round - 1];

    state.round         = round;
    state.phase         = 'playing';
    state.timer         = timerSecs * 60;
    state.timerMax      = timerSecs * 60;
    state.playerBullets = [];
    state.particles     = [];
    state.platforms     = Utils.generatePlatforms(canvas.width, canvas.height, round);
    scrollX             = 0;

    // spawn player on ground centre
    const ground    = state.platforms.find(p => p.isGround);
    player.x        = canvas.width / 2;
    player.y        = ground.y - player.radius;
    player.vx       = 0;
    player.vy       = 0;
    player.onGround = true;

    buildParallax(round);

    // collect all floating platforms
    const floatingPlatforms = state.platforms.filter(p => !p.isGround);

    // distribute bots across platforms
    state.bots = [];

    // place runner bots
    const runnerBotCount = botConfig.runners;
    for (let i = 0; i < runnerBotCount; i++) {
        const plat = floatingPlatforms[i % floatingPlatforms.length];
        const bx   = plat.x + plat.w * ((i % 3 + 1) / 4);
        state.bots.push(new Bot(
            Utils.clamp(bx, plat.x + 20, plat.x + plat.w - 20),
            plat.y - 16,
            plat, 'runner', round
        ));
    }

    // place shooter bots on higher platforms if available
    const shooterBotCount = botConfig.shooters;
    const higherPlatforms = floatingPlatforms.filter(p => p.level >= 3);
    const shooterPlatforms = higherPlatforms.length > 0
        ? higherPlatforms
        : floatingPlatforms;

    for (let i = 0; i < shooterBotCount; i++) {
        const plat = shooterPlatforms[i % shooterPlatforms.length];
        const bx   = plat.x + plat.w / 2 + (i % 2 === 0 ? -20 : 20);
        state.bots.push(new Bot(
            Utils.clamp(bx, plat.x + 24, plat.x + plat.w - 24),
            plat.y - 22,
            plat, 'shooter', round
        ));
    }

    updateHUD();
    hideOverlay();
}

function endRound(survived) {
    state.phase = 'between';
    if (!survived) { endGame(false); return; }

    const bonus   = SURVIVAL_BONUS[state.round - 1];
    const hpBonus = Math.floor((player.hp / player.maxHp) * 100) * 5;
    state.score  += bonus + hpBonus;
    player.hp     = Math.min(player.maxHp, player.hp + 20);
    updateHUD();

    if (state.round >= 5) { endGame(true); return; }

    showOverlay(
        'ROUND COMPLETE',
        `Round ${state.round} of 5 cleared<br>
         Survival bonus: +${bonus.toLocaleString()}<br>
         HP bonus: +${hpBonus}<br>
         HP restored: +20<br><br>
         Score: <strong style="color:#7F77DD">
         ${state.score.toLocaleString()}</strong>`,
        'NEXT ROUND',
        () => startRound(state.round + 1)
    );
}

function endGame(completed) {
    state.phase    = 'gameover';
    state.gameOver = true;
    saveScore();

    if (completed) {
        showOverlay(
            'MISSION COMPLETE',
            `All 5 rounds cleared!<br><br>
             Final Score: <strong style="color:#7F77DD">
             ${state.score.toLocaleString()}</strong><br>
             Total Kills: ${state.totalKills}<br>
             Rounds Survived: 5 / 5`,
            'VIEW LEADERBOARD',
            () => { window.location.href = 'leaderboard.php'; }
        );
    } else {
        showOverlay(
            'YOU DIED',
            `Eliminated in Round ${state.round}<br><br>
             Final Score: <strong style="color:#E24B4A">
             ${state.score.toLocaleString()}</strong><br>
             Total Kills: ${state.totalKills}<br>
             Rounds Survived: ${state.round - 1} / 5`,
            'VIEW MATCH RESULT',
            () => { window.location.href = 'profile.php'; }
        );
    }
}

function saveScore() {
    const survived = state.gameOver && player.hp <= 0
        ? Math.max(0, state.round - 1)
        : state.round;
    const form = new FormData();
    form.append('agent',           PLAYER_AGENT);
    form.append('score',           state.score);
    form.append('kills',           state.totalKills);
    form.append('rounds_survived', survived);
    fetch('save_score.php', { method: 'POST', body: form })
        .catch(e => console.error('Save:', e));
}

// ── HUD ──
function updateHUD() {
    const pct   = player.hp / player.maxHp;
    const color = pct > 0.5 ? '#1D9E75' : pct > 0.25 ? '#EF9F27' : '#E24B4A';

    const fill   = document.getElementById('hp-bar-fill');
    const hpText = document.getElementById('hp-text');
    if (fill)   { fill.style.width = (pct * 100) + '%'; fill.style.background = color; }
    if (hpText)   hpText.textContent = `${Math.max(0, Math.ceil(player.hp))} / ${player.maxHp}`;

    const roundEl = document.getElementById('round-num');
    const scoreEl = document.getElementById('score-num');
    if (roundEl) roundEl.textContent = `${state.round} / 5`;
    if (scoreEl) scoreEl.textContent  = state.score.toLocaleString();

    const alive   = state.bots.filter(b => b.alive).length;
    const botsEl  = document.getElementById('bots-remaining');
    if (botsEl) botsEl.textContent = alive;

    const timerPct = state.timer / state.timerMax;
    const timerBar = document.getElementById('timer-bar');
    if (timerBar) {
        timerBar.style.width      = (timerPct * 100) + '%';
        timerBar.style.background = timerPct > 0.4 ? '#7F77DD'
                                  : timerPct > 0.2 ? '#EF9F27' : '#E24B4A';
    }
}

function showOverlay(title, body, btnText, btnAction) {
    document.getElementById('overlay-title').innerHTML = title;
    document.getElementById('overlay-body').innerHTML  = body;
    const btn       = document.getElementById('overlay-btn');
    btn.textContent = btnText;
    btn.onclick     = btnAction;
    document.getElementById('overlay').classList.remove('hidden');
}

function hideOverlay() {
    document.getElementById('overlay').classList.add('hidden');
}

// ── main loop ──
function gameLoop() {
    requestAnimationFrame(gameLoop);

    if (state.phase !== 'playing') {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        drawBackground(state.round);
        return;
    }

    state.timer--;
    if (state.shootCooldown > 0) state.shootCooldown--;

    movePlayer();
    updateShake();
    updateParticles();

    // update player bullets
    state.playerBullets.forEach(b => b.update());

    // player bullets vs bots
    state.playerBullets.forEach(bullet => {
        if (!bullet.active) return;

        if (bullet.x < 0 || bullet.x > canvas.width ||
            bullet.y < 0 || bullet.y > canvas.height) {
            bullet.deactivate(); return;
        }

        state.bots.forEach(bot => {
            if (!bot.alive) return;
            if (Utils.circleCollision(bullet, bot)) {
                bot.takeDamage(agentCfg.damage);
                bullet.deactivate();
                spawnParticles(bullet.x, bullet.y, '#E24B4A', 6);
                triggerShake(3);

                if (!bot.alive) {
                    const pts         = KILL_POINTS[bot.type];
                    state.score      += pts * ROUND_MULT[state.round - 1] / 100;
                    state.totalKills += 1;
                    spawnParticles(bot.x, bot.y,
                        bot.type === 'shooter' ? '#EF9F27' : '#E24B4A', 16);
                    triggerShake(7);
                    updateHUD();
                }
            }
        });
    });

    state.playerBullets = state.playerBullets.filter(b => b.active);

    // update bots + their bullets vs player
    state.bots.forEach(bot => {
        if (!bot.alive) return;
        bot.update(player, canvas.width);

        bot.bullets.forEach(bullet => {
            if (!bullet.active) return;

            if (bullet.x < 0 || bullet.x > canvas.width ||
                bullet.y < 0 || bullet.y > canvas.height) {
                bullet.deactivate(); return;
            }

            if (Utils.circleCollision(bullet, player)) {
                player.hp -= bullet.damage;
                bullet.deactivate();
                triggerShake(9);
                spawnParticles(player.x, player.y, '#7F77DD', 7);
                updateHUD();

                if (player.hp <= 0) {
                    player.hp   = 0;
                    state.phase = 'between';
                    endRound(false);
                }
            }
        });
    });

    // all bots dead — round won
    const allDead = state.bots.length > 0 && state.bots.every(b => !b.alive);
    if (allDead) { endRound(true); return; }

    // timer ran out — game over
    if (state.timer <= 0) { endRound(false); return; }

    // ── DRAW ──
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    ctx.save();
    ctx.translate(shakeX, shakeY);

    drawBackground(state.round);
    drawPlatforms();

    // bot bullets
    state.bots.forEach(bot => {
        if (!bot.alive) return;
        bot.bullets.forEach(b => b.draw(ctx));
    });

    // bots
    state.bots.forEach(bot => {
        if (!bot.alive) return;
        bot.draw(ctx, images.bot, images.bot2);
    });

    // player bullets
    state.playerBullets.forEach(b => b.draw(ctx));

    // player
    drawPlayer();

    ctx.restore();

    drawParticles();
    updateHUD();
}

// ── init ──
async function init() {
    await Promise.all([
        loadImage(`player_${PLAYER_AGENT.toLowerCase()}`,
                  `assets/images/player_${PLAYER_AGENT.toLowerCase()}.png`),
        loadImage('bot',  'assets/images/bot.png'),
        loadImage('bot2', 'assets/images/bot2.png'),
    ]);

    showOverlay(
        'WOLF STRIKE',
        `Agent: <strong style="color:#7F77DD">${PLAYER_AGENT}</strong><br><br>
         Eliminate all enemies on every platform to advance<br>
         If time runs out — Game Over<br><br>
         <span style="font-size:0.85rem;color:rgba(255,255,255,0.4);">
         A / D move &nbsp;·&nbsp; W / Space jump<br>
         Mouse aim &nbsp;·&nbsp; Click shoot
         </span>`,
        'ENTER ARENA',
        () => startRound(1)
    );

    gameLoop();
}

init();