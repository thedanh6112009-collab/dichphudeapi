<?php
// admin.php - Trang duyệt đơn dành riêng cho Admin
session_start();

if (file_exists("api/db.php")) { require_once "api/db.php"; } 
elseif (file_exists("db.php")) { require_once "db.php"; }

// 1. Bảo mật: Kiểm tra xem user đăng nhập có đúng là Email của bạn không
if (!isset($_SESSION['user_id'])) {
    die("Bạn cần đăng nhập trước.");
}

$u_id = $_SESSION['user_id'];
$stmtCheck = $conn->prepare("SELECT email FROM users WHERE id = :id LIMIT 1");
$stmtCheck->execute(['id' => $u_id]);
$currentUser = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if (!$currentUser || $currentUser['email'] !== 'thedanh6112009@gmail.com') {
    die("⛔ Quyền truy cập bị từ chối! Trang này chỉ dành riêng cho Admin.");
}

// 2. Xử lý khi Admin bấm nút Duyệt
if (isset($_POST['approve_id'])) {
    $order_id = (int)$_POST['approve_id'];
    try {
        $conn->beginTransaction();

        // Lấy thông tin đơn hàng
        $stmtO = $conn->prepare("SELECT user_id FROM payment_keys WHERE id = :id LIMIT 1");
        $stmtO->execute(['id' => $order_id]);
        $order = $stmtO->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            // Cập nhật trạng thái đơn hàng sang 2 (Đã xử lý kích hoạt)
            $updateO = $conn->prepare("UPDATE payment_keys SET status = 2 WHERE id = :id");
            $updateO->execute(['id' => $order_id]);

            // Nâng cấp user lên Premium 30 ngày
            $expire_date = date('Y-m-d H:i:s', strtotime('+30 days'));
            $updateU = $conn->prepare("UPDATE users SET is_premium = 1, premium_expire = :expire WHERE id = :uid");
            $updateU->execute(['expire' => $expire_date, 'uid' => $order['user_id']]);
        }

        $conn->commit();
        echo "<script>alert('🎉 Đã duyệt thành công!'); window.location.href='admin.php';</script>";
    } catch (Exception $e) {
        if ($conn->inTransaction()) { $conn->rollBack(); }
        echo "Lỗi: " . $e->getMessage();
    }
}

// 3. Lấy danh sách đơn hàng đang chờ duyệt (status = 0 hoặc status = 1)
$stmtList = $conn->query("SELECT p.*, u.email FROM payment_keys p JOIN users u ON p.user_id = u.id WHERE p.status IN (0, 1) ORDER BY p.created_at DESC");
$orders = $stmtList->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Duyệt Thanh Toán</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: #0a0a0a; color: #fff; padding: 40px; }
        .container { max-width: 1000px; margin: 0 auto; }
        h1 { margin-bottom: 20px; font-size: 28px; background: linear-gradient(135deg, #fff, #777); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        table { width: 100%; border-collapse: collapse; background: #111; border: 1px solid #222; border-radius: 12px; overflow: hidden; margin-top: 20px; }
        th, td { padding: 16px; text-align: left; border-bottom: 1px solid #222; }
        th { background: #161616; color: #aaa; font-size: 14px; }
        tr:hover { background: #141414; }
        .badge { background: rgba(251, 188, 4, 0.1); color: #fbbc04; padding: 4px 10px; border-radius: 6px; font-size: 13px; font-weight: bold; }
        .btn-approve { background: #34a853; color: white; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-approve:hover { background: #2d8e47; transform: scale(1.05); }
        .no-data { text-align: center; color: #555; padding: 40px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Hệ Thống Duyệt Đơn Thủ Công - Admin</h1>
    <p style="color: #666;">Xin chào, <strong><?php echo htmlspecialchars($currentUser['email']); ?></strong></p>

    <table>
        <thead>
            <tr>
                <th>Khách hàng</th>
                <th>Gói</th>
                <th>Số tiền</th>
                <th>Mã nội dung QR</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
                <tr><td colspan="6" class="no-data">Hội trường yên ắng... Không có đơn hàng nào đang chờ duyệt.</td></tr>
            <?php else: ?>
                <?php foreach ($orders as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><strong><?php echo $row['package']; ?></strong></td>
                        <td style="color: #34a853; font-weight: 600;"><?php echo number_format($row['amount'], 0, ',', '.'); ?> đ</td>
                        <td><span style="background:#222; padding:4px 8px; border-radius:4px; font-family:monospace; color:#fbbc04;"><?php echo $row['payment_code']; ?></span></td>
                        <td><span class="badge">Chờ xác nhận</span></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="approve_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="btn-approve">✅ Duyệt Đứt 1 Click</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
