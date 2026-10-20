
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bulk Product QR Labels</title>

    <style>
        * { box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 15px;
        }

        .toolbar {
            margin-bottom: 20px;
        }

        button {
            padding: 10px 20px;
            cursor: pointer;
        }

        .labels {
            display: grid;
            grid-template-columns: repeat(3, 65mm);
            gap: 3mm;
            justify-content: center;
        }

        .label {
            width: 65mm;
            height: 75mm;
            border: 1px solid #ddd;
            padding: 5mm;
            text-align: center;
            overflow: hidden;
            break-inside: avoid;
        }

        .business { font-size: 11px; font-weight: bold; }
        .product { font-size: 12px; font-weight: bold; }

        .qr {
            display: block;
            width: 40mm;
            height: 40mm;
            margin: 3mm auto;
            object-fit: contain;
        }

        .meta { font-size: 10px; }
        .price { font-size: 13px; font-weight: bold; }

        @page {
            size: A4 portrait;
            margin: 8mm;
        }

        @media print {
            .toolbar { display: none; }
            body { padding: 0; }
        }
    </style>
</head>

<body>

    <div class="toolbar">
        <button onclick="window.print()">
            Print All Labels
        </button>
    </div>

    <div class="labels">

        @foreach($products as $product)
            <div class="label">
                <div class="business">{{ $business->name }}</div>
                <div class="product">{{ $product->name }}</div>

                <img
                    class="qr"
                    src="{{ asset('storage/' . $product->qr_code) }}"
                    alt="QR Code"
                >

                <div class="meta">
                    SKU: {{ $product->sku ?: 'N/A' }}
                </div>

                <div class="price">
                    ₹{{ number_format((float)($product->offer_price ?? $product->price), 2) }}
                </div>
            </div>
        @endforeach

    </div>

</body>
</html>
