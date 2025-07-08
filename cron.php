<?php
session_start();

$statusFile = __DIR__ . '/status.txt';
$logFile = __DIR__ . '/log.txt';
$externalScript = __DIR__ . '/includes/cleanup_orphan_references.php';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'start') {
        file_put_contents($statusFile, 'running');
        echo json_encode(['status' => 'started']);
        exit;
    }

    if ($action === 'stop') {
        file_put_contents($statusFile, 'stopped');
        echo json_encode(['status' => 'stopped']);
        exit;
    }

    if ($action === 'check') {
        $status = trim(@file_get_contents($statusFile));
        echo json_encode(['status' => $status]);
        exit;
    }

    if ($action === 'runScript') {
        if (trim(@file_get_contents($statusFile)) === 'running') {
            ob_start();
            include $externalScript; // run your external script
            ob_end_clean();

            $now = date('Y-m-d H:i:s');
            file_put_contents($logFile, "Script ran at $now\n", FILE_APPEND);
            echo json_encode(['ran' => true, 'timestamp' => $now]);
        } else {
            echo json_encode(['ran' => false]);
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Script Runner</title>
    <style>
        button { padding: 10px 20px; margin: 10px; font-size: 16px; }
        #log { margin-top: 20px; white-space: pre-wrap; background: #f0f0f0; padding: 10px; max-height: 300px; overflow-y: auto; }
    </style>
</head>
<body>
    <h2>Start/Stop External Script Runner</h2>
    <button onclick="startLoop()">Start</button>
    <button onclick="stopLoop()">Stop</button>

    <div id="log">Log Output:</div>

    <script>
        let intervalId = null;

        function startLoop() {
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=start'
            }).then(() => {
                if (!intervalId) {
                    intervalId = setInterval(runScript, 600000); // 10 minutes
                    runScript(); // run immediately on start
                }
            });
        }

        function stopLoop() {
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=stop'
            }).then(() => {
                clearInterval(intervalId);
                intervalId = null;
            });
        }

        function runScript() {
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=runScript'
            })
            .then(res => res.json())
            .then(data => {
                if (data.ran && data.timestamp) {
                    document.getElementById('log').textContent += `\n[✓] Ran script at ${data.timestamp}`;
                }
            });
        }
    </script>
</body>
</html>
