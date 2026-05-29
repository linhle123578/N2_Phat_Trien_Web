<?php
class DashboardModel {
    private $conn;

    public function __construct() {
        // Khởi tạo kết nối đồng bộ theo cấu trúc TiDB Cloud của CartModel
        $this->conn = mysqli_init();
        mysqli_ssl_set($this->conn, NULL, NULL, NULL, NULL, NULL);
        mysqli_options($this->conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
        
        $success = mysqli_real_connect(
            $this->conn,
            "gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com",
            "3YHrkxqAKWynehu.root",
            "BzDRrZAdAT2jLuyd",
            "db_web_farm2home",
            4000,
            NULL,
            MYSQLI_CLIENT_SSL
        );

        if (!$success) {
            die("Kết nối database thất bại tại DashboardModel: " . mysqli_connect_error());
        }
        mysqli_set_charset($this->conn, "utf8mb4");
    }

    // 1. LẤY SỐ LIỆU TỔNG QUAN (3 THẺ HIGHLIGHT)
    public function getOverviewStats() {
        $stats = [
            'revenue_current' => 0, 'revenue_growth' => 0,
            'orders_total' => 0, 'orders_shipping' => 0, 'orders_completed' => 0,
            'customers_total' => 0, 'customers_new' => 0
        ];

        // Lấy doanh thu tháng này vs tháng trước (Chỉ tính đơn 'completed')
        $revQuery = "
            SELECT 
                SUM(CASE WHEN MONTH(o.created_at) = MONTH(CURRENT_DATE()) AND YEAR(o.created_at) = YEAR(CURRENT_DATE()) THEN p.total_amount ELSE 0 END) AS current_month,
                SUM(CASE WHEN MONTH(o.created_at) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) AND YEAR(o.created_at) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) THEN p.total_amount ELSE 0 END) AS last_month
<<<<<<< HEAD
            FROM `order` o JOIN payment p ON o.order_id = p.order_id WHERE o.order_status = 'completed'";
=======
            FROM `order` o 
            LEFT JOIN payment p ON o.order_id = p.order_id 
            WHERE o.order_status = 'completed'";
>>>>>>> b0de28287d8381b6f88c230b9818ee9e6a08010f
        $revRes = $this->conn->query($revQuery);
        if ($revRes && $row = $revRes->fetch_assoc()) {
            $stats['revenue_current'] = (float)$row['current_month'];
            $last_month = (float)$row['last_month'];
            if ($last_month > 0) {
                $stats['revenue_growth'] = (($stats['revenue_current'] - $last_month) / $last_month) * 100;
            } else {
                $stats['revenue_growth'] = $stats['revenue_current'] > 0 ? 100 : 0;
            }
        }

        // Lấy dữ liệu Đơn hàng
        $orderQuery = "
            SELECT 
                COUNT(order_id) as total,
                SUM(CASE WHEN order_status IN ('pending', 'shipping') THEN 1 ELSE 0 END) as shipping,
                SUM(CASE WHEN order_status = 'completed' THEN 1 ELSE 0 END) as completed
            FROM `order`";
        $orderRes = $this->conn->query($orderQuery);
        if ($orderRes && $row = $orderRes->fetch_assoc()) {
            $stats['orders_total'] = (int)$row['total'];
            $stats['orders_shipping'] = (int)$row['shipping'];
            $stats['orders_completed'] = (int)$row['completed'];
        }

