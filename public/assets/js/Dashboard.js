document.addEventListener("DOMContentLoaded", function () {
    
    // 1. BIỂU ĐỒ ĐƯỜNG ĐÔI: XU HƯỚNG KINH DOANH (DOANH THU & SẢN LƯỢNG)
    const trendCtx = document.getElementById('trendChartElement').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: trendLabels,
            datasets: [
                {
                    label: 'Doanh thu (VND)',
                    data: trendRevenues,
                    borderColor: '#f0a04b',      
                    backgroundColor: 'rgba(240, 160, 75, 0.08)',
                    borderWidth: 3,
                    pointBackgroundColor: '#f0a04b',
                    pointRadius: 4,
                    yAxisID: 'yRevenue',
                    tension: 0.35,             
                    fill: true
                },
                {
                    label: 'Sản lượng (Đơn hàng)',
                    data: trendOrders,
                    borderColor: '#183a1d',      
                    backgroundColor: 'transparent',
                    borderWidth: 3,
                    pointBackgroundColor: '#183a1d',
                    pointRadius: 4,
                    yAxisID: 'yOrderVolume',
                    tension: 0.2,
                    borderDash: [6, 4]    
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
                    grid: { drawOnChartArea: false }
                }
            }
        }
    });

    // 2. BIỂU ĐỒ TRÒN: PHÂN TÍCH TỶ TRỌNG DOANH THU THEO DANH MỤC
    const pieCtx = document.getElementById('pieChartElement').getContext('2d');
    new Chart(pieCtx, {
        type: 'pie',
        data: {
            labels: pieLabels.map((label, index) => `${label}: ${pieData[index]}%`), // Mảng tên danh mục + %
            datasets: [{
                data: pieData, // Mảng phần trăm tương ứng
                backgroundColor: [
                    '#183a1d', 
                    '#f0a04b', 
                    '#28a745', 
                    '#a3b899', 
                    '#adb5bd'
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
                    position: 'right',
                    labels: { font: { family: "'Plus Jakarta Sans', sans-serif", weight: '600' }, padding: 15 }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let originalLabel = pieLabels[context.dataIndex];
                            return ` ${originalLabel}: ${context.raw}%`;
                        }
                    }
                }
            }
        }
    });
});
