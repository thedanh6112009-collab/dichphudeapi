<?php
// api/db.php
header("Content-Type: application/json; charset=UTF-8");

// Thông số kết nối lấy chính xác từ ảnh {69D74204-D203-4826-9F34-541D6F38FB12}.png
$host = "sql308.infinityfree.com";        
$db_name = "if0_42280779_dichphude"; 
$username = "if0_42280779"; 
$password = "0905047832q"; 

try {
    $conn = new PDO("mysql:host=" . $host . ";dbname=" . $db_name . ";charset=utf8", $username, $password);
    // Cấu hình báo lỗi PDO để dễ dàng kiểm tra lỗi nếu có
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $exception) {
    echo json_encode([
        "status" => "error",
        "message" => "Lỗi kết nối cơ sở dữ liệu: " . $exception->getMessage()
    ]);
    exit();
}
?>