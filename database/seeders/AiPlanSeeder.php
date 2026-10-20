<?php

namespace Database\Seeders;

use App\Models\AiPlan;
use Illuminate\Database\Seeder;

class AiPlanSeeder extends Seeder
{
    public function run(): void
    {
        // Draft examples: admin must review their own retail prices and activate.
        foreach ([
            ['Starter AI', 300, 3, 2, 'gpt-4.1-mini', 'gpt-image-1-mini', 10],
            ['Business AI', 1000, 10, 5, 'gpt-4.1-mini', 'gpt-image-1', 15],
            ['Premium AI', 2500, 30, 10, 'gpt-4.1', 'gpt-image-1.5', 20],
        ] as [$name, $credits, $prompts, $refs, $text, $image, $imageCost]) {
            AiPlan::firstOrCreate(['name' => $name], ['credits' => $credits, 'valid_days' => 30,
                'max_prompts' => $prompts, 'max_references' => $refs, 'text_model' => $text,
                'image_model' => $image, 'text_credits' => 1, 'image_credits' => $imageCost,
                'price_paise' => 0, 'is_active' => false,
                'description' => 'Draft package. Set your selling price and activate before accepting requests.']);
        }
    }
}
