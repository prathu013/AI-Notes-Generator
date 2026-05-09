<?php
require_once __DIR__ . '/config/config.php';

$systemPrompt = "You are an expert note-taking assistant. Your job is to analyze raw text and produce structured study notes.\n\nGiven the user's text, respond ONLY with a valid JSON object (no markdown fences) with the following keys:\n- \"title\": A concise, descriptive title for these notes (max 80 chars)\n- \"summary\": A clear, comprehensive summary paragraph (150–300 words)\n- \"key_points\": An array of 5–10 concise bullet-point strings highlighting the most important concepts\n- \"tags\": An array of 3–6 single-word or short-phrase topic tags (lowercase)\n\nBe accurate, educational and clear. Do NOT add any text outside the JSON object.";
$rawText = "artificial intelligence and machine learning";

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
        'maxOutputTokens' => (int) GEMINI_MAX_TOKENS,
        'responseMimeType' => 'application/json'
    ]
];

$url = GEMINI_API_URL . GEMINI_MODEL . ':generateContent?key=' . GEMINI_API_KEY;

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_TIMEOUT        => 120,
    CURLOPT_ENCODING       => '',
    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
    ],
]);
$raw = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    echo "CURL ERROR: $err\n";
    exit;
}

$data = json_decode($raw, true);
if (!$data && json_last_error() !== JSON_ERROR_NONE) {
    echo "Google API returned invalid JSON?! Raw:\n";
    echo $raw . "\n";
    exit;
}

if (isset($data['error'])) {
    echo "API ERROR:\n";
    print_r($data['error']);
    exit;
}

$textNode = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
if (!$textNode) {
    echo "Missing text node. Dump:\n";
    print_r($data);
    exit;
}

echo "TEXT NODE FROM GEMINI:\n";
echo $textNode . "\n";
echo "LENGTH: " . strlen($textNode) . "\n";

$parsed = json_decode($textNode, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "JSON DECODE FAILED on text node. Error: " . json_last_error_msg() . "\n";
} else {
    echo "JSON DECODE SUCCESS.\n";
}
