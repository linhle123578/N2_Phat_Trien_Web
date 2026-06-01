<?php
//session_start();
ob_start();

if (!function_exists('contactAdminButton')) {
    function contactAdminButton()
    {
        return "
        <div style='margin-top:12px; text-align:center;'>
            <button onclick='showContactAdmin()'
            class='btn-contact-admin'>
                <i class='fas fa-headset'></i> Liên hệ tổng đài
            </button>

            <div id='contactAdminBox' style='display:none;margin-top:12px; font-size: 13.5px; background: #f8f9fa; padding: 10px; border-radius: 10px; border: 1px solid #e9ecef;'>
                <b>Hotline:</b> 1900 6868 <br>
                <b>Email:</b> cskh@farm2home.vn <br>
                <i>08:00 - 22:00 mỗi ngày</i>
            </div>
        </div>
        ";
    }
}

if (!function_exists('getFaqButtons')) {
    function getFaqButtons()
    {
        return "
        <div class='faq-buttons'>
            <button onclick=\"askFAQ('sản phẩm bán chạy')\">🔥 Bán chạy</button>
            <button onclick=\"askFAQ('gợi ý sản phẩm')\">💡 Gợi ý cho tôi</button>
            <button onclick=\"askFAQ('đơn hàng của tôi')\">📦 Đơn hàng của tôi</button>
            <button onclick=\"askFAQ('vận chuyển')\">🚚 Vận chuyển</button>
            <button onclick=\"askFAQ('giá sản phẩm')\">💰 Hỏi giá sản phẩm</button>
        </div>
        ";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['farm2home_chatbot'])) {

    if (ob_get_length()) ob_clean();
    
    header('Content-Type: application/json; charset=utf-8');

    error_reporting(0);
    ini_set('display_errors', 0);

    $message = trim($_POST['message'] ?? '');
    $msg = mb_strtolower($message, 'UTF-8');
    $context = trim($_POST['context'] ?? '');

    $conn = mysqli_init();

    mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
        mysqli_options($conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);

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

    function reply($text, $set_context = null, $clear_context = false)
    {
        $response = ["reply" => $text];
        if ($set_context) {
            $response['set_context'] = $set_context;
        }
        if ($clear_context) {
            $response['clear_context'] = true;
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (
        str_contains($msg, 'xin chào') ||
        str_contains($msg, 'hello') ||
        str_contains($msg, 'hi')
    ) {

        reply("
            Xin chào, mình là trợ lý ảo Farm2Home. 🌱<br>
            Mình có thể giúp gì cho bạn hôm nay?<br>
            " . getFaqButtons() . "
        ");
    }

    //ĐƠN HÀNG CỦA TÔI

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

    //VẬN CHUYỂN

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
                o.order_status as shipment_status,
                DATE_ADD(o.created_at, INTERVAL 3 DAY) as estimated_date
            FROM `order` o
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
        $est_date = date('Y-m-d', strtotime($s['estimated_date']));

        reply("
            Thông tin vận chuyển:<br><br>

            Mã đơn: <b>{$s['order_id']}</b><br>
            Trạng thái: <b>{$s['shipment_status']}</b><br>
            Dự kiến giao: {$est_date}
        ");
    }

    //SẢN PHẨM BÁN CHẠY

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

    //GỢI Ý SẢN PHẨM

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

    // TÌM GIÁ
    if ($context === 'asking_price') {
        $search = mysqli_real_escape_string($conn, $msg);
        $sql = "SELECT * FROM product WHERE LOWER(product_name) LIKE '%$search%'";
        $rs = mysqli_query($conn, $sql);
        
        if ($rs && mysqli_num_rows($rs) > 0) {
            $html = "Đây là kết quả mình tìm được:<br><br>";
            while ($p = mysqli_fetch_assoc($rs)) {
                $html .= "<b>{$p['product_name']}</b><br>";
                $html .= "Giá: <b>" . number_format($p['price']) . "đ</b> / {$p['unit']}<br><br>";
            }
            reply($html, null, true);
        } else {
            reply("Mình không tìm thấy sản phẩm nào giống \"{$message}\". Bạn thử tên khác nhé!", 'asking_price');
        }
    }

    if (
        str_contains($msg, 'giá') ||
        str_contains($msg, 'bao nhiêu')
    ) {
        reply("Bạn muốn hỏi giá sản phẩm nào? (Vui lòng nhập tên sản phẩm, ví dụ: 'rau', 'cà chua'...)", 'asking_price');
    }

    //TỒN KHO

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

    //FALLBACK

    reply("
        Xin lỗi, AI chưa hiểu rõ yêu cầu này của bạn. 😔<br><br>
        Bạn có thể tham khảo một số chức năng bên dưới:<br>
        " . getFaqButtons() . contactAdminButton()
    );

    mysqli_close($conn);

    exit;
}
?>

<style>
@import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');

#farm2home-chat-btn{
    position:fixed;
    right:30px;
    bottom:30px;
    width:64px;
    height:64px;
    border:none;
    border-radius:50%;
    background: linear-gradient(135deg, #183a1d 0%, #2a5a31 100%);
    color:#fff;
    font-size:26px;
    cursor:pointer;
    z-index:999999;
    box-shadow: 0 8px 24px rgba(24, 58, 29, 0.4);
    transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    display: flex;
    align-items: center;
    justify-content: center;
}
#farm2home-chat-btn:hover {
    transform: scale(1.1);
}

#farm2home-chatbox{
    position:fixed;
    right:30px;
    bottom:110px;
    width:380px;
    height:600px;
    background:#ffffff;
    border-radius:20px;
    overflow:hidden;
    display:none;
    flex-direction:column;
    z-index:999999;
    box-shadow: 0 15px 40px rgba(0,0,0,0.15);
    font-family: 'Plus Jakarta Sans', sans-serif;
    border: 1px solid #f1eedd;
}

.chat-header{
    background: linear-gradient(135deg, #183a1d 0%, #2a5a31 100%);
    color:#fff;
    padding:18px 20px;
    font-size:16px;
    font-weight:700;
    display: flex;
    align-items: center;
    gap: 10px;
}
.chat-header i {
    font-size: 20px;
    color: #eba15c;
}

.chat-messages{
    flex:1;
    overflow-y:auto;
    background:#fafafa;
    padding:20px;
    scrollbar-width: thin;
}
.chat-messages::-webkit-scrollbar {
    width: 6px;
}
.chat-messages::-webkit-scrollbar-thumb {
    background-color: #ddd;
    border-radius: 10px;
}

.msg{
    display:flex;
    margin-bottom:18px;
    animation: fadeIn 0.3s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.msg.user{
    justify-content:flex-end;
}

.bubble{
    max-width:80%;
    padding:12px 16px;
    border-radius:18px;
    font-size:14.5px;
    line-height:1.5;
    box-shadow: 0 2px 5px rgba(0,0,0,0.04);
}

.user .bubble{
    background: #183a1d;
    color:#fff;
    border-bottom-right-radius: 4px;
}

.ai .bubble{
    background:#fff;
    border-bottom-left-radius: 4px;
    border: 1px solid #eee;
    color: #333;
}

.ai-avatar{
    width:36px;
    height:36px;
    border-radius:50%;
    background:#eba15c;
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    margin-right:12px;
    font-weight:bold;
    font-size: 18px;
    flex-shrink: 0;
    box-shadow: 0 3px 8px rgba(235, 161, 92, 0.4);
}

.chat-bottom{
    padding:16px;
    display:flex;
    gap:10px;
    border-top:1px solid #eee;
    background: #fff;
}

.chat-bottom input{
    flex:1;
    height:46px;
    border-radius:24px;
    border:1px solid #ddd;
    padding:0 18px;
    outline:none;
    font-family: inherit;
    font-size: 14px;
    transition: border-color 0.3s;
}
.chat-bottom input:focus {
    border-color: #eba15c;
}

.chat-bottom button{
    width:46px;
    height:46px;
    border:none;
    border-radius:50%;
    background:#eba15c;
    color:#fff;
    cursor:pointer;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.3s, transform 0.2s;
}
.chat-bottom button:hover {
    background:#d98e4a;
    transform: scale(1.05);
}

/* FAQ Buttons */
.faq-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
}
.faq-buttons button {
    background: #f1eedd;
    color: #183a1d;
    border: 1px solid #e2ddc4;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s ease;
    font-family: 'Plus Jakarta Sans', sans-serif;
}
.faq-buttons button:hover {
    background: #eba15c;
    color: #fff;
    border-color: #eba15c;
    transform: translateY(-2px);
}
.btn-contact-admin {
    background:#183a1d;
    color:white;
    border:none;
    padding:10px 18px;
    border-radius:20px;
    cursor:pointer;
    font-weight:600;
    font-size: 13px;
    transition: background 0.3s;
}
.btn-contact-admin:hover {
    background: #2a5a31;
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
<i class="fas fa-comment-dots"></i>
</button>


<div id="farm2home-chatbox">

    <div class="chat-header">
        <i class="fas fa-robot"></i> Farm2Home AI
    </div>

    <div class="chat-messages" id="chatMessages">

        <div class="msg ai">

            <div class="ai-avatar">
                <i class="fas fa-leaf"></i>
            </div>

            <div class="bubble">

                Xin chào, mình là trợ lý ảo Farm2Home. 🌱<br>
                Mình có thể giúp gì cho bạn hôm nay?<br>

                <?php echo getFaqButtons(); ?>

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
            <i class="fas fa-paper-plane"></i>
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
            <div class="ai-avatar"><i class="fas fa-leaf"></i></div>
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

function askFAQ(question) {
    document.getElementById('chatInput').value = question;
    sendChat();
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
    
    const currentContext = sessionStorage.getItem('chatContext');
    if (currentContext) {
        formData.append('context', currentContext);
    }

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
            
            if (data.set_context) {
                sessionStorage.setItem('chatContext', data.set_context);
            }
            if (data.clear_context) {
                sessionStorage.removeItem('chatContext');
            }

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
