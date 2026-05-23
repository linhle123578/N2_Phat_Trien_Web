document.addEventListener("DOMContentLoaded", function () {
    
    // 1. BIỂU ĐỒ ĐƯỜNG ĐÔI: XU HƯỚNG KINH DOANH (DOANH THU & SẢN LƯỢNG)
    const trendCtx = document.getElementById('trendChartElement').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: trendLabels, // Mảng tháng/năm truyền từ view
            datasets: [
                {
                    label: 'Doanh thu (VND)',
                    data: trendRevenues,
                    borderColor: '#f0a04b',       // Màu cam đất thương hiệu
                    backgroundColor: 'rgba(240, 160, 75, 0.08)',
                    borderWidth: 3,
                    pointBackgroundColor: '#f0a04b',
                    pointRadius: 4,
                    yAxisID: 'yRevenue',
                    tension: 0.35,                // Đường cong mượt mềm
                    fill: true
                },
                {
                    label: 'Sản lượng (Đơn hàng)',
                    data: trendOrders,
                    borderColor: '#183a1d',       // Màu xanh lá thẫm ruộng vườn
                    backgroundColor: 'transparent',
                    borderWidth: 3,
                    pointBackgroundColor: '#183a1d',
                    pointRadius: 4,
                    yAxisID: 'yOrderVolume',
                    tension: 0.2,
                    borderDash: [6, 4]            // Nét đứt phân tách
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: { font: { family: "'Plus Jakarta Sans', sans-serif", weight: '600' } }
                }
            },
            scales: {
                yRevenue: {
                    type: 'linear',
                    position: 'left',
                    title: { display: true, text: 'Doanh thu sản lượng (đ)', font: { weight: 'bold' } },
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('vi-VN') + 'đ';
                        }
                    }
                },
                yOrderVolume: {
                    type: 'linear',
                    position: 'right',
                    title: { display: true, text: 'Số lượng đơn hàng', font: { weight: 'bold' } },
                    grid: { drawOnChartArea: false } // Giữ lưới sạch không chồng chéo
                }
            }
        }
    });

    // 2. BIỂU ĐỒ TRÒN: PHÂN TÍCH TỶ TRỌNG DOANH THU THEO DANH MỤC
    const pieCtx = document.getElementById('pieChartElement').getContext('2d');
    new Chart(pieCtx, {
        type: 'pie',
        data: {
            labels: pieLabels, // Mảng tên danh mục truyền từ view
            datasets: [{
                data: pieData, // Mảng phần trăm tương ứng
                backgroundColor: [
                    '#183a1d', // Xanh lá đậm
                    '#f0a04b', // Cam đất
                    '#28a745', // Xanh lá sáng
                    '#a3b899', // Xanh xám nhạt
                    '#adb5bd'  // Xám trung tính
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { font: { family: "'Plus Jakarta Sans', sans-serif", weight: '600' }, padding: 15 }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return ` ${context.label}: ${context.raw}%`;
                        }
                    }
                }
            }
        }
    });
});