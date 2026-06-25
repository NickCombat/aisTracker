<?php
// php test_ais.php
require 'vendor/autoload.php';

use WebSocket\Client;

$apiKey = "28e5a1928748ee27ea0b61c7a901d8ddc1cb01fb";
//{"APIKey":"28e5a1928748ee27ea0b61c7a901d8ddc1cb01fb","BoundingBoxes":[[[-90,-180],[90,180]]]}
$client = new Client("wss://stream.aisstream.io/v0/stream", ['timeout' => 60]);

$message = json_encode([
    "APIKey" => $apiKey,
    "BoundingBoxes" => [[[-90, -180], [90, 180]]]
]);

echo "Sende Subscription...\n";
$client->text($message);

while (true) {
    try {
        echo "Warte auf Daten...\n";
        $data = $client->receive();
        echo substr($data, 0, 100) . "...\n"; // Nur die ersten 100 Zeichen ausgeben
    } catch (\Exception $e) {
        echo "Fehler: " . $e->getMessage() . "\n";
        break;
    }
}