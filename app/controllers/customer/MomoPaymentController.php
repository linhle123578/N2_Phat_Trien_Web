<?php

require_once __DIR__ . "/../../models/OrderModel.php";

define('MOMO_LIVE_MODE',     true);
define('MOMO_PARTNER_CODE',  'MOMO_PARTNER_CODE'); // <-- Thay bằng Partner Code thật của bạn
define('MOMO_ACCESS_KEY',    'MOMO_ACCESS_KEY');   // <-- Thay bằng Access Key thật của bạn
define('MOMO_SECRET_KEY',    'MOMO_SECRET_KEY');   // <-- Thay bằng Secret Key thật của bạn
define('MOMO_ENDPOINT',      'https://test-payment.momo.vn/v2/gateway/api/create');
define('MOMO_QUERY_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/query');

class MomoPaymentController
{

    private $orderModel;

    public function __construct()
    {
        $this->orderModel = new OrderModel();
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    // GET → hiển thị trang QR
    // ----------------------------------------------------------------
    public function showPage($order_id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['customer_id'])) {
            header("Location: /app/views/customer/LogIn.php");
            exit();
        }

        // Nếu có order_id thật (đã thanh toán xong và redirect về) → hiện order đó
        if ($order_id) {
            $order = $this->orderModel->getOrderById($order_id);
            if (!$order) {
                die("<h3 style='font-family:sans-serif;color:#B91C1C;padding:40px'>Không tìm thấy đơn hàng</h3>");
            }
            $customer_id = $_SESSION['customer_id'];
            if ($order['customer_id'] !== (string)$customer_id) {
                die("<h3 style='font-family:sans-serif;color:#B91C1C;padding:40px'>Bạn không có quyền xem đơn hàng này.</h3>");
            }
            $order_products = $this->orderModel->getOrderItems($order_id);
            $subtotal = 0;
            foreach ($order_products as $p) {
                $subtotal += (int)$p['price'] * (int)$p['quantity'];
            }
            $shipping_fee = (int)($_SESSION['last_order_shipping'] ?? 25000);
            $total_amount = $subtotal + $shipping_fee;
            require_once __DIR__ . "/../../views/customer/MomoPayment.php";
            return;
        }

        // Chưa có order_id → dùng pending_order từ session để hiện trang QR
        $pending = $_SESSION['pending_order'] ?? [];
        if (empty($pending)) {
            header("Location: /app/controllers/customer/CheckoutController.php");
            exit();
        }

        // Hiện thông tin sản phẩm từ session (chưa tạo đơn thật)
        $cart        = $pending['items'] ?? ($_SESSION['checkout_items'] ?? []);
        $shipping_fee = (int)($pending['shipping_fee'] ?? 25000);
        $total_amount = (int)($pending['total_amount'] ?? 0);

        // Lấy tên/giá sản phẩm từ cart DB để hiện lên UI
        require_once __DIR__ . "/../../models/CartModel.php";
        require_once __DIR__ . "/../../models/ProductModel.php";
        $cartModel = new CartModel();
        $productModel = new ProductModel();
        $all_cart_items = $cartModel->getCartItems($_SESSION['customer_id']);
        $cart_map = [];
        foreach ($all_cart_items as $ci) {
            $cart_map[$ci['product_id']] = $ci;
        }

        $order_products = [];
        $subtotal = 0;
        foreach ($cart as $item) {
            $pid = $item['product_id'];
            $qty = (int)($item['quantity'] ?? 1);
            $ci  = $cart_map[$pid] ?? null;
            if ($ci) {
                // Sản phẩm có trong giỏ hàng DB
                $order_products[] = [
                    'product_name'  => $ci['product_name'],
                    'product_image' => $ci['product_image'],
                    'price'         => $ci['unit_price'],
                    'quantity'      => $qty,
                    'unit'          => $ci['unit'] ?? '',
                ];
                $subtotal += $ci['unit_price'] * $qty;
            } else {
                // Fallback: mua ngay (buy_now) - sản phẩm không có trong cart DB
                $prod = $productModel->getProductById($pid);
                if ($prod) {
                    $order_products[] = [
                        'product_name'  => $prod['product_name'],
                        'product_image' => $prod['product_image'],
                        'price'         => $prod['price'],
                        'quantity'      => $qty,
                        'unit'          => $prod['unit'] ?? '',
                    ];
                    $subtotal += $prod['price'] * $qty;
                }
            }
        }

