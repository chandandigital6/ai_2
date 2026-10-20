<?php

namespace App\Services\Extensions;

use App\Exceptions\AiProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AiProvider
{
    private function check(Response $r): void
    {
        if ($r->successful()) {
            return;
        }
        $code = $r->json('error.code');
        Log::warning('AI provider rejected request', ['http_status' => $r->status(), 'provider_code' => $code,
            'request_id' => $r->header('x-request-id')]);
        if ($code === 'insufficient_quota') {
            throw new AiProviderException('provider_quota', 'The AI provider billing quota is exhausted. Contact the administrator.');
        }
        if ($r->status() === 429) {
            throw new AiProviderException('provider_rate_limit', 'The AI provider is busy or rate limited. Wait a minute and try again.');
        }
        if ($r->status() === 401) {
            throw new AiProviderException('provider_auth', 'The AI provider API key is invalid. Contact the administrator.');
        }
        if ($r->status() === 403 || $r->status() === 404) {
            throw new AiProviderException('model_unavailable', 'Your package model is unavailable to the configured AI account. Contact the administrator.');
        }
        if (in_array($code, ['content_policy_violation', 'moderation_blocked'], true)) {
            throw new AiProviderException('content_rejected', 'The provider could not accept this prompt or reference. Revise it and try again.');
        }
        if ($r->status() >= 500) {
            throw new AiProviderException('provider_unavailable', 'The AI provider is temporarily unavailable. Try again later.');
        }
        throw new AiProviderException('provider_request', 'The AI provider could not process this request. Try a simpler prompt or contact the administrator.');
    }

    public function generate(string $type, string $prompt, array $options = []): array
    {
        $key = config('growya_extensions.ai.key');
        if (! config('growya_extensions.ai.enabled') || ! $key) {
            throw new AiProviderException('provider_setup', 'AI provider is not configured. Contact the administrator.');
        }
        $model = $options['model'] ?? config('growya_extensions.ai.'.$type.'_model');
        if (! array_key_exists($model, config('growya_ai.'.$type.'_models', []))) {
            throw new AiProviderException('model_unavailable', 'The package model is not enabled on this server. Contact the administrator.');
        }
        $images = [];
        foreach ($options['assets'] ?? [] as $asset) {
            if (! Storage::disk('local')->exists($asset['path'])) {
                throw new AiProviderException('reference_missing', 'A saved reference image is missing. Upload it again before generating.');
            }
            $images[] = 'data:'.$asset['mime'].';base64,'.base64_encode(Storage::disk('local')->get($asset['path']));
        }
        $http = Http::withToken($key)->acceptJson()->connectTimeout(15)->timeout(180)->withOptions(['allow_redirects' => false]);
        try {
            if ($type === 'text') {
                $content = [['type' => 'input_text', 'text' => $prompt]];
                foreach ($images as $url) {
                    $content[] = ['type' => 'input_image', 'image_url' => $url, 'detail' => 'low'];
                }
                $r = $http->post('https://api.openai.com/v1/responses', ['model' => $model, 'store' => false,
                    'input' => [['role' => 'user', 'content' => $content]], 'max_output_tokens' => 1500]);
                $this->check($r);
                $text = '';
                foreach ($r->json('output', []) as $item) {
                    foreach ($item['content'] ?? [] as $part) {
                        if (($part['type'] ?? null) === 'output_text') {
                            $text .= $part['text'] ?? '';
                        }
                    }
                }
                if ($text === '') {
                    throw new AiProviderException('empty_output', 'The AI provider returned no caption. Try a clearer prompt.');
                }

                return ['text' => $text];
            }
            $body = ['model' => $model, 'prompt' => $prompt, 'n' => 1, 'size' => $options['size'] ?? '1024x1024', 'quality' => 'medium', 'output_format' => 'png'];
            $endpoint = 'https://api.openai.com/v1/images/generations';
            if ($images) {
                $endpoint = 'https://api.openai.com/v1/images/edits';
                $body['images'] = array_map(fn ($url) => ['image_url' => $url], $images);
                if (in_array($model, ['gpt-image-1', 'gpt-image-1.5'], true)) {
                    $body['input_fidelity'] = 'high';
                }
            }
            $r = $http->post($endpoint, $body);
            $this->check($r);
            $image = base64_decode($r->json('data.0.b64_json', ''), true);
            $dimensions = $image ? @getimagesizefromstring($image) : false;
            if (! $image || strlen($image) > 20 * 1024 * 1024 || ! $dimensions || $dimensions[2] !== IMAGETYPE_PNG) {
                throw new AiProviderException('invalid_output', 'The AI provider returned an invalid image. Try again.');
            }

            return ['image' => $image, 'width' => $dimensions[0], 'height' => $dimensions[1]];
        } catch (ConnectionException $e) {
            throw new AiProviderException('provider_timeout', 'The AI provider timed out. Wait briefly and try again.');
        }
    }
}
