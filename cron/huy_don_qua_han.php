<?php
// Chạy mỗi phút:  * * * * * php /đường/dẫn/waka/cron/huy_don_qua_han.php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../inc/don_hang.php';
echo 'Đã hủy ' . dh_huy_qua_han($pdo) . " đơn quá hạn\n";
