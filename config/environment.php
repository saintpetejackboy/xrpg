<?php
// Environment Configuration for Passkey Auth, Domains, etc.
// Change these as needed for local/server deployments.

return [
    // Full base URL for the app, used for WebAuthn origins
    'base_url' => 'http://localhost/xrpg',
    // Origin (protocol + domain + optional port), for WebAuthn
    'webauthn_origin' => 'http://localhost',
    // Relying Party Name
    'rp_name' => 'XRPG',
    // Relying Party ID (usually domain, used by WebAuthn format)
    'rp_id' => 'localhost',

    // Any other config you want here!
];
