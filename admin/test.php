<?php
// /admin/test_passkey_data.php - Test script to validate passkey data
require_once __DIR__ . '/../config/db.php';

echo "XRPG Passkey Data Test\n";
echo "======================\n\n";

function base64urlToBinary($data) {
    $base64 = strtr($data, '-_', '+/');
    return base64_decode(str_pad($base64, strlen($base64) % 4, '=', STR_PAD_RIGHT));
}

function binaryToBase64url($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

try {
    // Get a recent user with passkey data
    echo "1. FINDING TEST USER\n";
    echo "-------------------\n";
    
    $stmt = $pdo->query("
        SELECT u.id, u.username, u.user_id, up.credential_id, up.public_key, up.device_name
        FROM users u 
        JOIN user_passkeys up ON u.id = up.user_id 
        ORDER BY u.id DESC 
        LIMIT 1
    ");
    $testUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$testUser) {
        echo "❌ No users with passkeys found\n";
        exit(1);
    }
    
    echo "Found test user: {$testUser['username']} (ID: {$testUser['id']})\n";
    echo "User ID (base64): {$testUser['user_id']}\n";
    echo "Credential ID: {$testUser['credential_id']}\n";
    echo "Public Key Length: " . strlen($testUser['public_key']) . " bytes\n";
    echo "Device: {$testUser['device_name']}\n\n";
    
    // Test credential ID encoding/decoding
    echo "2. TESTING CREDENTIAL ID ENCODING\n";
    echo "--------------------------------\n";
    
    $credentialIdB64url = $testUser['credential_id'];
    echo "Original credential ID (base64url): $credentialIdB64url\n";
    
    try {
        $credentialIdBinary = base64urlToBinary($credentialIdB64url);
        echo "Converted to binary: " . strlen($credentialIdBinary) . " bytes\n";
        echo "Binary as hex: " . bin2hex($credentialIdBinary) . "\n";
        
        $credentialIdBackToB64url = binaryToBase64url($credentialIdBinary);
        echo "Converted back to base64url: $credentialIdBackToB64url\n";
        
        if ($credentialIdB64url === $credentialIdBackToB64url) {
            echo "✅ Credential ID round-trip conversion successful\n";
        } else {
            echo "❌ Credential ID round-trip conversion failed\n";
            echo "   Original: $credentialIdB64url\n";
            echo "   Result:   $credentialIdBackToB64url\n";
        }
    } catch (Exception $e) {
        echo "❌ Credential ID conversion failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // Test user ID encoding/decoding
    echo "3. TESTING USER ID ENCODING\n";
    echo "---------------------------\n";
    
    $userIdB64 = $testUser['user_id'];
    echo "User ID (base64): $userIdB64\n";
    
    try {
        $userIdBinary = base64_decode($userIdB64);
        echo "Decoded to binary: " . strlen($userIdBinary) . " bytes\n";
        echo "Binary as hex: " . bin2hex($userIdBinary) . "\n";
        
        $userIdBackToB64 = base64_encode($userIdBinary);
        echo "Encoded back to base64: $userIdBackToB64\n";
        
        if ($userIdB64 === $userIdBackToB64) {
            echo "✅ User ID round-trip conversion successful\n";
        } else {
            echo "❌ User ID round-trip conversion failed\n";
        }
    } catch (Exception $e) {
        echo "❌ User ID conversion failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // Test public key data
    echo "4. TESTING PUBLIC KEY DATA\n";
    echo "-------------------------\n";
    
    $publicKey = $testUser['public_key'];
    echo "Public key type: " . gettype($publicKey) . "\n";
    echo "Public key length: " . strlen($publicKey) . " bytes\n";
    
    if (strlen($publicKey) > 0) {
        echo "First 20 bytes (hex): " . bin2hex(substr($publicKey, 0, 20)) . "\n";
        echo "Last 10 bytes (hex): " . bin2hex(substr($publicKey, -10)) . "\n";
        
        // Check if it looks like valid CBOR/DER data
        $firstByte = ord($publicKey[0]);
        echo "First byte: 0x" . dechex($firstByte) . " (" . $firstByte . ")\n";
        
        if ($firstByte >= 0xa1 && $firstByte <= 0xbf) {
            echo "✅ Looks like CBOR map data (WebAuthn format)\n";
        } elseif ($firstByte == 0x30) {
            echo "⚠ Looks like DER format (less common)\n";
        } else {
            echo "⚠ Unknown public key format\n";
        }
    } else {
        echo "❌ Public key is empty\n";
    }
    
    echo "\n";
    
    // Test creating a WebAuthn credential source
    echo "5. TESTING WEBAUTHN CREDENTIAL SOURCE CREATION\n";
    echo "----------------------------------------------\n";
    
    try {
        require_once __DIR__ . '/../thirdparty/vendor/autoload.php';
        
        $credentialIdBinary = base64urlToBinary($testUser['credential_id']);
        $userIdBinary = base64_decode($testUser['user_id']);
        
        $credentialSource = new \Webauthn\PublicKeyCredentialSource(
            $credentialIdBinary,
            'public-key',
            [],
            'none',
            new \Webauthn\TrustPath\EmptyTrustPath(),
            null,
            $publicKey,
            $userIdBinary,
            0
        );
        
        echo "✅ WebAuthn credential source created successfully\n";
        echo "   Credential ID: " . bin2hex($credentialSource->getPublicKeyCredentialId()) . "\n";
        echo "   Public Key Length: " . strlen($credentialSource->getCredentialPublicKey()) . " bytes\n";
        echo "   User Handle: " . bin2hex($credentialSource->getUserHandle()) . "\n";
        
    } catch (Exception $e) {
        echo "❌ Failed to create WebAuthn credential source: " . $e->getMessage() . "\n";
        echo "   File: " . $e->getFile() . "\n";
        echo "   Line: " . $e->getLine() . "\n";
    }
    
    echo "\n";
    
    // Test repository lookup simulation
    echo "6. TESTING REPOSITORY LOOKUP SIMULATION\n";
    echo "---------------------------------------\n";
    
    try {
        $credentialIdBinary = base64urlToBinary($testUser['credential_id']);
        $credentialIdForLookup = binaryToBase64url($credentialIdBinary);
        
        echo "Looking up credential ID: $credentialIdForLookup\n";
        
        $stmt = $pdo->prepare('
            SELECT credential_id, public_key, LENGTH(public_key) as key_length 
            FROM user_passkeys 
            WHERE user_id = ? AND credential_id = ?
        ');
        $stmt->execute([$testUser['id'], $credentialIdForLookup]);
        $found = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($found) {
            echo "✅ Passkey found in database\n";
            echo "   Stored credential ID: {$found['credential_id']}\n";
            echo "   Stored key length: {$found['key_length']} bytes\n";
            
            if ($found['credential_id'] === $credentialIdForLookup) {
                echo "✅ Credential ID matches exactly\n";
            } else {
                echo "❌ Credential ID mismatch\n";
                echo "   Expected: $credentialIdForLookup\n";
                echo "   Found:    {$found['credential_id']}\n";
            }
            
        } else {
            echo "❌ Passkey not found in database\n";
            
            // Debug: show all passkeys for this user
            $debugStmt = $pdo->prepare('SELECT credential_id FROM user_passkeys WHERE user_id = ?');
            $debugStmt->execute([$testUser['id']]);
            $allCreds = $debugStmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo "   Available credential IDs for this user:\n";
            foreach ($allCreds as $cred) {
                echo "     - $cred\n";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ Repository lookup simulation failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n✅ Passkey data test completed!\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
}
