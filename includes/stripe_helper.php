<?php
/**
 * Stripe REST API helpers — uses cURL directly (no SDK required).
 */

function stripe_post(string $endpoint, array $data, string $secret_key): array {
    $ch = curl_init("https://api.stripe.com/v1/{$endpoint}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_USERPWD, "{$secret_key}:");
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    return ['data' => json_decode($response, true), 'status' => $http_code];
}

function stripe_get(string $endpoint, string $secret_key): array {
    $ch = curl_init("https://api.stripe.com/v1/{$endpoint}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "{$secret_key}:");
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    return ['data' => json_decode($response, true), 'status' => $http_code];
}
