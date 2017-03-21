<?php

require_once __DIR__ . '/vendor/autoload.php';

use Telegram\Bot\Api;

function getTelegramClient()
{
    $dotenv = new Dotenv\Dotenv(__DIR__);
    $dotenv->load();

    return new Api(getenv('TELEGRAM_BOT_TOKEN'));
}

$telegram = getTelegramClient();
echo "Starting bot with config {$telegram->getMe()}\n";

const S = 1000000; // 1000000 microseconds = 1 scond.
const POLL_DURATION = S; // In microseconds.

printf("\nStart polling Telegram api (will poll for updates every %d ms)\n\n", POLL_DURATION / 1000);

/**
 * Identifier of the first update to be returned.
 * Must be greater by one than the highest among the identifiers of previously received updates.
 * By default, updates starting with the earliest unconfirmed update are returned.
 * An update is considered confirmed as soon as getUpdates is called with an offset higher than its update_id.
 */
$offset = null;

function getUpdates($telegram, $offset)
{
    if ($offset === null) {
        $res = $telegram->getUpdates();
    } else {
        $res = $telegram->getUpdates(['offset' => $offset]);
    }

    // Update to the latest offset value.
    foreach ($res as $update) {
        if ($offset === null || $offset <= $update->getUpdateId()) {
            $offset = $update->getUpdateId() + 1;
        } else {
            continue; // We already processed this update.
        }

        $payload = json_encode($update);

        echo "\n\n";
        echo "---------------------------------------------------------------------------------------\n";
        echo $payload; // Log the updates to the console.
        echo "\n\n";

        // Send the updates to the Laravel application, just as if Telegram had used our webhook.
        $ch = curl_init('dockerhost/api/test');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response instead of printing.
        $response = curl_exec($ch);
        curl_close($ch);

        // Print Laravel response.
        var_dump($response);
        echo "---------------------------------------------------------------------------------------\n";
        echo "\n\n";
    }

    return $offset;
}

while (true) {
    $start = (int) microtime(true) * S;

    // get updates here
    $offset = getUpdates($telegram, $offset);
    // end get updates

    $end = (int) microtime(true) * S;
    $poll_took = $end - $start;

    $slept = 0;
    if ($poll_took < POLL_DURATION) {
        $slept = POLL_DURATION - $poll_took;
        usleep($slept);
    }

    printf("Poll took %d ms (slept %d µs)\n", $poll_took, $slept);
}