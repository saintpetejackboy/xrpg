<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>XRPG - UI Components Demo</title>
    <link rel="stylesheet" href="/assets/css/theme.css">
    <style>
        .demo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .demo-box {
            padding: 1.5rem;
        }
        
        .demo-box h3 {
            margin-top: 0;
            color: var(--color-accent);
        }
        
        .demo-row {
            margin: 1rem 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .stat-box {
            text-align: center;
            padding: 0.5rem;
            background: var(--color-surface-alt);
            border-radius: calc(var(--user-radius) * 0.3);
            border: 1px solid var(--color-border);
        }
        
        .progress-bar {
            width: 100%;
            height: 1.5rem;
            background: var(--color-surface-alt);
            border-radius: calc(var(--user-radius) * 0.3);
            overflow: hidden;
            position: relative;
            border: 1px solid var(--color-border);
        }
        
        .progress-fill {
            height: 100%;
            background: var(--gradient-accent);
            transition: width 0.3s ease;
            box-shadow: var(--shadow-glow);
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: var(--gradient-accent);
            color: white;
            border-radius: calc(var(--user-radius) * 0.5);
            font-size: 0.875rem;
            font-weight: 600;
            box-shadow: var(--shadow-default);
        }
        
        .floating-action {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 50%;
            background: var(--gradient-accent);
            border: none;
            box-shadow: var(--shadow-glow);
            cursor: pointer;
            font-size: 1.5rem;
            color: white;
            transition: all 0.3s;
        }
        
        .floating-action:hover {
            transform: scale(1.1);
            box-shadow: var(--shadow-glow), 0 8px 32px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
    <div class="demo-grid">
        <!-- Character Card -->
        <div class="card demo-box">
            <h3>🧙‍♂️ Character Profile</h3>
            <div class="demo-row">
                <input type="text" placeholder="Character Name" value="Zephyr Stormwind" style="width: 100%;">
            </div>
            <div class="demo-row">
                <select style="width: 100%;">
                    <option>Mage - Level 42</option>
                    <option>Warrior - Level 38</option>
                    <option>Rogue - Level 45</option>
                </select>
            </div>
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="text-muted">STR</div>
                    <div class="text-accent" style="font-size: 1.5rem; font-weight: bold;">18</div>
                </div>
                <div class="stat-box">
                    <div class="text-muted">INT</div>
                    <div class="text-accent" style="font-size: 1.5rem; font-weight: bold;">24</div>
                </div>
                <div class="stat-box">
                    <div class="text-muted">DEX</div>
                    <div class="text-accent" style="font-size: 1.5rem; font-weight: bold;">16</div>
                </div>
            </div>
        </div>

        <!-- Quest Log -->
        <div class="card demo-box">
            <h3>📜 Active Quests</h3>
            <div class="surface" style="padding: 1rem; margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-weight: 600;">The Crystal of Eternity</div>
                        <div class="text-muted" style="font-size: 0.875rem;">Main Quest • Dungeon of Shadows</div>
                    </div>
                    <span class="badge">Epic</span>
                </div>
                <div style="margin-top: 0.5rem;">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 75%;"></div>
                    </div>
                </div>
            </div>
            <button class="button" style="width: 100%;">View All Quests</button>
        </div>

        <!-- Inventory -->
        <div class="card demo-box">
            <h3>🎒 Inventory</h3>
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.5rem;">
                <?php for($i = 0; $i < 8; $i++): ?>
                <div class="surface" style="aspect-ratio: 1; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;">
                    <span style="font-size: 2rem; filter: grayscale(<?= $i > 3 ? '1' : '0' ?>);">
                        <?= ['⚔️', '🛡️', '🧪', '📜', '💎', '🗝️', '🍞', '🏺'][$i] ?>
                    </span>
                </div>
                <?php endfor; ?>
            </div>
            <div class="demo-row" style="margin-top: 1rem;">
                <div class="text-muted">Gold: <span class="text-accent" style="font-weight: bold;">2,847</span></div>
            </div>
        </div>

        <!-- Battle Controls -->
        <div class="card demo-box">
            <h3>⚔️ Battle Controls</h3>
            <div class="demo-row">
                <button class="button" style="width: 48%; margin-right: 4%;">Attack</button>
                <button class="button" style="width: 48%;">Defend</button>
            </div>
            <div class="demo-row">
                <button class="button" style="width: 48%; margin-right: 4%;">Magic</button>
                <button class="button" style="width: 48%;">Items</button>
            </div>
            <div class="demo-row">
                <button class="button" disabled style="width: 100%; opacity: var(--opacity-disabled);">Run (Boss Battle)</button>
            </div>
        </div>

        <!-- Chat/Messages -->
        <div class="card demo-box">
            <h3>💬 Guild Chat</h3>
            <div role="region" aria-label="Chat messages" style="height: 150px; overflow-y: auto; padding: 0.5rem; margin-bottom: 1rem;">
                <div class="demo-row">
                    <span class="text-accent">Thorin:</span> Anyone up for Dragon's Lair?
                </div>
                <div class="demo-row">
                    <span class="text-accent">Luna:</span> I need a healer for level 5!
                </div>
                <div class="demo-row">
                    <span class="text-muted">System: New event starting in 5 minutes</span>
                </div>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <input type="text" placeholder="Type message..." style="flex: 1;">
                <button class="button">Send</button>
            </div>
        </div>

        <!-- Settings Panel -->
        <div class="card demo-box">
            <h3>⚙️ Quick Settings</h3>
            <div class="demo-row">
                <label style="display: flex; align-items: center; justify-content: space-between;">
                    <span>Sound Effects</span>
                    <input type="checkbox" checked>
                </label>
            </div>
            <div class="demo-row">
                <label style="display: flex; align-items: center; justify-content: space-between;">
                    <span>Show Damage Numbers</span>
                    <input type="checkbox" checked>
                </label>
            </div>
            <div class="demo-row">
                <label style="display: flex; align-items: center; justify-content: space-between;">
                    <span>Auto-Loot</span>
                    <input type="checkbox">
                </label>
            </div>
            <div class="demo-row">
                <textarea placeholder="Bio / Notes..." rows="3" style="width: 100%;"></textarea>
            </div>
        </div>
    </div>

    <!-- Floating Action Button -->
    <button class="floating-action" onclick="alert('Theme customizer would open here!')">
        🎨
    </button>

    <!-- Include theme JS -->
    <script src="/assets/js/theme.js"></script>
</body>
</html>