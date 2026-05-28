<?php
//session_start();
ob_start();
/*
=========================================================
 FARM2HOME SMART AI CHATBOT
=========================================================
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['farm2home_chatbot'])) {

    // Xóa toàn bộ output cũ để tránh lỗi JSON
    if (ob_get_length()) ob_clean();
    
    header('Content-Type: application/json; charset=utf-8');

    error_reporting(0);
    ini_set('display_errors', 0);

    $message = trim($_POST['message'] ?? '');
    $msg = mb_strtolower($message, 'UTF-8');

    /* =========================================================
       CONNECT DATABASE
    ========================================================= */

    $conn = mysqli_init();

    mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

    $connected = mysqli_real_connect(
        $conn,
        "gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com",
        "3YHrkxqAKWynehu.root",
        "BzDRrZAdAT2jLuyd",
        "db_web_farm2home",
        4000,
        NULL,
        MYSQLI_CLIENT_SSL
    );

    if (!$connected) {

        echo json_encode([
            "reply" => "Không thể kết nối hệ thống AI."
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    mysqli_set_charset($conn, "utf8mb4");

    $customer_id = $_SESSION['customer_id'] ?? null;

    $customer_id_safe = $customer_id
        ? mysqli_real_escape_string($conn, $customer_id)
        : null;

    /* =========================================================
       FUNCTION REPLY
    ========================================================= */

    function reply($text)
    {
        echo json_encode([
            "reply" => $text
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    function contactAdminButton()
    {
        return "
        <br><br>

        <button onclick='showContactAdmin()'
        style='
            background:#1e3a2f;
            color:white;
            border:none;
            padding:10px 16px;
            border-radius:10px;
            cursor:pointer;
            font-weight:bold;
        '>
            Liên hệ quản lý website
        </button>

        <div id='contactAdminBox'
        style='display:none;margin-top:12px;'>

            Hotline: 1900 6868 <br>
            Email: cskh@farm2home.vn <br>
            08:00 - 22:00 mỗi ngày

        </div>
        ";
    }

    /* =========================================================
       HELLO
    ========================================================= */

    if (
        str_contains($msg, 'xin chào') ||
        str_contains($msg, 'hello') ||
        str_contains($msg, 'hi')
    ) {

        reply("
            Xin chào, mình là AI Farm2Home.<br><br>

            Mình có thể hỗ trợ:<br>

            • Sản phẩm bán chạy<br>
            • Giá sản phẩm<br>
            • Kiểm tra tồn kho<br>
            • Đơn hàng của tôi<br>
            • Theo dõi vận chuyển<br>
            • Đổi trả hàng<br>
            • Thanh toán<br>
            • Gợi ý sản phẩm
        ");
    }

    /* =========================================================
       ĐƠN HÀNG CỦA TÔI
    ========================================================= */

    if (
        str_contains($msg, 'đơn hàng') ||
        str_contains($msg, 'đơn hàng của tôi') ||
        str_contains($msg, 'đơn gần nhất')
    ) {

        if (!$customer_id) {

            reply("Bạn cần đăng nhập để xem đơn hàng.");
        }

        $sql = "
            SELECT *
            FROM `order`
            WHERE customer_id='$customer_id_safe'
            ORDER BY created_at DESC
            LIMIT 1
        ";

        $rs = mysqli_query($conn, $sql);

        if (!$rs) {

            reply("SQL ERROR: " . mysqli_error($conn));
        }

        if (mysqli_num_rows($rs) <= 0) {

            reply("Bạn chưa có đơn hàng.");
        }

        $o = mysqli_fetch_assoc($rs);

        reply("
            Đơn hàng gần nhất:<br><br>

            Mã đơn: <b>{$o['order_id']}</b><br>
            Trạng thái: <b>{$o['order_status']}</b><br>
            Tổng sản phẩm: {$o['total_quantity_order']}<br>
            Ngày đặt: {$o['created_at']}
        ");
    }

    /* =========================================================
       VẬN CHUYỂN
    ========================================================= */

    if (
        str_contains($msg, 'vận chuyển') ||
        str_contains($msg, 'đang giao')
    ) {

        if (!$customer_id) {

            reply("Bạn cần đăng nhập.");
        }

        $sql = "
            SELECT
                o.order_id,
                s.shipment_status,
                s.estimated_date
            FROM `order` o
            JOIN shipment s
            ON o.order_id = s.order_id
            WHERE o.customer_id='$customer_id_safe'
            ORDER BY o.created_at DESC
            LIMIT 1
        ";

        $rs = mysqli_query($conn, $sql);

        if (!$rs) {

            reply("SQL ERROR: " . mysqli_error($conn));
        }

        if (mysqli_num_rows($rs) <= 0) {

            reply("Không có thông tin vận chuyển.");
        }

        $s = mysqli_fetch_assoc($rs);

        reply("
            Thông tin vận chuyển:<br><br>

            Mã đơn: {$s['order_id']}<br>
            Trạng thái: <b>{$s['shipment_status']}</b><br>
            Dự kiến giao: {$s['estimated_date']}
        ");
    }

    /* =========================================================
       SẢN PHẨM BÁN CHẠY
    ========================================================= */

    if (
        str_contains($msg, 'bán chạy') ||
        str_contains($msg, 'best seller') ||
        str_contains($msg, 'hot')
    ) {

        $sql = "
            SELECT
                p.product_name,
                p.price,
                p.unit,
                p.product_description,
                SUM(oi.quantity) total_sold
            FROM orderitem oi
            JOIN product p
            ON oi.product_id = p.product_id
            GROUP BY p.product_id
            ORDER BY total_sold DESC
            LIMIT 5
        ";

        $rs = mysqli_query($conn, $sql);

        if (!$rs) {

            reply("SQL ERROR: " . mysqli_error($conn));
        }

        if (mysqli_num_rows($rs) <= 0) {

            reply("Chưa có dữ liệu bán hàng.");
        }

        $html = "Top sản phẩm bán chạy:<br><br>";

        while ($p = mysqli_fetch_assoc($rs)) {

            $html .= "
                <div style='
                    margin-bottom:15px;
                    padding-bottom:12px;
                    border-bottom:1px solid #eee;
                '>

                    <b>{$p['product_name']}</b><br>

                    Đã bán: {$p['total_sold']} sản phẩm<br>

                    " . number_format($p['price']) . "đ / {$p['unit']}<br>

                    {$p['product_description']}

                </div>
            ";
        }

        reply($html);
    }

    /* =========================================================
       GỢI Ý SẢN PHẨM
    ========================================================= */

    if (
        str_contains($msg, 'gợi ý') ||
        str_contains($msg, 'nên mua')
    ) {

        $sql = "
            SELECT *
            FROM product
            ORDER BY RAND()
            LIMIT 4
        ";

        $rs = mysqli_query($conn, $sql);

        if (!$rs) {

            reply("SQL ERROR: " . mysqli_error($conn));
        }

        $html = "AI đề xuất cho bạn:<br><br>";

        while ($p = mysqli_fetch_assoc($rs)) {

            $html .= "
                <div style='
                    margin-bottom:14px;
                    padding-bottom:12px;
                    border-bottom:1px solid #eee;
                '>

                    <b>{$p['product_name']}</b><br>

                    " . number_format($p['price']) . "đ<br>

                    Tồn kho: {$p['stock']}<br>

                    {$p['product_description']}

                </div>
            ";
        }

        reply($html);
    }

    /* =========================================================
       TÌM GIÁ
    ========================================================= */

    if (
        str_contains($msg, 'giá') ||
        str_contains($msg, 'bao nhiêu')
    ) {

        $sql = "SELECT * FROM product";

        $rs = mysqli_query($conn, $sql);

        while ($p = mysqli_fetch_assoc($rs)) {

            $name = mb_strtolower($p['product_name'], 'UTF-8');

            if (str_contains($msg, $name)) {

                reply("
                    {$p['product_name']}<br><br>

                    Giá:
                    <b>" . number_format($p['price']) . "đ</b><br>

                    Đơn vị:
                    {$p['unit']}
                ");
            }
        }
    }

    /* =========================================================
       TỒN KHO
    ========================================================= */

    if (
        str_contains($msg, 'còn hàng') ||
        str_contains($msg, 'tồn kho') ||
        str_contains($msg, 'hết hàng')
    ) {

        $sql = "SELECT * FROM product";

        $rs = mysqli_query($conn, $sql);

        while ($p = mysqli_fetch_assoc($rs)) {

            $name = mb_strtolower($p['product_name'], 'UTF-8');

            if (str_contains($msg, $name)) {

                if ($p['stock'] > 0) {

                    reply("
                        {$p['product_name']} còn hàng.<br><br>

                        Tồn kho:
                        {$p['stock']}
                    ");

                } else {

                    reply("
                        {$p['product_name']} đã hết hàng.
                    ");
                }
            }
        }
    }

    /* =========================================================
       FALLBACK
    ========================================================= */

    reply("
        Xin lỗi, AI chưa hiểu yêu cầu này.<br><br>

        Bạn có thể hỏi:<br>

        • sản phẩm bán chạy<br>
        • đơn hàng của tôi<br>
        • vận chuyển<br>
        • giá sản phẩm<br>
        • gợi ý sản phẩm<br><br>

        " . contactAdminButton()
    );

    mysqli_close($conn);

    exit;
}
?>

<style>

#farm2home-chat-btn{
    position:fixed;
    right:25px;
    bottom:25px;
    width:68px;
    height:68px;
    border:none;
    border-radius:50%;
    background:#1e3a2f;
    color:#fff;
    font-size:28px;
    cursor:pointer;
    z-index:999999;
    box-shadow:0 12px 30px rgba(0,0,0,.2);
}

#farm2home-chatbox{
    position:fixed;
    right:25px;
    bottom:105px;
    width:380px;
    height:600px;
    background:#fff;
    border-radius:24px;
    overflow:hidden;
    display:none;
    flex-direction:column;
    z-index:999999;
    box-shadow:0 18px 45px rgba(0,0,0,.18);
    font-family:Arial;
}

.chat-header{
    background:#1e3a2f;
    color:#fff;
    padding:18px;
    font-size:18px;
    font-weight:bold;
}

.chat-messages{
    flex:1;
    overflow-y:auto;
    background:#f5f5f5;
    padding:16px;
}

.msg{
    display:flex;
    margin-bottom:14px;
}

.msg.user{
    justify-content:flex-end;
}

.bubble{
    max-width:75%;
    padding:12px 14px;
    border-radius:16px;
    font-size:14px;
    line-height:1.6;
}

.user .bubble{
    background:#1e3a2f;
    color:#fff;
}

.ai .bubble{
    background:#fff;
}

.ai-avatar{
    width:42px;
    height:42px;
    border-radius:50%;
    background:#1e3a2f;
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    margin-right:8px;
    font-weight:bold;
}

.chat-bottom{
    padding:14px;
    display:flex;
    gap:10px;
    border-top:1px solid #eee;
}

.chat-bottom input{
    flex:1;
    height:46px;
    border-radius:12px;
    border:1px solid #ddd;
    padding:0 14px;
    outline:none;
}

.chat-bottom button{
    width:55px;
    border:none;
    border-radius:12px;
    background:#1e3a2f;
    color:#fff;
    cursor:pointer;
}

</style>
<?php
$uri = $_SERVER['REQUEST_URI'];

$allowChat =
    str_contains($uri, 'TrangChu') ||
    str_contains($uri, 'Products');

if (!$allowChat) return;
?>

<button id="farm2home-chat-btn">
💬
</button>


<div id="farm2home-chatbox">

    <div class="chat-header">
        Farm2Home Chatbot
    </div>

    <div class="chat-messages" id="chatMessages">

        <div class="msg ai">

            <div class="ai-avatar">
                AI
            </div>

            <div class="bubble">

                Xin chào <br><br>

                Bạn có thể hỏi:<br><br>

                • sản phẩm bán chạy<br>
                • đơn hàng của tôi<br>
                • vận chuyển<br>
                • giá sản phẩm<br>
                • gợi ý sản phẩm

            </div>

        </div>

    </div>

    <div class="chat-bottom">

        <input
            type="text"
            id="chatInput"
            placeholder="Nhập câu hỏi..."
        >

        <button onclick="sendChat()">
            ➤
        </button>

    </div>

</div>

<script>

const chatBtn = document.getElementById('farm2home-chat-btn');
const chatBox = document.getElementById('farm2home-chatbox');

chatBtn.onclick = () => {

    if(chatBox.style.display === 'flex'){
        chatBox.style.display = 'none';
    }else{
        chatBox.style.display = 'flex';
    }

};

function appendMessage(type, text){

    const messages = document.getElementById('chatMessages');

    const div = document.createElement('div');

    div.className = 'msg ' + type;

    if(type === 'ai'){

        div.innerHTML = `
            <div class="ai-avatar">AI</div>
            <div class="bubble">${text}</div>
        `;

    }else{

        div.innerHTML = `
            <div class="bubble">${text}</div>
        `;
    }

    messages.appendChild(div);

    messages.scrollTop = messages.scrollHeight;
}

function sendChat(){

    const input = document.getElementById('chatInput');

    const message = input.value.trim();

    if(message === '') return;

    appendMessage('user', message);

    input.value = '';

    const formData = new FormData();

    formData.append('farm2home_chatbot', 1);
    formData.append('message', message);

    fetch(window.location.href, {
        method:'POST',
        body:formData
    })

    .then(res => res.text())

    .then(text => {

        console.log(text);

        try{

            const start = text.indexOf('{');
            const end = text.lastIndexOf('}');

            if(start === -1 || end === -1){
                throw new Error('JSON invalid');
            }

            const jsonText = text.substring(start, end + 1);

            const data = JSON.parse(jsonText);

            appendMessage('ai', data.reply);

        }catch(err){

            console.error(err);

            appendMessage(
                'ai',
                'AI trả dữ liệu lỗi.'
            );
        }

    })

    .catch(err => {

        console.error(err);

        appendMessage(
            'ai',
            'Hệ thống AI đang bận.'
        );

    });

}

document.getElementById('chatInput')
.addEventListener('keypress', function(e){

    if(e.key === 'Enter'){
        sendChat();
    }

});

function showContactAdmin(){

    const box = document.getElementById('contactAdminBox');

    if(box){
        box.style.display = 'block';
    }

}

</script>