<?php
require_once __DIR__ . "/../../models/admin/DashboardModel.php";

class DashboardController {
    
    public function index() {
        // Khởi động session nếu hệ thống yêu cầu kiểm tra quyền Admin
        if (session_status() === PHP_SESSION_NONE) session_start();

        $model = new DashboardModel();

        // Xử lý bộ lọc: Mặc định lấy từ 6 tháng trước đến tháng hiện tại nếu không chọn
        $start_month = $_GET['start_month'] ?? date('Y-m', strtotime('-5 month'));
        $end_month = $_GET['end_month'] ?? date('Y-m');

        // Thực hiện lấy dữ liệu từ tầng dữ liệu (Model)
        $overview = $model->getOverviewStats();
        $analytics = $model->getFilteredAnalytics($start_month, $end_month);

        // Gọi View kết xuất giao diện đồ họa cho người dùng
        require_once __DIR__ . "/../../views/admin/Dashboard.php";
    }
}

// Khởi tạo và chạy Controller
$controller = new DashboardController();
$controller->index();