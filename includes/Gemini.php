<?php
// ============================================================
// includes/Gemini.php — Google Gemini API client (cURL)
// ============================================================

require_once __DIR__ . '/../config/config.php';

class Gemini {
    // ── Generate notes from raw text ─────────────────────────
    public static function generateNotes(string $rawText): array {
        if (empty(trim($rawText))) {
            throw new RuntimeException('Input text cannot be empty.');
        }

        if (empty(GEMINI_API_KEY)) {
            throw new RuntimeException('Gemini API key is not configured. Please set GEMINI_API_KEY in your .env file.');
        }

        $systemPrompt = <<<PROMPT
You are an expert note-taking assistant. Your job is to analyze raw text and produce structured study notes.

Given the user's text, respond ONLY with a valid JSON object (no markdown fences) with the following keys:
- "title": A concise, descriptive title for these notes (max 80 chars)
- "summary": A clear, comprehensive summary paragraph (150–300 words)
- "key_points": An array of 5–10 concise bullet-point strings highlighting the most important concepts
- "tags": An array of 3–6 single-word or short-phrase topic tags (lowercase)

Be accurate, educational and clear. Do NOT add any text outside the JSON object.
PROMPT;

        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => $systemPrompt]]
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [['text' => 'Here is the text to convert into notes:\n\n' . $rawText]]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.4,
                'maxOutputTokens' => (int) GEMINI_MAX_TOKENS, // Ensure this is large enough in config
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'title' => ['type' => 'STRING'],
                        'summary' => ['type' => 'STRING'],
                        'key_points' => [
                            'type' => 'ARRAY',
                            'items' => ['type' => 'STRING']
                        ],
                        'tags' => [
                            'type' => 'ARRAY',
                            'items' => ['type' => 'STRING']
                        ]
                    ],
                    'required' => ['title', 'summary', 'key_points', 'tags']
                ]
            ]
        ];

        $response = self::call($payload);

        // ── Parse AI JSON output ──────────────────────────────
        // Gemini Response Structure: $response['candidates'][0]['content']['parts'][0]['text']
        $content = '';
        if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            $content = $response['candidates'][0]['content']['parts'][0]['text'];
        }

        $content = trim($content);

        // Strip possible markdown code fences (even with responseMimeType, it occasionally adds them)
        $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
        $content = preg_replace('/\s*```$/', '', trim($content));

        $parsed = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($parsed)) {
            $jsonError = json_last_error_msg();
            // Get end of content to see if it was cut off, or just show the whole thing if it's short
            $snippet = strlen($content) > 500 ? substr($content, 0, 250) . '...[TRUNCATED]...' . substr($content, -250) : $content;
            throw new RuntimeException("AI returned invalid JSON ($jsonError). Raw snippet: " . $snippet);
        }

        // Validate required keys
        foreach (['title', 'summary', 'key_points', 'tags'] as $key) {
            if (!array_key_exists($key, $parsed)) {
                throw new RuntimeException("AI response missing required key: {$key}");
            }
        }

        // Usage statistics
        $usage = [
            'prompt_tokens'     => $response['usageMetadata']['promptTokenCount'] ?? 0,
            'completion_tokens' => $response['usageMetadata']['candidatesTokenCount'] ?? 0,
            'total_tokens'      => $response['usageMetadata']['totalTokenCount'] ?? 0,
        ];

        return [
            'title'      => (string) ($parsed['title']   ?? ''),
            'summary'    => (string) ($parsed['summary']  ?? ''),
            'key_points' => (array)  ($parsed['key_points'] ?? []),
            'tags'       => (array)  ($parsed['tags']     ?? []),
            'usage'      => $usage,
            'model'      => GEMINI_MODEL,
        ];
    }

    // ── Low-level cURL POST to Gemini ────────────────────────
    private static function call(array $payload, int $maxRetries = 3): array {
        $url = GEMINI_API_URL . GEMINI_MODEL . ':generateContent?key=' . GEMINI_API_KEY;
        $encodedPayload = json_encode($payload);
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $encodedPayload,
                CURLOPT_TIMEOUT        => 120,
                CURLOPT_SSL_VERIFYPEER => false,              // Bypass SSL verification for localhost
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_ENCODING       => '',                 // Accept all encodings (gzip) to shrink payload
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1, // Force HTTP/1.1 to prevent HTTP/2 stalls
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                ],
            ]);

            $raw      = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr  = curl_error($ch);
            curl_close($ch);

            if ($curlErr) {
                if ($attempt < $maxRetries) {
                    sleep((int)pow(2, $attempt));
                    continue;
                }
                throw new RuntimeException("cURL error: {$curlErr}");
            }

            if ($raw === false || empty($raw)) {
                if ($attempt < $maxRetries) {
                    sleep((int)pow(2, $attempt));
                    continue;
                }
                throw new RuntimeException("Empty response from Gemini (HTTP {$httpCode}).");
            }

            $data = json_decode($raw, true);

            if ($httpCode !== 200) {
                $errMsg = $data['error']['message'] ?? "HTTP {$httpCode}";
                
                // If it's a 503 Overloaded / High Demand error, retry with exponential backoff
                if (($httpCode === 503 || strpos(strtolower($errMsg), 'high demand') !== false || strpos(strtolower($errMsg), 'overloaded') !== false) && $attempt < $maxRetries) {
                    sleep((int)pow(2, $attempt));
                    continue; // Retry
                }
                
                throw new RuntimeException("Gemini API error: {$errMsg}");
            }

            return $data;
        }
        throw new RuntimeException("Gemini API failed after $maxRetries attempts.");
    }
}
