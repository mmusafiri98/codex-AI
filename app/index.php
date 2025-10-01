<?php
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["prompt"])) {
    $prompt = $_POST["prompt"];

    // Préparer la requête pour Cohere Chat API
    $headers = [
        "Content-Type: application/json",
        "Authorization: " . "Bearer Uw540GN865rNyiOs3VMnWhRaYQ97KAfudAHAnXzJ"
    ];

    $data = [
        "model" => "command-a-03-2025",  // modèle chat supporté
        "messages" => [
            [
                "role" => "user",
                "content" => $prompt . "\n\nIMPORTANT : Réponds uniquement avec du code valide, sans texte ni explication."
            ]
        ]
    ];

    $ch = curl_init("https://api.cohere.ai/v2/chat");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $result = curl_exec($ch);
    curl_close($ch);

    header("Content-Type: application/json");
    echo $result;
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Codex AI - Code Generator (Chat API)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/pyodide/v0.23.4/full/pyodide.js"></script>
    <style>
        #codeOutput {
            background: #1e1e1e;
            color: #00ff00;
            padding: 15px;
            border-radius: 8px;
            height: 100%;
            white-space: pre-wrap;
            font-family: monospace;
            overflow-y: auto;
            position: relative;
        }

        iframe {
            width: 100%;
            height: 100%;
            border: 2px solid #3498db;
            border-radius: 8px;
            background: white;
        }

        .section-container {
            height: calc(100vh - 160px);
        }

        .spinner-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }
    </style>
</head>

<body>
    <header class="bg-primary text-white text-center py-3 mb-3">
        <h1>🤖 Codex AI - Code Generator</h1>
    </header>

    <div class="container mb-3">
        <form id="promptForm" class="d-flex flex-column flex-md-row gap-2">
            <textarea class="form-control" name="prompt" id="prompt" placeholder="Ex: Crée une fonction Python qui additionne deux nombres..." rows="3"></textarea>
            <button type="submit" class="btn btn-primary">Générer</button>
        </form>
    </div>

    <div class="container-fluid section-container">
        <div class="row h-100 g-3">
            <div class="col-12 col-md-6 h-100 d-flex flex-column">
                <h2>📜 Code généré</h2>
                <div id="codeOutput" class="flex-grow-1 d-flex align-items-center justify-content-center">
                    <!-- Spinner affiché pendant la génération -->
                    <div id="spinner" class="spinner-overlay d-none">
                        <div class="spinner-border text-success" role="status" style="width: 3rem; height: 3rem;"></div>
                        <p class="mt-2 text-white">Génération du code en cours...</p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 h-100 d-flex flex-column">
                <h2>🖥️ Monitor</h2>
                <iframe id="liveFrame" class="flex-grow-1"></iframe>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let pyodideReady = false;
        let pyodide;

        async function loadPyodideAsync() {
            pyodide = await loadPyodide();
            pyodideReady = true;
        }
        loadPyodideAsync();

        function typeWriterEffect(element, text, speed = 15, callback) {
            element.textContent = "";
            let i = 0;
            function typing() {
                if (i < text.length) {
                    element.textContent += text.charAt(i);
                    i++;
                    setTimeout(typing, speed);
                } else if (callback) {
                    callback();
                }
            }
            typing();
        }

        async function executeCode(language, code) {
            const monitor = document.getElementById("liveFrame");

            if (language === "html") {
                monitor.srcdoc = code;
            } else if (language === "js") {
                try {
                    let result = eval(code);
                    monitor.srcdoc = `<pre style="background:black;color:lime;padding:10px;">${result === undefined ? "" : result}</pre>`;
                } catch (e) {
                    monitor.srcdoc = `<pre style="background:black;color:red;padding:10px;">${e}</pre>`;
                }
            } else if (language === "python") {
                if (!pyodideReady) {
                    monitor.srcdoc = `<pre style="background:black;color:yellow;padding:10px;">Python engine loading...</pre>`;
                    await loadPyodideAsync();
                }
                try {
                    let output = await pyodide.runPythonAsync(`
import sys
import io
buf = io.StringIO()
sys.stdout = buf
${code}
buf.getvalue()
                    `);
                    monitor.srcdoc = `<pre style="background:black;color:lime;padding:10px;">${output}</pre>`;
                } catch (e) {
                    monitor.srcdoc = `<pre style="background:black;color:red;padding:10px;">${e}</pre>`;
                }
            } else {
                monitor.srcdoc = `<pre style="background:black;color:yellow;padding:10px;">Langage non supporté pour exécution</pre>`;
            }
        }

        document.getElementById("promptForm").addEventListener("submit", async (e) => {
            e.preventDefault();
            const prompt = document.getElementById("prompt").value.trim();
            if (!prompt) return;

            const outputElement = document.getElementById("codeOutput");
            const spinner = document.getElementById("spinner");
            spinner.classList.remove("d-none");
            outputElement.textContent = "";

            document.getElementById("liveFrame").srcdoc = "";

            const response = await fetch("", {
                method: "POST",
                body: new URLSearchParams({ prompt })
            });
            const data = await response.json();

            spinner.classList.add("d-none");

            let code = "";
            if (data.message && data.message.content && data.message.content.length > 0) {
                code = data.message.content[0].text.trim();

                const match = code.match(/```[a-zA-Z]*\n([\s\S]*?)```/);
                if (match) code = match[1].trim();
            } else {
                code = "❌ Erreur API: " + JSON.stringify(data, null, 2);
            }

            typeWriterEffect(outputElement, code, 10, async () => {
                let lang = "js";
                if (code.includes("<html") || code.includes("<body")) lang = "html";
                else if (prompt.toLowerCase().includes("python")) lang = "python";

                await executeCode(lang, code);
            });
        });
    </script>
</body>

</html>
