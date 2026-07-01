<?php
session_start();

// 1. TỰ ĐỘNG BẬT HIỂN THỊ LỖI ĐỂ KIỂM TRA (NẾU CÓ)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. NHÚNG FILE KẾT NỐI DATABASE (ĐƯỜNG DẪN THỰC TẾ TRÊN INFINITYFREE CỦA BẠN)
if (file_exists('db.php')) {
    require_once 'db.php';
} elseif (file_exists('api/db.php')) {
    require_once 'api/db.php';
} else {
    die("<div style='color:red;padding:20px;font-family:sans-serif;'><b>LỖI HỆ THỐNG:</b> Không tìm thấy file db.php kết nối cơ sở dữ liệu! Hãy kiểm tra lại vị trí đặt file.</div>");
}

// 3. KIỂM TRA TRẠNG THÁI ĐĂNG NHẬP
if (!isset($_SESSION['user_logged'])) {
    header("Location: index.php"); // Nếu chưa đăng nhập thì đẩy về trang chủ/login
    exit();
}

$username = $_SESSION['user_logged'];
$money = 139000; // Số tiền gói Pro
$package = 'PRO';

// 4. CƠ CHẾ SỬA LỖI TỰ ĐỘNG: Nếu thiếu user_id trong Session, tự động truy vấn tìm lại từ Database
if (!isset($_SESSION['user_id'])) {
    try {
        $user_stmt = $conn->prepare("SELECT id FROM users WHERE username = :user LIMIT 1");
        $user_stmt->execute(['user' => $username]);
        if ($user_row = $user_stmt->fetch(PDO::FETCH_ASSOC)) {
            $_SESSION['user_id'] = $user_row['id']; // Gán ngược lại vào Session để sửa lỗi
        } else {
            die("Lỗi: Không tìm thấy ID của tài khoản này trong hệ thống dữ liệu.");
        }
    } catch (PDOException $e) {
        die("Lỗi truy vấn tìm ID tài khoản: " . $e->getMessage());
    }
}

$user_id = $_SESSION['user_id'];

// 5. HÀM SINH MÃ GIAO DỊCH 6 KÝ TỰ NGẪU NHIÊN CHỮ HOA/THƯỜNG/SỐ
function generatePaymentCode($length = 6) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

// 6. TIẾN HÀNH TẠO VÀ LƯU HÓA ĐƠN VÀO DATABASE
try {
    // Vòng lặp đảm bảo mã tạo ra không trùng với hóa đơn đang chờ khác
    do {
        $payment_code = generatePaymentCode(6);
        $stmt = $conn->prepare("SELECT id FROM payment_orders WHERE payment_code = :code AND status = 0");
        $stmt->execute(['code' => $payment_code]);
    } while ($stmt->fetch());

    // Thêm hóa đơn nạp tiền mới
    $stmt = $conn->prepare("INSERT INTO payment_orders (user_id, payment_code, package, amount) VALUES (:user_id, :code, :package, :amount)");
    $stmt->execute([
        'user_id' => $user_id,
        'code' => $payment_code,
        'package' => $package,
        'amount' => $money
    ]);
} catch (PDOException $e) {
    // THÔNG BÁO LỖI NẾU BẠN CHƯA CHẠY SQL TẠO BẢNG PAYMENT_ORDERS
    echo "<div style='background:#111; color:#ff4a4a; padding:30px; border-radius:12px; margin:50px auto; max-width:600px; font-family:sans-serif; border:1px solid #333;'>";
    echo "<h3>⚠️ LỖI DATABASE: Bảng lưu trữ hóa đơn chưa được khởi tạo!</h3>";
    echo "<p>Vui lòng mở bảng quản trị cơ sở dữ liệu của bạn ra và chạy câu lệnh SQL này trước khi truy cập:</p>";
    echo "<pre style='background:#222; color:#fff; padding:15px; border-radius:6px; overflow-x:auto;'>
CREATE TABLE IF NOT EXISTS payment_orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  payment_code VARCHAR(10) NOT NULL UNIQUE,
  package VARCHAR(20) NOT NULL,
  amount INT NOT NULL,
  status TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);</pre>";
    echo "<p>Chi tiết lỗi hệ thống: " . $e->getMessage() . "</p>";
    echo "</div>";
    exit();
}

// 7. CẤU HÌNH THÔNG TIN TÀI KHOẢN NGÂN HÀNG CỦA BẠN (HÃY THAY BẰNG THÔNG TIN THỰC TẾ)
$NganHang = "MB"; // Tên ngân hàng viết tắt (MB, Vietcombank, Techcombank, ACB...)
$SoTaiKhoan = "0905047832"; // Số tài khoản nhận tiền của bạn
$TenChuTaiKhoan = "NGUYEN VAN A"; // Tên chủ tài khoản viết hoa không dấu

// Nội dung chuyển khoản bắt buộc là mã 6 ký tự vừa sinh ra
$NoiDungChuyenKhoan = $payment_code; 

