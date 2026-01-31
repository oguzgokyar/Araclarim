<?php
require_once 'AppLogic.php';

$models = [
    'gemini-2.5-flash',
    'gemini-2.5-pro',
    'gemini-2.0-flash',
    'gemini-2.0-flash-001',
    'gemini-1.5-flash',
    'gemini-1.5-pro',
    'gemini-pro'
];

$app = new AppLogic();
$settings = $app->getSettings();
$apiKey = $settings['openai_api_key'];

foreach ($models as $model) {
    echo "Testing model: $model ... ";
    
    $url = "https://generativelanguage.googleapis.com/v1beta/models/$model:generateContent?key=" . $apiKey;
    $data = [
        "contents" => [
            ["parts" => [["text" => "Hello"]]]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200) {
        echo "SUCCESS! (Use this)\n";
        exit;
    } else {
        $err = json_decode($response, true);
        echo "FAILED ($httpCode) - " . ($err['error']['message'] ?? 'Unknown') . "\n";
    }
}
echo "No working models found.\n";
