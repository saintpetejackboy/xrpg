<?php
require_once __DIR__ . '/../thirdparty/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

return [
    'base_url' => $_ENV['DOMAIN_URL'],
    'webauthn_origin' => $_ENV['WEBAUTHN_ORIGIN'],
    'rp_id' => $_ENV['RP_ID'],
    'rp_name' => $_ENV['RP_NAME'],
];
