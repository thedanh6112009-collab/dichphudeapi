<?php
// api_handler.php (Đặt tại thư mục gốc của hosting, ngang hàng với index.php)

// Cấu hình các Header phản hồi JSON và chống lỗi bảo mật CORS cho API
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Xử lý request dạng OPTIONS của API
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Lấy dữ liệu JSON gửi từ body lên nếu có
$inputData = json_decode(file_get_contents("php://input"), true);

// Kiểm tra tham số 'action' từ URL (GET) hoặc từ Body (POST JSON)
$action = '';
if (isset($_GET['action'])) {
    $action = strtolower(trim($_GET['action']));
} elseif (isset($inputData['action'])) {
    $action = strtolower(trim($inputData['action']));
}

// Bộ định tuyến kết nối các tính năng API xử lý backend
switch ($action) {
    
    case 'register':
        if (file_exists("api/register.php")) {
            require_once "api/register.php";
        } else {
            echo json_encode(["status" => "error", "message" => "Hệ thống thiếu file xử lý đăng ký!"]);
        }
        break;

    case 'login':
        if (file_exists("api/login.php")) {
            require_once "api/login.php";
        } else {
            echo json_encode(["status" => "error", "message" => "Hệ thống thiếu file xử lý đăng nhập!"]);
        }
        break;

    case 'activate':
        if (file_exists("api/activate_key.php")) {
            require_once "api/activate_key.php";
        } else {
            echo json_encode(["status" => "error", "message" => "Hệ thống thiếu file kích hoạt key!"]);
        }
        break;

    default:
        // Nếu không khớp hành động nào hoặc gọi sai cách
        echo json_encode([
            "status" => "error", 
            "message" => "Yêu cầu không hợp lệ hoặc không xác định được hành động (action)!"
        ]);
        break;
}
?>