SELECT 
    o.order_id,
    c.full_name,
    o.order_status,
    o.created_at,

    SUM(oi.quantity * oi.price) AS total_amount,

    GROUP_CONCAT(
        CONCAT(
            p.product_name,
            ' (x',
            oi.quantity,
            ')'
        )
        SEPARATOR ', '
    ) AS products

FROM `order` o

LEFT JOIN customer c
    ON o.customer_id = c.customer_id

LEFT JOIN orderitem oi
    ON o.order_id = oi.order_id

LEFT JOIN product p
    ON oi.product_id = p.product_id

GROUP BY
    o.order_id,
    c.full_name,
    o.order_status,
    o.created_at

ORDER BY o.created_at DESC;