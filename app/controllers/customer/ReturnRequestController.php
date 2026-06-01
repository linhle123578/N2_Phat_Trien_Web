<?php

class ReturnRequestController
{
    private $model;
    private string $customer_id;

    public string $view_name = '';
    public array $view_data = [];

    public function __construct($conn)
    {
        $this->model       = new ReturnRequestModel($conn);
        $this->customer_id = $_SESSION['customer_id'] ?? 'CUS001';
    }

   
    // GET: Hiển thị form yêu cầu trả hàng
     
    public function showForm(): void
    {
        $order_id = trim($_GET['order_id'] ?? '');

        if (empty($order_id)) {
            $this->redirectWithError('order_history', 'Thiếu mã đơn hàng.');
            return;
        }

        // Kiểm tra điều kiện
        $eligibility = $this->model->checkEligibility($order_id, $this->customer_id);
        if (!$eligibility['eligible']) {
            if ($eligibility['reason'] === 'Đơn hàng đã có yêu cầu trả hàng đang xử lý.') {
                $return_info = $this->model->getReturnByOrderId($order_id);
                if ($return_info) {
                    $return_id = $return_info['return_id'];
                    $data = compact('return_id', 'return_info', 'order_id');
                    $this->render('return_request_success', $data);
                    return;
                }
            }
            $this->redirectWithError('OrderHistory', $eligibility['reason']);
            return;
        }

        $order_info = $this->model->getOrderInfo($order_id, $this->customer_id);
        $order_items = $this->model->getOrderItems($order_id);
        $suggest_reasons = ReturnRequestModel::SUGGEST_REASONS;

        $data = compact('order_id', 'order_info', 'order_items', 'suggest_reasons');
        $data['errors'] = [];
        $data['old']    = [];

        $this->render('return_request_form', $data);
    }

    //POST: Xử lý submit form

    public function handleSubmit(): void
    {
        $order_id = trim($_POST['order_id'] ?? '');

        // Kiểm tra lại điều kiện
        $eligibility = $this->model->checkEligibility($order_id, $this->customer_id);
        if (!$eligibility['eligible']) {
            $this->redirectWithError('OrderHistory', $eligibility['reason']);
            return;
        }

        // Validate
        $errors = [];
        $old    = $_POST;

        $reason      = trim($_POST['reason'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $return_type = trim($_POST['return_type'] ?? '');
        $bank_name   = trim($_POST['bank_name'] ?? '');
        $bank_account= trim($_POST['bank_account'] ?? '');
        $bank_holder = trim($_POST['bank_holder'] ?? '');

        if (empty($reason)) {
            $errors['reason'] = 'Vui lòng chọn lý do trả hàng.';
        }
        if (!in_array($return_type, ['Đổi hàng', 'Hoàn tiền'])) {
            $errors['return_type'] = 'Vui lòng chọn hình thức xử lý.';
        }
        // Nếu hoàn tiền → cần thông tin ngân hàng
        if ($return_type === 'Hoàn tiền') {
            if (empty($bank_name))    $errors['bank_name']    = 'Vui lòng nhập tên ngân hàng.';
            if (empty($bank_account)) $errors['bank_account'] = 'Vui lòng nhập số tài khoản.';
            if (empty($bank_holder))  $errors['bank_holder']  = 'Vui lòng nhập tên chủ tài khoản.';
        }
        if (strlen($description) > 1000) {
            $errors['description'] = 'Mô tả không được vượt quá 1000 ký tự.';
        }

        if (!empty($errors)) {
            // Re-render form với lỗi
            $order_info      = $this->model->getOrderInfo($order_id, $this->customer_id);
            $order_items     = $this->model->getOrderItems($order_id);
            $suggest_reasons = ReturnRequestModel::SUGGEST_REASONS;
            $data = compact('order_id', 'order_info', 'order_items', 'suggest_reasons', 'errors', 'old');
            $this->render('return_request_form', $data);
            return;
        }

        // Tạo return request
        $return_id = $this->model->createReturnRequest([
            'order_id'     => $order_id,
            'reason'       => $reason,
            'description'  => $description,
            'return_type'  => $return_type,
            'bank_name'    => $return_type === 'Hoàn tiền' ? $bank_name : null,
            'bank_account' => $return_type === 'Hoàn tiền' ? $bank_account : null,
            'bank_holder'  => $return_type === 'Hoàn tiền' ? $bank_holder : null,
        ]);

        if ($return_id) {
            // Thành công thì hiển thị trang xác nhận
            $return_info = $this->model->getReturnById($return_id);
            $data = compact('return_id', 'return_info', 'order_id');
            $this->render('return_request_success', $data);
            return;
        } else {
            // Lỗi DB
            $errors['_global'] = 'Đã xảy ra lỗi khi gửi yêu cầu. Vui lòng thử lại.';
            $order_info      = $this->model->getOrderInfo($order_id, $this->customer_id);
            $order_items     = $this->model->getOrderItems($order_id);
            $suggest_reasons = ReturnRequestModel::SUGGEST_REASONS;
            $data = compact('order_id', 'order_info', 'order_items', 'suggest_reasons', 'errors', 'old');
            $this->render('return_request_form', $data);
        }
    }

    // Dispatch request theo method
    public function dispatch(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSubmit();
        } else {
            $this->showForm();
        }
    }

    // Helpers

    private function render(string $view, array $data = []): void
    {
        $this->view_name = $view;
        $this->view_data = $data;
    }

    private function redirectWithError(string $page, string $msg): void
    {
        $url = $page . '.php?error=' . urlencode($msg);
        header("Location: $url");
        exit;
    }

    public static function e(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }

    public static function formatPrice(float $n): string
    {
        return number_format($n, 0, ',', '.') . ' ₫';
    }

    public static function statusLabel(string $s): string
    {
        return match($s) {
            'Đang xử lý' => 'Đang xử lý',
            'Đã hoàn tiền' => 'Đã hoàn tiền',
            'Đã đổi hàng' => 'Đã đổi hàng',
            'Đã hủy đơn' => 'Đã hủy đơn',
            'Từ chối' => 'Từ chối',
            default => $s,
        };
    }
}
