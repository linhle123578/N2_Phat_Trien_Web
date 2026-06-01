<?php
require_once __DIR__ . "/../../models/DashboardModel.php";

class DashboardController {
    
    public function index() {
        // Khởi động session
        if (session_status() === PHP_SESSION_NONE) session_start();

        $model = new DashboardModel();

        // Xử lý bộ lọc: Mặc định lấy từ 6 tháng trước đến tháng hiện tại
        $start_month = $_GET['start_month'] ?? date('Y-m', strtotime('-5 month'));
        $end_month = $_GET['end_month'] ?? date('Y-m');

        $overview = $model->getOverviewStats();
        $analytics = $model->getFilteredAnalytics($start_month, $end_month);

        require_once __DIR__ . "/../../views/admin/Dashboard.php";
    }
}

$controller = new DashboardController();
$controller->index();
