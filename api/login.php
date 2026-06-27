<?php
// api/login.php
require_once "db.php";

// 1. Nhận dữ liệu thông minh (Hỗ trợ cả JSON từ Python và $_POST từ Web Form)
$data = json_decode(file_get_contents("php://input"), true);

$user = isset($data['username']) ? trim($data['username']) : (isset($_POST['username']) ? trim($_POST['username']) : null);
$pass = isset($data['password']) ? trim($data['password']) : (isset($_POST['password']) ? trim($_POST['password']) : null);

// 2. Kiểm tra dữ liệu đầu vào
if (empty($user) || empty($pass)) {
    echo json_encode(["status" => "error", "message" => "Thiếu thông tin đăng nhập!"]);
    exit();
}

try {
    // 3. Tìm tài khoản trong database
    $stmt = $conn->prepare("SELECT id, username, password, is_premium, premium_expire FROM users WHERE username = :username");
    $stmt->execute(['username' => $user]);
    
    // Nếu không tìm thấy tên tài khoản
    if ($stmt->rowCount() == 0) {
        echo json_encode(["status" => "error", "message" => "Tài khoản không tồn tại!"]);
        exit();
    }

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // 4. Xác thực mật khẩu đã hash bằng hàm password_verify
    if (password_verify($pass, $row['password'])) {
        
        // Kiểm tra xem gói Premium đã hết hạn chưa (nếu có ngày hết hạn)
        $is_premium = (int)$row['is_premium'];
        if ($is_premium == 1 && !empty($row['premium_expire'])) {
            if (strtotime($row['premium_expire']) < time()) {
                // Đã hết hạn -> Cập nhật tự động về tài khoản thường (0)
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
        // Nếu đúng tên tài khoản nhưng gõ sai mật khẩu
        echo json_encode(["status" => "error", "message" => "Mật khẩu không chính xác!"]);
    }
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Lỗi: " . $e->getMessage()]);
}
?>
