<?php
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["prompt"])) {
    $prompt = $_POST["prompt"];

    $headers = [
        "Content-Type: application/json",
        "Authorization: Bearer Uw540GN865rNyiOs3VMnWhRaYQ97KAfudAHAnXzJ"
    ];

    $payload = [
        "model" => "command-a-03-2025",
        "messages" => [[
            "role" => "user",
            "content" => $prompt . "\n\nRéponds uniquement avec du code valide."
        ]]
    ];

    $ch = curl_init("https://api.cohere.ai/v2/chat");
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);

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

    // Nettoyage markdown
    $code = preg_replace('/```[a-zA-Z]*\n?/', '', $code);
    $code = str_replace('```', '', $code);
    $code = trim($code);

    // Détection langage
    $lang = "text";
    if (str_contains($code, "<html")) $lang = "html";
    elseif (str_contains($code, "console.log") || str_contains($code, "function")) $lang = "js";
    elseif (str_contains($code, "def ")) $lang = "python";
    elseif (str_contains($code, "<?php")) $lang = "php";

    echo json_encode([
        "code" => $code,
        "lang" => $lang
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Codex AI – Monitor universel</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#020617;color:white}
#monitor{
    background:black;
    border-radius:10px;
    border:2px solid #22c55e;
    height:100%;
    width:100%
}
#code{
    background:#020617;
    color:#22c55e;
    padding:15px;
    border-radius:10px;
    white-space:pre-wrap;
    font-family:monospace;
    height:100%;
    overflow:auto
}
.section{height:calc(100vh - 170px)}
</style>
</head>

<body>
<header class="bg-primary text-center py-3">
<h1>🤖 Codex AI — Universal Monitor</h1>
</header>

<div class="container mt-3">
<form id="form" class="d-flex gap-2">
<textarea id="prompt" class="form-control" rows="3" placeholder="Ex: calcule 2+2 / crée une page html / écris un script JS"></textarea>
<button class="btn btn-success">Générer</button>
</form>
</div>

<div class="container-fluid section mt-3">
<div class="row h-100 g-3">
<div class="col-md-6 h-100 d-flex flex-column">
<h5>📜 Code généré</h5>
<div id="code" class="flex-grow-1"></div>
</div>
<div class="col-md-6 h-100 d-flex flex-column">
<h5>🖥 Monitor (OUTPUT FINAL)</h5>
<iframe id="monitor" class="flex-grow-1"></iframe>
</div>
</div>
</div>

<script>
const form = document.getElementById("form");
const codeBox = document.getElementById("code");
const monitor = document.getElementById("monitor");

form.addEventListener("submit", async e => {
    e.preventDefault();
    codeBox.textContent = "⏳ Génération...";
    monitor.srcdoc = "";

    const res = await fetch("", {
        method: "POST",
        body: new URLSearchParams({ prompt: prompt.value })
    });

    const data = await res.json();
    const code = data.code || "Erreur API";

    codeBox.textContent = code;

    // TOUJOURS afficher quelque chose dans le monitor
    if (data.lang === "html") {
        monitor.srcdoc = code;
    }
    else if (data.lang === "js") {
        monitor.srcdoc = `
        <pre style="color:lime;background:black;padding:15px">
<script>
try {
    let result = eval(\`${code}\`);
    document.write(result !== undefined ? result : "✔ Script exécuté");
} catch(e) {
    document.write("❌ " + e);
}
<\/script>
        </pre>`;
    }
    else {
        monitor.srcdoc = `
        <pre style="color:lime;background:black;padding:15px;white-space:pre-wrap">
${code}
        </pre>`;
    }
});
</script>

</body>
</html>

