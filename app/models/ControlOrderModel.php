<?php

// Xoá dấu tiếng Việt để tìm kiếm không phân biệt dấu/hoa thường
function removeAccents($str) {
    $str  = mb_strtolower($str, 'UTF-8');
    $from = ['à','á','ạ','ả','ã','â','ầ','ấ','ậ','ẩ','ẫ','ă','ằ','ắ','ặ','ẳ','ẵ',
             'è','é','ẹ','ẻ','ẽ','ê','ề','ế','ệ','ể','ễ',
             'ì','í','ị','ỉ','ĩ',
             'ò','ó','ọ','ỏ','õ','ô','ồ','ố','ộ','ổ','ỗ','ơ','ờ','ớ','ợ','ở','ỡ',
             'ù','ú','ụ','ủ','ũ','ư','ừ','ứ','ự','ử','ữ',
             'ỳ','ý','ỵ','ỷ','ỹ','đ'];
    $to   = ['a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a',
             'e','e','e','e','e','e','e','e','e','e','e',
             'i','i','i','i','i',
             'o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o',
             'u','u','u','u','u','u','u','u','u','u','u',
             'y','y','y','y','y','d'];
    return str_replace($from, $to, $str);
}

class OrderModel {

    private $conn;

    public function __construct() {

        $this->conn = mysqli_init();

        mysqli_ssl_set(
            $this->conn,
            NULL,
            NULL,
            NULL,
            NULL,
            NULL
        );

        // Bỏ qua xác thực chứng chỉ SSL
        mysqli_options($this->conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);

        mysqli_real_connect(
            $this->conn,
            "gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com",
            "3YHrkxqAKWynehu.root",
            "BzDRrZAdAT2jLuyd",
            "db_web_farm2home",
            4000,
            NULL,
            MYSQLI_CLIENT_SSL
        );

        if (!$this->conn) {

            die(
                "Kết nối thất bại: "
                . mysqli_connect_error()
            );
        }

        mysqli_set_charset(
            $this->conn,
            "utf8"
        );
    }

    // ======================
    // ĐẾM YÊU CẦU TRẢ HÀNG
    // ======================

    public function getPendingReturns() {

        $pendingReturns = 0;

        $rr = mysqli_query(
            $this->conn,
            "SELECT COUNT(*) as cnt
             FROM returnrequest
             WHERE return_status='Đang xử lý'"
        );

        if ($rr) {

            $pendingReturns =
                mysqli_fetch_assoc($rr)['cnt'];
        }

        return $pendingReturns;
    }

    // ======================
    // UPDATE STATUS
    // ======================

    public function updateStatus(
        $order_id,
        $status
    ) {

        $order_id =
            mysqli_real_escape_string(
                $this->conn,
                $order_id
            );

        $status =
            mysqli_real_escape_string(
                $this->conn,
                $status
            );

        return mysqli_query(
            $this->conn,
            "UPDATE `order`
             SET order_status='$status'
             WHERE order_id='$order_id'"
        );
    }

    // ======================
    // HANDLE RETURN
    // ======================

    public function handleReturn(
        $return_id,
        $return_status
    ) {

        $return_id =
            mysqli_real_escape_string(
                $this->conn,
                $return_id
            );

        $return_status =
            mysqli_real_escape_string(
                $this->conn,
                $return_status
            );

        return mysqli_query(
            $this->conn,
            "UPDATE returnrequest
             SET return_status='$return_status'
             WHERE return_id='$return_id'"
        );
    }

    // ======================
    // DELETE ORDER
    // ======================

    public function deleteOrder($order_id) {

        $order_id =
            mysqli_real_escape_string(
                $this->conn,
                $order_id
            );

        mysqli_query(
            $this->conn,
            "DELETE FROM returnrequest
             WHERE order_id='$order_id'"
        );

        mysqli_query(
            $this->conn,
            "DELETE FROM shipment
             WHERE order_id='$order_id'"
        );

        mysqli_query(
            $this->conn,
            "DELETE FROM orderitem
             WHERE order_id='$order_id'"
        );

        mysqli_query(
            $this->conn,
            "DELETE FROM payment
             WHERE order_id='$order_id'"
        );

        mysqli_query(
            $this->conn,
            "DELETE FROM `order`
             WHERE order_id='$order_id'"
        );
    }

    // ======================
    // COUNT ORDERS
    // ======================

