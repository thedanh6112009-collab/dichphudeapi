<?php
// api/db.php (Mã nguồn chạy trên RENDER - Bản sửa lỗi ký tự đặc biệt mật khẩu)
header("Content-Type: application/json; charset=UTF-8");

$host = 'pg-3064389b-dichphude-2f09.l.aivencloud.com'; 
$port = '15482'; 
$db   = 'defaultdb';
$user = 'avnadmin';

// SỬ DỤNG NHÁY ĐƠN ĐỂ TRÁNH LỖI KÝ TỰ $
$pass = 'AVNS_DOAkoPJywbouEH5sxfs'; 

try {
    $conn = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Lỗi kết nối cơ sở dữ liệu mới: " . $e->getMessage()]);
    exit();
}
?>
