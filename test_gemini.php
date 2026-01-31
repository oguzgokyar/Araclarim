<?php
require_once 'AppLogic.php';

try {
    $app = new AppLogic();
    $settings = $app->getSettings();
    $apiKey = $settings['openai_api_key']; // Using the field we repurpossed

    if (empty($apiKey)) {
        die("API Key is empty in settings.json\n");
    }

    $url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $apiKey;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    
    if (isset($data['models'])) {
        echo "Approved Models:\n";
        foreach ($data['models'] as $model) {
            if (strpos($model['supportedGenerationMethods'][0] ?? '', 'generateContent') !== false) {
                 echo "- " . $model['name'] . "\n";
            }
        }
    } else {
        echo "Error: " . $response . "\n";
    }

} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
