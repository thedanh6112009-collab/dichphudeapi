<?php
// api/webhook_payment.php
require_once "db.php"; 

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "Không nhận được dữ liệu"]);
    exit();
}

// Tiếp nhận mảng danh sách lịch sử giao dịch biến động số dư từ cổng thanh toán trung gian
$transactions = isset($data['data']) ? $data['data'] : [$data]; 

foreach ($transactions as $transaction) {
    // Lấy nội dung ghi chú chuyển khoản từ ngân hàng gửi sang
    $memo = trim($transaction['description'] ?? $transaction['memo'] ?? '');
    
    if (empty($memo)) continue;

    // Sử dụng biểu thức chính quy (Regex) trích xuất chính xác cụm 6 ký tự liên tiếp
    if (preg_match('/[a-zA-Z0-9]{6}/', $memo, $matches)) {
        $extracted_code = $matches[0];

        // 1. Tìm đơn hàng khớp chính xác mã phân biệt hoa thường (PostgreSQL mặc định phân biệt hoa thường với dấu =)
        $stmt = $conn->prepare("SELECT id, user_id, package, status FROM payment_orders WHERE payment_code = :code AND status = 0 LIMIT 1");
        $stmt->execute(['code' => $extracted_code]);

        if ($order = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $user_id = $order['user_id'];
            
            // Tính toán thời gian hết hạn (Cộng thêm 30 ngày)
            $expire_date = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            // Bắt đầu một Transaction để cập nhật đồng bộ CSDL PostgreSQL
            $conn->beginTransaction();
            try {
                // 2. Kích hoạt tài khoản người dùng lên Premium
                $updateUser = $conn->prepare("UPDATE users SET is_premium = 1, premium_expire = :expire WHERE id = :user_id");
                $updateUser->execute(['expire' => $expire_date, 'user_id' => $user_id]);

                // 3. Đổi trạng thái đơn hàng nạp tiền thành Đã xử lý (status = 1) để không bị lặp lại
                $updateOrder = $conn->prepare("UPDATE payment_orders SET status = 1 WHERE id = :order_id");
                $updateOrder->execute(['order_id' => $order['id']]);

                $conn->commit();
            } catch (Exception $e) {
                $conn->rollBack();
                continue;
            }
        }
    }
}

echo json_encode(["status" => "success", "message" => "Xử lý nạp tiền PostgreSQL hoàn tất!"]);
?>
