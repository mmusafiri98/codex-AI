<?php
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["prompt"])) {
    $prompt = $_POST["prompt"];

    $headers = [
        "Content-Type: application/json",
        "Authorization: Bearer TON_API_KEY_COHERE"
    ];

    $data = [
        "model" => "command-a-03-2025",
        "messages" => [
            [
                "role" => "user",
                "content" => $prompt . "\n\nIMPORTANT : Réponds uniquement avec du code valide."
            ]
        ]
    ];

    $ch = curl_init("https://api.cohere.ai/v2/chat");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $response = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($response, true);

    $code = "";
    if (isset($json["message"]["content"])) {
        foreach ($json["message"]["content"] as $part) {
            if ($part["type"] === "text") {
                $code .= $part["text"];
            }
        }
    }

    // Nettoyage markdown ```lang
    $code = preg_replace('/```[a-zA-Z]*\n?/', '', $code);
    $code = str_replace('```', '', $code);
    $code = trim($code);

    // Détection langage + filename
    $filename = "code.txt";
    if (str_contains($code, "<html")) $filename = "index.html";
    elseif (str_contains($code, "def ")) $filename = "main.py";
    elseif (str_contains($code, "<?php")) $filename = "script.php";
    elseif (str_contains($code, "function") || str_contains($code, "console.log")) $filename = "script.js";

    header("Content-Type: application/json");
    echo json_encode([
        "code" => $code,
        "filename" => $filename
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Codex AI – Générateur de code</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background:#0f172a; color:white }
#codeOutput { background:#020617; color:#22c55e; padding:15px; border-radius:8px; white-space:pre-wrap; overflow:auto; font-family:monospace; height:100% }
iframe { width:100%; height:100%; border-radius:8px; border:2px solid #2563eb }
.section { height:calc(100vh - 180px) }
</style>
</head>
<body>

<header class="bg-primary text-center py-3">
<h1>🤖 Codex AI</h1>
</header>

<div class="container mt-3">
<form id="promptForm" class="d-flex gap-2">
<textarea id="prompt" class="form-control" rows="3" placeholder="Ex: crée une fonction python..."></textarea>
<button class="btn btn-success">Générer</button>
</form>
</div>

<div class="container-fluid section mt-3">
<div class="row h-100 g-3">
<div class="col-md-6 h-100 d-flex flex-column">
<h5>📜 Code</h5>
<div id="codeOutput" class="flex-grow-1"></div>
<button id="downloadBtn" class="btn btn-outline-info mt-2 d-none">⬇ Télécharger le fichier</button>
</div>
<div class="col-md-6 h-100 d-flex flex-column">
<h5>🖥 Preview</h5>
<iframe id="preview" class="flex-grow-1"></iframe>
</div>
</div>
</div>

<script>
const form = document.getElementById("promptForm");
const output = document.getElementById("codeOutput");
const preview = document.getElementById("preview");
const downloadBtn = document.getElementById("downloadBtn");

form.addEventListener("submit", async e => {
    e.preventDefault();
    output.textContent = "⏳ Génération...";
    preview.srcdoc = "";
    downloadBtn.classList.add("d-none");

    const res = await fetch("", {
        method: "POST",
        body: new URLSearchParams({ prompt: prompt.value })
    });

    const data = await res.json();

    if (!data.code) {
        output.textContent = "❌ Erreur API";
        return;
    }

    output.textContent = data.code;

    // Preview HTML
    if (data.filename.endsWith(".html")) {
        preview.srcdoc = data.code;
    }

    // Download
    const blob = new Blob([data.code], { type: "text/plain" });
    const url = URL.createObjectURL(blob);

    downloadBtn.onclick = () => {
        const a = document.createElement("a");
        a.href = url;
        a.download = data.filename;
        a.click();
    };

    downloadBtn.classList.remove("d-none");
});
</script>

</body>
</html>

