<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/rss+xml");

$url = "https://blog.softwareveteran.dev/feeds/posts/default?alt=rss";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

if ($response === false) {
    http_response_code(500);
    echo "Error fetching data from the API.";
} else {
    echo $response;
}
?>