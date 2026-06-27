<?php
// api/login.php
require_once "db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['username']) || !isset($data['password'])) {
    echo json_encode(["status" => "error", "message" => "Thiếu thông tin đăng nhập!"]);
    exit();
}

$user = trim($data['username']);
$pass = trim($data['password']);

try {
    $stmt = $conn->prepare("SELECT id, username, password, is_premium, premium_expire FROM users WHERE username = :username");
    $stmt->execute(['username' => $user]);
    
    if ($stmt->rowCount() == 0) {
        echo json_encode(["status" => "error", "message" => "Tài khoản không tồn tại!"]);
        exit();
    }

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // Xác thực mật khẩu đã hash
    if (password_verify($pass, $row['password'])) {
        
        // Kiểm tra xem gói Premium đã hết hạn chưa (nếu có ngày hết hạn)
        $is_premium = (int)$row['is_premium'];
        if ($is_premium == 1 && !empty($row['premium_expire'])) {
            if (strtotime($row['premium_expire']) < time()) {
                // Đã hết hạn -> Cập nhật tự động về tài khoản thường
                $is_premium = 0;
                $update_stmt = $conn->prepare("UPDATE users SET is_premium = 0 WHERE id = :id");
                $update_stmt->execute(['id' => $row['id']]);
            }
        }

        echo json_encode([
            "status" => "success",
            "message" => "Đăng nhập thành công!",
            "username" => $row['username'],
            "is_premium" => $is_premium,
            "premium_expire" => $row['premium_expire']
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Mật khẩu không chính xác!"]);
    }
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Lỗi: " . $e->getMessage()]);
}
?>