<?php
// /auth/RateLimiter.php - Comprehensive rate limiting and security

class RateLimiter {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Check if an action is rate limited
     */
    public function checkRateLimit($endpoint, $ip, $username = null) {
        $this->cleanupOldEntries();
        
        // Check for existing rate limit entry
        $stmt = $this->pdo->prepare('
            SELECT * FROM rate_limits 
            WHERE ip_address = ? AND endpoint = ? AND username = ?
        ');
        $stmt->execute([$ip, $endpoint, $username]);
        $existing = $stmt->fetch();
        
        $limits = $this->getLimitsForEndpoint($endpoint);
        $now = time();
        
        if ($existing) {
            $firstAttempt = strtotime($existing['first_attempt']);
            $blockedUntil = $existing['blocked_until'] ? strtotime($existing['blocked_until']) : 0;
            
            // Check if still blocked
            if ($blockedUntil && $now < $blockedUntil) {
                $this->logSecurityEvent($ip, $username, 'blocked_attempt', 
                    "Attempted $endpoint while blocked", ['endpoint' => $endpoint]);
                return [
                    'allowed' => false, 
                    'reason' => 'temporarily_blocked',
                    'retry_after' => $blockedUntil - $now
                ];
            }
            
            // Check if we're within the time window
            $timeWindow = $now - $firstAttempt;
            if ($timeWindow < $limits['window']) {
                // Within window - check attempt count
                if ($existing['attempts'] >= $limits['max_attempts']) {
                    // Block for escalating time
                    $blockDuration = min($limits['block_duration'] * pow(2, floor($existing['attempts'] / $limits['max_attempts'])), 3600);
                    $blockedUntil = $now + $blockDuration;
                    
                    $stmt = $this->pdo->prepare('
                        UPDATE rate_limits 
                        SET attempts = attempts + 1, blocked_until = FROM_UNIXTIME(?)
                        WHERE id = ?
                    ');
                    $stmt->execute([$blockedUntil, $existing['id']]);
                    
                    $this->logSecurityEvent($ip, $username, 'multiple_failures', 
                        "Blocked after {$existing['attempts']} attempts for $endpoint");
                    
                    return [
                        'allowed' => false, 
                        'reason' => 'too_many_attempts',
                        'retry_after' => $blockDuration
                    ];
                }
                
                // Increment attempt count
                $stmt = $this->pdo->prepare('
                    UPDATE rate_limits 
                    SET attempts = attempts + 1, last_attempt = CURRENT_TIMESTAMP
                    WHERE id = ?
                ');
                $stmt->execute([$existing['id']]);
                
                return ['allowed' => true, 'attempts' => $existing['attempts'] + 1];
            } else {
                // Outside window - reset
                $stmt = $this->pdo->prepare('
                    UPDATE rate_limits 
                    SET attempts = 1, first_attempt = CURRENT_TIMESTAMP, 
                        last_attempt = CURRENT_TIMESTAMP, blocked_until = NULL
                    WHERE id = ?
                ');
                $stmt->execute([$existing['id']]);
                
                return ['allowed' => true, 'attempts' => 1];
            }
        } else {
            // First attempt - create entry
            $stmt = $this->pdo->prepare('
                INSERT INTO rate_limits (ip_address, endpoint, username, attempts) 
                VALUES (?, ?, ?, 1)
            ');
            $stmt->execute([$ip, $endpoint, $username]);
            
            return ['allowed' => true, 'attempts' => 1];
        }
    }
    
    /**
     * Check account creation limits (more strict)
     */
    public function checkAccountCreationLimits($ip) {
        $stmt = $this->pdo->prepare('
            SELECT * FROM creation_limits WHERE ip_address = ?
        ');
        $stmt->execute([$ip]);
        $existing = $stmt->fetch();
        
        $now = time();
        $today = date('Y-m-d');
        
        if ($existing) {
            // Reset daily counter if needed
            if ($existing['last_daily_reset'] !== $today) {
                $stmt = $this->pdo->prepare('
                    UPDATE creation_limits 
                    SET daily_count = 1, last_daily_reset = CURRENT_DATE 
                    WHERE id = ?
                ');
                $stmt->execute([$existing['id']]);
                return ['allowed' => true, 'daily_count' => 1];
            }
            
            // Check daily limit (3 accounts per IP per day)
            if ($existing['daily_count'] >= 3) {
                $this->logSecurityEvent($ip, null, 'blocked_creation', 
                    "IP blocked from creating accounts (daily limit reached)");
                return [
                    'allowed' => false, 
                    'reason' => 'daily_limit_reached',
                    'retry_after' => strtotime('tomorrow') - $now
                ];
            }
            
            // Check total limit (10 accounts per IP total)
            if ($existing['accounts_created'] >= 10) {
                $this->logSecurityEvent($ip, null, 'blocked_creation', 
                    "IP permanently blocked from creating accounts (total limit reached)");
                return [
                    'allowed' => false, 
                    'reason' => 'total_limit_reached',
                    'retry_after' => false // Permanent
                ];
            }
            
            // Check rapid creation (max 1 account per hour)
            $lastCreation = strtotime($existing['last_creation']);
            if ($now - $lastCreation < 3600) {
                return [
                    'allowed' => false, 
                    'reason' => 'too_soon',
                    'retry_after' => 3600 - ($now - $lastCreation)
                ];
            }
            
            // Update counters
            $stmt = $this->pdo->prepare('
                UPDATE creation_limits 
                SET accounts_created = accounts_created + 1, 
                    daily_count = daily_count + 1, 
                    last_creation = CURRENT_TIMESTAMP 
                WHERE id = ?
            ');
            $stmt->execute([$existing['id']]);
            
            return [
                'allowed' => true, 
                'total_created' => $existing['accounts_created'] + 1,
                'daily_count' => $existing['daily_count'] + 1
            ];
        } else {
            // First account from this IP
            $stmt = $this->pdo->prepare('
                INSERT INTO creation_limits (ip_address, accounts_created, daily_count) 
                VALUES (?, 1, 1)
            ');
            $stmt->execute([$ip]);
            
            return ['allowed' => true, 'total_created' => 1, 'daily_count' => 1];
        }
    }
    
    /**
     * Get rate limits for specific endpoints
     */
    private function getLimitsForEndpoint($endpoint) {
        $limits = [
            'register' => [
                'max_attempts' => 3,
                'window' => 900, // 15 minutes
                'block_duration' => 1800 // 30 minutes
            ],
            'login' => [
                'max_attempts' => 5,
                'window' => 300, // 5 minutes
                'block_duration' => 600 // 10 minutes
            ],
            'default' => [
                'max_attempts' => 10,
                'window' => 60,
                'block_duration' => 300
            ]
        ];
        
        return $limits[$endpoint] ?? $limits['default'];
    }
    
    /**
     * Log security events for monitoring
     */
    private function logSecurityEvent($ip, $username, $eventType, $description, $metadata = null) {
        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO security_events (ip_address, username, event_type, description, metadata) 
                VALUES (?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $ip, 
                $username, 
                $eventType, 
                $description, 
                $metadata ? json_encode($metadata) : null
            ]);
        } catch (Exception $e) {
            error_log("Failed to log security event: " . $e->getMessage());
        }
    }
    
    /**
     * Clean up old rate limit entries
     */
    private function cleanupOldEntries() {
        try {
            // Clean entries older than 24 hours
            $this->pdo->exec('
                DELETE FROM rate_limits 
                WHERE last_attempt < DATE_SUB(NOW(), INTERVAL 24 HOUR)
                  AND (blocked_until IS NULL OR blocked_until < NOW())
            ');
            
            // Clean old security events (keep 30 days)
            $this->pdo->exec('
                DELETE FROM security_events 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
            ');
        } catch (Exception $e) {
            error_log("Rate limiter cleanup failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get user's IP address with proxy support
     */
    public static function getClientIP() {
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = trim(explode(',', $_SERVER[$header])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    /**
     * Detect suspicious patterns
     */
    public function detectSuspiciousActivity($ip, $username = null) {
        $suspicious = false;
        $reasons = [];
        
        // Check for rapid requests across multiple endpoints
        $stmt = $this->pdo->prepare('
            SELECT endpoint, COUNT(*) as count 
            FROM rate_limits 
            WHERE ip_address = ? AND last_attempt > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            GROUP BY endpoint
        ');
        $stmt->execute([$ip]);
        $recentActivity = $stmt->fetchAll();
        
        if (count($recentActivity) > 3) {
            $suspicious = true;
            $reasons[] = 'multiple_endpoints';
        }
        
        // Check for user agent patterns
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (empty($userAgent) || strlen($userAgent) < 10 || 
            strpos($userAgent, 'bot') !== false || 
            strpos($userAgent, 'curl') !== false) {
            $suspicious = true;
            $reasons[] = 'suspicious_user_agent';
        }
        
        if ($suspicious) {
            $this->logSecurityEvent($ip, $username, 'suspicious_timing', 
                "Suspicious activity detected", ['reasons' => $reasons]);
        }
        
        return $suspicious;
    }
}
