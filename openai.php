<?php
require_once 'config.php';
//show all errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
function paraphrase($txt) {
    if (!defined('OPENAI_API_KEY')) {
        throw new Exception('OPENAI_API_KEY is not defined');
    }

    $data = [
        'model' => 'gpt-3.5-turbo',
        'temperature' => 0.7,
        'n' => NUM_SUGGESTIONS,
        'messages' => [    
            [
                'role' => 'system',
                'content' => 'You are a helpful assistant that helps a non-native English speaker to improve their email writing skills. Focus on spelling, grammar and punctuation as well as word choice. Try to keep the same meaning but rephrase the text.'
            ],
            [
                'role' => 'user',
                'content' => 'Please paraphrases this email in a positive, professional and business friendly tone, as an American speaker would write it. Please do not suggest email subject line or signature, just the body of the email and try to keep the same meaning as the original email: \n'.$txt
            ]
        ]    
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . OPENAI_API_KEY,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $response = curl_exec($ch);
    
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception('cURL Error: ' . $error);
    }
    
    curl_close($ch);
    
    $decodedResponse = json_decode($response, true);
    
    if (isset($decodedResponse['error'])) {
        throw new Exception('API Error: ' . $decodedResponse['error']['message']);
    }
    
    if (!isset($decodedResponse['choices'][0]['message']['content'])) {
        throw new Exception('Unexpected API response format');
    }

    $ret = array();
    foreach ($decodedResponse['choices'] as $choice) {
        $ret[] = $choice['message']['content'];
    }

    return $ret;
}


$userText = $_POST['txt'];

try {
    $paraphrasedText = paraphrase($userText);
    echo json_encode($paraphrasedText);
} catch (Exception $e) {
    echo '{"error": "' . $e->getMessage() . '"}';
}