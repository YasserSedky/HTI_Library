<?php
// Simple proxy endpoint to Google AI Studio (Gemini) to keep API key on server
// Expects JSON: { "mode": "qa"|"summary", "question": "...", "context": "..." }

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([ 'error' => 'Method Not Allowed' ]);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) {
    echo json_encode([ 'error' => 'Invalid JSON body' ]);
    exit;
}

$mode = isset($data['mode']) && in_array($data['mode'], ['qa','summary']) ? $data['mode'] : 'qa';
$provider = isset($data['provider']) && in_array($data['provider'], ['google','openai']) ? $data['provider'] : 'google';
$question = trim($data['question'] ?? '');
$context = trim($data['context'] ?? '');

if ($mode === 'qa' && $question === '' && $context === '') {
    echo json_encode([ 'error' => 'Empty input' ]);
    exit;
}

// API keys (Google/OpenAI)
$googleKey = getenv('GOOGLE_API_KEY');
if (!$googleKey && isset($_SERVER['GOOGLE_API_KEY'])) $googleKey = $_SERVER['GOOGLE_API_KEY'];
if (!$googleKey && isset($_ENV['GOOGLE_API_KEY'])) $googleKey = $_ENV['GOOGLE_API_KEY'];
$openaiKey = getenv('OPENAI_API_KEY');
if (!$openaiKey && isset($_SERVER['OPENAI_API_KEY'])) $openaiKey = $_SERVER['OPENAI_API_KEY'];
if (!$openaiKey && isset($_ENV['OPENAI_API_KEY'])) $openaiKey = $_ENV['OPENAI_API_KEY'];
if (file_exists(__DIR__ . '/local_config.php')) {
    include __DIR__ . '/local_config.php';
    if (!$googleKey && isset($GOOGLE_API_KEY) && $GOOGLE_API_KEY) $googleKey = $GOOGLE_API_KEY;
    if (!$openaiKey && isset($OPENAI_API_KEY) && $OPENAI_API_KEY) $openaiKey = $OPENAI_API_KEY;
}
if ($provider==='google' && !$googleKey) { http_response_code(500); echo json_encode(['error'=>'Missing GOOGLE_API_KEY']); exit; }
if ($provider==='openai' && !$openaiKey) { http_response_code(500); echo json_encode(['error'=>'Missing OPENAI_API_KEY']); exit; }

function call_gemini($baseVersion, $model, $payload, $apiKey) {
    $url = 'https://generativelanguage.googleapis.com/' . $baseVersion . '/models/' . $model . ':generateContent?key=' . urlencode($apiKey);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [ 'Content-Type: application/json; charset=utf-8' ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    // In some local XAMPP setups, SSL CA bundle may be missing
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 25);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);
    return [ $resp, $httpCode, $curlErr ];
}

// Build prompt
$systemInstruction = "أنت مساعد أكاديمي لمكتبة HTI. تساعد الطلاب في: \n- تلخيص الكتب والمقالات بدقة، مع إبراز الأفكار الرئيسية والنقاط العملية.\n- الإجابة عن الأسئلة باختصار ودقة مع أمثلة موجزة عند الحاجة.\n- اقتراح محاور للبحث العلمي وخطوات منهجية مختصرة.\nقواعد: كن موجزًا وواضحًا، استخدم العربية الفصحى المبسطة، وتجنب اختلاق معلومات غير مؤكدة.";

$userParts = [];
if ($mode === 'summary') {
    $prompt = ($question !== '' ? ("تعليمات التلخيص: " . $question . "\n\n") : '') . "النص المراد تلخيصه:\n" . $context;
    $userParts[] = [ 'text' => $prompt ];
} else {
    $prompt = "السؤال:\n" . $question;
    if ($context !== '') $prompt .= "\n\nسياق/نص مساعد:\n" . $context;
    $userParts[] = [ 'text' => $prompt ];
}

$payload = [
    'contents' => [
        [ 'role' => 'user', 'parts' => $userParts ]
    ],
    'systemInstruction' => [ 'role' => 'system', 'parts' => [ [ 'text' => $systemInstruction ] ] ],
    'generationConfig' => [
        'temperature' => 0.3,
        'topK' => 40,
        'topP' => 0.9,
        'maxOutputTokens' => 1024
    ],
    'safetySettings' => [
        [ 'category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE' ],
        [ 'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE' ],
        [ 'category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE' ],
        [ 'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE' ]
    ]
];
if ($provider === 'openai') {
    // OpenAI Chat Completions
    $model = 'gpt-4o-mini';
    $url = 'https://api.openai.com/v1/chat/completions';
    $messages = [ [ 'role' => 'system', 'content' => $systemInstruction ] ];
    if ($mode === 'summary') {
        $messages[] = [ 'role' => 'user', 'content' => ($question? ("تعليمات التلخيص: ".$question."\n\n") : '') . "النص المراد تلخيصه:\n".$context ];
    } else {
        $msg = "السؤال:\n".$question; if ($context!=='') $msg .= "\n\nسياق/نص مساعد:\n".$context;
        $messages[] = [ 'role' => 'user', 'content' => $msg ];
    }
    $body = [ 'model' => $model, 'messages' => $messages, 'temperature' => 0.3 ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [ 'Content-Type: application/json', 'Authorization: Bearer '.$openaiKey ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($resp !== false && $code >= 200 && $code < 300) {
        $d = json_decode($resp, true);
        $answer = $d['choices'][0]['message']['content'] ?? '';
        echo json_encode([ 'ok' => true, 'mode' => $mode, 'answer' => $answer ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    http_response_code(500);
    echo json_encode([ 'error' => 'Upstream error', 'status' => $code, 'raw' => $resp, 'curl' => $err ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Google (default)
$model = 'gemini-pro';
$apiVersion = 'v1beta';
list($resp, $code, $err) = call_gemini($apiVersion, $model, $payload, $googleKey);
if ($resp !== false && $code >= 200 && $code < 300) {
    $data = json_decode($resp, true);
    $text = '';
    if (isset($data['candidates'][0]['content']['parts'])) {
        foreach ($data['candidates'][0]['content']['parts'] as $part) {
            if (isset($part['text'])) $text .= $part['text'];
        }
    }
    echo json_encode([ 'ok' => true, 'mode' => $mode, 'answer' => $text ], JSON_UNESCAPED_UNICODE);
    exit;
}
http_response_code(500);
echo json_encode([ 'error' => 'Upstream error', 'status' => $code, 'raw' => $resp, 'curl' => $err ], JSON_UNESCAPED_UNICODE);
?>

