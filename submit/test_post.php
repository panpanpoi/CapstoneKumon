<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "<h1>POST request received </h1>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
} else {
    echo "<h1>No POST data </h1>";
    echo "<p>Request method: " . $_SERVER['REQUEST_METHOD'] . "</p>";
}

