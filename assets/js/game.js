const canvas  = document.getElementById('gameCanvas');
const ctx     = canvas.getContext('2d');

canvas.width  = window.innerWidth;
canvas.height = window.innerHeight;

window.addEventListener('resize', () => {
    canvas.width  = window.innerWidth;
    canvas.height = window.innerHeight;
});

const ROUND_CONFIG = [
    { bots: 3, timer: 60, survivalBonus: 500  },
    { bots: 4, timer: 60, survivalBonus: 600  },
    { bots: 5, timer: 55, survivalBonus: 750  },
    { bots: 6, timer: 50, survivalBonus: 900  },
    { bots: 7, timer: 45, survivalBonus: 1200 },
];

const KILL_POINTS = [100, 120, 140, 160, 180];

const images = {};
function loadImage(key, src) {
    return new Promise(resolve => {
        const img = new Image();
        img.onload  = () => { images[key] = img; resolve(); };
        img.onerror = () => { images[key] = null; resolve(); };
        img.src = src;
    });
}

const state = {
    round:          1,
    score:          0,
    totalKills:     0,
    playerHp:       PLAYER_MAX_HP,
    phase:          'starting',
    timer:          0,
    timerMax:       0,
    obstacles:      [],
    bots:           [],
    playerBullets:  [],
    keys:           {},
    mouse:          { x: canvas.width / 2, y: canvas.height / 2 },
    shootCooldown:  0,
    player: {
        x:       canvas.width  / 2,
        y:       canvas.height / 2,
        radius:  20,
        angle:   0,
    },
    regen: 20,
    gameOver: false,
};

canvas.addEventListener('mousemove', e => {
    const rect    = canvas.getBoundingClientRect();
    state.mouse.x = e.clientX - rect.left;
    state.mouse.y = e.clientY - rect.top;
});

canvas.addEventListener('click', () => {
    if (state.phase === 'playing') shootPlayerBullet();
});

window.addEventListener('keydown', e => { state.keys[e.code] = true; });
window.addEventListener('keyup',   e => { state.keys[e.code] = false; });

function shootPlayerBullet() {
    if (state.shootCooldown > 0) return;
    const angle = Utils.angleBetween(state.player, state.mouse);
    state.playerBullets.push(new Bullet(
        state.player.x, state.player.y,
        angle, PLAYER_BULLET_SPEED, PLAYER_DAMAGE, '#7F77DD'
    ));
    state.shootCooldown = 15;
}

function startRound(round) {
    const config        = ROUND_CONFIG[round - 1];
    state.round         = round;
    state.phase         = 'playing';
    state.timer         = config.timer * 60;
    state.timerMax      = config.timer * 60;
    state.playerBullets = [];
    state.obstacles     = Utils.generateObstacles(canvas.width, canvas.height, 8);

    state.player.x = canvas.width  / 2;
    state.player.y = canvas.height / 2;

    state.bots = [];
    for (let i = 0; i < config.bots; i++) {
        const pos = Utils.randomEdgePosition(canvas.width, canvas.height, 60);
        state.bots.push(new Bot(pos.x, pos.y, round));
    }

    updateHUD();
    hideOverlay();
}

