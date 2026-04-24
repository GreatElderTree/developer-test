<?php

class OrderController
{
    public function store($request)
    {
        $db = new PDO(...);

        $customer = $db->query("SELECT * FROM customers WHERE id = ".$request['customer_id'])->fetch();

        if (!$customer) {
            die("Customer not found");
        }

        $total = 0;

        foreach ($request['items'] as $item) {
            $product = $db->query("SELECT * FROM products WHERE id = ".$item['product_id'])->fetch();

            $total += $product['price'] * $item['qty'];
        }

        $db->exec("INSERT INTO orders(total) VALUES($total)");

        mail($customer['email'], "Order Confirmed", "Thanks!");

        echo json_encode(["success" => true]);
    }
}
