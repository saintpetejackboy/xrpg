#!/usr/bin/php
<?php
// cron/job-rewards.php
// --------------------------------------------------
// Runs every 5 min, grants idle-gold to all completed characters
// and logs each grant into user_activity.
//
// Usage in crontab:
// */5 * * * * php /var/www/xrpg/cron/job-rewards.php >> /var/log/xrpg/job-rewards.log 2>&1
// --------------------------------------------------

date_default_timezone_set('America/New_York');

// load your PDO $pdo
require_once __DIR__ . '/../config/db.php';

try {
    // start transaction so either all users get their gold or none
    $pdo->beginTransaction();

    // fetch all active, completed characters with their job rates
    $fetch = $pdo->prepare("
        SELECT
          uc.user_id,
          us.level,
          j.idle_gold_rate,
          j.name AS job_name
        FROM user_characters uc
        JOIN user_stats      us ON uc.user_id = us.user_id
        JOIN jobs            j  ON uc.job_id    = j.id
        WHERE uc.is_character_complete = 1
    ");
    $fetch->execute();
    $chars = $fetch->fetchAll(PDO::FETCH_ASSOC);

    // prepare our two statements
    $updGold = $pdo->prepare("
        UPDATE user_stats
        SET gold = gold + :gain,
            last_idle_update = NOW()
        WHERE user_id = :uid
    ");

    $logActivity = $pdo->prepare("
        INSERT INTO user_activity (user_id, event_type, description)
        VALUES (:uid, 'job_reward', :desc)
    ");

    foreach ($chars as $c) {
        $uid   = (int)$c['user_id'];
        $lvl   = (int)$c['level'];
        $rate  = (float)$c['idle_gold_rate'];
        // formula: rate + (rate * (level * 0.25))
        $gain  = $rate + ($rate * ($lvl * 0.25));
        $gain  = (int)round($gain);
        if ($gain <= 0) {
            continue;
        }

        // update their gold
        $updGold->execute([
            ':gain' => $gain,
            ':uid'  => $uid,
        ]);

        // log it so it shows up in Recent Activity
        $desc = "💰 Idle reward: +{$gain} gold from “{$c['job_name']}”";
        $logActivity->execute([
            ':uid'  => $uid,
            ':desc' => $desc,
        ]);
    }

    $pdo->commit();
} catch (\Throwable $e) {
    $pdo->rollBack();
    error_log('[cron/job-rewards] ERROR: ' . $e->getMessage());
    exit(1);
}

exit(0);
