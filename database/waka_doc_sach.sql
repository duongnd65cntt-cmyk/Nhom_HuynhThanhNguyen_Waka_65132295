-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 04, 2026 at 03:03 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `waka_doc_sach`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_tinh_lai_diem_sach` (IN `p_ma_sach` INT)   BEGIN
    UPDATE sach s SET
        s.tong_danh_gia = (SELECT COUNT(*) FROM danh_gia d
                           WHERE d.ma_sach = p_ma_sach AND d.trang_thai = 'HIEN_THI'),
        s.diem_tb       = (SELECT IFNULL(ROUND(AVG(d.so_sao), 1), 0) FROM danh_gia d
                           WHERE d.ma_sach = p_ma_sach AND d.trang_thai = 'HIEN_THI')
    WHERE s.ma_sach = p_ma_sach;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `chi_tiet_don_hang`
--

CREATE TABLE `chi_tiet_don_hang` (
  `ma_chi_tiet` int(11) NOT NULL,
  `ma_don_hang` int(11) NOT NULL,
  `ma_sach` int(11) DEFAULT NULL,
  `ma_chuong` int(11) DEFAULT NULL,
  `ma_goi` int(11) DEFAULT NULL,
  `don_gia` decimal(12,0) NOT NULL,
  `so_hat_soi_nhan` int(11) NOT NULL DEFAULT 0
) ;

--
-- Dumping data for table `chi_tiet_don_hang`
--

INSERT INTO `chi_tiet_don_hang` (`ma_chi_tiet`, `ma_don_hang`, `ma_sach`, `ma_chuong`, `ma_goi`, `don_gia`, `so_hat_soi_nhan`) VALUES
(1, 1, NULL, 8, NULL, 20, 0),
(2, 2, NULL, NULL, NULL, 100000, 1000),
(3, 3, NULL, NULL, 2, 159000, 0),
(4, 4, 3, NULL, NULL, 120, 0),
(5, 5, NULL, NULL, 1, 59000, 0);

-- --------------------------------------------------------

--
-- Table structure for table `chuong`
--

CREATE TABLE `chuong` (
  `ma_chuong` int(11) NOT NULL,
  `ma_sach` int(11) NOT NULL,
  `so_thu_tu` int(11) NOT NULL,
  `tieu_de` varchar(255) NOT NULL,
  `noi_dung` longtext DEFAULT NULL,
  `tep_noi_dung` varchar(255) DEFAULT NULL,
  `che_do_truy_cap` enum('MIEN_PHI','HOI_VIEN','TRA_PHI') NOT NULL DEFAULT 'MIEN_PHI',
  `gia_hat_soi` decimal(12,0) NOT NULL DEFAULT 0,
  `trang_thai` tinyint(1) NOT NULL DEFAULT 1,
  `ngay_dang` datetime NOT NULL DEFAULT current_timestamp()
) ;

--
-- Dumping data for table `chuong`
--

INSERT INTO `chuong` (`ma_chuong`, `ma_sach`, `so_thu_tu`, `tieu_de`, `noi_dung`, `tep_noi_dung`, `che_do_truy_cap`, `gia_hat_soi`, `trang_thai`, `ngay_dang`) VALUES
(1, 1, 1, 'Chương 1: Làng Đo Đo', 'Làng Đo Đo nằm bên bờ sông, nơi Ngạn lớn lên cùng Hà Lan...', NULL, 'MIEN_PHI', 0, 1, '2026-11-04 08:59:17'),
(2, 1, 2, 'Chương 2: Đôi mắt', 'Đôi mắt của Hà Lan làm Ngạn thương nhớ suốt tuổi thơ...', NULL, 'MIEN_PHI', 0, 1, '2026-11-04 08:59:17'),
(3, 1, 3, 'Chương 3: Ra đi', 'Hà Lan theo gia đình lên thành phố...', NULL, 'MIEN_PHI', 0, 1, '2026-11-04 08:59:17'),
(4, 2, 1, 'Phần 1: Nguyên tắc ứng xử cơ bản', 'Đừng chỉ trích, oán trách hay phàn nàn...', NULL, 'HOI_VIEN', 0, 1, '2026-11-04 08:59:17'),
(5, 2, 2, 'Phần 2: Cách làm người khác yêu mến', 'Hãy thực lòng quan tâm đến người khác...', NULL, 'HOI_VIEN', 0, 1, '2026-11-04 08:59:17'),
(6, 2, 3, 'Phần 3: Thuyết phục người khác', 'Cách tốt nhất để thắng một cuộc tranh luận là tránh nó...', NULL, 'HOI_VIEN', 0, 1, '2026-11-04 08:59:17'),
(7, 3, 1, 'Chương 1: Bài học đầu tiên', 'Người giàu không làm việc vì tiền...', NULL, 'MIEN_PHI', 0, 1, '2026-11-04 08:59:17'),
(8, 3, 2, 'Chương 2: Tại sao dạy tài chính', 'Tài sản và thu nhập khác nhau thế nào...', NULL, 'TRA_PHI', 20, 1, '2026-11-04 08:59:17'),
(9, 3, 3, 'Chương 3: Lo chuyện của mình', 'Hãy lo việc kinh doanh của chính bạn...', NULL, 'TRA_PHI', 20, 1, '2026-11-04 08:59:17'),
(10, 4, 1, 'Truyện 1: Doraemon xuất hiện', NULL, '/uploads/truyen/doraemon-1/c1/', 'MIEN_PHI', 0, 1, '2026-11-04 08:59:17'),
(11, 4, 2, 'Truyện 2: Bảo bối thần kỳ', NULL, '/uploads/truyen/doraemon-1/c2/', 'MIEN_PHI', 0, 1, '2026-11-04 08:59:17'),
(12, 5, 1, 'Chương 1: Tuổi thơ', 'Thiều và Tường sống cùng nhau ở làng quê nghèo...', NULL, 'MIEN_PHI', 0, 1, '2026-11-04 08:59:17'),
(13, 5, 2, 'Chương 2: Mùa hè', 'Mùa hè năm ấy có nhiều chuyện xảy ra...', NULL, 'TRA_PHI', 30, 1, '2026-11-04 08:59:17'),
(14, 6, 1, 'Phần 1: Sống trọn trong hôm nay', NULL, '/uploads/audio/quang-ganh-lo-di/p1.mp3', 'HOI_VIEN', 0, 1, '2026-11-04 08:59:17'),
(15, 6, 2, 'Phần 2: Cách phân tích lo âu', NULL, '/uploads/audio/quang-ganh-lo-di/p2.mp3', 'HOI_VIEN', 0, 1, '2026-11-04 08:59:17');

-- --------------------------------------------------------

--
-- Table structure for table `dang_ky_hoi_vien`
--

CREATE TABLE `dang_ky_hoi_vien` (
  `ma_dang_ky` int(11) NOT NULL,
  `ma_nguoi_dung` int(11) NOT NULL,
  `ma_goi` int(11) NOT NULL,
  `ma_don_hang` int(11) NOT NULL,
  `ngay_bat_dau` datetime NOT NULL,
  `ngay_het_han` datetime NOT NULL
) ;

--
-- Dumping data for table `dang_ky_hoi_vien`
--

INSERT INTO `dang_ky_hoi_vien` (`ma_dang_ky`, `ma_nguoi_dung`, `ma_goi`, `ma_don_hang`, `ngay_bat_dau`, `ngay_het_han`) VALUES
(1, 4, 2, 3, '2026-11-03 10:00:00', '2027-02-08 10:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `danh_gia`
--

CREATE TABLE `danh_gia` (
  `ma_danh_gia` int(11) NOT NULL,
  `ma_sach` int(11) NOT NULL,
  `ma_nguoi_dung` int(11) NOT NULL,
  `so_sao` tinyint(4) NOT NULL,
  `binh_luan` text DEFAULT NULL,
  `trang_thai` enum('HIEN_THI','AN') NOT NULL DEFAULT 'HIEN_THI',
  `ngay_danh_gia` datetime NOT NULL DEFAULT current_timestamp(),
  `ngay_chinh_sua` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `danh_gia`
--

INSERT INTO `danh_gia` (`ma_danh_gia`, `ma_sach`, `ma_nguoi_dung`, `so_sao`, `binh_luan`, `trang_thai`, `ngay_danh_gia`, `ngay_chinh_sua`) VALUES
(1, 1, 2, 5, 'Truyện rất xúc động, đọc xong vẫn còn day dứt.', 'HIEN_THI', '2026-11-04 08:59:17', NULL),
(2, 1, 3, 4, 'Văn phong nhẹ nhàng, kết thúc hơi buồn.', 'HIEN_THI', '2026-11-04 08:59:17', NULL),
(3, 2, 3, 5, 'Sách nên đọc ít nhất một lần.', 'HIEN_THI', '2026-11-04 08:59:17', NULL),
(4, 3, 2, 4, 'Nhiều bài học tài chính bổ ích.', 'HIEN_THI', '2026-11-04 08:59:17', NULL),
(5, 4, 5, 5, 'Tuổi thơ ùa về!', 'HIEN_THI', '2026-11-04 08:59:17', NULL),
(6, 6, 4, 4, 'Giọng đọc dễ nghe, nội dung thực tế.', 'HIEN_THI', '2026-11-04 08:59:17', NULL);

--
-- Triggers `danh_gia`
--
DELIMITER $$
CREATE TRIGGER `tg_danh_gia_ad` AFTER DELETE ON `danh_gia` FOR EACH ROW BEGIN CALL sp_tinh_lai_diem_sach(OLD.ma_sach); END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tg_danh_gia_ai` AFTER INSERT ON `danh_gia` FOR EACH ROW BEGIN CALL sp_tinh_lai_diem_sach(NEW.ma_sach); END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tg_danh_gia_au` AFTER UPDATE ON `danh_gia` FOR EACH ROW BEGIN CALL sp_tinh_lai_diem_sach(NEW.ma_sach); END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `dau_trang`
--

CREATE TABLE `dau_trang` (
  `ma_dau_trang` int(11) NOT NULL,
  `ma_nguoi_dung` int(11) NOT NULL,
  `ma_chuong` int(11) NOT NULL,
  `vi_tri_cuon` decimal(5,2) NOT NULL DEFAULT 0.00,
  `ghi_chu` varchar(255) DEFAULT NULL,
  `ngay_tao` datetime NOT NULL DEFAULT current_timestamp()
) ;

--
-- Dumping data for table `dau_trang`
--

INSERT INTO `dau_trang` (`ma_dau_trang`, `ma_nguoi_dung`, `ma_chuong`, `vi_tri_cuon`, `ghi_chu`, `ngay_tao`) VALUES
(1, 2, 1, 30.00, 'Đoạn mô tả làng quê rất hay', '2026-11-04 08:59:18'),
(2, 3, 4, 15.50, 'Quay lại đọc sau', '2026-11-04 08:59:18'),
(3, 4, 14, 50.00, NULL, '2026-11-04 08:59:18');

-- --------------------------------------------------------

--
-- Table structure for table `don_hang`
--

CREATE TABLE `don_hang` (
  `ma_don_hang` int(11) NOT NULL,
  `so_don_hang` varchar(30) NOT NULL,
  `ma_nguoi_dung` int(11) NOT NULL,
  `loai_don` enum('MUA_NOI_DUNG','NAP_HAT_SOI','MUA_VIP') NOT NULL,
  `ma_giam_gia` int(11) DEFAULT NULL,
  `tong_tien_goc` decimal(12,0) NOT NULL,
  `tien_giam` decimal(12,0) NOT NULL DEFAULT 0,
  `hat_soi_tang` int(11) NOT NULL DEFAULT 0,
  `ngay_vip_tang` int(11) NOT NULL DEFAULT 0,
  `trang_thai` enum('CHO_THANH_TOAN','DA_THANH_TOAN','THAT_BAI','DA_HUY') NOT NULL DEFAULT 'CHO_THANH_TOAN',
  `han_thanh_toan` datetime DEFAULT NULL,
  `ly_do_huy` varchar(255) DEFAULT NULL,
  `ngay_huy` datetime DEFAULT NULL,
  `ngay_tao` datetime NOT NULL DEFAULT current_timestamp()
) ;

--
-- Dumping data for table `don_hang`
--

INSERT INTO `don_hang` (`ma_don_hang`, `so_don_hang`, `ma_nguoi_dung`, `loai_don`, `ma_giam_gia`, `tong_tien_goc`, `tien_giam`, `hat_soi_tang`, `ngay_vip_tang`, `trang_thai`, `han_thanh_toan`, `ly_do_huy`, `ngay_huy`, `ngay_tao`) VALUES
(1, 'DH20261101-0001', 2, 'MUA_NOI_DUNG', NULL, 20, 0, 0, 0, 'DA_THANH_TOAN', NULL, NULL, NULL, '2026-11-04 08:59:18'),
(2, 'DH20261102-0001', 3, 'NAP_HAT_SOI', 2, 100000, 20000, 0, 0, 'DA_THANH_TOAN', NULL, NULL, NULL, '2026-11-04 08:59:18'),
(3, 'DH20261103-0001', 4, 'MUA_VIP', 3, 159000, 0, 0, 7, 'DA_THANH_TOAN', NULL, NULL, NULL, '2026-11-04 08:59:18'),
(4, 'DH20261104-0001', 5, 'MUA_NOI_DUNG', NULL, 120, 0, 0, 0, 'CHO_THANH_TOAN', '2026-11-04 23:59:59', NULL, NULL, '2026-11-04 08:59:18'),
(5, 'DH20261030-0001', 2, 'MUA_VIP', NULL, 59000, 0, 0, 0, 'DA_HUY', NULL, 'Người dùng đổi ý', '2026-10-30 21:15:00', '2026-11-04 08:59:18');

-- --------------------------------------------------------

--
-- Table structure for table `gio_hang`
--

CREATE TABLE `gio_hang` (
  `ma_gio_hang` int(11) NOT NULL,
  `ma_nguoi_dung` int(11) NOT NULL,
  `ma_sach` int(11) DEFAULT NULL,
  `ma_chuong` int(11) DEFAULT NULL,
  `ngay_them` datetime NOT NULL DEFAULT current_timestamp()
) ;

--
-- Dumping data for table `gio_hang`
--

INSERT INTO `gio_hang` (`ma_gio_hang`, `ma_nguoi_dung`, `ma_sach`, `ma_chuong`, `ngay_them`) VALUES
(1, 5, NULL, 9, '2026-11-04 08:59:18'),
(2, 3, 5, NULL, '2026-11-04 08:59:18');

-- --------------------------------------------------------

--
-- Table structure for table `goi_hoi_vien`
--

CREATE TABLE `goi_hoi_vien` (
  `ma_goi` int(11) NOT NULL,
  `ten_goi` varchar(100) NOT NULL,
  `gia_vnd` decimal(12,0) NOT NULL,
  `thoi_han_ngay` int(11) NOT NULL,
  `quyen_loi` text DEFAULT NULL,
  `trang_thai` tinyint(1) NOT NULL DEFAULT 1
) ;

--
-- Dumping data for table `goi_hoi_vien`
--

INSERT INTO `goi_hoi_vien` (`ma_goi`, `ten_goi`, `gia_vnd`, `thoi_han_ngay`, `quyen_loi`, `trang_thai`) VALUES
(1, 'WakaVIP 1 tháng', 59000, 30, 'Đọc không giới hạn sách hội viên', 1),
(2, 'WakaVIP 3 tháng', 159000, 90, 'Đọc không giới hạn sách hội viên', 1),
(3, 'WakaVIP 1 năm', 549000, 365, 'Đọc không giới hạn sách hội viên', 1);

-- --------------------------------------------------------

--
-- Table structure for table `lich_su_doc`
--

CREATE TABLE `lich_su_doc` (
  `ma_lich_su` int(11) NOT NULL,
  `ma_nguoi_dung` int(11) NOT NULL,
  `ma_chuong` int(11) NOT NULL,
  `ngay_doc` date NOT NULL,
  `thoi_diem_doc` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lich_su_doc`