        // order_id = null ở đây, JS sẽ gọi action=create_qr_pending
        $order_id = null;

        require_once __DIR__ . "/../../views/customer/MomoPayment.php";
    }

    // Tạo đơn từ pending_order trong session (chỉ gọi khi MoMo xác nhận paid)
    private function createOrderFromSession()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $pending     = $_SESSION['pending_order']  ?? [];
        $cart        = $pending['items'] ?? ($_SESSION['checkout_items'] ?? []);
        $customer_id = $_SESSION['customer_id']    ?? null;

        if (!$pending || !$cart || !$customer_id) return null;

        require_once __DIR__ . "/../../models/OrderDetailModel.php";
        require_once __DIR__ . "/../../models/CartModel.php";

        $orderModel       = new OrderModel();
        $cartModel        = new CartModel();
        $orderDetailModel = new OrderDetailModel();

        $order_id = $orderModel->createOrder(
            $customer_id,
            $pending['address_id'] ?? null,
            $pending['shipment_id'] ?? 'SHP001',
            $pending['total_amount'],
            'momo'
        );
        if (!$order_id) return null;

        // Trạng thái sau thanh toán MoMo thành công → Chờ xác nhận
        $orderModel->updateStatus($order_id, 'Chờ xác nhận');

        $all_cart_items = $cartModel->getCartItems($customer_id);
        $price_map        = [];
        $cart_item_id_map = [];
        foreach ($all_cart_items as $ci) {
            $price_map[$ci['product_id']]        = $ci['unit_price'];
            $cart_item_id_map[$ci['product_id']] = $ci['cart_item_id'];
        }

        require_once __DIR__ . "/../../models/ProductModel.php";
        $productModel = new ProductModel();

        foreach ($cart as $item) {
            $pid   = $item['product_id'];
            $qty   = (int)($item['quantity'] ?? 1);

            // Lấy giá: ưu tiên cart DB, fallback sang ProductModel (cho trường hợp buy_now)
            if (isset($price_map[$pid])) {
                $price = $price_map[$pid];
            } else {
                $prod  = $productModel->getProductById($pid);
                $price = $prod ? $prod['price'] : 0;
            }

            // Lưu order detail
            $orderDetailModel->addDetail($order_id, $pid, $price, $qty);

            // Trừ tồn kho
            $orderModel->decreaseStock($pid, $qty);

            // Xóa khỏi cart DB (chỉ xóa nếu có trong cart, buy_now không có)
            $cid = $cart_item_id_map[$pid] ?? null;
            if ($cid) $cartModel->deleteItem($cid);
        }

        $_SESSION['last_order_id']       = $order_id;
        $_SESSION['last_order_shipping'] = $pending['shipping_fee'];
        unset($_SESSION['pending_order']);
        unset($_SESSION['checkout_items']);
        unset($_SESSION['checkout_info']);

        return $order_id;
    }

    // POST action=create_qr
    // ----------------------------------------------------------------
    public function createQR($order_id)
    {
        header('Content-Type: application/json');
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Nếu chưa có order_id (pending), lấy amount từ session
        if (!$order_id) {
            $pending = $_SESSION['pending_order'] ?? [];
            if (empty($pending)) {
                echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy thông tin đơn hàng']);
                return;
            }
            $amount     = (int)($pending['total_amount'] ?? 0);
            $ref_id     = 'PENDING-' . $_SESSION['customer_id'] . '-' . time();
            $_SESSION['momo_pending_ref'] = $ref_id;

            if (MOMO_LIVE_MODE) {
                $result = $this->callMomoAPI($ref_id, $amount);
            } else {
                $result = $this->mockQR($ref_id, $amount);
            }
            echo json_encode($result);
            return;
        }

        // Có order_id → đơn đã tồn tại (user quay lại trang)
        $order = $this->orderModel->getOrderById($order_id);
        if (!$order) {
            echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy đơn hàng']);
            return;
        }

        if (in_array($order['order_status'], ['Chờ xác nhận', 'Đang giao', 'Hoàn thành'])) {
            echo json_encode(['status' => 'already_paid', 'order_id' => $order_id]);
            return;
        }

        $items = $this->orderModel->getOrderItems($order_id);
        $subtotal = 0;
        foreach ($items as $p) {
            $subtotal += (int)$p['price'] * (int)$p['quantity'];
        }
        $shipping_fee = (int)($_SESSION['last_order_shipping'] ?? 25000);
        $amount = $subtotal + $shipping_fee;

        if (MOMO_LIVE_MODE) {
            $result = $this->callMomoAPI($order_id, $amount);
        } else {
            $result = $this->mockQR($order_id, $amount);
        }

        echo json_encode($result);
    }

    // POST action=check_status
    // ----------------------------------------------------------------
    public function checkStatus($order_id)
    {
        header('Content-Type: application/json');
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Nếu chưa có order_id → check trạng thái pending qua MoMo ref
        if (!$order_id) {
            if (!MOMO_LIVE_MODE) {
                // Demo mode: chưa paid
                echo json_encode(['status' => 'ok', 'paid' => false, 'order_id' => null]);
                return;
            }
            $ref_id      = $_SESSION['momo_pending_ref'] ?? '';
            $momo_result = $ref_id ? $this->queryMomoStatus($ref_id) : null;
            if ($momo_result && isset($momo_result['resultCode']) && $momo_result['resultCode'] == 0) {
                $new_order_id = $this->createOrderFromSession();
                if ($new_order_id) {
                    $this->orderModel->updateMomoPayment($new_order_id, 'Chờ xác nhận', $momo_result['transId'] ?? null);
                    echo json_encode(['status' => 'ok', 'paid' => true, 'order_id' => $new_order_id]);
                    return;
                }
            }
            echo json_encode(['status' => 'ok', 'paid' => false, 'order_id' => null]);
            return;
        }

        // Có order_id → đơn đã tồn tại
        $order = $this->orderModel->getOrderById($order_id);
        if (!$order) {
            echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy đơn hàng']);
            return;
        }

        $order_status = $order['order_status'] ?? 'Chờ xác nhận';
        $paid = in_array($order_status, ['Chờ xác nhận', 'Đang giao', 'Hoàn thành']);

        if (!$paid && MOMO_LIVE_MODE) {
            $momo_result = $this->queryMomoStatus($order_id);
            if ($momo_result && isset($momo_result['resultCode']) && $momo_result['resultCode'] == 0) {
                $this->orderModel->updateMomoPayment($order_id, 'Chờ xác nhận', $momo_result['transId'] ?? null);
                $order_status = 'Chờ xác nhận';
                $paid = true;
            }
        }

        echo json_encode([
            'status'       => 'ok',
            'order_status' => $order_status,
            'paid'         => $paid,
            'order_id'     => $order_id,
        ]);
    }

    // POST action=mock_confirm (DEMO only)
    // ----------------------------------------------------------------
    public function mockConfirm($order_id)
    {
        header('Content-Type: application/json');
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (MOMO_LIVE_MODE) {
            echo json_encode(['status' => 'error', 'message' => 'Không khả dụng ở chế độ live']);
            return;
        }

        // Tạo đơn thật từ pending_order
        $new_order_id = $this->createOrderFromSession();
        if (!$new_order_id) {
            echo json_encode(['status' => 'error', 'message' => 'Lỗi tạo đơn hàng']);
            return;
        }

        $this->orderModel->updateMomoPayment($new_order_id, 'Chờ xác nhận', 'MOCK-TXN-' . strtoupper(substr(uniqid(), -6)));
        echo json_encode(['status' => 'success', 'order_id' => $new_order_id, 'message' => 'Mock: thanh toán thành công!']);
    }

    // POST action=ipn
    // ----------------------------------------------------------------
    public function handleIPN()
    {
        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (!$data) {
            http_response_code(400);
            echo json_encode(['message' => 'Invalid payload']);
            return;
        }

        if (MOMO_LIVE_MODE && !$this->verifyIPNSignature($data)) {
            http_response_code(403);
            echo json_encode(['message' => 'Invalid signature']);
            return;
        }

        $raw_momo_order_id = $data['orderId'] ?? '';
        $parts = explode('_', $raw_momo_order_id);
        $order_id = $parts[0]; // Chặt đuôi timestamp, lấy lại "ORD-D6B631F3"

        $result_code = (int)($data['resultCode'] ?? -1);
        $trans_id    = $data['transId']    ?? null;

        if ($result_code === 0) {
            $this->orderModel->updateMomoPayment($order_id, 'Đang giao', $trans_id);
            $this->clearCart($order_id);
        } else {
            $this->orderModel->updateStatus($order_id, 'Đã Hủy');
        }

        http_response_code(200);
        echo json_encode(['message' => 'Received']);
    }

    // ================================================================
    // PRIVATE helpers
    // ================================================================
    private function callMomoAPI($order_id, $amount)
    {
        $partnerCode = MOMO_PARTNER_CODE;
        $accessKey   = MOMO_ACCESS_KEY;
        $secretKey   = MOMO_SECRET_KEY;

        // Dùng uniqid() để đảm bảo requestId không bao giờ bị trùng dù bạn có spam F5
        $requestId   = $partnerCode . '_' . uniqid();

        // Tạo order_id gắn timestamp lách luật trùng mã
        $momo_order_id = $order_id . '_' . time();
        $_SESSION['momo_order_id_' . $order_id] = $momo_order_id;

        $orderInfo   = 'Farm2Home - Don hang ' . $order_id;
        $redirectUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']
            . '/app/controllers/customer/MomoPaymentController.php?order_id=' . urlencode($order_id);
        $ipnUrl      = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']
            . '/app/controllers/customer/MomoPaymentController.php?action=ipn';
        $extraData   = base64_encode(json_encode(['order_id' => $order_id]));
        $requestType = 'captureWallet';

        $rawHash = "accessKey=$accessKey"
            . "&amount=$amount"
            . "&extraData=$extraData"
            . "&ipnUrl=$ipnUrl"
            . "&orderId=$momo_order_id"
            . "&orderInfo=$orderInfo"
            . "&partnerCode=$partnerCode"
            . "&redirectUrl=$redirectUrl"
            . "&requestId=$requestId"
            . "&requestType=$requestType";

        $signature = hash_hmac('sha256', $rawHash, $secretKey);

        $payload = json_encode([
            'partnerCode'  => $partnerCode,
            'accessKey'    => $accessKey,
            'requestId'    => $requestId,
            'amount'       => (int)$amount,
            'orderId'      => $momo_order_id,
            'orderInfo'    => $orderInfo,
            'redirectUrl'  => $redirectUrl,
            'ipnUrl'       => $ipnUrl,
            'extraData'    => $extraData,
            'requestType'  => $requestType,
            'signature'    => $signature,
            'lang'         => 'vi',
            'orderGroupId' => '',
        ]);

        $ch = curl_init(MOMO_ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $response  = curl_exec($ch);
        $curl_err  = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$response) {
            return ['status' => 'error', 'message' => 'cURL Error: ' . $curl_err];
        }
        if ($http_code !== 200) {
            return ['status' => 'error', 'message' => 'HTTP ' . $http_code . ' | MoMo said: ' . $response];
        }

        $result = json_decode($response, true);

        if (isset($result['resultCode']) && $result['resultCode'] == 0) {
            return [
                'status'   => 'success',
                'qr_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($result['payUrl'] ?? ''),
                'deeplink' => $result['deeplink']   ?? '',
                'pay_url'  => $result['payUrl']     ?? '',
                'order_id' => $order_id,
            ];
        }

        $errorMsg = $result['localMessage'] ?? $result['message'] ?? '';
        if (empty($errorMsg)) {
            $errorMsg = 'Lỗi ngầm từ MoMo (Mã lỗi: ' . ($result['resultCode'] ?? 'Không rõ') . ')';
        }

        return [
            'status'  => 'error',
            'message' => 'MoMo Error: ' . $errorMsg,
            'code'    => $result['resultCode'] ?? -1,
        ];
    }

    private function queryMomoStatus($order_id)
    {
        $partnerCode = MOMO_PARTNER_CODE;
        $accessKey   = MOMO_ACCESS_KEY;
        $secretKey   = MOMO_SECRET_KEY;
        $requestId   = $partnerCode . time();

        $momo_order_id = $_SESSION['momo_order_id_' . $order_id] ?? $order_id;

        // SỬA $order_id thành $momo_order_id ở rawHash
        $rawHash   = "accessKey=$accessKey&orderId=$momo_order_id&partnerCode=$partnerCode&requestId=$requestId";
        $signature = hash_hmac('sha256', $rawHash, $secretKey);

        // SỬA $order_id thành $momo_order_id ở payload
        $payload = json_encode([
            'partnerCode' => $partnerCode,
            'accessKey'   => $accessKey,
            'requestId'   => $requestId,
            'orderId'     => $momo_order_id,
            'signature'   => $signature,
            'lang'        => 'vi',
        ]);

        $ch = curl_init(MOMO_QUERY_ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 10,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response ? json_decode($response, true) : null;
    }

    private function mockQR($order_id, $amount)
    {
        $qr_content = "MOMO|{$order_id}|{$amount}|Farm2Home thanh toan don hang {$order_id}";
        $qr_url     = 'https://api.qrserver.com/v1/create-qr-code/?size=224x224&data=' . urlencode($qr_content);

        return [
            'status'    => 'success',
            'qr_url'    => $qr_url,
            'deeplink'  => '',
            'pay_url'   => '',
            'order_id'  => $order_id,
            'demo_mode' => true,
            'amount'    => number_format($amount, 0, ',', '.'),
        ];
    }

    private function verifyIPNSignature(array $data)
    {
        $secretKey = MOMO_SECRET_KEY;
        $accessKey = MOMO_ACCESS_KEY;

        $rawHash = "accessKey=$accessKey"
            . "&amount={$data['amount']}"
            . "&extraData={$data['extraData']}"
            . "&message={$data['message']}"
            . "&orderId={$data['orderId']}"
            . "&orderInfo={$data['orderInfo']}"
            . "&orderType={$data['orderType']}"
            . "&partnerCode={$data['partnerCode']}"
            . "&payType={$data['payType']}"
            . "&requestId={$data['requestId']}"
            . "&responseTime={$data['responseTime']}"
            . "&resultCode={$data['resultCode']}"
            . "&transId={$data['transId']}";

        $expected = hash_hmac('sha256', $rawHash, $secretKey);
        return hash_equals($expected, $data['signature'] ?? '');
    }

    private function clearCart($order_id)
    {
        $order = $this->orderModel->getOrderById($order_id);
        if (!$order) return;

        require_once __DIR__ . "/../../models/CartModel.php";
        $cartModel = new CartModel();

        $order_items = $this->orderModel->getOrderItems($order_id);
        $cart_items = $cartModel->getCartItems($order['customer_id']);

        // Xóa các sản phẩm đã thanh toán khỏi Database giỏ hàng
        if (!empty($cart_items) && !empty($order_items)) {
            foreach ($order_items as $oi) {
                foreach ($cart_items as $ci) {
                    if ($ci['product_id'] == $oi['product_id']) {
                        $cartModel->deleteItem($ci['cart_item_id']);
                    }
                }
            }
        }

        // Xóa Session
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (isset($_SESSION['checkout_items'])) {
            unset($_SESSION['checkout_items']);
        }
    }
}

// ROUTER
// ================================================================
$action   = $_GET['action']   ?? ($_POST['action']   ?? '');
$order_id = $_GET['order_id'] ?? ($_POST['order_id'] ?? '');

$ctrl = new MomoPaymentController();
$json_data = json_decode(file_get_contents('php://input'), true);
if (is_array($json_data)) {
    $_POST = array_merge($_POST, $json_data);
}

$ctrl = new MomoPaymentController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'create_qr':
            $ctrl->createQR($order_id);
            break;
        case 'check_status':
            $ctrl->checkStatus($order_id);
            break;
        case 'mock_confirm':
            $ctrl->mockConfirm($order_id);
            break;
        case 'ipn':
            $ctrl->handleIPN();
            break;
        default:
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Unknown action: ' . $action]);
    }
} else {
    // GET: order_id có thể rỗng (pending flow) hoặc có giá trị (đã tạo đơn)
    $ctrl->showPage($order_id ?: null);
}