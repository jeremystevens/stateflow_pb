<?php
require __DIR__ . '/../vendor/autoload.php';
use SebastianBergmann\Diff\Differ;

$db = new PDO('sqlite:' . __DIR__ . '/../database/pastebin.db');
$pasteId = $_GET['paste_id'];
$a = $_GET['a'];
$b = $_GET['b'];

$q = $db->prepare("SELECT version_number, content FROM paste_versions WHERE paste_id = ? AND version_number IN (?, ?)");
$q->execute([$pasteId, $a, $b]);
$results = [];
while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
    $results[$row['version_number']] = $row['content'];
}

$differ = new Differ;
$diff = $differ->diff($results[$a] ?? '', $results[$b] ?? '');
echo "<code><pre>" . htmlentities($diff) . "</pre></code>";
