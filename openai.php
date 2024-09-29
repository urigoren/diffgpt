<?php
require_once 'config.php';

function complete($txt, $prompt) {
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
                'content' => $prompt.'\n'.$txt
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

    return $decodedResponse;
}

function paraphrase_entire_email($txt) {
    
    $res = complete($txt, 'The following is an email draft about to be sent in a business setting.\nPlease paraphrases this email in a positive, professional and business friendly tone, as an American speaker would write it:');

    if (!isset($res['choices'][0]['message']['content'])) {
        throw new Exception('Unexpected API response format');
    }

    $ret = array();
    foreach ($res['choices'] as $choice) {
        $ret[] = $choice['message']['content'];
    }

    return $ret;
}

function paraphrase_sentence($txt) {
    
    $res = complete($txt, 'Given the following sentence, Please make sure its grammatically correct with no spelling mistakes. Suggest minor edits that preserve the meaning of the sentence but make it more positive and business friendly:');

    if (!isset($res['choices'][0]['message']['content'])) {
        throw new Exception('Unexpected API response format');
    }

    $ret = array();
    foreach ($res['choices'] as $choice) {
        $ret[] = $choice['message']['content'];
    }

    return $ret;
}


$userText = $_POST['txt'];
$mode = $_POST['mode'] ?? 'email';

try {
    if ($mode=='email')
    {
        $paraphrasedText = paraphrase_entire_email($userText);
    }
    elseif ($mode=='sentence')
    {
        $paraphrasedText = paraphrase_sentence($userText);
    }
    else {
        echo '{"error": "' .$mode.' is an unknown mode"}';    
    }
    echo json_encode($paraphrasedText);
} catch (Exception $e) {
    echo '{"error": "' . $e->getMessage() . '"}';
}