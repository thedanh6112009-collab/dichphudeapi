<?php
// api/register.php
require_once "db.php";

// 1. Nhận dữ liệu thông minh (Hỗ trợ cả JSON từ Python và $_POST từ Web Form)
$data = json_decode(file_get_contents("php://input"), true);

$user = isset($data['username']) ? trim($data['username']) : (isset($_POST['username']) ? trim($_POST['username']) : null);
$pass = isset($data['password']) ? trim($data['password']) : (isset($_POST['password']) ? trim($_POST['password']) : null);
$email = isset($data['email']) ? trim($data['email']) : (isset($_POST['email']) ? trim($_POST['email']) : null);

// 2. Kiểm tra dữ liệu đầu vào
if (empty($user) || empty($pass)) {
    echo json_encode(["status" => "error", "message" => "Tài khoản và mật khẩu không được để trống!"]);
    exit();
}

// 3. Mã hóa mật khẩu bảo mật tuyệt đối
$hashed_password = password_hash($pass, PASSWORD_BCRYPT);

try {
    // 4. Kiểm tra xem tên tài khoản đã tồn tại trong PostgreSQL chưa
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
    $check_stmt->execute(['username' => $user]);
    if ($check_stmt->rowCount() > 0) {
        echo json_encode(["status" => "error", "message" => "Tên tài khoản này đã tồn tại!"]);
        exit();
    }

    // 5. Thêm người dùng mới vào database
    $stmt = $conn->prepare("INSERT INTO users (username, password, email) VALUES (:username, :password, :email)");
    $stmt->execute([
        'username' => $user,
        'password' => $hashed_password,
        'email' => $email
    ]);

    echo json_encode(["status" => "success", "message" => "Đăng ký tài khoản thành công!"]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Lỗi hệ thống: " . $e->getMessage()]);
}
?>
