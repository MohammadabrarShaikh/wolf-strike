<?php
session_start();
require_once 'db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $email    = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if (empty($username) || empty($email) || empty($password) || empty($confirm)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$username' OR email='$email'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Username or email already exists.";
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $insert = mysqli_query($conn, "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$hashed')");
            if ($insert) {
                $success = "Account created. You may now enter the arena.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — Wolf Strike</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;900&family=Rajdhani:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --wolf-purple:    #7F77DD;
            --wolf-purple-2:  #534AB7;
            --wolf-teal:      #1D9E75;
            --wolf-dark:      #05050f;
            --wolf-card:      rgba(255,255,255,0.03);
            --wolf-border:    rgba(127,119,221,0.2);
            --wolf-text:      #e8e8f0;
            --wolf-muted:     rgba(232,232,240,0.45);
            --glow-purple:    0 0 20px rgba(127,119,221,0.5);
            --glow-teal:      0 0 20px rgba(29,158,117,0.5);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: var(--wolf-dark);
            font-family: 'Rajdhani', sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
            color: var(--wolf-text);
        }

        /* ── animated grid background ── */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(127,119,221,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(127,119,221,0.04) 1px, transparent 1px);
            background-size: 40px 40px;
            animation: gridMove 20s linear infinite;
            pointer-events: none;
            z-index: 0;
        }

        @keyframes gridMove {
            0%   { transform: translateY(0); }
            100% { transform: translateY(40px); }
        }

        /* ── orb glows ── */
        body::after {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 600px 400px at 15% 50%,  rgba(127,119,221,0.1) 0%, transparent 70%),
                radial-gradient(ellipse 500px 300px at 85% 20%,  rgba(29,158,117,0.08) 0%, transparent 70%),
                radial-gradient(ellipse 400px 400px at 60% 90%,  rgba(83,74,183,0.07) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        .page-wrap {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 16px;
        }

        /* ── card ── */
        .register-card {
            width: 100%;
            max-width: 440px;
            background: var(--wolf-card);
            border: 1px solid var(--wolf-border);
            border-radius: 20px;
            padding: 44px 40px;
            backdrop-filter: blur(24px);
            position: relative;
            animation: cardIn 0.6s cubic-bezier(0.16,1,0.3,1) both;
        }

        @keyframes cardIn {
            from { opacity: 0; transform: translateY(32px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0)    scale(1); }
        }

        /* card top accent line */
        .register-card::before {
            content: '';
            position: absolute;
            top: 0; left: 20%; right: 20%;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--wolf-purple), transparent);
            border-radius: 1px;
        }

        /* ── logo & title ── */
        .wolf-logo {
            text-align: center;
            margin-bottom: 8px;
        }

        .wolf-logo-text {
            font-family: 'Orbitron', monospace;
            font-size: 1.9rem;
            font-weight: 900;
            letter-spacing: 8px;
            color: #ffffff;
            text-shadow:
                0 0 10px rgba(127,119,221,0.9),
                0 0 30px rgba(127,119,221,0.5),
                0 0 60px rgba(127,119,221,0.2);
            animation: titlePulse 3s ease-in-out infinite;
        }

        @keyframes titlePulse {
            0%, 100% { text-shadow: 0 0 10px rgba(127,119,221,0.9), 0 0 30px rgba(127,119,221,0.5), 0 0 60px rgba(127,119,221,0.2); }
            50%       { text-shadow: 0 0 15px rgba(127,119,221,1),   0 0 45px rgba(127,119,221,0.7), 0 0 90px rgba(127,119,221,0.3); }
        }

        .wolf-subtitle {
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.8rem;
            font-weight: 500;
            letter-spacing: 4px;
            color: var(--wolf-muted);
            text-align: center;
            margin-top: 4px;
            margin-bottom: 28px;
        }

        /* ── divider ── */
        .wolf-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
        }

        .wolf-divider-line {
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(127,119,221,0.3), transparent);
        }

        .wolf-divider-text {
            font-family: 'Orbitron', monospace;
            font-size: 0.6rem;
            letter-spacing: 3px;
            color: rgba(127,119,221,0.6);
        }

        /* ── form labels ── */
        .field-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 2px;
            color: var(--wolf-muted);
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .field-icon {
            width: 18px;
            height: 18px;
            opacity: 0.7;
            flex-shrink: 0;
        }

        /* ── inputs ── */
        .wolf-input {
            width: 100%;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(127,119,221,0.2);
            border-radius: 10px;
            padding: 12px 16px;
            font-family: 'Rajdhani', sans-serif;
            font-size: 1rem;
            font-weight: 500;
            color: #ffffff;
            outline: none;
            transition: all 0.25s;
            letter-spacing: 0.5px;
        }

        .wolf-input::placeholder {
            color: rgba(255,255,255,0.2);
            font-weight: 400;
        }

        .wolf-input:focus {
            border-color: var(--wolf-purple);
            background: rgba(127,119,221,0.06);
            box-shadow: 0 0 0 3px rgba(127,119,221,0.12), var(--glow-purple);
        }

        .wolf-input:focus + .input-glow {
            opacity: 1;
        }

        .field-wrap {
            position: relative;
            margin-bottom: 20px;
        }

        /* ── submit button ── */
        .wolf-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--wolf-purple), var(--wolf-purple-2));
            border: none;
            border-radius: 10px;
            font-family: 'Orbitron', monospace;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 4px;
            color: #ffffff;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all 0.25s;
            margin-top: 8px;
        }

        .wolf-btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg,
                rgba(255,255,255,0.15) 0%,
                transparent 50%);
            opacity: 0;
            transition: opacity 0.25s;
        }

        .wolf-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(127,119,221,0.4), var(--glow-purple);
        }

        .wolf-btn:hover::before { opacity: 1; }

        .wolf-btn:active {
            transform: translateY(0);
            box-shadow: none;
        }

        /* ── scan line animation on button ── */
        .wolf-btn::after {
            content: '';
            position: absolute;
            top: -100%;
            left: 0; right: 0;
            height: 100%;
            background: linear-gradient(transparent, rgba(255,255,255,0.08), transparent);
            animation: btnScan 3s linear infinite;
        }

        @keyframes btnScan {
            0%   { top: -100%; }
            100% { top: 200%;  }
        }

        /* ── alerts ── */
        .wolf-alert {
            border-radius: 10px;
            padding: 12px 16px;
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.95rem;
            font-weight: 500;
            letter-spacing: 0.5px;
            margin-bottom: 20px;
            border-left: 3px solid;
            animation: alertIn 0.3s ease both;
        }

        @keyframes alertIn {
            from { opacity: 0; transform: translateX(-8px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        .wolf-alert-error {
            background: rgba(226,75,74,0.1);
            border-color: #E24B4A;
            color: #f09595;
        }

        .wolf-alert-success {
            background: rgba(29,158,117,0.1);
            border-color: #1D9E75;
            color: #5DCAA5;
        }

        /* ── footer link ── */
        .wolf-footer-text {
            text-align: center;
            margin-top: 24px;
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--wolf-muted);
            letter-spacing: 0.5px;
        }

        .wolf-link {
            color: var(--wolf-purple);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
            position: relative;
        }

        .wolf-link::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0; right: 0;
            height: 1px;
            background: var(--wolf-purple);
            transform: scaleX(0);
            transition: transform 0.2s;
        }

        .wolf-link:hover {
            color: #9088e8;
            text-shadow: 0 0 8px rgba(127,119,221,0.6);
        }

        .wolf-link:hover::after { transform: scaleX(1); }

        /* ── corner decorations ── */
        .corner-tl, .corner-br {
            position: absolute;
            width: 16px;
            height: 16px;
        }

        .corner-tl {
            top: 12px; left: 12px;
            border-top: 2px solid rgba(127,119,221,0.5);
            border-left: 2px solid rgba(127,119,221,0.5);
            border-radius: 3px 0 0 0;
        }

        .corner-br {
            bottom: 12px; right: 12px;
            border-bottom: 2px solid rgba(127,119,221,0.5);
            border-right: 2px solid rgba(127,119,221,0.5);
            border-radius: 0 0 3px 0;
        }

        /* ── staggered field animation ── */
        .field-wrap:nth-child(1) { animation: fieldIn 0.5s 0.1s cubic-bezier(0.16,1,0.3,1) both; }
        .field-wrap:nth-child(2) { animation: fieldIn 0.5s 0.2s cubic-bezier(0.16,1,0.3,1) both; }
        .field-wrap:nth-child(3) { animation: fieldIn 0.5s 0.3s cubic-bezier(0.16,1,0.3,1) both; }
        .field-wrap:nth-child(4) { animation: fieldIn 0.5s 0.4s cubic-bezier(0.16,1,0.3,1) both; }

        @keyframes fieldIn {
            from { opacity: 0; transform: translateX(-16px); }
            to   { opacity: 1; transform: translateX(0); }
        }
    </style>
</head>
<body>
<div class="page-wrap">
    <div class="register-card">

        <div class="corner-tl"></div>
        <div class="corner-br"></div>

        <div class="wolf-logo">
            <div class="wolf-logo-text">WOLF STRIKE</div>
            <div class="wolf-subtitle">CREATE ACCOUNT</div>
        </div>

        <div class="wolf-divider">
            <div class="wolf-divider-line"></div>
            <div class="wolf-divider-text">REGISTER</div>
            <div class="wolf-divider-line"></div>
        </div>

        <?php if ($error): ?>
            <div class="wolf-alert wolf-alert-error">
                ⚠ <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="wolf-alert wolf-alert-success">
                ✓ <?php echo $success; ?>
                <a href="login.php" class="wolf-link" style="margin-left:8px;">
                    Login here →
                </a>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">

            <div class="field-wrap">
                <label class="field-label">
                    <svg class="field-icon" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    Username
                </label>
                <input type="text" name="username" class="wolf-input"
                       placeholder="Choose your callsign" required
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>

            <div class="field-wrap">
                <label class="field-label">
                    <svg class="field-icon" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                    Email
                </label>
                <input type="email" name="email" class="wolf-input"
                       placeholder="Enter your email" required
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="field-wrap">
                <label class="field-label">
                    <svg class="field-icon" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    Password
                </label>
                <input type="password" name="password" class="wolf-input"
                       placeholder="Min 6 characters" required>
            </div>

            <div class="field-wrap">
                <label class="field-label">
                    <svg class="field-icon" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    Confirm Password
                </label>
                <input type="password" name="confirm_password" class="wolf-input"
                       placeholder="Repeat your password" required>
            </div>

            <button type="submit" class="wolf-btn">
                ENTER THE PACK
            </button>

        </form>

        <div class="wolf-footer-text">
            Already have an account?
            <a href="login.php" class="wolf-link">Login here →</a>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<canvas id="bgCanvas"></canvas>

<script>
const bgCanvas  = document.getElementById('bgCanvas');
const bgCtx     = bgCanvas.getContext('2d');

bgCanvas.style.cssText = `
    position: fixed;
    inset: 0;
    width: 100%;
    height: 100%;
    z-index: 0;
    pointer-events: none;
`;

bgCanvas.width  = window.innerWidth;
bgCanvas.height = window.innerHeight;

window.addEventListener('resize', () => {
    bgCanvas.width  = window.innerWidth;
    bgCanvas.height = window.innerHeight;
});

const SHAPES = [
    // gaming controller buttons
    { type: 'circle',   symbol: '●', color: '#E24B4A' },
    { type: 'circle',   symbol: '■', color: '#7F77DD' },
    { type: 'circle',   symbol: '▲', color: '#1D9E75' },
    { type: 'circle',   symbol: '✕', color: '#EF9F27' },
    // geometric
    { type: 'triangle', symbol: null, color: '#7F77DD' },
    { type: 'hexagon',  symbol: null, color: '#1D9E75' },
    { type: 'diamond',  symbol: null, color: '#534AB7' },
    { type: 'triangle', symbol: null, color: '#EF9F27' },
    { type: 'hexagon',  symbol: null, color: '#E24B4A' },
    { type: 'diamond',  symbol: null, color: '#7F77DD' },
    // extra circles with symbols
    { type: 'circle',   symbol: '●', color: '#1D9E75' },
    { type: 'circle',   symbol: '■', color: '#EF9F27' },
    { type: 'triangle', symbol: null, color: '#534AB7' },
    { type: 'hexagon',  symbol: null, color: '#7F77DD' },
    { type: 'diamond',  symbol: null, color: '#E24B4A' },
];

function randomBetween(a, b) {
    return Math.random() * (b - a) + a;
}

const particles = SHAPES.map((s, i) => ({
    type:    s.type,
    symbol:  s.symbol,
    color:   s.color,
    x:       randomBetween(0, window.innerWidth),
    y:       randomBetween(0, window.innerHeight),
    size:    randomBetween(16, 38),
    vx:      randomBetween(-0.35, 0.35),
    vy:      randomBetween(-0.35, 0.35),
    angle:   randomBetween(0, Math.PI * 2),
    spin:    randomBetween(-0.008, 0.008),
    alpha:   randomBetween(0.06, 0.18),
    pulse:   randomBetween(0, Math.PI * 2),
    pulseSpeed: randomBetween(0.015, 0.03),
}));

function drawTriangle(ctx, x, y, size, angle) {
    ctx.save();
    ctx.translate(x, y);
    ctx.rotate(angle);
    ctx.beginPath();
    ctx.moveTo(0, -size);
    ctx.lineTo(size * 0.866, size * 0.5);
    ctx.lineTo(-size * 0.866, size * 0.5);
    ctx.closePath();
    ctx.restore();
}

function drawHexagon(ctx, x, y, size, angle) {
    ctx.save();
    ctx.translate(x, y);
    ctx.rotate(angle);
    ctx.beginPath();
    for (let i = 0; i < 6; i++) {
        const a = (Math.PI / 3) * i;
        const px = Math.cos(a) * size;
        const py = Math.sin(a) * size;
        i === 0 ? ctx.moveTo(px, py) : ctx.lineTo(px, py);
    }
    ctx.closePath();
    ctx.restore();
}

function drawDiamond(ctx, x, y, size, angle) {
    ctx.save();
    ctx.translate(x, y);
    ctx.rotate(angle);
    ctx.beginPath();
    ctx.moveTo(0, -size);
    ctx.lineTo(size * 0.6, 0);
    ctx.lineTo(0, size);
    ctx.lineTo(-size * 0.6, 0);
    ctx.closePath();
    ctx.restore();
}

function drawCircleBtn(ctx, x, y, size, symbol, color, alpha) {
    ctx.save();
    ctx.translate(x, y);

    ctx.strokeStyle = color;
    ctx.lineWidth   = 1.5;
    ctx.globalAlpha = alpha;
    ctx.shadowColor = color;
    ctx.shadowBlur  = 12;
    ctx.beginPath();
    ctx.arc(0, 0, size, 0, Math.PI * 2);
    ctx.stroke();

    ctx.font        = `${size * 0.9}px Arial`;
    ctx.fillStyle   = color;
    ctx.textAlign   = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(symbol, 0, 1);

    ctx.restore();
}

function bgLoop() {
    bgCtx.clearRect(0, 0, bgCanvas.width, bgCanvas.height);

    particles.forEach(p => {
        p.x     += p.vx;
        p.y     += p.vy;
        p.angle += p.spin;
        p.pulse += p.pulseSpeed;

        const pulsedAlpha = p.alpha + Math.sin(p.pulse) * 0.04;
        const glowSize    = p.size  + Math.sin(p.pulse) * 3;

        if (p.x < -80)  p.x = bgCanvas.width  + 80;
        if (p.x > bgCanvas.width  + 80) p.x = -80;
        if (p.y < -80)  p.y = bgCanvas.height + 80;
        if (p.y > bgCanvas.height + 80) p.y = -80;

        bgCtx.globalAlpha = pulsedAlpha;
        bgCtx.strokeStyle = p.color;
        bgCtx.fillStyle   = p.color;
        bgCtx.lineWidth   = 1.5;
        bgCtx.shadowColor = p.color;
        bgCtx.shadowBlur  = 15;

        if (p.type === 'circle' && p.symbol) {
            drawCircleBtn(bgCtx, p.x, p.y, glowSize, p.symbol, p.color, pulsedAlpha);
        } else if (p.type === 'triangle') {
            drawTriangle(bgCtx, p.x, p.y, glowSize, p.angle);
            bgCtx.stroke();
        } else if (p.type === 'hexagon') {
            drawHexagon(bgCtx, p.x, p.y, glowSize, p.angle);
            bgCtx.stroke();
        } else if (p.type === 'diamond') {
            drawDiamond(bgCtx, p.x, p.y, glowSize, p.angle);
            bgCtx.stroke();
        }

        bgCtx.shadowBlur  = 0;
        bgCtx.globalAlpha = 1;
    });

    requestAnimationFrame(bgLoop);
}

bgLoop();
</script>
</body>
</html>