--

INSERT INTO `lich_su_doc` (`ma_lich_su`, `ma_nguoi_dung`, `ma_chuong`, `ngay_doc`, `thoi_diem_doc`) VALUES
(1, 2, 1, '2026-11-01', '2026-11-01 08:10:00'),
(2, 2, 8, '2026-11-01', '2026-11-01 09:35:00'),
(3, 3, 4, '2026-11-02', '2026-11-02 20:00:00'),
(4, 4, 14, '2026-11-03', '2026-11-03 22:15:00'),
(5, 5, 10, '2026-11-04', '2026-11-04 07:45:00');

-- --------------------------------------------------------

--
-- Table structure for table `ma_giam_gia`
--

CREATE TABLE `ma_giam_gia` (
  `ma_giam_gia` int(11) NOT NULL,
  `ma_code` varchar(30) NOT NULL,
  `loai_giam` enum('GIAM_PHAN_TRAM','GIAM_TIEN','TANG_THEM') NOT NULL,
  `gia_tri` decimal(12,0) NOT NULL,
  `loai_quyen_loi_tang` enum('HAT_SOI','NGAY_VIP') DEFAULT NULL,
  `muc_giam_toi_da` decimal(12,0) DEFAULT NULL,
  `gia_tri_don_toi_thieu` decimal(12,0) NOT NULL DEFAULT 0,
  `pham_vi_ap_dung` enum('MUA_NOI_DUNG','NAP_HAT_SOI','MUA_VIP','TAT_CA') NOT NULL DEFAULT 'TAT_CA',
  `ngay_bat_dau` datetime NOT NULL,
  `ngay_het_han` datetime NOT NULL,
  `gioi_han_toan_he` int(11) DEFAULT NULL,
  `so_luot_da_dung` int(11) NOT NULL DEFAULT 0,
  `gioi_han_moi_nguoi` int(11) NOT NULL DEFAULT 1,
  `trang_thai` tinyint(1) NOT NULL DEFAULT 1,
  `ngay_tao` datetime NOT NULL DEFAULT current_timestamp()
) ;

