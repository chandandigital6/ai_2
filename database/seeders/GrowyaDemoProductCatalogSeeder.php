<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GrowyaDemoProductCatalogSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Demo catalog seeder cannot run in production.');
        }

        $now = now();
        $groups = [
            'jewellery-gems' => [
                'tags' => ['Gold Jewellery', 'Silver Jewellery', 'Bridal', 'Hallmarked', 'Gift Ideas'],
                'attributes' => ['Material' => ['Yellow Gold','Rose Gold','Sterling Silver'], 'Purity' => ['18K','22K','925 Silver'], 'Weight' => ['Lightweight','Mediumweight','Heavyweight']],
                'variant' => 'Purity',
            ],
            'electronics-mobiles' => [
                'tags' => ['Electronics', 'Mobile Accessories', 'Best Seller', 'New Arrival', 'Warranty'],
                'attributes' => ['Brand' => ['DemoTech','DemoMobile','Growya Demo'], 'Storage' => ['64GB','128GB','256GB'], 'Color' => ['Black','Blue','White']],
                'variant' => 'Storage',
            ],
            'clothing-fashion' => [
                'tags' => ['Fashion', 'Ethnic Wear', 'Casual Wear', 'Trending', 'Cotton'],
                'attributes' => ['Size' => ['S','M','L','XL'], 'Fabric' => ['Cotton','Silk','Denim','Linen'], 'Fit' => ['Regular','Slim']],
                'variant' => 'Size',
            ],
            'solar-renewable-energy' => [
                'tags' => ['Solar Energy', 'Renewable', 'Off Grid', 'Energy Saving', 'Installation Ready'],
                'attributes' => ['Wattage' => ['20W','100W','550W'], 'Battery Capacity' => ['12Ah','50Ah','100Ah'], 'Panel Type' => ['Monocrystalline','Polycrystalline']],
                'variant' => 'Wattage',
            ],
        ];

        DB::transaction(function () use ($groups, $now) {
            foreach ($groups as $slug => $config) {
                $businessId = DB::table('businesses')->where('slug', 'demo-'.$slug)->value('id');
                if (!$businessId) {
                    throw new \RuntimeException("Demo business demo-{$slug} missing. Run GrowyaDemoSeeder first.");
                }
                $tagIds = [];
                foreach ($config['tags'] as $name) {
                    $tagSlug = 'demo-'.Str::slug($name);
                    DB::table('tags')->updateOrInsert(['slug' => $tagSlug], ['name' => $name, 'updated_at' => $now]);
                    $tagIds[] = DB::table('tags')->where('slug', $tagSlug)->value('id');
                }
                $attributeValueIds = [];
                foreach ($config['attributes'] as $attributeName => $values) {
                    $attrSlug = 'demo-'.Str::slug($attributeName);
                    DB::table('attributes')->updateOrInsert(['slug'=>$attrSlug], ['name'=>$attributeName, 'updated_at'=>$now]);
                    $attrId = DB::table('attributes')->where('slug', $attrSlug)->value('id');
                    foreach ($values as $position => $value) {
                        DB::table('attribute_values')->updateOrInsert(
                            ['attribute_id'=>$attrId, 'value'=>$value],
                            ['sort_order'=>$position + 1, 'updated_at'=>$now]
                        );
                        $attributeValueIds[$attributeName][] = DB::table('attribute_values')
                            ->where('attribute_id',$attrId)->where('value',$value)->value('id');
                    }
                }

                $products = DB::table('products')->where('business_id',$businessId)
                    ->where('slug','like','demo-%')->orderBy('id')->get();
                if ($products->count() < 10) {
                    throw new \RuntimeException("Expected 10 demo products for {$slug}; found {$products->count()}.");
                }
                foreach ($products as $i => $product) {
                    // 2 topical tags per product, plus a rotating extra tag.
                    $productTags = array_unique([
                        $tagIds[$i % count($tagIds)],
                        $tagIds[($i + 1) % count($tagIds)],
                        $tagIds[($i + 3) % count($tagIds)],
                    ]);
                    foreach ($productTags as $tagId) {
                        DB::table('product_tag')->updateOrInsert(
                            ['product_id'=>$product->id,'tag_id'=>$tagId],
                            ['updated_at'=>$now]
                        );
                    }
                    $choice = [];
                    foreach ($attributeValueIds as $attrName => $values) {
                        $choice[$attrName] = $values[$i % count($values)];
                    }
                    foreach ($choice as $valueId) {
                        DB::table('product_attribute_values')->updateOrInsert(
                            ['product_id'=>$product->id,'attribute_value_id'=>$valueId],
                            ['updated_at'=>$now]
                        );
                    }
                    // Two variants for first 6 products of each business.
                    if ($i >= 6) continue;
                    $variantAttr = $config['variant'];
                    $candidateIds = $attributeValueIds[$variantAttr];
                    foreach ([0,1] as $n) {
                        $valueId = $candidateIds[($i + $n) % count($candidateIds)];
                        $label = DB::table('attribute_values')->where('id',$valueId)->value('value');
                        $sku = sprintf('DEMO-%d-V%02d-%d', array_search($slug,array_keys($groups),true)+1, $i+1, $n+1);
                        DB::table('product_variants')->updateOrInsert(
                            ['product_id'=>$product->id,'sku'=>$sku],
                            ['name'=>$product->name.' / '.$label,
                             'price'=>(float)$product->price + ($n*100),
                             'stock_quantity'=>5 + $n*5,
                             'status'=>'active','updated_at'=>$now]
                        );
                        $variantId = DB::table('product_variants')->where('product_id',$product->id)->where('sku',$sku)->value('id');
                        DB::table('product_variant_attribute_values')->updateOrInsert(
                            ['product_variant_id'=>$variantId,'attribute_value_id'=>$valueId],
                            ['updated_at'=>$now]
                        );
                    }
                }
            }
        });
    }
}
