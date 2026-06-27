<?php
// api/activate_key.php
require_once "db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['username']) || !isset($data['key_code'])) {
    echo json_encode(["status" => "error", "message" => "Thiếu thông tin kích hoạt!"]);
    exit();
}

$user = trim($data['username']);
$key_code = trim($data['key_code']);

try {
    // 1. Kiểm tra tài khoản người dùng có tồn tại không
    $user_stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
    $user_stmt->execute(['username' => $user]);
    if ($user_stmt->rowCount() == 0) {
        echo json_encode(["status" => "error", "message" => "Tài khoản kích hoạt không hợp lệ!"]);
        exit();
    }
    $user_row = $user_stmt->fetch(PDO::FETCH_ASSOC);
    $user_id = $user_row['id'];

    // 2. Kiểm tra tính hợp lệ của Key
    $key_stmt = $conn->prepare("SELECT id, status FROM activation_keys WHERE key_code = :key_code");
    $key_stmt->execute(['key_code' => $key_code]);
    if ($key_stmt->rowCount() == 0) {
        echo json_encode(["status" => "error", "message" => "Mã kích hoạt (Key) không tồn tại trên hệ thống!"]);
        exit();
    }
    
    $key_row = $key_stmt->fetch(PDO::FETCH_ASSOC);
    if ((int)$key_row['status'] === 1) {
        echo json_encode(["status" => "error", "message" => "Mã key này đã được sử dụng cho một tài khoản khác trước đó!"]);
        exit();
    }

    // 3. Tiến hành giao dịch kích hoạt (Sử dụng Transaction để đảm bảo tính đồng bộ dữ liệu)
    $conn->beginTransaction();

    // Cập nhật trạng thái Key gắn liền với User ID
    $update_key = $conn->prepare("UPDATE activation_keys SET user_id = :user_id, status = 1, activated_at = NOW() WHERE id = :key_id");
    $update_key->execute(['user_id' => $user_id, 'key_id' => $key_row['id']]);

    // Cập nhật tài khoản thành Premium (ví dụ thời hạn 30 ngày hoặc vĩnh viễn tùy bạn cấu hình ở đây)
    // Dưới đây cấu hình cộng thêm 30 ngày sử dụng từ lúc kích hoạt
    $expire_date = date('Y-m-d H:i:s', strtotime('+30 days'));
    $update_user = $conn->prepare("UPDATE users SET is_premium = 1, premium_expire = :expire WHERE id = :user_id");
    $update_user->execute(['expire' => $expire_date, 'user_id' => $user_id]);

    $conn->commit();

    echo json_encode([
        "status" => "success", 
        "message" => "Kích hoạt tài khoản Premium thành công!",
        "premium_expire" => $expire_date
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(["status" => "error", "message" => "Lỗi xử lý kích hoạt: " . $e->getMessage()]);
}
?>