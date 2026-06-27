<?php
// api/db.php (Mã nguồn chạy trên RENDER - Bản nâng cấp sửa lỗi xác thực)

function query_infinity_bridge($action, $payload) {
    $url = "http://dichphude.great-site.net/db_bridge.php"; 
    
    $payload['bridge_action'] = $action;
    $payload['bridge_secret'] = 'MatKhauBaoMat123'; 

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

// Class giả lập đầy đủ tính năng của PDO thực tế
class BridgePDO {
    private $statement_action;
    private $current_data = [];
    private $pointer = 0;

    public function prepare($sql) {
        $this->statement_action = $sql;
        return $this;
    }

    public function execute($params = []) {
        $this->pointer = 0;
        $res = query_infinity_bridge('execute', [
            'sql' => $this->statement_action,
            'params' => $params
        ]);
        
        if (isset($res['status']) && $res['status'] === 'success') {
            $this->current_data = $res['data'] ?? [];
            return true;
        }
        $this->current_data = [];
        return false;
    }

    // Sửa lỗi xác thực dữ liệu: Trả về false đúng chuẩn PHP khi không có bản ghi nào
    public function fetch($mode = null) {
        if ($this->pointer < count($this->current_data)) {
            $row = $this->current_data[$this->pointer];
            $this->pointer++;
            return $row;
        }
        return false; 
    }

    public function fetchAll($mode = null) {
        return $this->current_data;
    }

    public function rowCount() {
        return count($this->current_data);
    }
}

$conn = new BridgePDO();
