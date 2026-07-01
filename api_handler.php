<?php
// api_handler.php - Xử lý API đồng bộ trạng thái thanh toán thủ công
session_start();
header('Content-Type: application/json');

// Nhúng file kết nối CSDL PostgreSQL
if (file_exists("api/db.php")) {
    require_once "api/db.php";
} elseif (file_exists("db.php")) {
    require_once "db.php";
} else {
    echo json_encode(["status" => "error", "message" => "Không tìm thấy file db.php"]);
    exit();
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    // =========================================================================
    // HÀM KIỂM TRA TRẠNG THÁI VÀ TỰ ĐỘNG ĐỒNG BỘ LÊN VIP KHI ADMIN DUYỆT TAY
    // =========================================================================
    case 'check_premium_status':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(["is_premium" => 0, "message" => "Chưa đăng nhập"]);
            exit();
        }

        $u_id = $_SESSION['user_id'];
        try {
            // 1. Quét bảng payment_keys xem Admin đã duyệt đơn hàng nào của User này chưa (status = 1)
            $stmtOrder = $conn->prepare("SELECT id, package FROM payment_keys WHERE user_id = :user_id AND status = 1 LIMIT 1");
            $stmtOrder->execute(['user_id' => $u_id]);
            $order = $stmtOrder->fetch(PDO::FETCH_ASSOC);

            if ($order) {
                // Nếu tìm thấy đơn hàng vừa được Admin duyệt, tiến hành kích hoạt tài khoản
                $conn->beginTransaction();

                // Nâng cấp user lên Premium (Hạn 30 ngày kể từ lúc duyệt đơn)
                $expire_date = date('Y-m-d H:i:s', strtotime('+30 days'));
                $updateUser = $conn->prepare("UPDATE users SET is_premium = 1, premium_expire = :expire WHERE id = :user_id");
                $updateUser->execute([
                    'expire' => $expire_date,
                    'user_id' => $u_id
                ]);

                // Đổi status sang = 2 (Đã xử lý kích hoạt) để tránh vòng lặp UPDATE liên tục
                $updateOrder = $conn->prepare("UPDATE payment_keys SET status = 2 WHERE id = :id");
                $updateOrder->execute(['id' => $order['id']]);

                $conn->commit();
                
                // Trả về kết quả 1 để kích hoạt JavaScript chuyển hướng trình duyệt của khách
                echo json_encode(["is_premium" => 1]);
                exit();
            }

            // 2. Nếu không có đơn hàng nào vừa duyệt, kiểm tra trực tiếp trạng thái hiện tại trong bảng users
            $stmtUser = $conn->prepare("SELECT is_premium FROM users WHERE id = :id LIMIT 1");
            $stmtUser->execute(['id' => $u_id]);
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if ($user && (int)$user['is_premium'] === 1) {
                echo json_encode(["is_premium" => 1]);
            } else {
                echo json_encode(["is_premium" => 0]);
            }

        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            echo json_encode(["is_premium" => 0, "error" => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Hành động API không hợp lệ"]);
        break;
}
