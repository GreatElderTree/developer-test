<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order Confirmed</title>
    <style>
        body { font-family: sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        th, td { padding: 8px 12px; border: 1px solid #ddd; text-align: left; }
        th { background: #f5f5f5; }
        .total-row td { font-weight: bold; }
    </style>
</head>
<body>
    <h2>Thank you for your order!</h2>
    <p>Your order <strong>#{{ $order->id() }}</strong> has been confirmed.</p>

    <table>
        <thead>
            <tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Line Total</th></tr>
        </thead>
        <tbody>
            @foreach ($order->items() as $item)
            <tr>
                <td>{{ $item->productName }}</td>
                <td>{{ $item->qty }}</td>
                <td>€{{ number_format($item->unitPrice, 2) }}</td>
                <td>€{{ number_format($item->lineTotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr><td colspan="3">Subtotal</td><td>€{{ number_format($order->subtotal(), 2) }}</td></tr>
            @if ($order->discountPercentage() > 0)
            <tr><td colspan="3">Discount ({{ $order->discountPercentage() }}%)</td><td>-€{{ number_format($order->discountAmount(), 2) }}</td></tr>
            @endif
            <tr class="total-row"><td colspan="3">Total</td><td>€{{ number_format($order->total(), 2) }}</td></tr>
        </tfoot>
    </table>
</body>
</html>
