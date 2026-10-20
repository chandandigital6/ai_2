<?php

return [
    // Only tested API-compatible model families may be selected by a plan.
    'text_models' => ['gpt-4.1-mini' => 'GPT-4.1 mini', 'gpt-4.1' => 'GPT-4.1'],
    'image_models' => ['gpt-image-1-mini' => 'GPT Image 1 mini', 'gpt-image-1' => 'GPT Image 1', 'gpt-image-1.5' => 'GPT Image 1.5'],
    'sizes' => ['1024x1024', '1024x1536', '1536x1024'],
    'max_asset_kb' => 5120,
    'max_in_flight' => 2,
    'generation_rate' => 10,
];
