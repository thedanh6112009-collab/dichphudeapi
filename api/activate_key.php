<?php
// api/activate_key.php
require_once "db.php"; // Kết nối database qua PDO

// Nhận dữ liệu JSON gửi từ phần mềm lên
$data = json_decode(file_get_contents("php://input"), true);

// Kiểm tra đầy đủ 3 tham số: username, key_code và mã máy device_id
if (!isset($data['username']) || !isset($data['key_code']) || !isset($data['device_id'])) {
    echo json_encode(["status" => "error", "message" => "Thiếu thông tin kích hoạt hoặc định danh thiết bị!"]);
    exit();
}

$user = trim($data['username']);
$key_code = trim($data['key_code']);
$device_id = trim($data['device_id']); // Mã ID phần cứng duy nhất của máy khách

if (empty($device_id)) {
    echo json_encode(["status" => "error", "message" => "Thiết bị không hợp lệ (Mã thiết bị trống)!"]);
    exit();
}

try {
    // 1. Kiểm tra tài khoản người dùng có tồn tại không
    $user_stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
    $user_stmt->execute(['username' => $user]);
    if ($user_stmt->rowCount() == 0) {
        echo json_encode(["status" => "error", "message" => "Tài khoản kích hoạt không tồn tại trên hệ thống!"]);
        exit();
    }
    $user_row = $user_stmt->fetch(PDO::FETCH_ASSOC);
    $user_id = $user_row['id'];

    // 2. Kiểm tra tính hợp lệ của Key và lấy device_id đã khóa trước đó
    $key_stmt = $conn->prepare("SELECT id, status, device_id FROM activation_keys WHERE key_code = :key_code");
    $key_stmt->execute(['key_code' => $key_code]);
    if ($key_stmt->rowCount() == 0) {
        echo json_encode(["status" => "error", "message" => "Mã kích hoạt (Key) không tồn tại trên hệ thống!"]);
        exit();
    }
    
    $key_row = $key_stmt->fetch(PDO::FETCH_ASSOC);

    // XỬ LÝ LOGIC CHỐNG CHIA SẺ KEY
    if ((int)$key_row['status'] === 1) {
        // Nếu Key đã dùng, so sánh mã máy đang gửi lên với mã máy đã lưu trong DB
        if (!empty($key_row['device_id']) && $key_row['device_id'] !== $device_id) {
            echo json_encode([
                "status" => "error", 
                "message" => "Mã Key này đã được kích hoạt cố định cho một máy tính khác. Bạn không thể dùng chung!"
            ]);
            exit();
        }
        
        // Nếu trùng device_id (người dùng cũ cài lại app hoặc kích hoạt lại trên chính máy đó) -> Cho qua luôn
        echo json_encode([
            "status" => "success", 
            "message" => "Thiết bị này đã kích hoạt gói trước đó. Trạng thái Premium đã được đồng bộ!"
        ]);
        exit();
    }

    // 3. Tiến hành kích hoạt cho thiết bị MỚI tinh (Sử dụng Transaction để đồng bộ dữ liệu)
    $conn->beginTransaction();

    // Cập nhật Key: Chuyển status = 1, lưu user_id và KHÓA CHẶT bằng device_id của máy này
    $update_key = $conn->prepare("UPDATE activation_keys SET user_id = :user_id, status = 1, device_id = :device_id, activated_at = NOW() WHERE id = :key_id");
    $update_key->execute([
        'user_id'   => $user_id, 
        'device_id' => $device_id,
        'key_id'    => $key_row['id']
    ]);

    // Cập nhật thời hạn sử dụng Premium cho tài khoản người dùng (Ví dụ: cộng 30 ngày)
    $expire_date = date('Y-m-d H:i:s', strtotime('+30 days'));
    $update_user = $conn->prepare("UPDATE users SET is_premium = 1, premium_expire = :expire WHERE id = :user_id");
    $update_user->execute(['expire' => $expire_date, 'user_id' => $user_id]);

    $conn->commit();

    echo json_encode([
        "status" => "success", 
        "message" => "Kích hoạt bản quyền Premium trên thiết bị này thành công!",
        "premium_expire" => $expire_date
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(["status" => "error", "message" => "Lỗi xử lý hệ thống bảo mật: " . $e->getMessage()]);
}
?>
