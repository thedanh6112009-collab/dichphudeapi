<?php
// api/db.php (Mã nguồn chạy trên RENDER)

// Định nghĩa một hàm để gửi yêu cầu kết nối hộ về phía InfinityFree
function query_infinity_bridge($action, $payload) {
    // THAY ĐƯỜNG DẪN DƯỚI ĐÂY THÀNH ĐƯỜNG DẪN ĐẾN FILE BRIDGE TRÊN INFINITYFREE CỦA BẠN
    $url = "http://dichphude.great-site.net/db_bridge.php"; 
    
    $payload['bridge_action'] = $action;
    $payload['bridge_secret'] = 'MatKhauBaoMat123'; // Khóa bảo mật tự chế để tránh người lạ phá hoại

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

// Giả lập class PDO tối giản để không phải sửa các file login.php hay register.php của bạn
class BridgePDO {
    private $statement_action;
    public function prepare($sql) {
        $this->statement_action = $sql;
        return $this;
    }
    public function execute($params = []) {
        $res = query_infinity_bridge('execute', [
            'sql' => $this->statement_action,
            'params' => $params
        ]);
        $_SESSION['last_bridge_res'] = $res['data'] ?? [];
        return $res['status'] === 'success';
    }
    public function fetch($mode = null) {
        $data = $_SESSION['last_bridge_res'] ?? [];
        return !empty($data) ? $data[0] : false;
    }
}

// Đổi biến kết nối gốc thành class giả lập cầu nối
$conn = new BridgePDO();