// Đường dẫn sinh mã QR chuẩn VietQR Napas điền sẵn tiền và nội dung
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
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: #050505; color: #fff; min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
        .payment-container { background: rgba(255, 255, 255, 0.01); border: 1px solid rgba(255, 255, 255, 0.05); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border-radius: 28px; width: 100%; max-width: 480px; padding: 40px 30px; text-align: center; box-shadow: 0 30px 60px rgba(0,0,0,0.6); position: relative; overflow: hidden; }
        .payment-container::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(52, 168, 83, 0.05) 0%, transparent 60%); z-index: 0; pointer-events: none; }
        .inner-content { position: relative; z-index: 1; }
        .badge-package { display: inline-flex; align-items: center; gap: 6px; background: rgba(52, 168, 83, 0.1); color: #34a853; border: 1px solid rgba(52, 168, 83, 0.2); padding: 6px 16px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 20px; }
        h2 { font-size: 26px; font-weight: 700; margin-bottom: 8px; background: linear-gradient(135deg, #fff 0%, #aaa 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        p.desc { color: #888; font-size: 14px; line-height: 1.5; margin-bottom: 30px; }
        .qr-wrapper { background: #fff; padding: 16px; border-radius: 20px; display: inline-block; margin-bottom: 30px; box-shadow: 0 15px 30px rgba(0,0,0,0.3); transition: transform 0.3s; }
        .qr-wrapper:hover { transform: scale(1.02); }
        .qr-wrapper img { max-width: 220px; width: 100%; display: block; border-radius: 8px; }
        .info-table { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.04); border-radius: 16px; padding: 10px 20px; margin-bottom: 25px; text-align: left; }
        .info-row { display: flex; justify-content: space-between; align-items: center; padding: 14px 0; border-bottom: 1px solid rgba(255,255,255,0.04); font-size: 14px; }
        .info-row:last-child { border-bottom: none; }
        .info-row span { color: #777; font-weight: 500; }
        .info-row strong { color: #fff; font-family: monospace; font-size: 16px; font-weight: 600; }
        .highlight-code { color: #fbbc04 !important; font-weight: 700 !important; background: rgba(251, 188, 4, 0.1); padding: 4px 10px; border-radius: 6px; border: 1px solid rgba(251, 188, 4, 0.2); letter-spacing: 1px; }
        .status-waiting { color: #34a853; font-size: 13.5px; font-weight: 500; display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 5px; background: rgba(52, 168, 83, 0.05); padding: 12px; border-radius: 12px; border: 1px solid rgba(52, 168, 83, 0.1); }
        .btn-return { display: inline-flex; align-items: center; gap: 8px; margin-top: 30px; color: #666; text-decoration: none; font-size: 14px; font-weight: 500; transition: all 0.3s; }
        .btn-return:hover { color: #fff; }
    </style>
</head>
<body>

<div class="payment-container">
    <div class="inner-content">
        <div class="badge-package"><i class="fa-solid fa-gem"></i> Gói Chuyên Nghiệp</div>
        <h2>Thanh Toán AI Pro Video</h2>
        <p class="desc">Vui lòng sử dụng ứng dụng Ngân hàng (Banking) bất kỳ để quét mã QR dưới đây để hệ thống tự động kích hoạt key.</p>
        
        <div class="qr-wrapper">
            <img src="<?php echo $link_qr; ?>" alt="Mã QR VietQR Thanh Toán">
        </div>

        <div class="info-table">
            <div class="info-row">
                <span>Số tiền thanh toán:</span>
                <strong style="color: #34a853; font-size: 18px;">139.000 đ</strong>
            </div>
            <div class="info-row">
                <span>Ngân hàng nhận:</span>
                <strong><?php echo $NganHang; ?></strong>
            </div>
            <div class="info-row">
                <span>Số tài khoản:</span>
                <strong><?php echo $SoTaiKhoan; ?></strong>
            </div>
            <div class="info-row">
                <span>Nội dung chuyển khoản:</span>
                <strong class="highlight-code"><?php echo $NoiDungChuyenKhoan; ?></strong>
            </div>
        </div>

        <div class="status-waiting">
            <i class="fa-solid fa-circle-notch fa-spin"></i> Hệ thống đang chờ ngân hàng xác nhận giao dịch...
        </div>

        <a href="home.php" class="btn-return"><i class="fa-solid fa-arrow-left-long"></i> Quay trở lại trang chủ</a>
    </div>
</div>

<script>
    // Cơ chế Script chạy ngầm gửi yêu cầu kiểm tra trạng thái Premium mỗi 4 giây một lần
    setInterval(function(){
        fetch('api_handler.php?action=check_premium_status')
        .then(response => response.json())
        .then(data => {
            if(data.is_premium === 1 || data.is_premium === true) {
                alert('🎉 Tuyệt vời! Hệ thống đã nhận được tiền thành công. Tài khoản của bạn đã nâng cấp bản quyền PRO.');
                window.location.href = 'home.php';
            }
        }).catch(err => console.log("Lỗi kiểm tra trạng thái: ", err));
    }, 4000);
</script>

</body>
</html>