--
-- Dumping data for table `ma_giam_gia`
--

INSERT INTO `ma_giam_gia` (`ma_giam_gia`, `ma_code`, `loai_giam`, `gia_tri`, `loai_quyen_loi_tang`, `muc_giam_toi_da`, `gia_tri_don_toi_thieu`, `pham_vi_ap_dung`, `ngay_bat_dau`, `ngay_het_han`, `gioi_han_toan_he`, `so_luot_da_dung`, `gioi_han_moi_nguoi`, `trang_thai`, `ngay_tao`) VALUES
(1, 'CHAOBAN10', 'GIAM_PHAN_TRAM', 10, NULL, 20000, 0, 'TAT_CA', '2026-01-01 00:00:00', '2026-12-31 23:59:59', 1000, 0, 1, 1, '2026-11-04 08:59:18'),
(2, 'NAP20K', 'GIAM_TIEN', 20000, NULL, NULL, 100000, 'NAP_HAT_SOI', '2026-10-01 00:00:00', '2026-12-31 23:59:59', 500, 1, 1, 1, '2026-11-04 08:59:18'),
(3, 'TANGVIP7', 'TANG_THEM', 7, 'NGAY_VIP', NULL, 0, 'MUA_VIP', '2026-10-01 00:00:00', '2026-12-31 23:59:59', 200, 1, 1, 1, '2026-11-04 08:59:18');

-- --------------------------------------------------------

--
-- Table structure for table `nguoi_dung`
--

