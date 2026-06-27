<?php
// index.php (Đặt tại thư mục gốc của hosting)
session_start();

// Cấu hình các Header phản hồi JSON và chống lỗi bảo mật CORS cho API (giữ nguyên cho Python)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Xử lý request dạng OPTIONS của API
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Lấy tham số hành động từ URL
$action = isset($_REQUEST['action']) ? strtolower(trim($_REQUEST['action'])) : '';

// Bộ định tuyến kết nối các tính năng
switch ($action) {
    
    case 'register':
        header("Content-Type: application/json; charset=UTF-8");
        if (file_exists("api/register.php")) {
            require_once "api/register.php";
        } else {
            echo json_encode(["status" => "error", "message" => "Hệ thống thiếu file xử lý đăng ký!"]);
        }
        break;

    case 'login':
        header("Content-Type: application/json; charset=UTF-8");
        if (file_exists("api/login.php")) {
            require_once "api/login.php";
        } else {
            echo json_encode(["status" => "error", "message" => "Hệ thống thiếu file xử lý đăng nhập!"]);
        }
        break;

    case 'web_login':
        // Tính năng xử lý riêng cho Giao diện Web để tạo Session đăng nhập
        header("Content-Type: application/json; charset=UTF-8");
        if (!file_exists("api/db.php")) {
            echo json_encode(["status" => "error", "message" => "Thiếu file cấu hình database api/db.php!"]);
            exit();
        }
        require_once "api/db.php"; // Gọi file kết nối $conn của bạn
        
        $data = json_decode(file_get_contents("php://input"), true);
        $user = trim($data['username'] ?? '');
        $pass = trim($data['password'] ?? '');
        
        try {
            // Kiểm tra tài khoản trong bảng users
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = :user");
            $stmt->execute(['user' => $user]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row && password_verify($pass, $row['password'])) {
                $_SESSION['user_logged'] = $row['username']; // Đánh dấu đã đăng nhập thành công
                echo json_encode(["status" => "success", "message" => "Đăng nhập thành công!"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Tài khoản hoặc mật khẩu không chính xác!"]);
            }
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => "Lỗi kết nối dữ liệu: " . $e->getMessage()]);
        }
        break;

    case 'activate':
        header("Content-Type: application/json; charset=UTF-8");
        if (file_exists("api/activate_key.php")) {
            require_once "api/activate_key.php";
        } else {
            echo json_encode(["status" => "error", "message" => "Hệ thống thiếu file kích hoạt key!"]);
        }
        break;

    default:
        // Nếu đã đăng nhập trước đó rồi thì vào thẳng luôn trang chủ home.php
        if (isset($_SESSION['user_logged'])) {
            header("Location: home.php");
            exit();
        }
        
        // HIỂN THỊ GIAO DIỆN WEB (ĐĂNG NHẬP / ĐĂNG KÝ MỚI PHONG CÁCH HOME.PHP)
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
                    background: #030303; 
                    color: #fff; 
                    min-height: 100vh; 
                    display: flex; 
                    justify-content: center; 
                    align-items: center; 
                    overflow-x: hidden;
                    position: relative;
                    padding: 20px;
                }

                /* Lớp Canvas bọc toàn bộ nền phía sau */
                #particle-canvas {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    z-index: 0;
                    pointer-events: none;
                }

                /* Container Auth phong cách Glassmorphism của home.php */
                .auth-container { 
                    position: relative;
                    z-index: 1;
                    background: rgba(15, 15, 20, 0.45); 
                    border: 1px solid rgba(255, 255, 255, 0.05); 
                    backdrop-filter: blur(20px);
                    -webkit-backdrop-filter: blur(20px);
                    padding: 45px 35px; 
                    border-radius: 28px; 
                    box-shadow: 0 24px 50px rgba(0, 0, 0, 0.6), 0 0 40px rgba(26, 115, 232, 0.03); 
                    width: 100%; 
                    max-width: 420px; 
                    text-align: center; 
                    transition: all 0.4s ease;
                }

                .logo-section {
                    margin-bottom: 25px;
                }
                .logo-section i {
                    font-size: 32px;
                    color: #1a73e8;
                    margin-bottom: 12px;
                }
                
                h2 { 
                    font-size: 24px;
                    font-weight: 700;
                    margin-bottom: 25px; 
                    letter-spacing: -0.5px;
                    background: linear-gradient(135deg, #ffffff 0%, #b3b3b3 100%); 
                    -webkit-background-clip: text; 
                    -webkit-text-fill-color: transparent;
                }

                /* Tab chuyển đổi thanh mảnh như Google One */
                .tabs { 
                    display: flex; 
                    margin-bottom: 30px; 
                    background: rgba(255, 255, 255, 0.04); 
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
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
                }
                .tab.active { 
                    background: #1a73e8; 
                    color: white; 
                    box-shadow: 0 4px 15px rgba(26, 115, 232, 0.25);
                }

                .form-group { 
                    text-align: left; 
                    margin-bottom: 22px; 
                }
                label { 
                    display: block; 
                    margin-bottom: 9px; 
                    font-size: 13.5px; 
                    font-weight: 500;
                    color: #94a3b8; 
                }
                
                /* Input phong cách hiện đại */
                .input-wrapper {
                    position: relative;
                }
                .input-wrapper i {
                    position: absolute;
                    left: 15px;
                    top: 50%;
                    transform: translateY(-50%);
                    color: #4b5563;
                    font-size: 15px;
                    transition: color 0.3s;
                }
                input { 
                    width: 100%; 
                    padding: 13px 15px 13px 45px; 
                    border-radius: 14px; 
                    border: 1px solid rgba(255, 255, 255, 0.06); 
                    background: rgba(0, 0, 0, 0.2); 
                    color: #fff; 
                    font-size: 15px; 
                    outline: none; 
                    transition: all 0.3s ease; 
                }
                input:focus { 
                    border-color: #1a73e8; 
                    background: rgba(0, 0, 0, 0.4);
                    box-shadow: 0 0 15px rgba(26, 115, 232, 0.15); 
                }
                input:focus + i {
                    color: #1a73e8;
                }
                
                /* Nút hành động phong cách Google Blue */
                button { 
                    width: 100%; 
                    padding: 14px; 
                    border: none; 
                    border-radius: 14px; 
                    background: #1a73e8; 
                    color: white; 
                    font-size: 15px; 
                    font-weight: 600; 
                    cursor: pointer; 
                    box-shadow: 0 4px 15px rgba(26, 115, 232, 0.3);
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
                    margin-top: 10px; 
                }
                button:hover { 
                    background: #1557b0; 
                    transform: translateY(-1px);
                    box-shadow: 0 6px 20px rgba(26, 115, 232, 0.4);
                }
                
                /* Khu vực hiển thị tin nhắn thông báo */
                #message { 
                    margin-top: 20px; 
                    font-size: 14px; 
                    min-height: 20px; 
                    line-height: 1.5; 
                    padding: 10px;
                    border-radius: 10px;
                    transition: all 0.3s;
                }
                .success { 
                    background: rgba(52, 168, 83, 0.1); 
                    color: #34a853; 
                    border: 1px solid rgba(52, 168, 83, 0.2);
                }
                .error { 
                    background: rgba(234, 67, 53, 0.1); 
                    color: #ea4335; 
                    border: 1px solid rgba(234, 67, 53, 0.2);
                }
                .hidden { display: none !important; }
            </style>
        </head>
        <body>

        <!-- Nền hiệu ứng hạt trôi tự do và tương tác chuột từ home.php -->
        <canvas id="particle-canvas"></canvas>

        <div class="auth-container">
            <div class="logo-section">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
                <h2 id="authTitle">Đăng Nhập Hệ Thống</h2>
            </div>
            
            <div class="tabs">
                <div class="tab active" id="tabLogin" onclick="switchForm('login')">Đăng Nhập</div>
                <div class="tab" id="tabRegister" onclick="switchForm('register')">Đăng Ký</div>
            </div>
            
            <!-- FORM ĐĂNG NHẬP -->
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

            <!-- FORM ĐĂNG KÝ -->
            <form id="registerForm" class="hidden">
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
                <button type="submit" style="background: #34a853; box-shadow: 0 4px 15px rgba(52, 168, 83, 0.3);"><i class="fa-solid fa-user-check"></i> Đăng Ký Tài Khoản</button>
            </form>

            <div id="message"></div>
        </div>

        <script>
            // Chuyển đổi qua lại giữa Đăng Nhập và Đăng Ký
            function switchForm(mode) {
                const msgDiv = document.getElementById('message');
                msgDiv.innerHTML = '';
                msgDiv.className = '';
                msgDiv.style.display = 'none';
                
                if(mode === 'login') {
                    document.getElementById('authTitle').innerText = 'Đăng Nhập Hệ Thống';
                    document.getElementById('tabLogin').classList.add('active');
                    document.getElementById('tabRegister').classList.remove('active');
                    document.getElementById('loginForm').classList.remove('hidden');
                    document.getElementById('registerForm').classList.add('hidden');
                } else {
                    document.getElementById('authTitle').innerText = 'Tạo Tài Khoản Mới';
                    document.getElementById('tabRegister').classList.add('active');
                    document.getElementById('tabLogin').classList.remove('active');
                    document.getElementById('registerForm').classList.remove('hidden');
                    document.getElementById('loginForm').classList.add('hidden');
                }
            }

            // XỬ LÝ ĐĂNG KÝ
            document.getElementById('registerForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const msgDiv = document.getElementById('message');
                msgDiv.style.display = 'block';
                msgDiv.innerHTML = "<i class='fa-solid fa-spinner fa-spin'></i> Đang xử lý đăng ký...";
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
                        msgDiv.innerHTML = "🎉 Đăng ký thành công! Đang chuyển sang đăng nhập...";
                        msgDiv.className = 'success';
                        document.getElementById('registerForm').reset();
                        setTimeout(() => { switchForm('login'); }, 1500);
                    } else {
                        msgDiv.innerHTML = "❌ " + data.message;
                        msgDiv.className = 'error';
                    }
                })
                .catch(() => {
                    msgDiv.innerHTML = "❌ Lỗi kết nối máy chủ!";
                    msgDiv.className = 'error';
                });
            });

            // XỬ LÝ ĐĂNG NHẬP
            document.getElementById('loginForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const msgDiv = document.getElementById('message');
                msgDiv.style.display = 'block';
                msgDiv.innerHTML = "<i class='fa-solid fa-spinner fa-spin'></i> Đang xác thực thông tin...";
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
                        msgDiv.innerHTML = "🔓 Đăng nhập thành công! Đang vào trang chủ...";
                        msgDiv.className = 'success';
                        setTimeout(() => { window.location.href = 'home.php'; }, 1000);
                    } else {
                        msgDiv.innerHTML = "❌ " + data.message;
                        msgDiv.className = 'error';
                    }
                })
                .catch(() => {
                    msgDiv.innerHTML = "❌ Lỗi xác thực dữ liệu!";
                    msgDiv.className = 'error';
                });
            });

            // --- JAVASCRIPT ANIMATION CANVAS (ĐỒNG BỘ 100% TỪ HOME.PHP) ---
            const canvas = document.getElementById('particle-canvas');
            const ctx = canvas.getContext('2d');

            let particlesArray = [];
            const numberOfParticles = 80; 

            const mouse = { x: null, y: null, radius: 180 };

            window.addEventListener('mousemove', function(event) {
                mouse.x = event.clientX;
                mouse.y = event.clientY;
            });

            window.addEventListener('mouseout', function() {
                mouse.x = null;
                mouse.y = null;
            });

            window.addEventListener('resize', function() {
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;
                init();
            });

            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;

            class Particle {
                constructor() {
                    this.x = Math.random() * canvas.width;
                    this.y = Math.random() * canvas.height;
                    this.size = Math.random() * 2 + 1;
                    this.speedX = (Math.random() * 1) - 0.5;
                    this.speedY = (Math.random() * 1) - 0.5;
                }

                update() {
                    this.x += this.speedX;
                    this.y += this.speedY;

                    if (this.x < 0 || this.x > canvas.width) this.speedX = -this.speedX;
                    if (this.y < 0 || this.y > canvas.height) this.speedY = -this.speedY;

                    if (mouse.x != null && mouse.y != null) {
                        let dx = mouse.x - this.x;
                        let dy = mouse.y - this.y;
                        let distance = Math.sqrt(dx * dx + dy * dy);
                        
                        if (distance < mouse.radius) {
                            let force = (mouse.radius - distance) / mouse.radius;
                            this.x += (dx / distance) * force * 2;
                            this.y += (dy / distance) * force * 2;
                        }
                    }
                }

                draw() {
                    ctx.fillStyle = 'rgba(255, 255, 255, 0.4)';
                    ctx.beginPath();
                    ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                    ctx.closePath();
                    ctx.fill();
                }
            }

            function init() {
                particlesArray = [];
                for (let i = 0; i < numberOfParticles; i++) {
                    particlesArray.push(new Particle());
                }
            }

            function connectParticles() {
                let opacityValue = 1;
                for (let a = 0; a < particlesArray.length; a++) {
                    for (let b = a; b < particlesArray.length; b++) {
                        let dx = particlesArray[a].x - particlesArray[b].x;
                        let dy = particlesArray[a].y - particlesArray[b].y;
                        let distance = Math.sqrt(dx * dx + dy * dy);

                        if (distance < 110) {
                            opacityValue = 1 - (distance / 110);
                            if (mouse.x != null) {
                                let mdx = mouse.x - particlesArray[a].x;
                                let mdy = mouse.y - particlesArray[a].y;
                                let mDist = Math.sqrt(mdx * mdx + mdy * mdy);
                                if (mDist < mouse.radius) {
                                    opacityValue *= 2; 
                                }
                            }
                            ctx.strokeStyle = `rgba(26, 115, 232, ${opacityValue * 0.15})`;
                            ctx.lineWidth = 0.8;
                            ctx.beginPath();
                            ctx.moveTo(particlesArray[a].x, particlesArray[a].y);
                            ctx.lineTo(particlesArray[b].x, particlesArray[b].y);
                            ctx.stroke();
                        }
                    }
                }
            }

            function animate() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                for (let i = 0; i < particlesArray.length; i++) {
                    particlesArray[i].update();
                    particlesArray[i].draw();
                }
                connectParticles();
                requestAnimationFrame(animate);
            }

            init();
            animate();
        </script>

        </body>
        </html>
        <?php
        break;
}
?>
