<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>DiffGPT</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
	<link rel="stylesheet" href="style.css"/>
</head>
<body>
	<div class="container">
		<div class="row my-3">
			<div class="col-12">
				<h1 class="h1">Diff-GPT</h1>
				<p>Enter your email below and click “Suggest Edits” to receive AI-powered suggestions, displayed in a user-friendly diff format for easy review.</p>
			</div>
		</div>
		<div class="row my-3">
			<div class="col-12">
				<textarea id="original" class="form-control" rows="10">Hi John,
I can't make it tomorrow, I'm sick. are you available next week?</textarea>
			</div>
		</div>
		<div class="row my-3">
			<div class="col-12">
				<div class="d-flex flex-wrap gap-2">
					<button onclick="paraphrase()" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="top" title="CTRL+Enter">Suggest Edits</button>
					<button onclick="undo()" class="btn btn-danger" data-bs-toggle="tooltip" data-bs-placement="top" title="CTRL+Z">Undo</button>
					<button onclick="copy()" class="btn btn-info">Copy</button>
					<button onclick="reset()" class="btn btn-secondary" data-bs-toggle="tooltip" data-bs-placement="top" title="CTRL+0">Clear</button>
				</div>
			</div>
		</div>
		<div class="row my-3 d-none" id="resultsRow">
		<?php
		for ($i = 0; $i < NUM_SUGGESTIONS; $i++) {
			echo '<div class="col-sm-12 col-md-6 mb-3"><div class="card"><div class="card-body"><pre id="result'.$i.'"></pre></div></div></div>';
		}
		?>
		</div>
		<footer>
		<p class="text-center text-body-secondary">We hope you enjoy this free tool, courtesy of <a href="https://www.argmaxml.com">Argmax</a></p>
		</footer>
	</div>
	<script src="diff.js"></script>
	<script defer>
	var original = document.getElementById('original');
let results = [];
const resultsRow=document.getElementById("resultsRow");
<?php
for ($i = 0; $i < NUM_SUGGESTIONS; $i++) {
	echo 'results['.$i.'] = document.getElementById("result'.$i.'");';
}
?>

function replaceSelectedText(textarea, newText) {
    // Get the current selection
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;

	if (start>=end) {
		return;
	}

    const currentText = textarea.value;
    
    const updatedText = currentText.substring(0, start) + newText + currentText.substring(end);
    
    textarea.value = updatedText;
    textarea.setSelectionRange(start + newText.length, start + newText.length);
}

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
	let origVal = original.value;
	if (original.selectionStart<original.selectionEnd) {
		origVal = origVal.substring(original.selectionStart,original.selectionEnd);
		formData.append('txt', origVal);
		formData.append('mode', 'sentence');
		formData.append('changes', changelog.length);
	} else {
		origVal = origVal.replace(
			/(?<![A-Z][a-z]|\d)([.!?])\s+(?=[A-Z]|["""']|$)/g,
			"$1\n"
		);
		formData.append('txt', origVal);
		formData.append('mode', 'email');
		formData.append('changes', changelog.length);
	}

    fetch('openai.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        suggestion = data.map(x=>x.replace(
			/(?<![A-Z][a-z]|\d)([.!?])\s+(?=[A-Z]|["""']|$)/g,
			"$1\n"
		));
		for (let i = 0; i < suggestion.length; i++) {
			showDiff(origVal, suggestion[i], results[i]);
			results[i].appendChild(document.createElement('br'));
			const acceptButton = document.createElement('button');
			acceptButton.textContent = 'Accept Change '+(1+i);
			acceptButton.className = 'btn btn-success my-2';
			acceptButton.setAttribute('data-bs-toggle', 'tooltip');
			acceptButton.setAttribute('data-bs-placement', 'top');
			acceptButton.setAttribute('title', 'CTRL+'+(1+i));
			acceptButton.onclick = () => accept(suggestion[i]);
			results[i].appendChild(acceptButton);
		}
		resultsRow.classList.remove('d-none');
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function reset() {
	changelog.push(original.value);
	original.value = '';
	resultsRow.classList.add('d-none');
}

function copy() {
	original.select();
	document.execCommand('copy');
}

function accept(txt) {
	changelog.push(original.value);
	if(original.selectionStart<original.selectionEnd) {
		replaceSelectedText(original, txt);
	} else {
		original.value = txt;
	}
	resultsRow.classList.add('d-none');
	
}

function undo() {
	if(changelog.length<=0) {
		return;
	}
	original.value = changelog.pop();
	resultsRow.classList.remove('d-none');
}

let suggestion = [];
let changelog = [];

document.getElementById('original').addEventListener('keydown', function(event) {
    if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
        event.preventDefault();
        paraphrase();
    }
    if ((event.ctrlKey || event.metaKey) && ((event.key === 'z') || (event.key === 'Z'))) {
        event.preventDefault();
        undo();
    }
    if (event.key === 'Enter' || event.key === 'Delete' || event.key === 'Backspace') {
		changelog.push(original.value);
    }
    if ((event.ctrlKey || event.metaKey) && event.key === '0') {
        event.preventDefault();
        reset();
    }
<?php
for ($i = 0; $i < NUM_SUGGESTIONS; $i++) {
	echo "if ((event.ctrlKey || event.metaKey) && event.key === '".(1+$i)."') {";
	echo "event.preventDefault();";
	echo "accept(suggestion[$i]);";
	echo "}";
}
?>
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<?php if(SMARTLOOK_ID) {?><script type='text/javascript'>
  window.smartlook||(function(d) {
    var o=smartlook=function(){ o.api.push(arguments)},h=d.getElementsByTagName('head')[0];
    var c=d.createElement('script');o.api=new Array();c.async=true;c.type='text/javascript';
    c.charset='utf-8';c.src='https://web-sdk.smartlook.com/recorder.js';h.appendChild(c);
    })(document);
    smartlook('init', '<?php echo SMARTLOOK_ID;?>', { region: 'eu' });
	smartlook('record', { forms: true, ips: true })
</script><?php }?>
</body>
</html>