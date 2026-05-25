<?php

require_once "../models/ControlOrderModel.php";

class OrderController {

    private $model;

    public function __construct() {

        $this->model =
            new OrderModel();
    }

    public function index() {

        // ======================
        // HANDLE RETURN
        // ======================

        if (
            isset($_POST['handle_return'])
        ) {

            $this->model->handleReturn(
                $_POST['return_id'],
                $_POST['return_status']
            );

            header(
                "Location: index.php"
            );

            exit;
        }

        // ======================
        // DELETE ORDER
        // ======================

        if (
            isset($_POST['delete_order'])
        ) {

            $this->model->deleteOrder(
                $_POST['order_id']
            );

            header(
                "Location: index.php"
            );

            exit;
        }

        // ======================
        // UPDATE STATUS
        // ======================

        if (
            isset($_POST['update_status'])
        ) {

            $this->model->updateStatus(
                $_POST['order_id'],
                $_POST['status']
            );

            header(
                "Location: index.php"
            );

            exit;
        }

        // ======================
        // GET DATA
        // ======================

        $search =
            $_GET['search']
            ?? '';

        $filter =
            $_GET['filter']
            ?? '';

        $perPage = 15;

        $page = max(
            1,
            intval(
                $_GET['page']
                ?? 1
            )
        );

        $totalOrders =
            $this->model->countOrders(
                $search,
                $filter
            );

        $totalPages = max(
            1,
            ceil(
                $totalOrders
                / $perPage
            )
        );

        $page = min(
            $page,
            $totalPages
        );

        $offset =
            ($page - 1)
            * $perPage;

        $orders =
            $this->model->getOrders(
                $search,
                $filter,
                $perPage,
                $offset
            );

        $pendingReturns =
            $this->model->getPendingReturns();

        // ======================
        // MAP STATUS
        // ======================

        $statusMap = [

            'Chờ xác nhận' => [
                'label' => 'Chờ xác nhận',
                'class' => 'badge-pending'
            ],

            'Đang giao' => [
                'label' => 'Đang giao',
                'class' => 'badge-shipping'
            ],

            'Hoàn thành' => [
                'label' => 'Hoàn thành',
                'class' => 'badge-completed'
            ],

            'Đã hủy' => [
                'label' => 'Đã hủy',
                'class' => 'badge-cancel'
            ],
        ];

        $returnStatusMap = [

            'Đang xử lý' => [
                'label' => 'Đang xử lý',
                'class' => 'rs-pending'
            ],

            'Đã hoàn tiền' => [
                'label' => 'Đã hoàn tiền',
                'class' => 'rs-refunded'
            ],

            'Đã đổi hàng' => [
                'label' => 'Đã đổi hàng',
                'class' => 'rs-exchanged'
            ],

            'Đã hủy đơn' => [
                'label' => 'Đã hủy đơn',
                'class' => 'rs-cancelled'
            ],

            'Từ chối' => [
                'label' => 'Từ chối',
                'class' => 'rs-rejected'
            ],
        ];

        require "../views/orders/index.php";
    }
}
?>