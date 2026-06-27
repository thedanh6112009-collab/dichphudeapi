<?php
// db_bridge.php (Đặt trực tiếp tại thư mục gốc của INFINITYFREE)
header("Content-Type: application/json; charset=UTF-8");

$data = json_decode(file_get_contents("php://input"), true);

// Kiểm tra mật mã bảo mật giữa Render và InfinityFree
if (!isset($data['bridge_secret']) || $data['bridge_secret'] !== 'MatKhauBaoMat123') {
    echo json_encode(["status" => "error", "message" => "Không có quyền truy cập cầu kết nối!"]);
    exit();
}

// Thông số lấy chính xác từ ảnh {CF978BEA-D021-4528-B44D-5456BE01C06B}.png của bạn
$host = 'sql308.infinityfree.com'; 
$db   = 'if0_42280779_dichphude';
$user = 'if0_42280779';
$pass = '0905047832q';

try {
    $local_conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $local_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Cầu nối không thể kết nối MySQL: " . $e->getMessage()]);
    exit();
}

$action = $data['bridge_action'] ?? '';

if ($action === 'execute') {
    try {
        $stmt = $local_conn->prepare($data['sql']);
        $stmt->execute($data['params'] ?? []);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(["status" => "success", "data" => $results]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Lỗi thực thi truy vấn: " . $e->getMessage()]);
    }
}
?>
