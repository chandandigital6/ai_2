
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>QR Label - {{ $product->name }}</title>

    <style>
        * { box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f4f4f5;
            color: #18181b;
        }

        .toolbar { margin-bottom: 20px; }

        .toolbar button {
            padding: 10px 20px;
            cursor: pointer;
            background: #18181b;
            color: white;
            border: 0;
            border-radius: 8px;
        }

        .label {
            width: 65mm;
            min-height: 75mm;
            padding: 5mm;
            background: white;
            margin: 0 auto;
            border: 1px solid #ddd;
            text-align: center;
            overflow-wrap: anywhere;
        }

        .business {
            font-size: 12px;
            font-weight: bold;
        }

        .product {
            margin-top: 8px;
            font-size: 13px;
            font-weight: bold;
        }

        .qr {
            width: 43mm;
            height: 43mm;
            object-fit: contain;
            display: block;
            margin: 5px auto;
        }

        .meta { font-size: 11px; margin-top: 5px; }
        .price { font-size: 16px; font-weight: bold; }

        @page {
            size: 65mm 75mm;
            margin: 0;
        }

        @media print {
            body { padding: 0; background: white; }
            .toolbar { display: none; }
            .label { border: 0; margin: 0; }
        }
    </style>
</head>

<body>

    <div class="toolbar">
        <button onclick="window.print()">
            Print QR Label
        </button>
    </div>

    <div class="label">
        <div class="business">{{ $business->name }}</div>

        <div class="product">{{ $product->name }}</div>

        <img
            class="qr"
            src="{{ asset('storage/' . $product->qr_code) }}"
            alt="Product QR"
        >

        <div class="meta">
            SKU: {{ $product->sku ?: 'N/A' }}
        </div>

        <div class="price">
            ₹{{ number_format((float)($product->offer_price ?? $product->price), 2) }}
        </div>
    </div>

</body>
</html>
