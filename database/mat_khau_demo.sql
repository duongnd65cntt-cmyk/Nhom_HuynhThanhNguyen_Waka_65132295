-- Đặt mật khẩu thật cho 5 tài khoản mẫu (dữ liệu mẫu trong dump chỉ có chuỗi băm giả nên không đăng nhập được).
-- Sau khi chạy: đăng nhập bằng  admin / vanan / thuhuong / minhquan / baongoc  với mật khẩu  Waka@12345
-- Chỉ dùng cho môi trường học tập/demo!
USE waka_doc_sach;
UPDATE nguoi_dung SET mat_khau_bam = '$2y$10$uD9dIwV4UEnWKIYA50YN4e5RSn7cu4wfJfyFoGSqhepM1tP8LL.1G' WHERE ma_nguoi_dung BETWEEN 1 AND 5;
