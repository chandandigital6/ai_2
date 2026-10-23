# Product aur public checkout update

Date: 23 October 2026

## Owner product kaise add kare

1. Products → Add product kholen. Name, category, regular/offer price aur opening stock bharen. Main photo add karen.
2. Ek fixed item bechna hai to attributes/variants chhod sakte hain.
3. Size/colour/purity jaise options hain to **Sizes, colours & other options** kholen aur available values select karen. Example: Size = M, L; Colour = Red, Blue.
4. **Save & add variants** dabayen. Har sellable combination ke liye values, name, price aur opening stock ek hi form mein save karen. Example: Red / M = ₹499, stock 10; Blue / L = ₹549, stock 5.
5. Baad mein product ke upar tabs se Details, Photos, Attributes & options, Variants, Tags, Stock aur QR khol sakte hain.

Attribute = choice type (Size). Value = available choice (M). Variant = sellable combination (Red / M) jiska apna price aur stock hai. Specifications = descriptive details (Warranty: 1 year); Tags = search labels. Specifications aur tags variants nahin banate.

Main-product aur variant stock alag hain. Variants wale product ko kharidne ke liye customer ko variant select karna hoga. Existing quantity badalne ke liye **Stock** kholen: Add stock increases, Remove stock decreases, Set Actual Stock replaces quantity. Reason record hota hai. Product details/variant details edit karne se current stock overwrite nahin hota.

## Validation aur permissions

- Product create: `products.create`; details, options, variants, tags, photos update: existing `products.update` checks.
- Product screens: `products.view`; stock screen: `inventory.view`; existing stock change: `inventory.manage`.
- Owner/shared attribute values available hain; doosre business ke attribute values reject hote hain.
- Ek non-empty combination ke duplicate variants reject hote hain. Legacy named variants without mapped values supported hain.
- Variant mein used value product se remove karne par clear error milta hai; pehle variant ka combination badlen.
- Product ke saath options aur variant ke saath mapping transaction mein save hoti hai. Invalid mapping par partial save nahin hota.
- Existing stock ko direct variant API se badalne par bhi inventory permission aur stock movement record required hai.

## Public checkout

Cart, checkout, order detail aur My orders customer/public layout mein khulte hain. Checkout par hi coupon Apply/Remove aur final total dikhte hain. Summary mein regular total, product offer saving, merchandise subtotal, coupon discount, shipping, GST aur total payable alag rows hain.

Saved address select karen ya new address/state bharen. State change par total refresh hota hai. Coupon apply karen aur verified total review karke Place order dabayen. Invalid coupon, timeout ya quote failure page par error aur toast dikhate hain. Address/coupon change hone ke baad stale total submit nahin hota. Final stock, prices, coupon aur amount order create hote waqt server dobara validate karta hai. Preview se coupon usage consume nahin hota.

Payment methods aur gateway configuration existing implementation ke mutabik hain. Is update mein live payment transaction ya production deployment nahin ki gayi hai.

## Existing server par apply karna

Archive full project source hai. `docs/PRODUCT_CHECKOUT_CHANGED_FILES.json` mein is update ke exact changed/new files hain. Existing server mein unhi files ko project root ke matching paths par merge/replace karen; apna server `.env` aur uploaded media retain karen.

Project root mein run karen:

```bash
php artisan view:clear
php artisan queue:restart
```

Is update ke liye naya migration, seeder, Composer package ya npm build required nahin hai. Existing built assets retained hain; new product styling Blade partial mein scoped hai.

## Developer/API changes

Existing API adapter controllers ko reuse karta hai. Base path `/api/v1`; authenticated requests mein existing Growya bearer token aur `Accept: application/json` use karen. Is release mein route names/URLs change nahin hue.

| Task | Existing API path | Source |
| --- | --- | --- |
| Product form catalog | `GET /api/v1/businesses/{business}/products/create` or `.../{product}/edit` | `ProductController::create/edit` |
| Product create/update | `POST /api/v1/businesses/{business}/products`; `PUT .../{product}` | `ProductController::store/update` |
| Product option values | `PUT /api/v1/businesses/{business}/products/{product}/attributes` | `ProductAttributeController::update` |
| Variant catalog/create | `GET/POST /api/v1/businesses/{business}/products/{product}/variants` | `ProductVariantController::index/store` |
| Variant update | `PUT /api/v1/businesses/{business}/products/{product}/variants/{variant}` | `ProductVariantController::update` |
| Existing stock movement | `POST /api/v1/businesses/{business}/products/{product}/inventory` | `ProductInventoryController::store` |
| Checkout quote | `POST /api/v1/shops/{business}/quote` | `Extensions/QuoteController::quote` |
| Place order | `POST /api/v1/shops/{business}/checkout` | `GrowyaCartCheckoutController::place` |

Product create/update accepts optional `attribute_values: [value_id, ...]`. Omit this field on update to preserve existing selections; send `attribute_values: []` to explicitly clear. Web forms use `attribute_values_present: 1` so unchecking everything also clears. The dedicated attributes endpoint retains its existing payload key `values`.

Product create/edit catalog additionally returns `attributes` with their `values` and `selectedValues`. Variants index returns product-selected `attributes` with their values. Product/variant create/update retain the adapter's existing action-success response; fetch the relevant GET endpoint to refresh records.

Variant create/update can save the combination in the same request as details:

```json
{
  "name": "Red / M",
  "sku": "SHIRT-RED-M",
  "price": 599,
  "offer_price": 499,
  "stock_quantity": 10,
  "status": "active",
  "attributes": {"12": 41, "13": 52}
}
```

IDs above are examples: keys = attribute IDs; values = product-selected attribute_value IDs. Omit `attributes` on update to preserve mapping. Send `{}` to clear mapping. `stock_quantity` remains required for create; omit it on update to preserve current quantity. For stock adjustments prefer inventory endpoint with `stock_target`, `variant_id` when relevant, `movement_type`, `quantity`, `reason`.

Quote accepts `state` or an owned `address_id`, plus optional `coupon_code`. Existing response totals remain; new additive fields are `regular_subtotal_paise`, `product_savings_paise`, `coupon_code`. All monetary quote fields are **integer paise**. Example: `153470` = ₹1,534.70. Pass `total_paise` back as `quoted_total_paise` along with the address, coupon and payment fields when placing the order. Do not calculate final payable/coupon percentage only in Flutter.

Shared option rules live in `app/Services/ProductOptionService.php`. Web checkout quote behavior is in `resources/views/checkout/script.blade.php`; product interactions are in `resources/views/products/partials/script.blade.php`. Existing API route wiring is `routes/api_actions.php`, `routes/extension_actions.php`, and `Api/V1/ActionController.php`.

Automated/backend and browser validation details: `docs/PRODUCT_CHECKOUT_VALIDATION.json`.
