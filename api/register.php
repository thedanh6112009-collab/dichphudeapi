<?php
// api/register.php
require_once "db.php";

// Lấy dữ liệu dạng JSON gửi từ Python lên
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['username']) || !isset($data['password'])) {
    echo json_encode(["status" => "error", "message" => "Thiếu thông tin đăng ký!"]);
    exit();
}

$user = trim($data['username']);
$pass = trim($data['password']);
$email = isset($data['email']) ? trim($data['email']) : null;

if (empty($user) || empty($pass)) {
    echo json_encode(["status" => "error", "message" => "Tài khoản và mật khẩu không được để trống!"]);
    exit();
}

// Mã hóa mật khẩu bảo mật tuyệt đối
$hashed_password = password_hash($pass, PASSWORD_BCRYPT);

try {
    // Kiểm tra xem trùng tài khoản chưa
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
    $check_stmt->execute(['username' => $user]);
    if ($check_stmt->rowCount() > 0) {
        echo json_encode(["status" => "error", "message" => "Tên tài khoản này đã tồn tại!"]);
        exit();
    }

    // Thêm người dùng mới
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