    public function countOrders(
        $search,
        $filter
    ) {

        $searchRaw = $search;

        $search =
            mysqli_real_escape_string(
                $this->conn,
                $search
            );

        $filter =
            mysqli_real_escape_string(
                $this->conn,
                $filter
            );

        $where = "WHERE 1=1";

        if ($search) {

            $searchNoAccent =
                mysqli_real_escape_string(
                    $this->conn,
                    removeAccents($searchRaw)
                );

            $where .= "
            AND (
                c.full_name LIKE '%$search%'
                OR o.order_id LIKE '%$search%'
                OR c.full_name COLLATE utf8mb4_general_ci LIKE '%$searchNoAccent%'
                OR o.order_id COLLATE utf8mb4_general_ci LIKE '%$searchNoAccent%'
            )";
        }

        if ($filter === 'return') {

            $where .= "
            AND EXISTS (
                SELECT 1
                FROM returnrequest rr
                WHERE rr.order_id = o.order_id
            )";

        } elseif ($filter) {

            $where .= "
            AND o.order_status='$filter'";
        }

        $sql = "
        SELECT COUNT(*) as total

        FROM `order` o

        LEFT JOIN customer c
        ON o.customer_id = c.customer_id

        $where
        ";

        $result =
            mysqli_query(
                $this->conn,
                $sql
            );

        return mysqli_fetch_assoc(
            $result
        )['total'];
    }

    // ======================
    // GET ORDERS
    // ======================

    public function getOrders(
        $search,
        $filter,
        $perPage,
        $offset
    ) {

        $searchRaw = $search;

        $search =
            mysqli_real_escape_string(
                $this->conn,
                $search
            );

        $filter =
            mysqli_real_escape_string(
                $this->conn,
                $filter
            );

        $where = "WHERE 1=1";

        if ($search) {

            $searchNoAccent =
                mysqli_real_escape_string(
                    $this->conn,
                    removeAccents($searchRaw)
                );

            $where .= "
            AND (
                c.full_name LIKE '%$search%'
                OR o.order_id LIKE '%$search%'
                OR c.full_name COLLATE utf8mb4_general_ci LIKE '%$searchNoAccent%'
                OR o.order_id COLLATE utf8mb4_general_ci LIKE '%$searchNoAccent%'
            )";
        }

        if ($filter === 'return') {

            $where .= "
            AND EXISTS (
                SELECT 1
                FROM returnrequest rr
                WHERE rr.order_id = o.order_id
            )";

        } elseif ($filter) {

            $where .= "
            AND o.order_status='$filter'";
        }

        $sql = "
        SELECT
            o.order_id,
            c.full_name,
            o.order_status,
            o.created_at,
            p.total_amount

        FROM `order` o

        LEFT JOIN customer c
        ON o.customer_id = c.customer_id

        LEFT JOIN payment p
        ON o.order_id = p.order_id

        $where

        ORDER BY o.created_at DESC

        LIMIT $perPage OFFSET $offset
        ";

        $result =
            mysqli_query(
                $this->conn,
                $sql
            );

        $orders = [];

        while (
            $row = mysqli_fetch_assoc($result)
        ) {

            // PRODUCTS

            $dq = mysqli_query(
                $this->conn,
                "SELECT
                    pr.product_name,
                    pr.product_image,
                    oi.quantity,
                    oi.price

                 FROM orderitem oi

                 LEFT JOIN product pr
                 ON oi.product_id = pr.product_id

                 WHERE oi.order_id='"
                 . $row['order_id'] . "'"
            );

            $items = [];

            while (
                $d = mysqli_fetch_assoc($dq)
            ) {

                $items[] = $d;
            }

            $amount =
                $row['total_amount']
                ?:
                array_sum(
                    array_map(
                        fn($i)
                        =>
                        $i['price']
                        * $i['quantity'],
                        $items
                    )
                );

            // RETURN INFO

            $rq = mysqli_query(
                $this->conn,
                "SELECT
                    return_id,
                    reason,
                    return_status,
                    request_date

                 FROM returnrequest

                 WHERE order_id='"
                 . $row['order_id'] . "'

                 ORDER BY request_date DESC

                 LIMIT 1"
            );

            $returnInfo =
                $rq
                ? mysqli_fetch_assoc($rq)
                : null;

            $orders[] = [

                "id" =>
                    $row['order_id'],

                "name" =>
                    $row['full_name'],

                "status" =>
                    $row['order_status'],

                "time" =>
                    date(
                        'H:i:s d-m-Y',
                        strtotime(
                            $row['created_at']
                        )
                    ),

                "createdAt" =>
                    $row['created_at'],

                "amount" =>
                    $amount,

                "items" =>
                    $items,

                "returnInfo" =>
                    $returnInfo,
            ];
        }

        return $orders;
    }
}
?>