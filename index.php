<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Diff</title>
	<link rel="stylesheet" href="style.css"/>
</head>
<body>

<div contenteditable="true" id="original">restaurant</div>
<button onclick="paraphrase()">Paraphrase</button>
<br />
<table>
	<tr>
		<?php
		for ($i = 0; $i < NUM_SUGGESTIONS; $i++) {
			echo '<td><div><pre id="result'.$i.'"></pre></div></td>';
		}
		?>
	</tr>
</table>

<script src="diff.js"></script>
<script defer>
var original = document.getElementById('original');
let results = [];
<?php
for ($i = 0; $i < NUM_SUGGESTIONS; $i++) {
	echo 'results['.$i.'] = document.getElementById("result'.$i.'");';
}
?>


function showDiff(original,modified,result) {
	var fragment = document.createDocumentFragment();
	var diff;
	diff = Diff["diffWords"](original, modified);

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
    const formData = new FormData();
    formData.append('txt', original.textContent);

    fetch('openai.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const suggestion = data;
		for (let i = 0; i < suggestion.length; i++) {
			showDiff(original.textContent, suggestion[i], results[i]);
		}
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

</script>
</body>
</html>