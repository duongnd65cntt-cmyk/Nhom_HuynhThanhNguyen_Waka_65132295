<?php
/** Cấu hình dự án. Có thể ghi đè bằng biến môi trường (WAKA_DB_HOST, WAKA_DB_NAME, ...). */
const APP_ROOT       = __DIR__;
const APP_URL        = 'http://localhost/waka';   // dùng tạo link đặt lại mật khẩu
const APP_DEBUG      = true;                      // true: ghi email vào logs/mail.log
const MAT_KHAU_MIN   = 8;
const MAT_KHAU_MAX_BYTE = 72;
const TOKEN_HAN_PHUT = 30;                        // FR1.3
const ANH_MAX_BYTE   = 2 * 1024 * 1024;           // FR1.5
const HAN_THANH_TOAN_PHUT = 30;                   // FR3.4
const MOI_TRANG      = 20;                        // FR2.3
const CACHE_GIAY     = 60;                        // cache kệ trang chủ
// Thông tin chuyển khoản (QR VietQR)
const NH_MA = 'MB';  const NH_STK = '0000000000';  const NH_TEN = 'WAKA DEMO';
// SMTP (đặt mật khẩu ứng dụng qua biến môi trường WAKA_SMTP_PASS, không commit lên git)
define('SMTP_USER', getenv('WAKA_SMTP_USER') ?: '');
define('SMTP_PASS', getenv('WAKA_SMTP_PASS') ?: '');

try {
    $pdo = new PDO(
        'mysql:host=' . (getenv('WAKA_DB_HOST') ?: 'localhost') . ';dbname=' . (getenv('WAKA_DB_NAME') ?: 'waka_doc_sach') . ';charset=utf8mb4',
        getenv('WAKA_DB_USER') ?: 'root',
        getenv('WAKA_DB_PASS') ?: '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => true]
    );
} catch (PDOException $e) {
    error_log('DB: ' . $e->getMessage());
    http_response_code(500);
    exit('Không kết nối được CSDL. Kiểm tra config.php.');
}