function endRound(survived) {
    state.phase = 'between';

    if (!survived) {
        endGame(false);
        return;
    }

    const bonus   = ROUND_CONFIG[state.round - 1].survivalBonus;
    const hpBonus = Math.floor((state.playerHp / PLAYER_MAX_HP) * 100) * 5;
    state.score  += bonus + hpBonus;
    state.playerHp = Math.min(PLAYER_MAX_HP, state.playerHp + state.regen);

    updateHUD();

    if (state.round >= 5) {
        endGame(true);
        return;
    }

    showOverlay(
        'ROUND COMPLETE',
        `Round ${state.round} of 5 cleared<br>
         Survival bonus: +${bonus.toLocaleString()}<br>
         HP bonus: +${hpBonus}<br>
         HP restored: +${state.regen}<br><br>
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
            'GAME COMPLETE',
            `You survived all 5 rounds!<br><br>
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
    const form = new FormData();
    form.append('agent',           PLAYER_AGENT);
    form.append('score',           state.score);
    form.append('kills',           state.totalKills);
    form.append('rounds_survived', state.round - (state.gameOver && state.playerHp <= 0 ? 1 : 0));

    fetch('save_score.php', { method: 'POST', body: form })
        .then(r => r.json())
        .then(d => console.log('Score saved:', d.message))
        .catch(e => console.error('Save error:', e));
}

function updateHUD() {
    const pct    = state.playerHp / PLAYER_MAX_HP;
    const fill   = document.getElementById('hp-bar-fill');
    const hpText = document.getElementById('hp-text');
    const color  = pct > 0.5 ? '#1D9E75' : pct > 0.25 ? '#EF9F27' : '#E24B4A';

    fill.style.width      = (pct * 100) + '%';
    fill.style.background = color;
    hpText.textContent    = `${Math.max(0, state.playerHp)} / ${PLAYER_MAX_HP}`;

    document.getElementById('round-num').textContent  = `${state.round} / 5`;
    document.getElementById('score-num').textContent  = state.score.toLocaleString();

    const timerPct  = state.timer / state.timerMax;
    const timerBar  = document.getElementById('timer-bar');
    timerBar.style.width      = (timerPct * 100) + '%';
    timerBar.style.background = timerPct > 0.4 ? '#7F77DD' : timerPct > 0.2 ? '#EF9F27' : '#E24B4A';
}

function showOverlay(title, body, btnText, btnAction) {
    document.getElementById('overlay-title').innerHTML = title;
    document.getElementById('overlay-body').innerHTML  = body;
    const btn = document.getElementById('overlay-btn');
    btn.textContent  = btnText;
    btn.onclick      = btnAction;
    document.getElementById('overlay').classList.remove('hidden');
}

function hideOverlay() {
    document.getElementById('overlay').classList.add('hidden');
}

function movePlayer() {
    const k = state.keys;
    let dx = 0, dy = 0;

    if (k['KeyW']      || k['ArrowUp'])    dy -= PLAYER_SPEED;
    if (k['KeyS']      || k['ArrowDown'])  dy += PLAYER_SPEED;
    if (k['KeyA']      || k['ArrowLeft'])  dx -= PLAYER_SPEED;
    if (k['KeyD']      || k['ArrowRight']) dx += PLAYER_SPEED;

    if (dx !== 0 && dy !== 0) {
        dx *= 0.707;
        dy *= 0.707;
    }

    const nx = state.player.x + dx;
    const ny = state.player.y + dy;

    const testX = { x: nx, y: state.player.y, radius: state.player.radius };
    const testY = { x: state.player.x, y: ny,  radius: state.player.radius };

    const hitX = state.obstacles.some(o => Utils.rectCollision(testX, o));
    const hitY = state.obstacles.some(o => Utils.rectCollision(testY, o));

    if (!hitX) state.player.x = Utils.clamp(nx, state.player.radius, canvas.width  - state.player.radius);
    if (!hitY) state.player.y = Utils.clamp(ny, state.player.radius, canvas.height - state.player.radius);

    state.player.angle = Utils.angleBetween(state.player, state.mouse);
}

function drawArena() {
    ctx.fillStyle = '#0d0d18';
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    for (let gx = 0; gx < canvas.width; gx += 40) {
        ctx.strokeStyle = 'rgba(255,255,255,0.025)';
        ctx.lineWidth   = 0.5;
        ctx.beginPath();
        ctx.moveTo(gx, 0);
        ctx.lineTo(gx, canvas.height);
        ctx.stroke();
    }
    for (let gy = 0; gy < canvas.height; gy += 40) {
        ctx.beginPath();
        ctx.moveTo(0, gy);
        ctx.lineTo(canvas.width, gy);
        ctx.stroke();
    }

    state.obstacles.forEach(o => {
        ctx.fillStyle   = '#1a1a2e';
        ctx.strokeStyle = 'rgba(127,119,221,0.3)';
        ctx.lineWidth   = 1;
        ctx.fillRect(o.x, o.y, o.w, o.h);
        ctx.strokeRect(o.x, o.y, o.w, o.h);
    });
}

function drawPlayer() {
    const p = state.player;

    ctx.save();
    ctx.translate(p.x, p.y);
    ctx.rotate(p.angle + Math.PI / 2);

    if (images.player && images.player.complete) {
        ctx.drawImage(images.player, -p.radius, -p.radius, p.radius * 2, p.radius * 2);
    } else {
        ctx.fillStyle = '#7F77DD';
        ctx.beginPath();
        ctx.arc(0, 0, p.radius, 0, Math.PI * 2);
        ctx.fill();
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(-3, -p.radius, 6, 12);
    }

    ctx.restore();
}

function drawPlayerBullets() {
    state.playerBullets.forEach(b => b.draw(ctx));
}

let frameCount = 0;

function gameLoop() {
    requestAnimationFrame(gameLoop);

    if (state.phase !== 'playing') {
        drawArena();
        drawPlayer();
        return;
    }

    frameCount++;

    movePlayer();

    state.timer--;
    if (state.shootCooldown > 0) state.shootCooldown--;

    state.playerBullets.forEach(b => b.update());

    state.playerBullets.forEach(bullet => {
        if (!bullet.active) return;

        if (bullet.x < 0 || bullet.x > canvas.width ||
            bullet.y < 0 || bullet.y > canvas.height) {
            bullet.deactivate();
            return;
        }

        state.obstacles.forEach(o => {
            if (Utils.rectCollision(bullet, o)) bullet.deactivate();
        });

        state.bots.forEach(bot => {
            if (!bot.alive) return;
            if (Utils.circleCollision(bullet, bot)) {
                bot.takeDamage(PLAYER_DAMAGE);
                bullet.deactivate();
                if (!bot.alive) {
                    const pts = KILL_POINTS[state.round - 1];
                    state.score      += pts;
                    state.totalKills += 1;
                    updateHUD();
                }
            }
        });
    });

    state.playerBullets = state.playerBullets.filter(b => b.active);

    state.bots.forEach(bot => {
        if (!bot.alive) return;
        bot.update(state.player, state.obstacles, canvas.width, canvas.height);

        bot.bullets.forEach(bullet => {
            if (!bullet.active) return;

            if (bullet.x < 0 || bullet.x > canvas.width ||
                bullet.y < 0 || bullet.y > canvas.height) {
                bullet.deactivate();
                return;
            }

            state.obstacles.forEach(o => {
                if (Utils.rectCollision(bullet, o)) bullet.deactivate();
            });

            if (Utils.circleCollision(bullet, state.player)) {
                state.playerHp -= bullet.damage;
                bullet.deactivate();
                updateHUD();

                if (state.playerHp <= 0) {
                    state.playerHp = 0;
                    state.phase    = 'between';
                    endRound(false);
                }
            }
        });
    });

    const allDead = state.bots.every(b => !b.alive);
    if (allDead && state.bots.length > 0) {
        endRound(true);
        return;
    }

    if (state.timer <= 0) {
        endRound(state.playerHp > 0);
        return;
    }

    drawArena();

    state.bots.forEach(bot => bot.draw(ctx, images.bot));

    drawPlayerBullets();
    drawPlayer();

    updateHUD();
}

async function init() {
    await Promise.all([
        loadImage('player', `assets/images/player_${PLAYER_AGENT.toLowerCase()}.png`),
        loadImage('bot',    'assets/images/bot.png'),
    ]);

    showOverlay(
        'WOLF STRIKE',
        `Agent: ${PLAYER_AGENT}<br>5 rounds · Survive or eliminate all bots<br>
         WASD to move · Mouse to aim · Click to shoot`,
        'START GAME',
        () => startRound(1)
    );

    gameLoop();
}

init();