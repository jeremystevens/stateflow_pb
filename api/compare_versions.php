<?php
require __DIR__ . '/../vendor/autoload.php';
//use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\DiffOnlyOutputBuilder;
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
//$outputBuilder = new UnifiedDiffOutputBuilder("--- Original\n+++ New\n");
$outputBuilder = new DiffOnlyOutputBuilder('');

//$differ = new Differ($outputBuilder);
$differ = new Differ($outputBuilder);
//$differ = new Differ;
$diff = $differ->diff($results[$a] ?? '', $results[$b] ?? '');
//echo "<code><pre>" . htmlentities($diff) . "</pre></code>";
// Highlight diff lines
$highlighted = '';
foreach (explode("\n", $diff) as $line) {
    if (str_starts_with($line, '+')) {
        $highlighted .= '<div style="color: #3adb76;">' . htmlentities($line) . '</div>';
    } elseif (str_starts_with($line, '-')) {
        $highlighted .= '<div style="color: #ec5840;">' . htmlentities($line) . '</div>';
    } elseif (str_starts_with($line, '@@')) {
        $highlighted .= '<div style="color: #aaa;"><strong>' . htmlentities($line) . '</strong></div>';
    } else {
        $highlighted .= '<div>' . htmlentities($line) . '</div>';
    }
}

//echo '<div style="font-family: monospace; white-space: pre;">' . $highlighted . '</div>';
$added = 0;
$removed = 0;
$highlighted = '';

foreach (explode("\n", $diff) as $line) {
    if (str_starts_with($line, '+') && !str_starts_with($line, '+++')) {
        $added++;
        $highlighted .= '<div style="color: #3adb76;">' . htmlentities($line) . '</div>';
    } elseif (str_starts_with($line, '-') && !str_starts_with($line, '---')) {
        $removed++;
        $highlighted .= '<div style="color: #ec5840;">' . htmlentities($line) . '</div>';
    } elseif (str_starts_with($line, '@@')) {
        $highlighted .= '<div style="color: #aaa;"><strong>' . htmlentities($line) . '</strong></div>';
    } else {
        $highlighted .= '<div>' . htmlentities($line) . '</div>';
    }
}

echo "<div style='font-family: monospace; white-space: pre; color: #ccc;'>
<strong>+{$added}</strong> additions, <strong>-{$removed}</strong> deletions
--------------------------
</div>";
echo '<div style="font-family: monospace; white-space: pre;">' . $highlighted . '</div>';