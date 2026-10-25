<?php
// ============================================================
// FILE: application/libraries/AiCopywriter.php
// Thin cURL wrapper around the Anthropic Messages API for generating WhatsApp
// campaign copy variants. Follows the same style as WhatsAppClient.php — no
// SDK, plain curl_*, credentials from company_settings (whatsapp_ai_api_key,
// configured in Settings under require_system_owner()).
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class AiCopywriter {

    private $CI;
    private $api_key;
    // A known-stable Anthropic model id — deliberately not the newest/fastest
    // one, to minimize the odds of this string going stale before anyone
    // revisits it. Swap freely if a newer model is preferred.
    private $model = 'claude-3-5-sonnet-20241022';

    public function __construct() {
        $this->CI =& get_instance();
        $row = $this->CI->db->where('key_name', 'whatsapp_ai_api_key')->get('company_settings')->row();
        $this->api_key = $row->key_value ?? '';
    }

    public function is_configured() {
        return $this->api_key !== '';
    }

    // Returns ['ok'=>true,'variants'=>[...]] or ['ok'=>false,'error'=>...].
    public function generate_variants($campaign_type, $brief, $count = 3) {
        if (!$this->is_configured()) {
            return ['ok' => false, 'error' => 'AI Copywriter API key is not configured (Settings → WhatsApp Marketing).'];
        }

        $type_label = str_replace('_', ' ', $campaign_type);
        $prompt = "You write short WhatsApp marketing messages for an Indian FMCG/grocery retail store. "
            . "Campaign type: {$type_label}. Offer brief: \"{$brief}\". "
            . "Write {$count} distinct message variants, each under 300 characters, friendly and persuasive, "
            . "suitable for WhatsApp (no markdown formatting, no emojiheavy spam). "
            . "Use the literal placeholder {{CustomerName}} where a personal greeting fits naturally, "
            . "and {{Date}} for an offer end date if relevant. "
            . "Respond with ONLY a JSON array of {$count} strings — no other text, no code fences.";

        $payload = [
            'model'      => $this->model,
            'max_tokens' => 800,
            'messages'   => [['role' => 'user', 'content' => $prompt]],
        ];

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'x-api-key: ' . $this->api_key,
                'anthropic-version: 2023-06-01',
                'content-type: application/json',
            ],
            CURLOPT_TIMEOUT        => 30,
        ]);
        $raw       = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err  = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return ['ok' => false, 'error' => 'cURL error: ' . $curl_err];
        }
        $body = json_decode($raw, true);
        if ($http_code !== 200) {
            return ['ok' => false, 'error' => $body['error']['message'] ?? ('HTTP ' . $http_code)];
        }

        $text = $body['content'][0]['text'] ?? '';
        $variants = json_decode(trim($text), true);
        if (!is_array($variants)) {
            // Model didn't return clean JSON — fall back to non-empty lines
            // rather than surfacing an error for what's otherwise usable copy.
            $variants = array_values(array_filter(array_map('trim', explode("\n", $text))));
        }
        if (!$variants) {
            return ['ok' => false, 'error' => 'The AI did not return any usable message text.'];
        }

        return ['ok' => true, 'variants' => array_slice($variants, 0, $count)];
    }
}
