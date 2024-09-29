<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Review my email</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
	<link rel="stylesheet" href="style.css"/>
</head>
<body>
	<div class="container">
		<div class="row">
			<div class="col-12">
				<h1>Review my email</h1>
				<p>Write your email below and click "Rephrase" to get a better version of it.</p>
			</div>
			<div class="col-12">
				<textarea id="original" class="form-control" rows="10">
Sorry Friday won't work for me, next week?
				</textarea>
				<button onclick="paraphrase()" class="btn btn-primary">Rephrase</button>
				<button onclick="copy()" class="btn btn-info">Copy</button>
				<button onclick="reset()" class="btn btn-secondary">Reset</button>
			</div>
		</div>
		<div class="row">
			<div class="col-12">
				&nbsp;
			</div>
		</div>
		<div class="row">
		<?php
		for ($i = 0; $i < NUM_SUGGESTIONS; $i++) {
			echo '<div class="col-6"><pre id="result'.$i.'"></pre></div>';
		}
		?>
		</div>

	</div>
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
    formData.append('txt', original.value);

    fetch('openai.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const suggestion = data;
		for (let i = 0; i < suggestion.length; i++) {
			showDiff(original.value, suggestion[i], results[i]);
			results[i].appendChild(document.createElement('br'));
			const acceptButton = document.createElement('button');
			acceptButton.textContent = 'Accept';
			acceptButton.className = 'btn btn-success';
			acceptButton.onclick = () => accept(suggestion[i]);
			results[i].appendChild(acceptButton);
		}
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function reset() {
	original.value = '';
	for (let i = 0; i < <?php echo NUM_SUGGESTIONS; ?>; i++) {
		results[i].textContent = '';
	}
}

function copy() {
	original.select();
	document.execCommand('copy');
}

function accept(txt) {
	original.value = txt;
	for (let i = 0; i < <?php echo NUM_SUGGESTIONS; ?>; i++) {
		results[i].textContent = '';
	}
}

</script>
</body>
</html>