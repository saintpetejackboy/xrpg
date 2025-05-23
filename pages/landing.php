<?php
// /pages/landing.php
// $config = require __DIR__ . '/../config/environment.php'; // removed - file does not exist
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>XRPG - Welcome</title>
    <link rel="stylesheet" href="/assets/css/theme.css">
    <link rel="icon" type="image/png" href="/assets/img/icon.png">
</head>
<body>
    <header>
        <div style="display:flex;align-items:center;gap:1em;">
            <img src="/assets/img/icon.png" alt="XRPG" width="48" height="48" style="border-radius:14px;">
            <b style="font-size:1.42em;letter-spacing:1px;">XRPG</b>
        </div>
        <nav class="navlinks">
            <a href="#" style="opacity:.54;pointer-events:none;">Characters</a>
            <a href="#" style="opacity:.54;pointer-events:none;">Dungeons</a>
            <a href="#" style="opacity:.54;pointer-events:none;">Shop</a>
            <a href="#" style="opacity:.54;pointer-events:none;">Inventory</a>
        </nav>
        <button id="theme-toggle-btn" class="theme-toggle-btn">Dark Theme</button>
    </header>

    <main>
        <div style="display: flex; flex-direction: column; align-items: center;">
            <div class="avatar-preview"></div>
            <h2 style="margin:0;">Welcome to XRPG!</h2>
            <p style="margin:.6em 0 1.3em 0;font-size:1.09em;">Sign in to start customizing your experience.<br>Character/game features are coming soon!</p>
            <button onclick="document.getElementById('login-modal').showModal()" class="theme-toggle-btn" style="margin:1.5em 0 .8em 0;">Sign In / Register</button>
        </div>
        <hr style="margin:2.5em 0 2em 0;opacity:.13">
        <div>
            <h3 style="margin-bottom:.6em;">Personalize the Look!</h3>
            <div class="theme-preview" id="theme-preview"></div>
            <div class="control-group">
                <label for="accent-picker-light" class="control-label">Accent (Light)</label>
                <input type="color" id="accent-picker-light" value="#558cff">
            </div>
            <div class="control-group">
                <label for="accent-picker-dark" class="control-label">Accent (Dark)</label>
                <input type="color" id="accent-picker-dark" value="#558cff">
            </div>
            <div class="control-group">
                <label for="roundness-range" class="control-label">Roundness</label>
                <input type="range" id="roundness-range" min="9" max="40" value="18">
            </div>
            <div class="control-group">
                <label for="shadow-range" class="control-label">Shadow</label>
                <input type="range" id="shadow-range" min="0.05" max="0.35" step="0.01" value="0.12">
            </div>
        </div>
    </main>

    <dialog id="login-modal" style="border:none;background:var(--theme-bg);border-radius:22px;box-shadow:0 1px 60px rgba(0,0,0,.36);padding:2.7em 2.4em 2.1em 2.4em;max-width:370px;width:90%;color:var(--theme-text)">
        <form id="login-form" method="dialog">
            <h3 style="margin-top:0;text-align:center;">Sign In</h3>
            <div style="margin:1.1em 0;">
                <label style="font-weight:bold">Username<br>
                    <input type="text" id="username" required style="width:100%;padding:.5em;margin-top:.25em;border-radius:8px;border:1px solid #bbb;background:var(--theme-card-bg);color:var(--theme-text)">
                </label>
            </div>
            <!-- PASSKEY Button/Challenge UI would go here -->
            <div style="text-align:center;margin-bottom:1.1em;">
                <button type="button" id="passkey-login" class="theme-toggle-btn">Sign in with Passkey</button>
            </div>
            <div style="text-align:center;">
                <button type="button" disabled class="theme-toggle-btn" style="font-size:0.98em;opacity:.5">Sign in with Password</button>
            </div>
            <div style="text-align:center;margin-top:2em;">
                <span style="font-size:.97em;opacity:.8">No account? <button type="button" id="register-btn" class="theme-toggle-btn" style="padding:.1em 1.1em;">Register</button></span>
            </div>
        </form>
        <form id="register-form" method="dialog" style="display:none;">
            <h3 style="margin-top:0;text-align:center;">Register</h3>
            <div style="margin:1.1em 0;">
                <label style="font-weight:bold">Username<br>
                    <input type="text" id="reg-username" required style="width:100%;padding:.5em;margin-top:.25em;border-radius:8px;border:1px solid #bbb;background:var(--theme-card-bg);color:var(--theme-text)">
                </label>
            </div>
            <div style="text-align:center;margin-bottom:1.6em;">
                <button type="button" id="passkey-register" class="theme-toggle-btn">Register with Passkey</button>
            </div>
            <div style="text-align:center;margin-top:2em;">
                <span style="font-size:.97em;opacity:.8">Already have an account? <button type="button" id="login-btn" class="theme-toggle-btn" style="padding:.1em 1.1em;">Sign In</button></span>
            </div>
        </form>
        <button onclick="this.closest('dialog').close()" style="position:absolute;top:10px;right:16px;font-size:2em;background:none;border:none;color:var(--theme-text);line-height:.7;cursor:pointer;">×</button>
    </dialog>
    <script src="/assets/js/theme.js"></script>
    <script>
        // Modal simple login/register switch
        document.getElementById('register-btn').onclick = function(){
            document.getElementById('login-form').style.display = 'none';
            document.getElementById('register-form').style.display = '';
        };
        document.getElementById('login-btn').onclick = function(){
            document.getElementById('login-form').style.display = '';
            document.getElementById('register-form').style.display = 'none';
        };
    </script>
</body>
</html>
