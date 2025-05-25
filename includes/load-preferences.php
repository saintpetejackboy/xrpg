<?php
// /includes/load-preferences.php - Helper to load user preferences consistently

function loadUserPreferences($pdo, $userId) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM user_preferences WHERE user_id = ?');
        $stmt->execute([$userId]);
        $preferences = $stmt->fetch();
        
        if (!$preferences) {
            // Create default preferences for new user
            $defaultPrefs = [
                'theme_mode' => 'dark',
                'accent_color' => '#5299e0',
                'accent_secondary' => '#81aaff',
                'border_radius' => 18,
                'shadow_intensity' => 0.36,
                'ui_opacity' => 0.96,
                'font_family' => 'sans'
            ];
            
            $stmt = $pdo->prepare('
                INSERT INTO user_preferences (user_id, theme_mode, accent_color, accent_secondary, border_radius, shadow_intensity, ui_opacity, font_family) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $userId, 
                $defaultPrefs['theme_mode'],
                $defaultPrefs['accent_color'],
                $defaultPrefs['accent_secondary'],
                $defaultPrefs['border_radius'],
                $defaultPrefs['shadow_intensity'],
                $defaultPrefs['ui_opacity'],
                $defaultPrefs['font_family']
            ]);
            
            // Return the default preferences
            return $defaultPrefs;
        }
        
        return $preferences;
    } catch (Exception $e) {
        error_log("Failed to load user preferences: " . $e->getMessage());
        // Return defaults if database fails
        return [
            'theme_mode' => 'dark',
            'accent_color' => '#5299e0',
            'accent_secondary' => '#81aaff',
            'border_radius' => 18,
            'shadow_intensity' => 0.36,
            'ui_opacity' => 0.96,
            'font_family' => 'sans'
        ];
    }
}

function getUserInfo($user) {
    if (is_string($user)) {
        // Legacy: session stores just username
        return [
            'id' => null,
            'username' => $user,
            'type' => 'player'
        ];
    } elseif (is_array($user)) {
        // New format: session stores user array
        return [
            'id' => $user['id'] ?? null,
            'username' => $user['username'] ?? 'Unknown',
            'type' => $user['type'] ?? 'player'
        ];
    }
    
    return [
        'id' => null,
        'username' => 'Unknown',
        'type' => 'player'
    ];
}
