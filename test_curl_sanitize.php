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
        'maxOutputTokens' => 1500,
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
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
]);
$raw = curl_exec($ch);
curl_close($ch);

$data = json_decode($raw, true);
$textNode = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
echo "Original Decode Error: " . (json_decode($textNode) ? 'NONE' : json_last_error_msg()) . "\n";

// Sanitize 1: Regex replace control chars except newline
$s1 = preg_replace('/[\x00-\x09\x0B-\x1F\x7F]/', '', $textNode);
// Replace literal newlines with escaped newlines
$s1 = str_replace("\n", "\\n", $s1);
$s1 = str_replace("\r", "", $s1);

echo "S1 Decode Error: " . (json_decode($s1) ? 'NONE' : json_last_error_msg()) . "\n";
if (json_decode($s1)) echo "S1 works!\n";

// Find exactly where the error is
if (!json_decode($textNode)) {
    for ($i = 0; $i < strlen($textNode); $i++) {
        $c = ord($textNode[$i]);
        if ($c < 32 && $c != 10 && $c != 13) echo "Found bad char: $c at pos $i\n";
    }
}
