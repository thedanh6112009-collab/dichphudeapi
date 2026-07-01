<?php
// api/db.php (Bản chuẩn chạy PostgreSQL trên Render - Không gây lỗi giao diện)

// ❌ XÓA HOẶC COMMENT DÒNG NÀY ĐỂ KHÔNG PHÁ GIAO DIỆN HTML:
// header("Content-Type: application/json; charset=UTF-8");

$host = 'pg-3064389b-dichphude-2f09.l.aivencloud.com'; 
$port = '15482'; 
$db   = 'defaultdb';
$user = 'avnadmin';
$pass = 'AVNS_DOAkoPJywbouEH5sxfs'; 

try {
    $conn = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Chỉ khi database bị sập hoàn toàn mới ép dòng JSON này để báo lỗi chuyên sâu
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode(["status" => "error", "message" => "Lỗi kết nối database: " . $e->getMessage()]);
    exit();
}
?>
