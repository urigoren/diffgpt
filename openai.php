<?php
require_once 'config.php';

function complete($txt, $prompt) {
    if (!defined('OPENAI_API_KEY')) {
        throw new Exception('OPENAI_API_KEY is not defined');
    }

    $data = [
        'model' => 'gpt-4o-mini',
        'temperature' => 1.5,
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
        $ret[] = array('body' =>$choice['message']['content']);
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
        $ret[] = array('body' =>$choice['message']['content']);
    }

    return $ret;
}

function duplicate_text($txt) {
    $ret = array();
    for ($i = 0; $i < NUM_SUGGESTIONS; $i++) {
        $ret[] = array('body' => $txt);
    }
    return $ret;
}


$userText = $_POST['txt'];
$mode = $_POST['mode'] ?? 'email';
$changes = $_POST['changes'] ?? '0';

if (($changes == '0') && (NUM_SUGGESTIONS==4)) {
    // no changes on UI, spare the openai api call on first load
    $paraphrasedText = array(
        array('tags'=>array('friendly', 'informal'), 'body' =>"Hi John,\n\nI'm not feeling well and won't be able to make it tomorrow. Would you be available sometime next week to reschedule? Please let me know what works for you.\n\nBest,\n[Your Name]"),
        array('tags'=>array('apologetic', 'informal'),'body' =>"Dear John,\nUnfortunately, I'm feeling unwell and won't be able to attend tomorrow. Could we possibly reschedule for sometime next week? Please let me know your availability.\n\nBest regards,\n[Your Name]"),
        array('tags'=>array('formal'),'body' =>"Dear Mr. [Last Name],\n\nI regret to inform you that I am unwell and will be unable to attend our scheduled meeting tomorrow. Would it be possible to arrange an alternative time next week? Please let me know your availability at your earliest convenience.\n\nSincerely,\n[Your Full Name]"),
        array('tags'=>array('direct', 'informal'), 'body' =>"Hey John,\nI'm not feeling well, so I can't make it tomorrow. Are you free sometime next week?"));
    echo json_encode($paraphrasedText);
} else {
    try {
        if (strlen($userText)<MIN_CHARS_FOR_SUGGESTION) {
            $paraphrasedText = duplicate_text($userText);
        }
        elseif (strlen($userText)>MAX_CHARS_FOR_SUGGESTION) {
            $paraphrasedText = duplicate_text($userText);
        }
        elseif ($mode=='email')
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
}