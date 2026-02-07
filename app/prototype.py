<?php
// ================= CONFIG =================
$API_KEY = "Bearer Uw540GN865rNyiOs3VMnWhRaYQ97KAfudAHAnXzJ";

// ================= AJAX =================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["prompt"])) {

    $prompt = $_POST["prompt"];

    $payload = [
        "model" => "command-a-03-2025",
        "messages" => [[
            "role" => "user",
            "content" =>
                $prompt .
                "\n\nIMPORTANT : Réponds UNIQUEMENT avec du code valide. Aucun texte. Aucun commentaire hors code."
        ]]
    ];

    $ch = curl_init("https://api.cohere.ai/v2/chat");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Authorization: $API_KEY"
        ],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    $code = $data["message"]["content"][0]["text"] ?? "";

    // ================= LANG DETECTION =================
    $filename = "code.txt";

    if (strpos($code, "<html") !== false) $filename = "index.html";
    elseif (strpos($code, "def ") !== false) $filename = "main.py";
    elseif (strpos($code, "console.log") !== false || strpos($code, "function") !== false) $filename = "script.js";
    elseif (strpos($code, "<?php") !== false) $filename = "index.php";

    // ================= FILE GENERATION =================
    $tmpPath = sys_get_temp_dir() . "/" . $filename;
    file_put_contents($tmpPath, $code);

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
<title>Codex AI — Générateur de Code</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{background:#0e0e0e;color:#00ff99}
#codeOutput{background:#111;color:#00ff00;font-family:monospace;padding:15px;border-radius:8px;white-space:pre-wrap;height:100%;overflow:auto}
iframe{width:100%;height:100%;border:2px solid #00ff99;border-radius:8px}
.section{height:calc(100vh - 160px)}
.download-btn{margin-top:10px}
</style>
</head>

<body>
<header class="bg-dark text-center text-success py-3">
<h1>🤖 Codex AI — Code Generator</h1>
</header>

<div class="container my-3">
<form id="promptForm" class="d-flex gap-2">
<textarea id="prompt" class="form-control" rows="3" placeholder="Ex: Crée une page HTML avec bouton"></textarea>
<button class="btn btn-success">Générer</button>
</form>
</div>

<div class="container-fluid section">
<div class="row h-100 g-3">

<div class="col-md-6 d-flex flex-column">
<h4>📜 Code généré</h4>
<div id="codeOutput" class="flex-grow-1"></div>
<button id="downloadBtn" class="btn btn-outline-success download-btn d-none">⬇ Télécharger le fichier</button>
</div>

<div class="col-md-6 d-flex flex-column">
<h4>🖥️ Preview</h4>
<iframe id="preview" class="flex-grow-1"></iframe>
</div>

</div>
</div>

<script>
let generatedCode = "";
let generatedFile = "";

document.getElementById("promptForm").addEventListener("submit", async e => {
    e.preventDefault();

    const prompt = document.getElementById("prompt").value.trim();
    if (!prompt) return;

    const output = document.getElementById("codeOutput");
    const iframe = document.getElementById("preview");
    const downloadBtn = document.getElementById("downloadBtn");

    output.textContent = "⏳ Génération en cours...";
    iframe.srcdoc = "";
    downloadBtn.classList.add("d-none");

    const res = await fetch("", {
        method: "POST",
        body: new URLSearchParams({ prompt })
    });

    const data = await res.json();
    generatedCode = data.code;
    generatedFile = data.filename;

    output.textContent = generatedCode;

    // Preview HTML
    if (generatedFile.endsWith(".html")) {
        iframe.srcdoc = generatedCode;
    }

    downloadBtn.textContent = `⬇ Télécharger ${generatedFile}`;
    downloadBtn.classList.remove("d-none");
});

// ================= DOWNLOAD =================
document.getElementById("downloadBtn").onclick = () => {
    const blob = new Blob([generatedCode], { type: "text/plain" });
    const a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = generatedFile;
    a.click();
};
</script>
</body>
</html>
