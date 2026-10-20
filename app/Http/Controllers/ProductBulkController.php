<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Services\BusinessAccessService;
use App\Services\GrowyaPlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductBulkController extends Controller
{
    private const HEADERS = [
        'name',
        'sku',
        'category_id',
        'description',
        'price',
        'offer_price',
        'stock_quantity',
        'status',
    ];

    public function __construct(
        private BusinessAccessService $access
    ) {}

    private function authorizeAccess(
        Business $business,
        string $permission
    ): void {
        abort_unless(
            auth()->check()
            && $this->access->can(
                auth()->user(),
                $business,
                $permission
            ),
            403
        );
    }

    public function index(Business $business)
    {
        $this->authorizeAccess($business, 'products.view');

        $categories = Category::query()->where(fn ($q) => $q->whereNull('business_id')->orWhere('business_id', $business->id))
            ->where('category_type', 'product')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('products.bulk.index', compact(
            'business',
            'categories'
        ));
    }

    public function template(Business $business)
    {
        $this->authorizeAccess($business, 'products.create');

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');

            fputcsv($out, self::HEADERS, ',', '"', '');

            // Example row — replace values before importing.
            fputcsv($out, [
                'Sample T-Shirt',
                'SAMPLE-TS-001',
                '',
                'Cotton T-Shirt',
                '599.00',
                '499.00',
                '20',
                'active',
            ], ',', '"', '');

            fclose($out);
        }, 'growya-products-template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function export(Business $business)
    {
        $this->authorizeAccess($business, 'products.view');

        $filename = 'growya-products-business-'
            .$business->id.'-'
            .now()->format('Ymd-His')
            .'.csv';

        return response()->streamDownload(
            function () use ($business) {
                $out = fopen('php://output', 'w');

                // UTF-8 BOM for Excel compatibility.
                fwrite($out, "\xEF\xBB\xBF");

                fputcsv($out, self::HEADERS, ',', '"', '');

                Product::query()
                    ->where('business_id', $business->id)
                    ->orderBy('id')
                    ->chunkById(500, function ($products) use ($out) {
                        foreach ($products as $product) {
                            $values = [
                                $product->name,
                                $product->sku,
                                $product->category_id,
                                $product->description,
                                $product->price,
                                $product->offer_price,
                                $product->stock_quantity,
                                $product->status,
                            ];

                            // Protect spreadsheet users from CSV formulas.
                            $values = array_map(
                                fn ($v) => $this->csvSafe($v),
                                $values
                            );

                            fputcsv($out, $values, ',', '"', '');
                        }
                    });

                fclose($out);
            },
            $filename,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    private function csvSafe(mixed $value): string
    {
        $value = (string) ($value ?? '');

        if (preg_match('/^[\s]*[=+\-@]/u', $value)) {
            return "'".$value;
        }

        return $value;
    }

    public function import(Request $request, Business $business)
    {
        $this->authorizeAccess($business, 'products.create');

        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:2048',
            ],
        ]);

        $handle = fopen(
            $request->file('file')->getRealPath(),
            'rb'
        );

        if (! $handle) {
            throw ValidationException::withMessages([
                'file' => 'Unable to read the CSV file.',
            ]);
        }

        try {
            $header = fgetcsv($handle, 0, ',', '"', '');

            if (! $header) {
                throw ValidationException::withMessages([
                    'file' => 'CSV file is empty.',
                ]);
            }

            // Remove UTF-8 BOM from first header.
            $header[0] = preg_replace(
                '/^\xEF\xBB\xBF/',
                '',
                $header[0]
            );

            $header = array_map(
                fn ($v) => strtolower(trim((string) $v)),
                $header
            );

            if ($header !== self::HEADERS) {
                throw ValidationException::withMessages([
                    'file' => 'Invalid CSV headers. Download and use the sample template.',
                ]);
            }

            $categories = Category::query()->where(fn ($q) => $q->whereNull('business_id')->orWhere('business_id', $business->id))
                ->where('category_type', 'product')
                ->where('is_active', true)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $existingSkus = Product::withTrashed()
                ->where('business_id', $business->id)
                ->whereNotNull('sku')
                ->pluck('sku')
                ->map(fn ($sku) => mb_strtolower(trim($sku)))
                ->flip()
                ->all();

            $rows = [];
            $errors = [];
            $seenSkus = [];

            $line = 1;

            while (($columns = fgetcsv(
                $handle,
                0,
                ',',
                '"',
                ''
            )) !== false) {
                $line++;

                if ($columns === [null]) {
                    continue;
                }

                if (count($rows) >= 500) {
                    $errors[] = [
                        $line,
                        'Maximum 500 products per import.',
                    ];
                    break;
                }

                if (count($columns) !== count(self::HEADERS)) {
                    $errors[] = [
                        $line,
                        'Column count does not match template.',
                    ];

                    continue;
                }

                $row = array_combine(
                    self::HEADERS,
                    array_map(
                        fn ($v) => trim((string) $v),
                        $columns
                    )
                );

                $rowErrors = [];

                $name = $row['name'];
                $sku = $row['sku'];

                if ($name === '' || mb_strlen($name) > 255) {
                    $rowErrors[] = 'Invalid product name.';
                }

                if (mb_strlen($sku) > 255) {
                    $rowErrors[] = 'SKU exceeds 255 characters.';
                }

                $skuKey = mb_strtolower($sku);

                if ($sku !== '') {
                    if (isset($existingSkus[$skuKey])) {
                        $rowErrors[] = 'SKU already exists in this business.';
                    }

                    if (isset($seenSkus[$skuKey])) {
                        $rowErrors[] = 'Duplicate SKU inside CSV.';
                    }

                    $seenSkus[$skuKey] = true;
                }

                $categoryId = $row['category_id'];

                if (
                    $categoryId !== ''
                    && (
                        ! ctype_digit($categoryId)
                        || ! in_array((int) $categoryId, $categories, true)
                    )
                ) {
                    $rowErrors[] = 'Invalid or inactive product category.';
                }

                foreach (['price', 'offer_price'] as $field) {
                    if (
                        ($field === 'price' || $row[$field] !== '')
                        && (
                            ! preg_match('/^\d+(\.\d{1,2})?$/', $row[$field])
                            || (float) $row[$field] > 999999999999.99
                        )
                    ) {
                        $rowErrors[] = "Invalid {$field}.";
                    }
                }

                if (
                    $row['price'] !== ''
                    && is_numeric($row['price'])
                    && $row['offer_price'] !== ''
                    && is_numeric($row['offer_price'])
                    && (float) $row['offer_price'] > (float) $row['price']
                ) {
                    $rowErrors[] = 'Offer price exceeds regular price.';
                }

                if (
                    ! ctype_digit($row['stock_quantity'])
                    || (float) $row['stock_quantity'] > 2147483647
                ) {
                    $rowErrors[] = 'Invalid stock quantity.';
                }

                if (! in_array(
                    $row['status'],
                    ['active', 'inactive'],
                    true
                )) {
                    $rowErrors[] = 'Status must be active or inactive.';
                }

                if (mb_strlen($row['description']) > 20000) {
                    $rowErrors[] = 'Description is too long.';
                }

                if ($rowErrors) {
                    $errors[] = [
                        $line,
                        implode(' ', $rowErrors),
                    ];

                    continue;
                }

                $quantity = (int) $row['stock_quantity'];

                $rows[] = [
                    'name' => $name,
                    'sku' => $sku !== '' ? $sku : null,
                    'category_id' => $categoryId !== ''
                        ? (int) $categoryId
                        : null,
                    'description' => $row['description'] ?: null,
                    'price' => $row['price'],
                    'offer_price' => $row['offer_price'] !== ''
                        ? $row['offer_price']
                        : null,
                    'stock_quantity' => $quantity,
                    'stock_status' => $quantity > 0
                        ? 'in_stock'
                        : 'out_of_stock',
                    'status' => $row['status'],
                ];
            }
        } finally {
            fclose($handle);
        }

        if ($errors && $request->is('api/*')) {
            return response()->json(['message' => 'Invalid CSV rows. No products imported.', 'errors' => ['file' => ['Correct invalid CSV rows.']], 'import_errors' => $errors], 422);
        }
        if ($errors) {
            $this->saveErrorReport($request, $business, $errors);

            return back()
                ->withErrors([
                    'file' => count($errors)
                        .' invalid CSV rows. No products imported.',
                ])
                ->with('import_errors', array_slice($errors, 0, 20));
        }

        if (! $rows) {
            return back()->withErrors([
                'file' => 'No valid product rows found.',
            ]);
        }

        DB::transaction(function () use ($rows, $business) {
            $locked = Business::query()->whereKey($business->id)->lockForUpdate()->firstOrFail();
            app(GrowyaPlanLimitService::class)->assertCanAddMany($locked, 'max_products', count($rows));
            foreach ($rows as $row) {
                // Generate a unique business-scoped slug.
                do {
                    $slug = Str::slug($row['name'])
                        ?: 'product';

                    $slug .= '-'.strtolower(
                        Str::random(12)
                    );
                } while (
                    Product::withTrashed()
                        ->where('business_id', $business->id)
                        ->where('slug', $slug)
                        ->exists()
                );

                $row['slug'] = $slug;

                $business->products()->create($row);
            }
        });

        return redirect()
            ->route('businesses.products.bulk.index', $business)
            ->with(
                'success',
                count($rows).' products imported successfully.'
            );
    }

    private function saveErrorReport(
        Request $request,
        Business $business,
        array $errors
    ): void {
        $stream = fopen('php://temp', 'w+');

        fputcsv(
            $stream,
            ['row_number', 'error_message'],
            ',',
            '"',
            ''
        );

        foreach ($errors as $error) {
            fputcsv(
                $stream,
                $error,
                ',',
                '"',
                ''
            );
        }

        rewind($stream);

        $path = 'product-import-errors/'
            .Str::uuid().'.csv';

        Storage::disk('local')->put(
            $path,
            stream_get_contents($stream)
        );

        fclose($stream);

        $request->session()->put(
            'product_import_error_report',
            [
                'business_id' => $business->id,
                'path' => $path,
            ]
        );
    }

    public function errorReport(
        Request $request,
        Business $business
    ) {
        $this->authorizeAccess($business, 'products.create');

        $report = $request->session()->get(
            'product_import_error_report'
        );

        abort_unless(
            $report
            && (int) $report['business_id'] === (int) $business->id
            && Storage::disk('local')->exists($report['path']),
            404
        );

        return Storage::disk('local')->download(
            $report['path'],
            'growya-product-import-errors.csv'
        );
    }
}
