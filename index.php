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
        
        // HIỂN THỊ GIAO DIỆN WEB (ĐĂNG NHẬP / ĐĂNG KÝ)
        header("Content-Type: text/html; charset=UTF-8");
        ?>
        <!DOCTYPE html>
        <html lang="vi">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Cổng Thành Viên - AI Video System</title>
            <style>
                * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
                body { background: linear-gradient(135deg, #1e1e2f, #252540); display: flex; justify-content: center; align-items: center; min-height: 100vh; color: #fff; padding: 20px; }
                .auth-container { background: #2d2d44; padding: 40px; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.3); width: 100%; max-width: 400px; text-align: center; }
                h2 { margin-bottom: 20px; color: #00adb5; }
                .tabs { display: flex; margin-bottom: 25px; background: #1e1e2f; border-radius: 6px; padding: 4px; }
                .tab { flex: 1; padding: 10px; cursor: pointer; border-radius: 4px; font-weight: bold; color: #aaa; transition: 0.3s; }
                .tab.active { background: #00adb5; color: white; }
                .form-group { text-align: left; margin-bottom: 18px; }
                label { display: block; margin-bottom: 8px; font-size: 14px; color: #ccc; }
                input { width: 100%; padding: 12px; border-radius: 6px; border: 1px solid #444; background: #1e1e2f; color: #fff; font-size: 16px; outline: none; transition: 0.3s; }
                input:focus { border-color: #00adb5; box-shadow: 0 0 8px rgba(0, 173, 181, 0.4); }
                button { width: 100%; padding: 12px; border: none; border-radius: 6px; background: #00adb5; color: white; font-size: 16px; font-weight: bold; cursor: pointer; transition: 0.3s; margin-top: 10px; }
                button:hover { background: #008f95; }
                #message { margin-top: 15px; font-size: 14px; min-height: 20px; line-height: 1.4; }
                .success { color: #2ecc71; }
                .error { color: #e74c3c; }
                .hidden { display: none !important; }
            </style>
        </head>
        <body>

        <div class="auth-container">
            <h2 id="authTitle">Đăng Nhập Hệ Thống</h2>
            
            <div class="tabs">
                <div class="tab active" id="tabLogin" onclick="switchForm('login')">Đăng Nhập</div>
                <div class="tab" id="tabRegister" onclick="switchForm('register')">Đăng Ký</div>
            </div>
            
            <!-- FORM ĐĂNG NHẬP -->
            <form id="loginForm">
                <div class="form-group">
                    <label>Tên tài khoản</label>
                    <input type="text" id="loginUser" required placeholder="Nhập tên đăng nhập...">
                </div>
                <div class="form-group">
                    <label>Mật khẩu</label>
                    <input type="password" id="loginPass" required placeholder="Nhập mật khẩu...">
                </div>
                <button type="submit">Đăng Nhập Ngay</button>
            </form>

            <!-- FORM ĐĂNG KÝ -->
            <form id="registerForm" class="hidden">
                <div class="form-group">
                    <label>Tên tài khoản mới</label>
                    <input type="text" id="regUser" required placeholder="Tạo tên đăng nhập...">
                </div>
                <div class="form-group">
                    <label>Email (Không bắt buộc)</label>
                    <input type="email" id="regEmail" placeholder="Nhập email của bạn...">
                </div>
                <div class="form-group">
                    <label>Mật khẩu</label>
                    <input type="password" id="regPass" required placeholder="Tạo mật khẩu bảo mật...">
                </div>
                <button type="submit">Đăng Ký Tài Khoản</button>
            </form>

            <div id="message"></div>
        </div>

        <script>
            // Chuyển đổi qua lại giữa Đăng Nhập và Đăng Ký
            function switchForm(mode) {
                document.getElementById('message').innerHTML = '';
                document.getElementById('message').className = '';
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
                msgDiv.innerHTML = "Đang đăng ký...";
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
                        msgDiv.innerHTML = "🎉 Đăng ký thành công! Đang chuyển sang giao diện đăng nhập...";
                        msgDiv.className = 'success';
                        document.getElementById('registerForm').reset();
                        // Đăng ký xong tự động nhảy qua form đăng nhập sau 1.5 giây
                        setTimeout(() => { switchForm('login'); }, 1500);
                    } else {
                        msgDiv.innerHTML = data.message;
                        msgDiv.className = 'error';
                    }
                })
                .catch(() => {
                    msgDiv.innerHTML = "Lỗi kết nối máy chủ!";
                    msgDiv.className = 'error';
                });
            });

            // XỬ LÝ ĐĂNG NHẬP
            document.getElementById('loginForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const msgDiv = document.getElementById('message');
                msgDiv.innerHTML = "Đang xác thực...";
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
                        // Đăng nhập đúng thông tin -> Chuyển thẳng vào home.php
                        setTimeout(() => { window.location.href = 'home.php'; }, 1000);
                    } else {
                        msgDiv.innerHTML = data.message;
                        msgDiv.className = 'error';
                    }
                })
                .catch(() => {
                    msgDiv.innerHTML = "Lỗi xác thực dữ liệu!";
                    msgDiv.className = 'error';
                });
            });
        </script>

        </body>
        </html>
        <?php
        break;
}
?>