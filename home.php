<?php
session_start();

// Kiểm tra trạng thái đăng nhập
$is_logged_in = isset($_SESSION['user_logged']);
$username = $is_logged_in ? $_SESSION['user_logged'] : '';
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Khai báo biến lưu trạng thái Key Premium trong Database
$has_premium_key = false;

// CHỈ KIỂM TRA KEY KHI USER ĐÃ ĐĂNG NHẬP
if ($is_logged_in && $user_id) {
    // Nhúng file db.php kết nối database
    require_once 'api/db.php';

    try {
        // Kiểm tra xem user này đã kích hoạt hoặc có đơn hàng thành công nào chưa
        // Bạn có thể check qua bảng users (is_premium = 1) hoặc bảng activation_keys tùy logic cấu trúc cũ
        $sql = "SELECT is_premium FROM users WHERE id = :user_id AND is_premium = 1 LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);
        
        if ($stmt->rowCount() > 0) {
            $has_premium_key = true;
        }
    } catch (PDOException $e) {
        $has_premium_key = false;
    }
}

// Xử lý nút Đăng xuất
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: home.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gói và Mức Giá - Dịch phụ đề & lồng tiếng AI</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * { 
            box-sizing: border-box; 
            margin: 0; 
            padding: 0; 
            font-family: 'Plus Jakarta Sans', sans-serif; 
        }
        
        body { 
            background: #030303; 
            color: #fff; 
            min-height: 100vh; 
            display: flex; 
            flex-direction: column;
            overflow-x: hidden;
            position: relative;
        }

        /* Lớp Canvas bọc toàn bộ nền phía sau */
        #particle-canvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            pointer-events: none;
        }

        /* Header phong cách hiện đại */
        nav { 
            background: rgba(3, 3, 3, 0.7); 
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 14px 6%; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        nav .logo { font-size: 20px; font-weight: 700; color: #fff; letter-spacing: -0.5px; }
        nav .logo span { color: #1a73e8; }
        
        nav .user-actions { display: flex; align-items: center; gap: 15px; }
        
        /* Cụm thông tin tài khoản và trạng thái bản quyền */
        .account-status-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 13.5px;
        }
        .account-status-wrapper .username-txt { color: #aaa; }
        .account-status-wrapper .username-txt b { color: #fff; }

        /* Badge Trạng thái Tài khoản Miễn phí */
        .badge-header-free {
            background: rgba(234, 67, 53, 0.1);
            color: #ea4335;
            border: 1px solid rgba(234, 67, 53, 0.25);
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            cursor: pointer;
        }

        /* Badge Trạng thái Tài khoản Premium */
        .badge-header-premium {
            background: rgba(52, 168, 83, 0.12);
            color: #34a853;
            border: 1px solid rgba(52, 168, 83, 0.25);
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .btn-auth { 
            background: #1a73e8; 
            color: white; 
            text-decoration: none; 
            padding: 8px 20px; 
            border-radius: 24px; 
            font-size: 13.5px; 
            font-weight: 600; 
            box-shadow: 0 4px 15px rgba(26, 115, 232, 0.3);
            transition: all 0.3s ease; 
        }
        .btn-auth:hover { background: #1557b0; transform: translateY(-1px); }
        
        .btn-logout { 
            background: transparent; 
            color: #ff4a4a; 
            text-decoration: none; 
            font-size: 13px; 
            font-weight: 600; 
            padding: 6px 14px;
            border: 1px solid rgba(255, 74, 74, 0.3);
            border-radius: 24px;
            transition: all 0.3s;
        }
        .btn-logout:hover { background: rgba(255, 74, 74, 0.08); border-color: #ff4a4a; }

        /* Khung nội dung chính */
        .container { 
            position: relative;
            z-index: 1; 
            flex: 1; 
            max-width: 1200px; 
            width: 100%; 
            margin: 0 auto; 
            padding: 50px 20px; 
            text-align: center; 
        }
        
        .title-section h1 { 
            font-size: 42px; 
            font-weight: 700; 
            margin-bottom: 12px; 
            letter-spacing: -1px;
            background: linear-gradient(135deg, #ffffff 0%, #a3a3a3 100%); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent; 
        }
        .title-section p { color: #8c8c8c; font-size: 16px; margin-bottom: 50px; }

        /* Bảng giá 3 cột hiệu ứng Glassmorphism */
        .pricing-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 30px; margin-top: 20px; }
        
        .price-card { 
            background: rgba(255, 255, 255, 0.01); 
            border: 1px solid rgba(255, 255, 255, 0.03); 
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 28px; 
            padding: 45px 35px; 
            text-align: left; 
            display: flex; 
            flex-direction: column; 
            position: relative;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .price-card:hover { 
            background: rgba(255, 255, 255, 0.03);
            transform: translateY(-8px);
            border-color: rgba(26, 115, 232, 0.4); 
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5), 0 0 30px rgba(26, 115, 232, 0.1); 
        }
        
        .price-card.featured { 
            border-color: rgba(52, 168, 83, 0.15); 
            background: rgba(52, 168, 83, 0.02);
        }
        .price-card.featured:hover { 
            background: rgba(52, 168, 83, 0.05);
            border-color: #34a853;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6), 0 0 30px rgba(52, 168, 83, 0.15); 
        }

        .badge { 
            display: inline-block; 
            background: rgba(255, 255, 255, 0.05); 
            border: 1px solid rgba(255, 255, 255, 0.1); 
            color: #d1d1d1; 
            padding: 6px 14px; 
            border-radius: 20px; 
            font-size: 12px; 
            font-weight: 600; 
            margin-bottom: 25px; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .card-title { font-size: 26px; font-weight: 700; color: #fff; margin-bottom: 10px; }
        .card-price { font-size: 36px; color: #fff; margin-bottom: 8px; font-weight: 700; }
        .card-price span { font-size: 15px; color: #666; font-weight: normal; }
        .card-desc { color: #888; font-size: 14.5px; line-height: 1.6; margin-bottom: 30px; min-height: 70px; }
        
        .line-sep { height: 1px; background: rgba(255, 255, 255, 0.06); margin-bottom: 30px; }

        .feature-list { list-style: none; display: flex; flex-direction: column; gap: 16px; flex: 1; margin-bottom: 45px; }
        .feature-list li { font-size: 14.5px; color: #cbd5e1; display: flex; align-items: flex-start; gap: 12px; line-height: 1.4; }
        .feature-list li i { margin-top: 3px; font-size: 14px; }
        .feature-list li.disabled { color: #52525b; }
        .feature-list li.disabled i { color: #3f3f46; }
        
        .btn-action { 
            display: block; 
            width: 100%; 
            padding: 15px; 
            border: none; 
            border-radius: 25px; 
            background: #1a73e8; 
            color: white; 
            font-size: 15px; 
            font-weight: 600; 
            text-align: center; 
            text-decoration: none; 
            box-shadow: 0 4px 12px rgba(26, 115, 232, 0.2);
            transition: all 0.3s; 
        }
        .btn-action:hover { background: #1557b0; transform: translateY(-1px); box-shadow: 0 6px 18px rgba(26, 115, 232, 0.3); }
        
        .price-card.featured .btn-action { background: #34a853; box-shadow: 0 4px 12px rgba(52, 168, 83, 0.2); }
        .price-card.featured .btn-action:hover { background: #2d8b47; box-shadow: 0 6px 18px rgba(52, 168, 83, 0.3); }
        
        /* Thông tin lợi ích bổ sung */
        .benefits-section { margin-top: 100px; padding: 40px 20px; border-top: 1px solid rgba(255,255,255,0.04); }
        .benefits-section h2 { font-size: 32px; font-weight: 700; margin-bottom: 15px; letter-spacing: -0.5px; }
        .benefits-section .subtitle { color: #888; margin-bottom: 50px; font-size: 16px; }
        
        .benefits-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; }
        .benefit-box { background: rgba(255,255,255,0.01); border: 1px solid rgba(255,255,255,0.03); padding: 30px; border-radius: 20px; text-align: left; transition: all 0.3s; backdrop-filter: blur(4px); }
        .benefit-box:hover { background: rgba(255,255,255,0.03); transform: translateY(-4px); }
        .benefit-icon { width: 50px; height: 50px; border-radius: 12px; background: rgba(26, 115, 232, 0.1); color: #1a73e8; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 20px; }
        .benefit-box.green .benefit-icon { background: rgba(52, 168, 83, 0.1); color: #34a853; }
        .benefit-box.red .benefit-icon { background: rgba(234, 67, 53, 0.1); color: #ea4335; }
        .benefit-box.yellow .benefit-icon { background: rgba(251, 188, 4, 0.1); color: #fbbc04; }
        .benefit-box h3 { font-size: 18px; font-weight: 600; margin-bottom: 10px; color: #fff; }
        .benefit-box p { font-size: 14px; color: #888; line-height: 1.6; }

        footer { position: relative; z-index: 1; margin-top: 80px; padding: 30px; border-top: 1px solid rgba(255,255,255,0.04); text-align: center; color: #444; font-size: 13px; }
    </style>
</head>
<body>

    <canvas id="particle-canvas"></canvas>

    <nav>
        <div class="logo">Dịch phụ đề & lồng tiếng <span>AI</span></div>
        <div class="user-actions">
            <?php if ($is_logged_in): ?>
                <div class="account-status-wrapper">
                    <span class="username-txt"><i class="fa-regular fa-user"></i> <b><?php echo htmlspecialchars($username); ?></b></span>
                    
                    <?php if ($has_premium_key): ?>
                        <span class="badge-header-premium">
                            <i class="fa-solid fa-crown"></i> Bản quyền Premium
                        </span>
                    <?php else: ?>
                        <span class="badge-header-free" onclick="alert('Bạn chưa nâng cấp gói Premium trực tuyến. Các tính năng mở rộng đám mây hiện đang khóa.')" title="Xem chi tiết">
                            <i class="fa-solid fa-lock"></i> Tài khoản miễn phí
                        </span>
                    <?php endif; ?>
                </div>
                <a href="home.php?logout=true" class="btn-logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Đăng Xuất</a>
            <?php else: ?>
                <a href="index.php" class="btn-auth"><i class="fa-solid fa-user-plus"></i> Đăng Nhập / Đăng Ký</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <div class="title-section">
            <h1>Chọn gói nâng cấp AI phù hợp</h1>
            <p>Tăng tốc 300% hiệu suất làm video, phim ngắn, TikTok Reels bằng trí tuệ nhân tạo chuyên sâu</p>
        </div>

        <div class="pricing-grid">
            <div class="price-card">
                <div class="badge">Mặc định</div>
                <div class="card-title" style="color: #4285F4;">AI Free Plan</div>
                <div class="card-price">0đ <span>/ vĩnh viễn</span></div>
                <div class="card-desc">Sử dụng đầy đủ các tính năng biên tập cốt lõi của phần mềm trên máy tính cá nhân của bạn.</div>
                <div class="line-sep"></div>
                <ul class="feature-list">
                    <li><i class="fa-solid fa-check" style="color: #4285F4;"></i> Quét & dịch chữ cứng bằng PaddleOCR</li>
                    <li><i class="fa-solid fa-check" style="color: #4285F4;"></i> Khung Preview Video thời gian thực (24 FPS)</li>
                    <li><i class="fa-solid fa-check" style="color: #4285F4;"></i> Đọc lồng tiếng Edge-TTS (4 mẫu giọng cơ bản)</li>
                    <li><i class="fa-solid fa-check" style="color: #4285F4;"></i> Chống chồng thoại (Auto Speed tự động tính toán)</li>
                    <li><i class="fa-solid fa-check" style="color: #4285F4;"></i> Điều chỉnh vị trí/kích thước phụ đề kéo thả</li>
                    <li><i class="fa-solid fa-check" style="color: #4285F4;"></i> Chỉnh sửa text và tốc độ từng câu thoại</li>
                    <li><i class="fa-solid fa-check" style="color: #4285F4;"></i> Xuất video đè cứng phụ đề mã hóa Libx264</li>
                    <li class="disabled"><i class="fa-solid fa-xmark"></i> Không có bộ lọc nâng cao (Hộp thoại Blur/Khung mờ)</li>
                </ul>
                <a href="#" onclick="alert('Bạn đang sử dụng phiên bản phần mềm máy cục bộ mặc định!')" class="btn-action" style="background:rgba(255,255,255,0.04); color:#666; box-shadow:none;">Bản máy cục bộ</a>
            </div>

            <div class="price-card featured">
                <div class="badge" style="border-color:#34a853; color:#34a853;"><i class="fa-solid fa-fire"></i> Được khuyên dùng</div>
                <div class="card-title" style="color: #34a853;">AI Pro Video</div>
                <div class="card-price">139k <span>/ tháng</span></div>
                <div class="card-desc">Nâng tầm video chuyên nghiệp. Mở khóa các bộ lọc hiệu ứng chữ nâng cao và âm thanh phòng thu.</div>
                <div class="line-sep"></div>
                <ul class="feature-list">
                    <li><i class="fa-solid fa-check" style="color: #34a853;"></i> <b>Bao gồm toàn bộ tính năng của gói FREE</b></li>
                    <li><i class="fa-solid fa-check" style="color: #34a853;"></i> Mở khóa <b>Bộ lọc Khung Mờ (Semi-transparent)</b></li>
                    <li><i class="fa-solid fa-check" style="color: #34a853;"></i> Thêm hiệu ứng <b>Blur/Bóng đổ chữ</b> chuẩn Cinematic</li>
                    <li><i class="fa-solid fa-check" style="color: #34a853;"></i> Tính năng <b>Tách Nhạc Nền & Giọng Nói Video</b> gốc</li>
                    <li><i class="fa-solid fa-check" style="color: #34a853;"></i> Truy cập kho 20+ Giọng đọc AI Premium Đa vùng miền</li>
                    <li><i class="fa-solid fa-check" style="color: #34a853;"></i> Tốc độ Render xuất video nhanh gấp 3 lần (GPU)</li>
                    <li><i class="fa-solid fa-check" style="color: #34a853;"></i> Ưu tiên cập nhật bản sửa lỗi tự động</li>
                </ul>
                <?php if ($is_logged_in): ?>
                    <a href="pro.php" class="btn-action">Nâng Cấp Bản Pro →</a>
                <?php else: ?>
                    <a href="index.php" onclick="alert('Vui lòng đăng nhập tài khoản để thực hiện nâng cấp gói Pro!')" class="btn-action">Đăng Nhập Để Mua</a>
                <?php endif; ?>
            </div>

            <div class="price-card">
                <div class="badge">Không giới hạn</div>
                <div class="card-title" style="color: #ea4335;">AI Ultra Power</div>
                <div class="card-price">349k <span>/ tháng</span></div>
                <div class="card-desc">Dành cho các Studio, Agency làm phim hoặc nhà sáng tạo nội dung số lượng lớn hàng ngày.</div>
                <div class="line-sep"></div>
                <ul class="feature-list">
                    <li><i class="fa-solid fa-check" style="color: #ea4335;"></i> <b>Quyền lợi tối cao của gói PRO</b></li>
                    <li><i class="fa-solid fa-check" style="color: #ea4335;"></i> Nhận diện giọng nói thì thầm đa ngôn ngữ (Whisper)</li>
                    <li><i class="fa-solid fa-check" style="color: #ea4335;"></i> Dịch thuật ngữ cảnh bằng mô hình GPT-4o thế hệ mới</li>
                    <li><i class="fa-solid fa-check" style="color: #ea4335;"></i> Hỗ trợ xuất đồng thời tệp phụ đề rời (.SRT, .VTT, .TXT)</li>
                    <li><i class="fa-solid fa-check" style="color: #ea4335;"></i> Không giới hạn thời lượng video đầu vào (Phim dài)</li>
                    <li><i class="fa-solid fa-check" style="color: #ea4335;"></i> Bản quyền thương mại hóa âm thanh, video 100%</li>
                    <li><i class="fa-solid fa-check" style="color: #ea4335;"></i> Kênh hỗ trợ kỹ thuật VIP 1 kèm 1 từ đội ngũ Admin</li>
                </ul>
                <?php if ($is_logged_in): ?>
                    <a href="ultra.php" class="btn-action" style="background:#ea4335; box-shadow: 0 4px 12px rgba(234, 67, 53, 0.2);">Sở Hữu Bản Ultra →</a>
                <?php else: ?>
                    <a href="index.php" onclick="alert('Vui lòng đăng nhập tài khoản để thực hiện mua đặc quyền gói Ultra!')" class="btn-action" style="background:#ea4335;">Đăng Nhập Để Mua</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="benefits-section">
            <h2>Tại sao bạn nên chọn giải pháp của chúng tôi?</h2>
            <p class="subtitle">Quy trình dịch thuật và xử lý video tự động hóa tối đa, tiết kiệm hàng giờ đồng hồ ngồi edit thủ công</p>
            
            <div class="benefits-grid">
                <div class="benefit-box">
                    <div class="benefit-icon"><i class="fa-solid fa-bolt"></i></div>
                    <h3>Tốc độ vượt trội</h3>
                    <p>Hệ thống tự động quét chữ cứng trên phim, chuyển dịch sang tiếng Việt chính xác và đồng bộ thời gian (Timeline) chỉ trong vài phút.</p>
                </div>
                <div class="benefit-box green">
                    <div class="benefit-icon"><i class="fa-solid fa-microphone-lines"></i></div>
                    <h3>Giọng đọc AI tự nhiên</h3>
                    <p>Sử dụng công nghệ giọng nói AI tiên tiến, đọc diễn cảm, có đầy đủ ngữ điệu nam/nữ, Bắc/Nam loại bỏ hoàn toàn cảm giác robot.</p>
                </div>
                <div class="benefit-box red">
                    <div class="benefit-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
                    <h3>Thuật toán Chống chồng thoại</h3>
                    <p>Phần mềm độc quyền tự động tính toán độ dài văn bản dịch để điều chỉnh tốc độ đọc (Auto Speed), đảm bảo tiếng khớp hoàn toàn theo hình ảnh.</p>
                </div>
                <div class="benefit-box yellow">
                    <div class="benefit-icon"><i class="fa-solid fa-shield-halved"></i></div>
                    <h3>Bảo mật & Ổn định</h3>
                    <p>Phần mềm chạy mượt mà trên nền tảng máy tính, xử lý dữ liệu video cục bộ giúp bảo vệ tối đa bản quyền nội dung của bạn.</p>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <p>© 2026 Hệ thống Dịch Phụ Đề & Lồng Tiếng Video AI. Hỗ trợ kỹ thuật và phân phối độc quyền bởi Admin Team.</p>
    </footer>

    <script>
        const canvas = document.getElementById('particle-canvas');
        const ctx = canvas.getContext('2d');

        let particlesArray = [];
        const numberOfParticles = 100;
        const mouse = { x: null, y: null, radius: 180 };

        window.addEventListener('mousemove', function(event) {
            mouse.x = event.clientX;
            mouse.y = event.clientY;
        });

        window.addEventListener('mouseout', function() {
            mouse.x = null;
            mouse.y = null;
        });

        window.addEventListener('resize', function() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            init();
        });

        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        class Particle {
            constructor() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.size = Math.random() * 2 + 1;
                this.speedX = (Math.random() * 1) - 0.5;
                this.speedY = (Math.random() * 1) - 0.5;
            }

            update() {
                this.x += this.speedX;
                this.y += this.speedY;

                if (this.x < 0 || this.x > canvas.width) this.speedX = -this.speedX;
                if (this.y < 0 || this.y > canvas.height) this.speedY = -this.speedY;

                if (mouse.x != null && mouse.y != null) {
                    let dx = mouse.x - this.x;
                    let dy = mouse.y - this.y;
                    let distance = Math.sqrt(dx * dx + dy * dy);
                    
                    if (distance < mouse.radius) {
                        let force = (mouse.radius - distance) / mouse.radius;
                        this.x += (dx / distance) * force * 2.5;
                        this.y += (dy / distance) * force * 2.5;
                    }
                }
            }

            draw() {
                ctx.fillStyle = 'rgba(255, 255, 255, 0.45)';
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.closePath();
                ctx.fill();
            }
        }

        function init() {
            particlesArray = [];
            for (let i = 0; i < numberOfParticles; i++) {
                particlesArray.push(new Particle());
            }
        }

        function connectParticles() {
            let opacityValue = 1;
            for (let a = 0; a < particlesArray.length; a++) {
                for (let b = a; b < particlesArray.length; b++) {
                    let dx = particlesArray[a].x - particlesArray[b].x;
                    let dy = particlesArray[a].y - particlesArray[b].y;
                    let distance = Math.sqrt(dx * dx + dy * dy);

                    if (distance < 110) {
                        opacityValue = 1 - (distance / 110);
                        if (mouse.x != null) {
                            let mdx = mouse.x - particlesArray[a].x;
                            let mdy = mouse.y - particlesArray[a].y;
                            let mDist = Math.sqrt(mdx * mdx + mdy * mdy);
                            if (mDist < mouse.radius) {
                                opacityValue *= 2; 
                            }
                        }
                        ctx.strokeStyle = `rgba(26, 115, 232, ${opacityValue * 0.18})`;
                        ctx.lineWidth = 0.8;
                        ctx.beginPath();
                        ctx.moveTo(particlesArray[a].x, particlesArray[a].y);
                        ctx.lineTo(particlesArray[b].x, particlesArray[b].y);
                        ctx.stroke();
                    }
                }
            }
        }

        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            for (let i = 0; i < particlesArray.length; i++) {
                particlesArray[i].update();
                particlesArray[i].draw();
            }
            connectParticles();
            requestAnimationFrame(animate);
        }

        init();
        animate();
    </script>
</body>
</html>
