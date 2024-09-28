<?php
require 'config.php';

function paraphrase($txt){
	$data = [
		'model' => 'gpt-3.5-turbo',
		'temperature' => 0.7,
		'n' => 5,
		'messages' => [	
					[
						'role' => 'system',
						'content' => 'You are a helpful assistant that paraphrases the user\'s email in a positive, professional and business friendly tone.'
					],
					[
						'role' => 'user',
						'content' => $txt
					]
				]	
			];
	$ch = curl_init('https://api.openai.com/v1/chat/completions');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	'Authorization: Bearer ' . OPENAI_API_KEY,
	'Content-Type: application/json'
	));
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
	$response = curl_exec($ch);
	curl_close($ch);
	if ($response === false) {
		echo 'Error: ' . curl_error($ch);
	} else {
		$decodedResponse = json_decode($response, true);
		if ( !array_key_exists('error', $decodedResponse) ){
			$decodedResponse = json_decode($decodedResponse['choices'][0]['message']['content']);
			}
	}
	return $decodedResponse;
	}

// parse json request
$request = json_decode(file_get_contents('php://input'), true);
// get user text from json request
$userText = $request['txt'];

//if no user text, redirect to index.html
if (!$userText) {?><!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Diff</title>
	<link rel="stylesheet" href="style.css"/>
</head>
<body>


<table>
	<tr>
		<td><div contenteditable="true" id="a">restaurant</div></td>
		<td><div contenteditable="true" id="b">aura</td>
		<td><div><pre id="result"></pre></div></td>
	</tr>
</table>
<button onclick="paraphrase()">Paraphrase</button>

<script src="diff.js"></script>
<script defer>
var a = document.getElementById('a');
var b = document.getElementById('b');
var result = document.getElementById('result');

function changed() {
	var fragment = document.createDocumentFragment();
	var diff;
	diff = Diff["diffWords"](a.textContent, b.textContent);

	for (var i=0; i < diff.length; i++) {

		if (diff[i].added && diff[i + 1] && diff[i + 1].removed) {
			var swap = diff[i];
			diff[i] = diff[i + 1];
			diff[i + 1] = swap;
		}

		var node;
		if (diff[i].removed) {
			node = document.createElement('del');
			node.appendChild(document.createTextNode(diff[i].value));
		} else if (diff[i].added) {
			node = document.createElement('ins');
			node.appendChild(document.createTextNode(diff[i].value));
		} else if (diff[i].chunkHeader) {
			node = document.createElement('span');
			node.setAttribute('class', 'chunk-header');
			node.appendChild(document.createTextNode(diff[i].value));
		} else {
			node = document.createTextNode(diff[i].value);
		}
		fragment.appendChild(node);
	}

	result.textContent = '';
	result.appendChild(fragment);
}

function paraphrase() {
	fetch('index.php', {
		method: 'POST',
		body: JSON.stringify({ txt: a.textContent })
	}).then(response => response.json()).then(data => {
		b.textContent = data.choices[0].message.content;
		changed();
	});
}
a.onpaste = a.onchange =
b.onpaste = b.onchange = changed;

if ('oninput' in a) {
	a.oninput = b.oninput = changed;
} else {
	a.onkeyup = b.onkeyup = changed;
}
</script>
</body>
</html>
<?php
} else {

	$response = paraphrase($userText);
	
	// print the response
	echo json_encode($response);
}
?>