CREATE TABLE `nguoi_dung` (
  `ma_nguoi_dung` int(11) NOT NULL,
  `ten_dang_nhap` varchar(50) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mat_khau_bam` varchar(255) NOT NULL,
  `ho_ten` varchar(150) NOT NULL,
  `so_dien_thoai` varchar(20) DEFAULT NULL,
  `ngay_sinh` date DEFAULT NULL,
  `gioi_tinh` enum('NAM','NU','KHAC') DEFAULT NULL,
  `anh_dai_dien` varchar(255) DEFAULT NULL,
  `vai_tro` enum('THANH_VIEN','QUAN_TRI') NOT NULL DEFAULT 'THANH_VIEN',
  `trang_thai` enum('HOAT_DONG','BI_KHOA') NOT NULL DEFAULT 'HOAT_DONG',
  `so_du_hat_soi` decimal(12,0) NOT NULL DEFAULT 0,
  `ma_dat_lai_mat_khau` char(64) DEFAULT NULL,
  `han_dat_lai_mat_khau` datetime DEFAULT NULL,
  `ngay_tao` datetime NOT NULL DEFAULT current_timestamp(),
  `ngay_cap_nhat` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `nguoi_dung`
--

INSERT INTO `nguoi_dung` (`ma_nguoi_dung`, `ten_dang_nhap`, `email`, `mat_khau_bam`, `ho_ten`, `so_dien_thoai`, `ngay_sinh`, `gioi_tinh`, `anh_dai_dien`, `vai_tro`, `trang_thai`, `so_du_hat_soi`, `ma_dat_lai_mat_khau`, `han_dat_lai_mat_khau`, `ngay_tao`, `ngay_cap_nhat`) VALUES
(1, 'admin', 'admin@wakademo.vn', '$2b$10$adminadminadminadminadminadminadminadminadminadminadmi', 'Quản trị viên', '0900000001', '1990-01-01', 'KHAC', NULL, 'QUAN_TRI', 'HOAT_DONG', 0, NULL, NULL, '2026-11-04 08:59:17', '2026-11-04 08:59:17'),
(2, 'vanan', 'vanan@gmail.com', '$2b$10$vananvananvananvananvananvananvananvananvananvananvanan', 'Nguyễn Văn An', '0901234567', '2001-05-12', 'NAM', NULL, 'THANH_VIEN', 'HOAT_DONG', 80, NULL, NULL, '2026-11-04 08:59:17', '2026-11-04 08:59:17'),
(3, 'thuhuong', 'thuhuong@gmail.com', '$2b$10$thuhuongthuhuongthuhuongthuhuongthuhuongthuhuongthuhuon', 'Trần Thu Hương', '0912345678', '2000-09-20', 'NU', NULL, 'THANH_VIEN', 'HOAT_DONG', 1000, NULL, NULL, '2026-11-04 08:59:17', '2026-11-04 08:59:17'),
(4, 'minhquan', 'quan@gmail.com', '$2b$10$minhquanminhquanminhquanminhquanminhquanminhquanminhqu', 'Lê Minh Quân', NULL, '1998-03-02', 'NAM', NULL, 'THANH_VIEN', 'HOAT_DONG', 0, NULL, NULL, '2026-11-04 08:59:17', '2026-11-04 08:59:17'),
(5, 'baongoc', 'baongoc@gmail.com', '$2b$10$baongocbaongocbaongocbaongocbaongocbaongocbaongocbaong', 'Phạm Bảo Ngọc', '0987654321', '2003-11-30', 'NU', NULL, 'THANH_VIEN', 'HOAT_DONG', 50, NULL, NULL, '2026-11-04 08:59:17', '2026-11-04 08:59:17');

-- --------------------------------------------------------

--
-- Table structure for table `sach`
--

CREATE TABLE `sach` (
  `ma_sach` int(11) NOT NULL,
  `ten_sach` varchar(255) NOT NULL,
  `duong_dan_tinh` varchar(255) NOT NULL,
  `ma_the_loai` int(11) NOT NULL,
  `ma_tac_gia` int(11) NOT NULL,
  `anh_bia` varchar(255) NOT NULL,
  `mo_ta` text DEFAULT NULL,
  `nha_xuat_ban` varchar(150) DEFAULT NULL,
  `nam_xuat_ban` smallint(6) DEFAULT NULL,
  `dinh_dang` enum('SACH_DIEN_TU','SACH_NOI','TRUYEN_TRANH') NOT NULL DEFAULT 'SACH_DIEN_TU',
  `hinh_thuc_doc` enum('MIEN_PHI','HOI_VIEN','MUA_LE') NOT NULL DEFAULT 'MIEN_PHI',
  `gia_hat_soi` decimal(12,0) NOT NULL DEFAULT 0,
  `tinh_trang` enum('DANG_RA','HOAN_THANH','TAM_AN') NOT NULL DEFAULT 'DANG_RA',
  `luot_xem` bigint(20) NOT NULL DEFAULT 0,
  `diem_tb` decimal(2,1) NOT NULL DEFAULT 0.0,
  `tong_danh_gia` int(11) NOT NULL DEFAULT 0,
  `ngay_dang` datetime NOT NULL DEFAULT current_timestamp(),
  `ngay_cap_nhat` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `sach`
--

INSERT INTO `sach` (`ma_sach`, `ten_sach`, `duong_dan_tinh`, `ma_the_loai`, `ma_tac_gia`, `anh_bia`, `mo_ta`, `nha_xuat_ban`, `nam_xuat_ban`, `dinh_dang`, `hinh_thuc_doc`, `gia_hat_soi`, `tinh_trang`, `luot_xem`, `diem_tb`, `tong_danh_gia`, `ngay_dang`, `ngay_cap_nhat`) VALUES
(1, 'Mắt Biếc', 'mat-biec', 5, 1, '/uploads/bia/mat-biec.jpg', 'Câu chuyện tình đơn phương của Ngạn dành cho Hà Lan.', 'NXB Trẻ', 1990, 'SACH_DIEN_TU', 'MIEN_PHI', 0, 'HOAN_THANH', 1520, 4.5, 2, '2026-11-04 08:59:17', '2026-11-04 08:59:17'),
(2, 'Đắc Nhân Tâm', 'dac-nhan-tam', 2, 2, '/uploads/bia/dac-nhan-tam.jpg', 'Cuốn sách kinh điển về nghệ thuật đối nhân xử thế.', 'NXB Tổng hợp TP.HCM', 2018, 'SACH_DIEN_TU', 'HOI_VIEN', 0, 'HOAN_THANH', 3210, 5.0, 1, '2026-11-04 08:59:17', '2026-11-04 08:59:17'),
(3, 'Cha Giàu Cha Nghèo', 'cha-giau-cha-ngheo', 6, 3, '/uploads/bia/cha-giau-cha-ngheo.jpg', 'Bài học tài chính từ hai người cha có tư duy khác nhau.', 'NXB Trẻ', 2017, 'SACH_DIEN_TU', 'MUA_LE', 120, 'HOAN_THANH', 2750, 4.0, 1, '2026-11-04 08:59:17', '2026-11-04 08:59:17'),
(4, 'Doraemon - Tập 1', 'doraemon-tap-1', 4, 4, '/uploads/bia/doraemon-1.jpg', 'Những cuộc phiêu lưu đầu tiên của chú mèo máy đến từ tương lai.', 'NXB Kim Đồng', 2015, 'TRUYEN_TRANH', 'MIEN_PHI', 0, 'DANG_RA', 4890, 5.0, 1, '2026-11-04 08:59:17', '2026-11-04 08:59:17'),
(5, 'Tôi Thấy Hoa Vàng Trên Cỏ Xanh', 'toi-thay-hoa-vang-tren-co-xanh', 1, 1, '/uploads/bia/hoa-vang-co-xanh.jpg', 'Tuổi thơ miền quê qua đôi mắt của cậu bé Thiều.', 'NXB Trẻ', 2010, 'SACH_DIEN_TU', 'MUA_LE', 150, 'HOAN_THANH', 1980, 0.0, 0, '2026-11-04 08:59:17', '2026-11-04 08:59:17'),
(6, 'Quẳng Gánh Lo Đi Và Vui Sống', 'quang-ganh-lo-di', 2, 2, '/uploads/bia/quang-ganh-lo-di.jpg', 'Bản sách nói về cách sống tích cực, bớt lo âu.', 'NXB Tổng hợp TP.HCM', 2019, 'SACH_NOI', 'HOI_VIEN', 0, 'HOAN_THANH', 860, 4.0, 1, '2026-11-04 08:59:17', '2026-11-04 08:59:17');

-- --------------------------------------------------------

--
-- Table structure for table `tac_gia`
--

CREATE TABLE `tac_gia` (
  `ma_tac_gia` int(11) NOT NULL,
  `ten_tac_gia` varchar(150) NOT NULL,
  `duong_dan_tinh` varchar(160) NOT NULL,
  `anh_dai_dien` varchar(255) DEFAULT NULL,
  `tieu_su` text DEFAULT NULL,
  `ngay_tao` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tac_gia`
--

INSERT INTO `tac_gia` (`ma_tac_gia`, `ten_tac_gia`, `duong_dan_tinh`, `anh_dai_dien`, `tieu_su`, `ngay_tao`) VALUES
(1, 'Nguyễn Nhật Ánh', 'nguyen-nhat-anh', NULL, 'Nhà văn nổi tiếng với các tác phẩm về tuổi thơ và tuổi học trò.', '2026-11-04 08:59:17'),
(2, 'Dale Carnegie', 'dale-carnegie', NULL, 'Tác giả và diễn giả người Mỹ, chuyên về nghệ thuật giao tiếp.', '2026-11-04 08:59:17'),
(3, 'Robert Kiyosaki', 'robert-kiyosaki', NULL, 'Tác giả sách về tài chính cá nhân và đầu tư.', '2026-11-04 08:59:17'),
(4, 'Fujiko F. Fujio', 'fujiko-f-fujio', NULL, 'Họa sĩ manga Nhật Bản, cha đẻ của Doraemon.', '2026-11-04 08:59:17');

-- --------------------------------------------------------

--
-- Table structure for table `thanh_toan`
--

CREATE TABLE `thanh_toan` (
  `ma_thanh_toan` int(11) NOT NULL,
  `ma_don_hang` int(11) NOT NULL,
  `phuong_thuc` enum('VI_HAT_SOI','CHUYEN_KHOAN','GIA_LAP') NOT NULL,
  `so_tien` decimal(12,0) NOT NULL,
  `trang_thai` enum('CHO_XU_LY','THANH_CONG','THAT_BAI') NOT NULL DEFAULT 'CHO_XU_LY',
  `ma_giao_dich` varchar(100) DEFAULT NULL,
  `noi_dung_chuyen_khoan` varchar(50) DEFAULT NULL,
  `ma_nguoi_duyet` int(11) DEFAULT NULL,
  `ngay_duyet` datetime DEFAULT NULL,
  `ghi_chu` varchar(255) DEFAULT NULL,
  `ngay_tao` datetime NOT NULL DEFAULT current_timestamp(),
  `ngay_thanh_toan` datetime DEFAULT NULL
) ;

--
-- Dumping data for table `thanh_toan`
--

INSERT INTO `thanh_toan` (`ma_thanh_toan`, `ma_don_hang`, `phuong_thuc`, `so_tien`, `trang_thai`, `ma_giao_dich`, `noi_dung_chuyen_khoan`, `ma_nguoi_duyet`, `ngay_duyet`, `ghi_chu`, `ngay_tao`, `ngay_thanh_toan`) VALUES
(1, 1, 'VI_HAT_SOI', 20, 'THANH_CONG', NULL, NULL, NULL, NULL, 'Trừ trực tiếp từ ví hạt sồi', '2026-11-04 08:59:18', '2026-11-01 09:30:00'),
(2, 2, 'CHUYEN_KHOAN', 80000, 'THANH_CONG', 'FT26110212345', 'DH20261102-0001', 1, '2026-11-02 14:05:00', 'Admin duyệt thủ công', '2026-11-04 08:59:18', '2026-11-02 14:00:00'),
(3, 3, 'GIA_LAP', 159000, 'THANH_CONG', 'SIM-000001', NULL, NULL, NULL, 'Cổng thanh toán giả lập', '2026-11-04 08:59:18', '2026-11-03 10:00:00'),
(4, 5, 'CHUYEN_KHOAN', 59000, 'THAT_BAI', NULL, 'DH20261030-0001', NULL, NULL, 'Không nhận được tiền', '2026-11-04 08:59:18', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `the_loai`
--

CREATE TABLE `the_loai` (
  `ma_the_loai` int(11) NOT NULL,
  `ten_the_loai` varchar(100) NOT NULL,
  `duong_dan_tinh` varchar(120) NOT NULL,
  `mo_ta` text DEFAULT NULL,
  `ma_the_loai_cha` int(11) DEFAULT NULL,
  `thu_tu` int(11) NOT NULL DEFAULT 0,
  `trang_thai` tinyint(1) NOT NULL DEFAULT 1,
  `ngay_tao` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `the_loai`
--

INSERT INTO `the_loai` (`ma_the_loai`, `ten_the_loai`, `duong_dan_tinh`, `mo_ta`, `ma_the_loai_cha`, `thu_tu`, `trang_thai`, `ngay_tao`) VALUES
(1, 'Tiểu thuyết', 'tieu-thuyet', NULL, NULL, 1, 1, '2026-11-04 08:52:17'),
(2, 'Kỹ năng sống', 'ky-nang-song', NULL, NULL, 2, 1, '2026-11-04 08:52:17'),
(3, 'Kinh tế', 'kinh-te', NULL, NULL, 3, 1, '2026-11-04 08:52:17'),
(4, 'Truyện tranh', 'truyen-tranh', NULL, NULL, 4, 1, '2026-11-04 08:52:17'),
(5, 'Tiểu thuyết tình cảm', 'tieu-thuyet-tinh-cam', 'Các tác phẩm về tình yêu, tuổi trẻ', 1, 1, 1, '2026-11-04 08:59:17'),
(6, 'Đầu tư tài chính', 'dau-tu-tai-chinh', 'Quản lý tiền bạc và đầu tư', 3, 1, 1, '2026-11-04 08:59:17');

-- --------------------------------------------------------

--
-- Table structure for table `thu_vien`
--

CREATE TABLE `thu_vien` (
  `ma_thu_vien` int(11) NOT NULL,
  `ma_nguoi_dung` int(11) NOT NULL,
  `ma_sach` int(11) NOT NULL,
  `yeu_thich` tinyint(1) NOT NULL DEFAULT 0,
  `da_mua` tinyint(1) NOT NULL DEFAULT 0,
  `ngay_them` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `thu_vien`
--

INSERT INTO `thu_vien` (`ma_thu_vien`, `ma_nguoi_dung`, `ma_sach`, `yeu_thich`, `da_mua`, `ngay_them`) VALUES
(1, 2, 3, 0, 1, '2026-11-04 08:59:18'),
(2, 2, 1, 1, 0, '2026-11-04 08:59:18'),
(3, 3, 2, 1, 0, '2026-11-04 08:59:18'),
(4, 4, 6, 1, 0, '2026-11-04 08:59:18'),
(5, 5, 4, 1, 0, '2026-11-04 08:59:18');

-- --------------------------------------------------------

--
-- Table structure for table `tien_do_doc`
--

CREATE TABLE `tien_do_doc` (
  `ma_nguoi_dung` int(11) NOT NULL,
  `ma_chuong` int(11) NOT NULL,
  `vi_tri_cuon` decimal(5,2) NOT NULL DEFAULT 0.00,
  `ngay_cap_nhat` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `tien_do_doc`
--

INSERT INTO `tien_do_doc` (`ma_nguoi_dung`, `ma_chuong`, `vi_tri_cuon`, `ngay_cap_nhat`) VALUES
(2, 1, 45.50, '2026-11-04 08:59:18'),
(2, 8, 100.00, '2026-11-04 08:59:18'),
(3, 4, 20.00, '2026-11-04 08:59:18'),
(4, 14, 60.00, '2026-11-04 08:59:18'),
(5, 10, 80.25, '2026-11-04 08:59:18');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_don_hang`
-- (See below for the actual view)
--
CREATE TABLE `v_don_hang` (
`ma_don_hang` int(11)
,`so_don_hang` varchar(30)
,`ma_nguoi_dung` int(11)
,`loai_don` enum('MUA_NOI_DUNG','NAP_HAT_SOI','MUA_VIP')
,`ma_giam_gia` int(11)
,`tong_tien_goc` decimal(12,0)
,`tien_giam` decimal(12,0)
,`hat_soi_tang` int(11)
,`ngay_vip_tang` int(11)
,`trang_thai` enum('CHO_THANH_TOAN','DA_THANH_TOAN','THAT_BAI','DA_HUY')
,`han_thanh_toan` datetime
,`ly_do_huy` varchar(255)
,`ngay_huy` datetime
,`ngay_tao` datetime
,`tong_thanh_toan` decimal(13,0)
,`don_vi_tinh` varchar(7)
);

-- --------------------------------------------------------

--
-- Structure for view `v_don_hang`
--
DROP TABLE IF EXISTS `v_don_hang`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_don_hang`  AS SELECT `dh`.`ma_don_hang` AS `ma_don_hang`, `dh`.`so_don_hang` AS `so_don_hang`, `dh`.`ma_nguoi_dung` AS `ma_nguoi_dung`, `dh`.`loai_don` AS `loai_don`, `dh`.`ma_giam_gia` AS `ma_giam_gia`, `dh`.`tong_tien_goc` AS `tong_tien_goc`, `dh`.`tien_giam` AS `tien_giam`, `dh`.`hat_soi_tang` AS `hat_soi_tang`, `dh`.`ngay_vip_tang` AS `ngay_vip_tang`, `dh`.`trang_thai` AS `trang_thai`, `dh`.`han_thanh_toan` AS `han_thanh_toan`, `dh`.`ly_do_huy` AS `ly_do_huy`, `dh`.`ngay_huy` AS `ngay_huy`, `dh`.`ngay_tao` AS `ngay_tao`, `dh`.`tong_tien_goc`- `dh`.`tien_giam` AS `tong_thanh_toan`, CASE `dh`.`loai_don` WHEN 'MUA_NOI_DUNG' THEN 'HAT_SOI' ELSE 'VND' END AS `don_vi_tinh` FROM `don_hang` AS `dh` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `chi_tiet_don_hang`
--
ALTER TABLE `chi_tiet_don_hang`
  ADD PRIMARY KEY (`ma_chi_tiet`),
  ADD KEY `ix_ctdh_don_hang` (`ma_don_hang`),
  ADD KEY `ix_ctdh_sach` (`ma_sach`),
  ADD KEY `ix_ctdh_chuong` (`ma_chuong`),
  ADD KEY `ix_ctdh_goi` (`ma_goi`);

--
-- Indexes for table `chuong`
--
ALTER TABLE `chuong`
  ADD PRIMARY KEY (`ma_chuong`),
  ADD UNIQUE KEY `uq_chuong_sach_thu_tu` (`ma_sach`,`so_thu_tu`);

--
-- Indexes for table `dang_ky_hoi_vien`
--
ALTER TABLE `dang_ky_hoi_vien`
  ADD PRIMARY KEY (`ma_dang_ky`),
  ADD UNIQUE KEY `uq_dkhv_don_hang` (`ma_don_hang`),
  ADD KEY `ix_dkhv_nguoi_dung_han` (`ma_nguoi_dung`,`ngay_het_han`),
  ADD KEY `ix_dkhv_goi` (`ma_goi`);

--
-- Indexes for table `danh_gia`
--
ALTER TABLE `danh_gia`
  ADD PRIMARY KEY (`ma_danh_gia`),
  ADD UNIQUE KEY `uq_danh_gia_sach_nguoi_dung` (`ma_sach`,`ma_nguoi_dung`),
  ADD KEY `ix_danh_gia_nguoi_dung` (`ma_nguoi_dung`);

--
-- Indexes for table `dau_trang`
--
ALTER TABLE `dau_trang`
  ADD PRIMARY KEY (`ma_dau_trang`),
  ADD KEY `ix_dau_trang_nguoi_dung` (`ma_nguoi_dung`),
  ADD KEY `ix_dau_trang_chuong` (`ma_chuong`);

--
-- Indexes for table `don_hang`
--
ALTER TABLE `don_hang`
  ADD PRIMARY KEY (`ma_don_hang`),
  ADD UNIQUE KEY `uq_don_hang_so` (`so_don_hang`),
  ADD KEY `ix_don_hang_nguoi_dung` (`ma_nguoi_dung`,`trang_thai`),
  ADD KEY `ix_don_hang_giam_gia` (`ma_giam_gia`),
  ADD KEY `ix_don_hang_ngay` (`ngay_tao`);

--
-- Indexes for table `gio_hang`
--
ALTER TABLE `gio_hang`
  ADD PRIMARY KEY (`ma_gio_hang`),
  ADD UNIQUE KEY `uq_gio_hang_sach` (`ma_nguoi_dung`,`ma_sach`),
  ADD UNIQUE KEY `uq_gio_hang_chuong` (`ma_nguoi_dung`,`ma_chuong`),
  ADD KEY `ix_gio_hang_sach` (`ma_sach`),
  ADD KEY `ix_gio_hang_chuong` (`ma_chuong`);

--
-- Indexes for table `goi_hoi_vien`
--
ALTER TABLE `goi_hoi_vien`
  ADD PRIMARY KEY (`ma_goi`),
  ADD UNIQUE KEY `uq_goi_hoi_vien_ten` (`ten_goi`);

--
-- Indexes for table `lich_su_doc`
--
ALTER TABLE `lich_su_doc`
  ADD PRIMARY KEY (`ma_lich_su`),
  ADD UNIQUE KEY `uq_lich_su_nguoi_dung_chuong_ngay` (`ma_nguoi_dung`,`ma_chuong`,`ngay_doc`),
  ADD KEY `ix_lich_su_thoi_diem` (`ma_nguoi_dung`,`thoi_diem_doc`),
  ADD KEY `ix_lich_su_chuong` (`ma_chuong`);

--
-- Indexes for table `ma_giam_gia`
--
ALTER TABLE `ma_giam_gia`
  ADD PRIMARY KEY (`ma_giam_gia`),
  ADD UNIQUE KEY `uq_ma_giam_gia_code` (`ma_code`);

--
-- Indexes for table `nguoi_dung`
--
ALTER TABLE `nguoi_dung`
  ADD PRIMARY KEY (`ma_nguoi_dung`),
  ADD UNIQUE KEY `uq_nguoi_dung_ten_dang_nhap` (`ten_dang_nhap`),
  ADD UNIQUE KEY `uq_nguoi_dung_email` (`email`);

--
-- Indexes for table `sach`
--
ALTER TABLE `sach`
  ADD PRIMARY KEY (`ma_sach`),
  ADD UNIQUE KEY `uq_sach_duong_dan` (`duong_dan_tinh`),
  ADD KEY `ix_sach_the_loai` (`ma_the_loai`),
  ADD KEY `ix_sach_tac_gia` (`ma_tac_gia`),
  ADD KEY `ix_sach_loc` (`tinh_trang`,`dinh_dang`,`hinh_thuc_doc`),
  ADD KEY `ix_sach_ngay_dang` (`ngay_dang`),
  ADD KEY `ix_sach_luot_xem` (`luot_xem`);
ALTER TABLE `sach` ADD FULLTEXT KEY `ft_sach_ten` (`ten_sach`);

--
-- Indexes for table `tac_gia`
--
ALTER TABLE `tac_gia`
  ADD PRIMARY KEY (`ma_tac_gia`),
  ADD UNIQUE KEY `uq_tac_gia_duong_dan` (`duong_dan_tinh`),
  ADD KEY `ix_tac_gia_ten` (`ten_tac_gia`);

--
-- Indexes for table `thanh_toan`
--
ALTER TABLE `thanh_toan`
  ADD PRIMARY KEY (`ma_thanh_toan`),
  ADD UNIQUE KEY `uq_thanh_toan_ma_giao_dich` (`ma_giao_dich`),
  ADD KEY `ix_thanh_toan_don_hang` (`ma_don_hang`),
  ADD KEY `ix_thanh_toan_nguoi_duyet` (`ma_nguoi_duyet`);

--
-- Indexes for table `the_loai`
--
ALTER TABLE `the_loai`
  ADD PRIMARY KEY (`ma_the_loai`),
  ADD UNIQUE KEY `uq_the_loai_duong_dan` (`duong_dan_tinh`),
  ADD KEY `ix_the_loai_cha` (`ma_the_loai_cha`);

--
-- Indexes for table `thu_vien`
--
ALTER TABLE `thu_vien`
  ADD PRIMARY KEY (`ma_thu_vien`),
  ADD UNIQUE KEY `uq_thu_vien_nguoi_dung_sach` (`ma_nguoi_dung`,`ma_sach`),
  ADD KEY `ix_thu_vien_sach` (`ma_sach`);

--
-- Indexes for table `tien_do_doc`
--
ALTER TABLE `tien_do_doc`
  ADD PRIMARY KEY (`ma_nguoi_dung`,`ma_chuong`),
  ADD KEY `ix_tien_do_chuong` (`ma_chuong`),
  ADD KEY `ix_tien_do_moi_nhat` (`ma_nguoi_dung`,`ngay_cap_nhat`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `chi_tiet_don_hang`
--
ALTER TABLE `chi_tiet_don_hang`
  MODIFY `ma_chi_tiet` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chuong`
--
ALTER TABLE `chuong`
  MODIFY `ma_chuong` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dang_ky_hoi_vien`
--
ALTER TABLE `dang_ky_hoi_vien`
  MODIFY `ma_dang_ky` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `danh_gia`
--
ALTER TABLE `danh_gia`
  MODIFY `ma_danh_gia` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dau_trang`
--
ALTER TABLE `dau_trang`
  MODIFY `ma_dau_trang` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `don_hang`
--
ALTER TABLE `don_hang`
  MODIFY `ma_don_hang` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gio_hang`
--
ALTER TABLE `gio_hang`
  MODIFY `ma_gio_hang` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `goi_hoi_vien`
--
ALTER TABLE `goi_hoi_vien`
  MODIFY `ma_goi` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lich_su_doc`
--
ALTER TABLE `lich_su_doc`
  MODIFY `ma_lich_su` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `ma_giam_gia`
--
ALTER TABLE `ma_giam_gia`
  MODIFY `ma_giam_gia` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nguoi_dung`
--
ALTER TABLE `nguoi_dung`
  MODIFY `ma_nguoi_dung` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sach`
--
ALTER TABLE `sach`
  MODIFY `ma_sach` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tac_gia`
--
ALTER TABLE `tac_gia`
  MODIFY `ma_tac_gia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `thanh_toan`
--
ALTER TABLE `thanh_toan`
  MODIFY `ma_thanh_toan` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `the_loai`
--
ALTER TABLE `the_loai`
  MODIFY `ma_the_loai` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `thu_vien`
--
ALTER TABLE `thu_vien`
  MODIFY `ma_thu_vien` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `chi_tiet_don_hang`
--
ALTER TABLE `chi_tiet_don_hang`
  ADD CONSTRAINT `fk_ctdh_chuong` FOREIGN KEY (`ma_chuong`) REFERENCES `chuong` (`ma_chuong`),
  ADD CONSTRAINT `fk_ctdh_don_hang` FOREIGN KEY (`ma_don_hang`) REFERENCES `don_hang` (`ma_don_hang`),
  ADD CONSTRAINT `fk_ctdh_goi` FOREIGN KEY (`ma_goi`) REFERENCES `goi_hoi_vien` (`ma_goi`),
  ADD CONSTRAINT `fk_ctdh_sach` FOREIGN KEY (`ma_sach`) REFERENCES `sach` (`ma_sach`);

--
-- Constraints for table `chuong`
--
ALTER TABLE `chuong`
  ADD CONSTRAINT `fk_chuong_sach` FOREIGN KEY (`ma_sach`) REFERENCES `sach` (`ma_sach`) ON UPDATE CASCADE;

--
-- Constraints for table `dang_ky_hoi_vien`
--
ALTER TABLE `dang_ky_hoi_vien`
  ADD CONSTRAINT `fk_dkhv_don_hang` FOREIGN KEY (`ma_don_hang`) REFERENCES `don_hang` (`ma_don_hang`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dkhv_goi` FOREIGN KEY (`ma_goi`) REFERENCES `goi_hoi_vien` (`ma_goi`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dkhv_nguoi_dung` FOREIGN KEY (`ma_nguoi_dung`) REFERENCES `nguoi_dung` (`ma_nguoi_dung`) ON UPDATE CASCADE;

--
-- Constraints for table `danh_gia`
--
ALTER TABLE `danh_gia`
  ADD CONSTRAINT `fk_danh_gia_nguoi_dung` FOREIGN KEY (`ma_nguoi_dung`) REFERENCES `nguoi_dung` (`ma_nguoi_dung`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_danh_gia_sach` FOREIGN KEY (`ma_sach`) REFERENCES `sach` (`ma_sach`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `dau_trang`
--
ALTER TABLE `dau_trang`
  ADD CONSTRAINT `fk_dau_trang_chuong` FOREIGN KEY (`ma_chuong`) REFERENCES `chuong` (`ma_chuong`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dau_trang_nguoi_dung` FOREIGN KEY (`ma_nguoi_dung`) REFERENCES `nguoi_dung` (`ma_nguoi_dung`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `don_hang`
--
ALTER TABLE `don_hang`
  ADD CONSTRAINT `fk_don_hang_giam_gia` FOREIGN KEY (`ma_giam_gia`) REFERENCES `ma_giam_gia` (`ma_giam_gia`),
  ADD CONSTRAINT `fk_don_hang_nguoi_dung` FOREIGN KEY (`ma_nguoi_dung`) REFERENCES `nguoi_dung` (`ma_nguoi_dung`);

--
-- Constraints for table `gio_hang`
--
ALTER TABLE `gio_hang`
  ADD CONSTRAINT `fk_gio_hang_chuong` FOREIGN KEY (`ma_chuong`) REFERENCES `chuong` (`ma_chuong`),
  ADD CONSTRAINT `fk_gio_hang_nguoi_dung` FOREIGN KEY (`ma_nguoi_dung`) REFERENCES `nguoi_dung` (`ma_nguoi_dung`),
  ADD CONSTRAINT `fk_gio_hang_sach` FOREIGN KEY (`ma_sach`) REFERENCES `sach` (`ma_sach`);

--
-- Constraints for table `lich_su_doc`
--
ALTER TABLE `lich_su_doc`
  ADD CONSTRAINT `fk_lich_su_chuong` FOREIGN KEY (`ma_chuong`) REFERENCES `chuong` (`ma_chuong`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_lich_su_nguoi_dung` FOREIGN KEY (`ma_nguoi_dung`) REFERENCES `nguoi_dung` (`ma_nguoi_dung`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sach`
--
ALTER TABLE `sach`
  ADD CONSTRAINT `fk_sach_tac_gia` FOREIGN KEY (`ma_tac_gia`) REFERENCES `tac_gia` (`ma_tac_gia`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sach_the_loai` FOREIGN KEY (`ma_the_loai`) REFERENCES `the_loai` (`ma_the_loai`) ON UPDATE CASCADE;

--
-- Constraints for table `thanh_toan`
--
ALTER TABLE `thanh_toan`
  ADD CONSTRAINT `fk_thanh_toan_don_hang` FOREIGN KEY (`ma_don_hang`) REFERENCES `don_hang` (`ma_don_hang`),
  ADD CONSTRAINT `fk_thanh_toan_nguoi_duyet` FOREIGN KEY (`ma_nguoi_duyet`) REFERENCES `nguoi_dung` (`ma_nguoi_dung`);

--
-- Constraints for table `the_loai`
--
ALTER TABLE `the_loai`
  ADD CONSTRAINT `fk_the_loai_cha` FOREIGN KEY (`ma_the_loai_cha`) REFERENCES `the_loai` (`ma_the_loai`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `thu_vien`
--
ALTER TABLE `thu_vien`
  ADD CONSTRAINT `fk_thu_vien_nguoi_dung` FOREIGN KEY (`ma_nguoi_dung`) REFERENCES `nguoi_dung` (`ma_nguoi_dung`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_thu_vien_sach` FOREIGN KEY (`ma_sach`) REFERENCES `sach` (`ma_sach`) ON UPDATE CASCADE;

--
-- Constraints for table `tien_do_doc`
--
ALTER TABLE `tien_do_doc`
  ADD CONSTRAINT `fk_tien_do_chuong` FOREIGN KEY (`ma_chuong`) REFERENCES `chuong` (`ma_chuong`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tien_do_nguoi_dung` FOREIGN KEY (`ma_nguoi_dung`) REFERENCES `nguoi_dung` (`ma_nguoi_dung`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
