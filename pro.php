<?php
session_start();
if (!isset($_SESSION['user_logged']) || !isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'db.php'; // Nhúng file db.php kết nối PostgreSQL của bạn

$user_id = $_SESSION['user_id'];
$username = $_SESSION['user_logged'];
$money = 139000; 
$package = 'PRO';

// Hàm sinh mã 6 ký tự ngẫu nhiên (chữ hoa, chữ thường và số)
function generatePaymentCode($length = 6) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

// Vòng lặp đảm bảo mã tạo ra không bị trùng lặp trong hệ thống
do {
    $payment_code = generatePaymentCode(6);
    // Trong PostgreSQL, truy vấn phân biệt hoa thường mặc định
    $stmt = $conn->prepare("SELECT id FROM payment_orders WHERE payment_code = :code AND status = 0");
    $stmt->execute(['code' => $payment_code]);
} while ($stmt->fetch());

// Lưu hóa đơn thanh toán vào PostgreSQL
$stmt = $conn->prepare("INSERT INTO payment_orders (user_id, payment_code, package, amount) VALUES (:user_id, :code, :package, :amount)");
$stmt->execute([
    'user_id' => $user_id,
    'code' => $payment_code,
    'package' => $package,
    'amount' => $money
]);

// Cấu hình thông tin Ngân hàng của bạn
$NganHang = "MB"; 
$SoTaiKhoan = "0905047832"; 
$TenChuTaiKhoan = "NGUYEN VAN A"; 

// Nội dung chuyển khoản chính là đoạn mã 6 ký tự duy nhất
$NoiDungChuyenKhoan = $payment_code; 

$link_qr = "https://img.vietqr.io/image/{$NganHang}-{$SoTaiKhoan}-compact2.png?amount={$money}&addInfo={$NoiDungChuyenKhoan}&accountName={$TenChuTaiKhoan}";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán Gói AI Pro Video</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #030303; color: #fff; font-family: 'Plus Jakarta Sans', sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .payment-box { background: rgba(255, 255, 255, 0.01); border: 1px solid rgba(255, 255, 255, 0.05); backdrop-filter: blur(20px); padding: 40px; border-radius: 24px; text-align: center; max-width: 450px; width: 100%; box-shadow: 0 20px 40px rgba(0,0,0,0.5); }
        h2 { font-size: 24px; margin-bottom: 8px; color: #34a853; }
        p.desc { color: #888; font-size: 14px; margin-bottom: 25px; }
        .qr-code { background: #fff; padding: 15px; border-radius: 16px; display: inline-block; margin-bottom: 25px; box-shadow: 0 10px 20px rgba(0,0,0,0.2); }
        .qr-code img { max-width: 220px; display: block; }
        .info-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 14.5px; }
        .info-row span { color: #888; }
        .info-row strong { color: #fff; font-family: monospace; font-size: 16px; }
        .highlight { color: #fbbc04 !important; font-weight: 700 !important; background: rgba(251, 188, 4, 0.1); padding: 2px 8px; border-radius: 4px; letter-spacing: 1px; }
        .btn-back { display: inline-block; margin-top: 30px; color: #aaa; text-decoration: none; font-size: 14px; transition: 0.3s; }
        .btn-back:hover { color: #fff; }
        .waiting-box { margin-top: 15px; font-size: 13px; color: #34a853; display: flex; align-items: center; justify-content: center; gap: 8px; }
    </style>
</head>
<body>

<div class="payment-box">
    <h2>Nâng cấp AI Pro Video</h2>
    <p class="desc">Vui lòng quét mã QR bằng ứng dụng Ngân hàng để thanh toán tự động</p>
    
    <div class="qr-code">
        <img src="<?php echo $link_qr; ?>" alt="Mã QR Thanh Toán">
    </div>

    <div class="info-row">
        <span>Số tiền:</span>
        <strong style="color: #34a853;">139.000 đ</strong>
    </div>
    <div class="info-row">
        <span>Ngân hàng:</span>
        <strong><?php echo $NganHang; ?></strong>
    </div>
    <div class="info-row">
        <span>Số tài khoản:</span>
        <strong><?php echo $SoTaiKhoan; ?></strong>
    </div>
    <div class="info-row">
        <span>Nội dung chuyển khoản:</span>
        <strong class="highlight"><?php echo $NoiDungChuyenKhoan; ?></strong>
    </div>

    <div class="waiting-box">
        <i class="fa-solid fa-spinner fa-spin"></i> Hệ thống đang đợi phản hồi từ ngân hàng...
    </div>

    <a href="home.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Quay lại trang chủ</a>
</div>

<script>
    // Kiểm tra trạng thái kích hoạt ngầm mỗi 4 giây
    setInterval(function(){
        fetch('api_handler.php?action=check_premium_status')
        .then(response => response.json())
        .then(data => {
            if(data.is_premium === 1 || data.is_premium === true) {
                alert('Thanh toán hoàn tất! Tài khoản của bạn đã được nâng cấp tự động lên bản PRO.');
                window.location.href = 'home.php';
            }
        });
    }, 4000);
</script>

</body>
</html>
