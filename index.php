<?php
// index.php (Đặt tại thư mục gốc của hosting)
session_start();

// Cấu hình các Header phản hồi JSON và chống lỗi bảo mật CORS cho API (giữ nguyên cho Python)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$action = isset($_REQUEST['action']) ? strtolower(trim($_REQUEST['action'])) : '';

switch ($action) {
    case 'register':
        header("Content-Type: application/json; charset=UTF-8");
        if (file_exists("api/register.php")) { require_once "api/register.php"; } 
        else { echo json_encode(["status" => "error", "message" => "Hệ thống thiếu file xử lý đăng ký!"]); }
        break;

    case 'login':
        header("Content-Type: application/json; charset=UTF-8");
        if (file_exists("api/login.php")) { require_once "api/login.php"; } 
        else { echo json_encode(["status" => "error", "message" => "Hệ thống thiếu file xử lý đăng nhập!"]); }
        break;

    case 'web_login':
        header("Content-Type: application/json; charset=UTF-8");
        if (!file_exists("api/db.php")) {
            echo json_encode(["status" => "error", "message" => "Thiếu file cấu hình database api/db.php!"]);
            exit();
        }
        require_once "api/db.php"; 
        
        $data = json_decode(file_get_contents("php://input"), true);
        $user = trim($data['username'] ?? '');
        $pass = trim($data['password'] ?? '');
        
        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = :user");
            $stmt->execute(['user' => $user]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row && password_verify($pass, $row['password'])) {
                $_SESSION['user_logged'] = $row['username']; 
                echo json_encode(["status" => "success", "message" => "Đăng nhập thành công!"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Tài khoản hoặc mật khẩu không chính xác!"]);
            }
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => "Lỗi kết nối dữ liệu: " . $e->getMessage()]);
        }
        break;

    default:
        if (isset($_SESSION['user_logged'])) {
            header("Location: home.php");
            exit();
        }
        header("Content-Type: text/html; charset=UTF-8");
        ?>
        <!DOCTYPE html>
        <html lang="vi">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Cổng Thành Viên - AI Video System</title>
            <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            
            <style>
                * { 
                    box-sizing: border-box; 
                    margin: 0; 
                    padding: 0; 
                    font-family: 'Plus Jakarta Sans', sans-serif; 
                }
                
                body { 
                    background: #020205; 
                    color: #fff; 
                    min-height: 100vh; 
                    display: flex; 
                    justify-content: center; 
                    align-items: center; 
                    overflow: hidden;
                    position: relative;
                    padding: 20px;
                }

                #particle-canvas {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    z-index: 0;
                    pointer-events: none;
                }

                /* --- CONTAINER LIQUID GLASS SIÊU MƯỢT --- */
                .liquid-glass-wrapper {
                    position: relative;
                    width: 100%;
                    max-width: 430px;
                    padding: 3px; 
                    border-radius: 28px;
                    overflow: hidden;
                    z-index: 1;
                    box-shadow: 0 40px 80px rgba(0, 0, 0, 0.7);
                    transition: transform 0.5s cubic-bezier(0.25, 1, 0.5, 1);
                    will-change: transform;
                }

                /* Nền ánh sáng lỏng tối ưu hóa GPU (Không giật FPS) */
                .liquid-glass-wrapper::before {
                    content: '';
                    position: absolute;
                    top: -20%;
                    left: -20%;
                    width: 140%;
                    height: 140%;
                    background: radial-gradient(circle at center, rgba(0, 229, 255, 0.25) 0%, rgba(26, 115, 232, 0.1) 40%, transparent 70%);
                    background-size: 200% 200%;
                    animation: liquidMove 8s ease infinite alternate;
                    z-index: -2;
                    will-change: background-position;
                }

                /* Viền chạy ánh sáng Neon */
                .liquid-glass-wrapper::after {
                    content: '';
                    position: absolute;
                    inset: 0;
                    border-radius: 28px;
                    padding: 2px;
                    background: linear-gradient(135deg, rgba(26, 115, 232, 0.4), rgba(0, 229, 255, 0.4), transparent, rgba(26, 115, 232, 0.2));
                    background-size: 300% 300%;
                    animation: borderGlow 6s linear infinite;
                    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
                    mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
                    -webkit-mask-composite: xor;
                    mask-composite: exclude;
                    pointer-events: none;
                }

                .liquid-glass-wrapper:hover {
                    transform: translateY(-4px) scale(1.01);
                }

                @keyframes liquidMove {
                    0% { background-position: 0% 0%; }
                    50% { background-position: 100% 100%; }
                    100% { background-position: 0% 100%; }
                }

                @keyframes borderGlow {
                    0% { background-position: 0% 50%; }
                    50% { background-position: 100% 50%; }
                    100% { background-position: 0% 50%; }
                }

                /* Thân chính Container */
                .auth-container { 
                    position: relative;
                    background: rgba(10, 11, 18, 0.75); 
                    backdrop-filter: blur(35px) saturate(200%);
                    -webkit-backdrop-filter: blur(35px) saturate(200%);
                    padding: 40px 35px; 
                    border-radius: 26px; 
                    width: 100%; 
                    text-align: center;
                    z-index: 1;
                    overflow: hidden;
                }

                /* --- CƠ CHẾ KHỐI CO GIÃN ĐỘ CAO MƯỢT MÀ --- */
                .form-slider-box {
                    position: relative;
                    width: 100%;
                    transition: max-height 0.6s cubic-bezier(0.25, 1, 0.5, 1), opacity 0.4s ease;
                    max-height: 340px; /* Đăng nhập */
                    overflow: hidden;
                }

                .form-slider-box.expand-mode {
                    max-height: 440px; /* Tự mở rộng khi là Đăng ký */
                }

                .form-content-wrapper {
                    position: absolute;
                    width: 100%;
                    top: 0;
                    left: 0;
                    transition: transform 0.5s cubic-bezier(0.25, 1, 0.5, 1), opacity 0.4s ease;
                }

                /* Trạng thái ẩn/hiện mượt của các Form */
                .form-content-wrapper.login-side {
                    opacity: 1;
                    transform: translateX(0);
                    pointer-events: auto;
                }
                .form-content-wrapper.register-side {
                    opacity: 0;
                    transform: translateX(50px);
                    pointer-events: none;
                }

                /* Khi đổi sang tab Đăng Ký */
                .register-active .login-side {
                    opacity: 0;
                    transform: translateX(-50px);
                    pointer-events: none;
                }
                .register-active .register-side {
                    opacity: 1;
                    transform: translateX(0);
                    pointer-events: auto;
                }

                /* --- ELEMENT PHỤ TRỢ THIẾT KẾ --- */
                .logo-section i {
                    font-size: 34px;
                    color: #00e5ff;
                    margin-bottom: 12px;
                    filter: drop-shadow(0 0 8px rgba(0, 229, 255, 0.4));
                }
                
                h2 { 
                    font-size: 24px;
                    font-weight: 700;
                    margin-bottom: 25px; 
                    letter-spacing: -0.5px;
                    background: linear-gradient(135deg, #ffffff 30%, #a5b4fc 100%); 
                    -webkit-background-clip: text; 
                    -webkit-text-fill-color: transparent;
                }

                .tabs { 
                    display: flex; 
                    margin-bottom: 30px; 
                    background: rgba(255, 255, 255, 0.03); 
                    border: 1px solid rgba(255, 255, 255, 0.05);
                    border-radius: 30px; 
                    padding: 4px; 
                }
                .tab { 
                    flex: 1; 
                    padding: 11px; 
                    cursor: pointer; 
                    border-radius: 26px; 
                    font-size: 14px;
                    font-weight: 600; 
                    color: #94a3b8; 
                    transition: all 0.4s cubic-bezier(0.25, 1, 0.5, 1); 
                }
                .tab.active { 
                    background: linear-gradient(135deg, #1a73e8, #00e5ff); 
                    color: white; 
                    box-shadow: 0 4px 15px rgba(0, 229, 255, 0.2);
                }

                .form-group { text-align: left; margin-bottom: 20px; }
                label { display: block; margin-bottom: 8px; font-size: 13.5px; font-weight: 500; color: #94a3b8; }
                .input-wrapper { position: relative; }
                .input-wrapper i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #4b5563; font-size: 15px; transition: all 0.3s; }
                
                input { 
                    width: 100%; 
                    padding: 13px 15px 13px 46px; 
                    border-radius: 14px; 
                    border: 1px solid rgba(255, 255, 255, 0.05); 
                    background: rgba(0, 0, 0, 0.3); 
                    color: #fff; 
                    font-size: 15px; 
                    outline: none; 
                    transition: all 0.3s ease; 
                }
                input:focus { 
                    border-color: #00e5ff; 
                    background: rgba(0, 0, 0, 0.5);
                    box-shadow: 0 0 15px rgba(0, 229, 255, 0.1); 
                }
                input:focus + i { color: #00e5ff; }
                
                button { 
                    width: 100%; 
                    padding: 14px; 
                    border: none; 
                    border-radius: 14px; 
                    background: linear-gradient(135deg, #1a73e8, #00e5ff); 
                    color: white; 
                    font-size: 15px; 
                    font-weight: 600; 
                    cursor: pointer; 
                    box-shadow: 0 4px 20px rgba(26, 115, 232, 0.2);
                    transition: all 0.4s cubic-bezier(0.25, 1, 0.5, 1); 
                }
                button:hover { transform: translateY(-1px); box-shadow: 0 6px 25px rgba(0, 229, 255, 0.3); }
                
                #message { 
                    margin-top: 15px; 
                    font-size: 14px; 
                    padding: 12px;
                    border-radius: 12px;
                    display: none;
                }
                .success { background: rgba(52, 168, 83, 0.1); color: #34a853; border: 1px solid rgba(52, 168, 83, 0.15); }
                .error { background: rgba(234, 67, 53, 0.1); color: #ea4335; border: 1px solid rgba(234, 67, 53, 0.15); }
            </style>
        </head>
        <body>

        <canvas id="particle-canvas"></canvas>

        <div class="liquid-glass-wrapper">
            <div class="auth-container">
                <div class="logo-section">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                    <h2 id="authTitle">Đăng Nhập Hệ Thống</h2>
                </div>
                
                <div class="tabs">
                    <div class="tab active" id="tabLogin" onclick="switchForm('login')">Đăng Nhập</div>
                    <div class="tab" id="tabRegister" onclick="switchForm('register')">Đăng Ký</div>
                </div>
                
                <!-- BỘ TRƯỢT KHUNG CO GIÃN ĐỘ CAO -->
                <div class="form-slider-box" id="formSlider">
                    
                    <!-- FORM ĐĂNG NHẬP -->
                    <div class="form-content-wrapper login-side" id="loginWrapper">
                        <form id="loginForm">
                            <div class="form-group">
                                <label>Tên tài khoản</label>
                                <div class="input-wrapper">
                                    <input type="text" id="loginUser" required placeholder="Nhập tên đăng nhập...">
                                    <i class="fa-solid fa-user"></i>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Mật khẩu</label>
                                <div class="input-wrapper">
                                    <input type="password" id="loginPass" required placeholder="Nhập mật khẩu...">
                                    <i class="fa-solid fa-lock"></i>
                                </div>
                            </div>
                            <button type="submit"><i class="fa-solid fa-right-to-bracket"></i> Đăng Nhập Ngay</button>
                        </form>
                    </div>

                    <!-- FORM ĐĂNG KÝ -->
                    <div class="form-content-wrapper register-side" id="registerWrapper">
                        <form id="registerForm">
                            <div class="form-group">
                                <label>Tên tài khoản mới</label>
                                <div class="input-wrapper">
                                    <input type="text" id="regUser" required placeholder="Tạo tên đăng nhập...">
                                    <i class="fa-solid fa-user-plus"></i>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Email (Không bắt buộc)</label>
                                <div class="input-wrapper">
                                    <input type="email" id="regEmail" placeholder="Nhập email của bạn...">
                                    <i class="fa-solid fa-envelope"></i>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Mật khẩu</label>
                                <div class="input-wrapper">
                                    <input type="password" id="regPass" required placeholder="Tạo mật khẩu bảo mật...">
                                    <i class="fa-solid fa-key"></i>
                                </div>
                            </div>
                            <button type="submit" style="background: linear-gradient(135deg, #34a853, #2bb673);"><i class="fa-solid fa-user-check"></i> Đăng Ký Tài Khoản</button>
                        </form>
                    </div>

                </div>

                <div id="message"></div>
            </div>
        </div>

        <script>
            function switchForm(mode) {
                const msgDiv = document.getElementById('message');
                const slider = document.getElementById('formSlider');
                msgDiv.innerHTML = '';
                msgDiv.style.display = 'none';
                
                if(mode === 'login') {
                    document.getElementById('authTitle').innerText = 'Đăng Nhập Hệ Thống';
                    document.getElementById('tabLogin').classList.add('active');
                    document.getElementById('tabRegister').classList.remove('active');
                    slider.classList.remove('expand-mode');
                } else {
                    document.getElementById('authTitle').innerText = 'Tạo Tài Khoản Mới';
                    document.getElementById('tabRegister').classList.add('active');
                    document.getElementById('tabLogin').classList.remove('active');
                    slider.classList.add('expand-mode');
                }
            }

            // XỬ LÝ API ĐĂNG KÝ
            document.getElementById('registerForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const msgDiv = document.getElementById('message');
                msgDiv.style.display = 'block';
                msgDiv.innerHTML = "<i class='fa-solid fa-spinner fa-spin'></i> Đang đăng ký...";
                msgDiv.className = "";

                fetch('index.php?action=register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        username: document.getElementById('regUser').value,
                        email: document.getElementById('regEmail').value,
                        password: document.getElementById('regPass').value
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        msgDiv.innerHTML = "🎉 Đăng ký thành công!";
                        msgDiv.className = 'success';
                        document.getElementById('registerForm').reset();
                        setTimeout(() => { switchForm('login'); }, 1500);
                    } else {
                        msgDiv.innerHTML = "❌ " + data.message;
                        msgDiv.className = 'error';
                    }
                }).catch(() => { msgDiv.innerHTML = "❌ Lỗi kết nối!"; msgDiv.className = 'error'; });
            });

            // XỬ LÝ API ĐĂNG NHẬP
            document.getElementById('loginForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const msgDiv = document.getElementById('message');
                msgDiv.style.display = 'block';
                msgDiv.innerHTML = "<i class='fa-solid fa-spinner fa-spin'></i> Đang xác thực...";
                msgDiv.className = "";

                fetch('index.php?action=web_login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        username: document.getElementById('loginUser').value,
                        password: document.getElementById('loginPass').value
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        msgDiv.innerHTML = "🔓 Thành công! Đang chuyển hướng...";
                        msgDiv.className = 'success';
                        setTimeout(() => { window.location.href = 'home.php'; }, 1000);
                    } else {
                        msgDiv.innerHTML = "❌ " + data.message;
                        msgDiv.className = 'error';
                    }
                }).catch(() => { msgDiv.innerHTML = "❌ Lỗi xác thực!"; msgDiv.className = 'error'; });
            });

            // --- CANVAS PARTICLES (GIỮ NGUYÊN HOẠT ẢNH NỀN) ---
            const canvas = document.getElementById('particle-canvas');
            const ctx = canvas.getContext('2d');
            let particlesArray = [];
            const mouse = { x: null, y: null, radius: 150 };

            window.addEventListener('mousemove', (e) => { mouse.x = e.clientX; mouse.y = e.clientY; });
            window.addEventListener('mouseout', () => { mouse.x = null; mouse.y = null; });
            window.addEventListener('resize', () => { canvas.width = window.innerWidth; canvas.height = window.innerHeight; init(); });

            canvas.width = window.innerWidth; canvas.height = window.innerHeight;

            class Particle {
                constructor() {
                    this.x = Math.random() * canvas.width;
                    this.y = Math.random() * canvas.height;
                    this.size = Math.random() * 1.5 + 1;
                    this.speedX = (Math.random() * 0.6) - 0.3;
                    this.speedY = (Math.random() * 0.6) - 0.3;
                }
                update() {
                    this.x += this.speedX; this.y += this.speedY;
                    if (this.x < 0 || this.x > canvas.width) this.speedX = -this.speedX;
                    if (this.y < 0 || this.y > canvas.height) this.speedY = -this.speedY;
                    if (mouse.x != null && mouse.y != null) {
                        let dx = mouse.x - this.x; let dy = mouse.y - this.y;
                        let distance = Math.sqrt(dx * dx + dy * dy);
                        if (distance < mouse.radius) {
                            let force = (mouse.radius - distance) / mouse.radius;
                            this.x += (dx / distance) * force * 1.5; this.y += (dy / distance) * force * 1.5;
                        }
                    }
                }
                draw() { ctx.fillStyle = 'rgba(255, 255, 255, 0.25)'; ctx.beginPath(); ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2); ctx.fill(); }
            }

            function init() { particlesArray = []; for (let i = 0; i < 70; i++) particlesArray.push(new Particle()); }
            function connect() {
                for (let a = 0; a < particlesArray.length; a++) {
                    for (let b = a; b < particlesArray.length; b++) {
                        let dx = particlesArray[a].x - particlesArray[b].x;
                        let dy = particlesArray[a].y - particlesArray[b].y;
                        if (Math.sqrt(dx * dx + dy * dy) < 110) {
                            ctx.strokeStyle = `rgba(0, 229, 255, ${(1 - (Math.sqrt(dx * dx + dy * dy)/110)) * 0.08})`;
                            ctx.lineWidth = 0.6; ctx.beginPath(); ctx.moveTo(particlesArray[a].x, particlesArray[a].y); ctx.lineTo(particlesArray[b].x, particlesArray[b].y); ctx.stroke();
                        }
                    }
                }
            }
            function animate() { ctx.clearRect(0, 0, canvas.width, canvas.height); for (let i = 0; i < particlesArray.length; i++) { particlesArray[i].update(); particlesArray[i].draw(); } connect(); requestAnimationFrame(animate); }
            init(); animate();
        </script>
        </body>
        </html>
        <?php
        break;
}
?>
