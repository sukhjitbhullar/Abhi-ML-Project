<?php
require_once __DIR__ . '/config.php';
// function to generate URLs for routing
function route(string $name): string {
    //return BASE_URL . '/index.php?route=' . urlencode($name);
    return BASE_URL . '/' . urlencode($name);
}