        // Lấy dữ liệu Khách hàng (bảng customer không có ngày đăng ký, ta lấy tài khoản liên kết từ bảng account)
        $custQuery = "
            SELECT 
                (SELECT COUNT(*) FROM customer) as total,
                (SELECT COUNT(*) FROM account WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())) as new_cust";
        $custRes = $this->conn->query($custQuery);
        if ($custRes && $row = $custRes->fetch_assoc()) {
            $stats['customers_total'] = (int)$row['total'];
            $stats['customers_new'] = (int)$row['new_cust'];
        }

        return $stats;
    }

    // 2. LẤY DỮ LIỆU ĐƯỢC LỌC THEO THỜI GIAN (XU HƯỚNG, DANH MỤC, TOP SẢN PHẨM)
    public function getFilteredAnalytics($startMonth, $endMonth) {
        // Chuẩn hóa chuỗi ngày tháng để so sánh trong MySQL
        $startDate = $this->conn->real_escape_string($startMonth . "-01 00:00:00");
        $endDate = $this->conn->real_escape_string($endMonth . "-31 23:59:59");

        $analytics = [
            'trends' => ['months' => [], 'revenues' => [], 'order_counts' => []],
            'categories' => ['names' => [], 'percentages' => []],
            'top_products' => []
        ];

        // 2.1 Xu hướng kinh doanh (Đường đôi)
        $trendSql = "
            SELECT DATE_FORMAT(o.created_at, '%m/%Y') as month_label, SUM(p.total_amount) as total_rev, COUNT(o.order_id) as total_ord 
            FROM `order` o 
<<<<<<< HEAD
            JOIN payment p ON o.order_id = p.order_id
=======
            LEFT JOIN payment p ON o.order_id = p.order_id 
>>>>>>> b0de28287d8381b6f88c230b9818ee9e6a08010f
            WHERE o.order_status = 'completed' AND o.created_at BETWEEN '$startDate' AND '$endDate'
            GROUP BY DATE_FORMAT(o.created_at, '%m/%Y') ORDER BY MIN(o.created_at) ASC";
        $trendRes = $this->conn->query($trendSql);
        while ($trendRes && $row = $trendRes->fetch_assoc()) {
            $analytics['trends']['months'][] = $row['month_label'];
            $analytics['trends']['revenues'][] = (float)$row['total_rev'];
            $analytics['trends']['order_counts'][] = (int)$row['total_ord'];
        }

        // 2.2 Tỷ trọng doanh thu theo danh mục (Biểu đồ tròn)
        $catSql = "
            SELECT c.name as cat_name, SUM(oi.quantity * oi.price) as cat_revenue
            FROM orderitem oi
            JOIN `order` o ON oi.order_id = o.order_id
            JOIN product p ON oi.product_id = p.product_id
            JOIN category c ON p.category_id = c.category_id
            WHERE o.order_status = 'completed' AND o.created_at BETWEEN '$startDate' AND '$endDate'
            GROUP BY c.category_id";
        $catRes = $this->conn->query($catSql);
        $totalCatRevenue = 0;
        $tempCats = [];
        while ($catRes && $row = $catRes->fetch_assoc()) {
            $totalCatRevenue += (float)$row['cat_revenue'];
            $tempCats[] = ['name' => $row['cat_name'], 'revenue' => (float)$row['cat_revenue']];
        }
        foreach ($tempCats as $cat) {
            $analytics['categories']['names'][] = $cat['name'];
            $analytics['categories']['percentages'][] = $totalCatRevenue > 0 ? round(($cat['revenue'] / $totalCatRevenue) * 100, 1) : 0;
        }

        // 2.3 Top 3 sản phẩm bán chạy nhất
        $topSql = "
            SELECT p.product_name, p.product_image, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as total_revenue
            FROM orderitem oi
            JOIN `order` o ON oi.order_id = o.order_id
            JOIN product p ON oi.product_id = p.product_id
            WHERE o.order_status = 'completed' AND o.created_at BETWEEN '$startDate' AND '$endDate'
            GROUP BY p.product_id ORDER BY total_sold DESC LIMIT 3";
        $topRes = $this->conn->query($topSql);
        while ($topRes && $row = $topRes->fetch_assoc()) {
            $analytics['top_products'][] = $row;
        }

        return $analytics;
    }
}
<<<<<<< HEAD
=======
?>
>>>>>>> b0de28287d8381b6f88c230b9818ee9e6a08010f
