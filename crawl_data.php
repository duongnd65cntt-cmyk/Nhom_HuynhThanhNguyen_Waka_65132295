<?php
require_once 'config.php';

// Hàm tạo đường dẫn tĩnh (slug) chuẩn
function taoSlug($str) {
    $str = mb_strtolower($str, 'UTF-8');
    $str = preg_replace('/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/', 'a', $str);
    $str = preg_replace('/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/', 'e', $str);
    $str = preg_replace('/(ì|í|ị|ỉ|ĩ)/', 'i', $str);
    $str = preg_replace('/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/', 'o', $str);
    $str = preg_replace('/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/', 'u', $str);
    $str = preg_replace('/(ỳ|ý|ỵ|ỷ|ỹ)/', 'y', $str);
    $str = preg_replace('/(đ)/', 'd', $str);
    $str = preg_replace('/[^a-z0-9\-]/', '-', $str);
    $str = preg_replace('/-+/', '-', $str);
    return trim($str, '-');
}

$tu_khoa = ['tiểu thuyết ngôn tình', 'sách kinh tế quản trị', 'kỹ năng sống', 'truyện trinh thám', 'sách lịch sử'];
$so_sach_them_moi = 0;

foreach ($tu_khoa as $tu) {
    // Gọi API Google Books
    $url = "https://www.googleapis.com/books/v1/volumes?q=" . urlencode($tu) . "&maxResults=10&langRestrict=vi";
    $json = @file_get_contents($url);
    if (!$json) continue;
    
    $data = json_decode($json, true);
    if (!isset($data['items'])) continue;
    
    foreach ($data['items'] as $item) {
        $info = $item['volumeInfo'];
        $ten_sach = $info['title'] ?? 'Đang cập nhật';
        $tac_gia_arr = $info['authors'] ?? ['Nhiều tác giả'];
        $the_loai_arr = $info['categories'] ?? ['Sách tổng hợp'];
        $mo_ta = $info['description'] ?? 'Chưa có tóm tắt cho cuốn sách này.';
        $nxb = $info['publisher'] ?? 'NXB Trẻ';
        
        $ngay_xb = $info['publishedDate'] ?? '2023';
        $nam_xb = (int)substr($ngay_xb, 0, 4);
        if ($nam_xb === 0) $nam_xb = 2023;
        
        // Lấy ảnh bìa chất lượng cao nhất có thể
        $anh_bia = $info['imageLinks']['thumbnail'] ?? 'https://via.placeholder.com/250x350';
        $anh_bia = str_replace('http:', 'https:', $anh_bia);

        // 1. Xử lý Thể Loại
        $ten_tl = $the_loai_arr[0];
        $slug_tl = taoSlug($ten_tl);
        $stmt = $pdo->prepare("SELECT ma_the_loai FROM the_loai WHERE ten_the_loai = ?");
        $stmt->execute([$ten_tl]);
        $ma_tl = $stmt->fetchColumn();
        
        if (!$ma_tl) {
            $pdo->prepare("INSERT INTO the_loai (ten_the_loai, duong_dan_tinh, thu_tu) VALUES (?, ?, ?)")->execute([$ten_tl, $slug_tl, rand(1, 10)]);
            $ma_tl = $pdo->lastInsertId();
        }

        // 2. Xử lý Tác Giả
        $ten_tg = $tac_gia_arr[0];
        $slug_tg = taoSlug($ten_tg);
        $stmt = $pdo->prepare("SELECT ma_tac_gia FROM tac_gia WHERE ten_tac_gia = ?");
        $stmt->execute([$ten_tg]);
        $ma_tg = $stmt->fetchColumn();
        
        if (!$ma_tg) {
            $pdo->prepare("INSERT INTO tac_gia (ten_tac_gia, duong_dan_tinh) VALUES (?, ?)")->execute([$ten_tg, $slug_tg]);
            $ma_tg = $pdo->lastInsertId();
        }

        // 3. Xử lý Sách
        $slug_sach = taoSlug($ten_sach) . '-' . rand(1000, 9999);
        $stmt = $pdo->prepare("SELECT ma_sach FROM sach WHERE ten_sach = ?");
        $stmt->execute([$ten_sach]);
        
        if (!$stmt->fetchColumn()) {
            $dinh_dang_arr = ['SACH_DIEN_TU', 'SACH_NOI', 'TRUYEN_TRANH'];
            $dinh_dang = $dinh_dang_arr[rand(0, 2)];
            
            $hinh_thuc_arr = ['MIEN_PHI', 'HOI_VIEN', 'MUA_LE'];
            $hinh_thuc = $hinh_thuc_arr[rand(0, 2)];
            
            $gia_hat_soi = ($hinh_thuc == 'MUA_LE') ? rand(5, 50) * 10 : 0;
            $luot_xem = rand(100, 5000);
            $diem_tb = rand(35, 50) / 10;
            $tinh_trang = ['DANG_RA', 'HOAN_THANH'][rand(0, 1)];

            $sql = "INSERT INTO sach (ten_sach, duong_dan_tinh, ma_the_loai, ma_tac_gia, anh_bia, mo_ta, nha_xuat_ban, nam_xuat_ban, dinh_dang, hinh_thuc_doc, gia_hat_soi, tinh_trang, luot_xem, diem_tb) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([
                $ten_sach, $slug_sach, $ma_tl, $ma_tg, $anh_bia, $mo_ta, $nxb, $nam_xb, $dinh_dang, $hinh_thuc, $gia_hat_soi, $tinh_trang, $luot_xem, $diem_tb
            ]);
            $so_sach_them_moi++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Cập nhật dữ liệu - Waka Clone</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #121212; color: #fff; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .success-box { background-color: #1e1e1e; padding: 40px; border-radius: 12px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.5); border-top: 5px solid #1abc9c; max-width: 500px; }
        .icon { font-size: 50px; color: #1abc9c; margin-bottom: 20px; }
        h1 { margin: 0 0 15px; font-size: 24px; }
        p { color: #aaa; font-size: 16px; margin-bottom: 30px; line-height: 1.5; }
        .highlight { color: #f39c12; font-weight: bold; font-size: 20px; }
        .btn-home { background-color: #1abc9c; color: white; text-decoration: none; padding: 12px 25px; border-radius: 25px; font-weight: bold; font-size: 15px; transition: 0.3s; display: inline-block;}
        .btn-home:hover { background-color: #16a085; transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="success-box">
        <div class="icon">📚✓</div>
        <h1>Đồng bộ dữ liệu thành công!</h1>
        <p>Hệ thống đã kết nối với Google Books API và tự động kéo về thành công <br><span class="highlight"><?= $so_sach_them_moi ?></span> cuốn sách mới.</p>
        <a href="index.php" class="btn-home">Về trang chủ khám phá ngay</a>
    </div>
</body>
</html>