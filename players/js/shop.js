// /players/js/shop.js  –  XRPG Shop page functionality  (2025-05-25)
(function () {
    'use strict';

    /* -----------------------------------------------------------
       Dependency check
    ----------------------------------------------------------- */
    if (typeof XRPGPlayer === 'undefined') {
        console.error('XRPGPlayer not loaded – shop functionality may not work properly');
        return;
    }

    /* -----------------------------------------------------------
       State
    ----------------------------------------------------------- */
    let currentCategory = 'all';
    let selectedItem    = null;

    /* -----------------------------------------------------------
       Category filtering
    ----------------------------------------------------------- */
    function filterCategory(category) {
        currentCategory = category;

        // highlight tab
        document.querySelectorAll('.category-tab').forEach(tab => tab.classList.remove('active'));
        const activeTab = document.querySelector(`.category-tab[onclick*="filterCategory('${category}')"]`);
        if (activeTab) activeTab.classList.add('active');

        // show / hide items
        const items = document.querySelectorAll('.shop-item');
        items.forEach((item, index) => {
            const matches = category === 'all' || item.dataset.category === category;
            item.style.display = matches ? 'block' : 'none';

            if (matches) {
                item.style.opacity   = '0';
                item.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    item.style.transition = 'all 0.5s ease';
                    item.style.opacity    = '1';
                    item.style.transform  = 'translateY(0)';
                }, index * 50);
            }
        });

        updateEmptyState();
    }

    function updateEmptyState() {
        const grid  = document.getElementById('shop-grid');
        const items = [...grid.querySelectorAll('.shop-item')].filter(i => i.style.display !== 'none');

        // clear any previous message
        grid.querySelector('.no-items')?.remove();

        if (items.length) return;

        const div = document.createElement('div');
        div.className = 'no-items';
        div.style.cssText = `
            grid-column: 1 / -1;
            text-align : center;
            padding    : 3rem;
            color      : var(--color-text-secondary);
        `;
        const label = shopCategories[currentCategory]?.name ?? 'items';
        div.innerHTML = `<h3>🛒 No ${label} Available</h3><p>Check back later for new items in this category!</p>`;
        grid.appendChild(div);
    }

    /* -----------------------------------------------------------
       Modal – show item details
    ----------------------------------------------------------- */
    function showItemDetails(itemId) {
        const item = shopItems.find(i => i.id === itemId);
        if (!item) { console.error('Item not found:', itemId); return; }

        selectedItem = item;
        const modal   = document.getElementById('item-modal');
        const title   = document.getElementById('modal-item-name');
        const details = document.getElementById('modal-item-details');
        const actions = document.getElementById('modal-item-actions');

        /* ----- header ----- */
        title.textContent = item.name;
        title.style.color = item.rarity_color;

        /* ----- body ----- */
        let html = /* html */`
            <div style="display:flex;align-items:center;gap:1.5rem;margin-bottom:1.5rem">
                <div style="font-size:4rem">${getItemIcon(item.item_type)}</div>
                <div style="flex:1">
                    <div style="display:flex;align-items:center;gap:1rem;margin-bottom:0.5rem">
                        <span style="color:${item.rarity_color};font-size:1.2rem;font-weight:bold">${item.rarity_emoji} ${item.rarity}</span>
                        <span style="color:var(--color-muted)">⭐ Level ${item.level_req} Required</span>
                    </div>
                    <p style="color:var(--color-text-secondary);font-style:italic;margin:0;line-height:1.4">"${item.description}"</p>
                </div>
            </div>
        `;

        /* stats block */
        if (item.stats && Object.keys(item.stats).length) {
            html += `<div style="margin:1.5rem 0;padding:1.5rem;background:var(--color-surface-alt);border-radius:calc(var(--user-radius)*0.5);border-left:4px solid var(--color-accent)">
                        <h4 style="margin:0 0 1rem;color:var(--color-accent)">Item Statistics:</h4>`;
            for (const [k, v] of Object.entries(item.stats)) {
                html += /* html */`
                    <div style="display:flex;justify-content:space-between;padding:0.5rem 0;border-bottom:1px solid rgba(255,255,255,0.05)">
                        <span style="color:var(--color-text-secondary)">${k}:</span>
                        <span style="color:var(--color-accent);font-weight:bold">${v}</span>
                    </div>`;
            }
            html += '</div>';
        }

        /* price block */
        html += `<div style="margin:1.5rem 0;padding:1.5rem;background:linear-gradient(135deg,rgba(255,215,0,.1),rgba(255,215,0,.05));border-radius:calc(var(--user-radius)*0.5);border:1px solid rgba(255,215,0,.3)">
                    <h4 style="margin:0 0 1rem;color:#FFD700;display:flex;align-items:center;gap:0.5rem">💰 Pricing Information</h4>`;
        if (item.discount_amount) {
            html += /* html */`
                <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem">
                    <span>Original Price:</span>
                    <span style="text-decoration:line-through;color:var(--color-muted)">${formatGold(item.base_price)}</span>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem">
                    <span style="color:#20D74B;font-weight:bold">Discounted Price:</span>
                    <span style="color:#FFD700;font-weight:bold;font-size:1.2rem">${formatGold(item.final_price)}</span>
                </div>
                <div style="display:flex;justify-content:space-between">
                    <span style="color:#20D74B">You Save:</span>
                    <span style="color:#20D74B;font-weight:bold">${formatGold(item.discount_amount)}</span>
                </div>`;
        } else {
            html += `<div style="display:flex;justify-content:space-between">
                        <span>Price:</span>
                        <span style="color:#FFD700;font-weight:bold;font-size:1.2rem">${formatGold(item.final_price)}</span>
                     </div>`;
        }
        html += '</div>';

        /* category blurb */
        const cat = shopCategories[item.category];
        if (cat) {
            html += `<div style="margin:1rem 0;padding:1rem;background:var(--color-surface-alt);border-radius:calc(var(--user-radius)*0.5)">
                        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.5rem">
                            <span style="font-size:1.5rem">${cat.icon}</span>
                            <span style="font-weight:bold">${cat.name}</span>
                        </div>
                        <p style="color:var(--color-text-secondary);margin:0;font-size:.875rem">${cat.description}</p>
                     </div>`;
        }
        details.innerHTML = html;

        /* ----- footer actions ----- */
        actions.innerHTML = buildActionButtons(item);
        modal.style.display = 'flex';
        modal.style.opacity = '0';
        requestAnimationFrame(() => { modal.style.transition = 'opacity .3s ease'; modal.style.opacity = '1'; });
    }

    function buildActionButtons(item) {
        if (!item.in_stock) {
            return `<div style="flex:1;text-align:center;padding:1rem;background:rgba(255,100,100,.1);border-radius:calc(var(--user-radius)*0.5);color:#ff6464">
                        <h4 style="margin:0">❌ Out of Stock</h4>
                        <p style="margin:.5rem 0 0;font-size:.875rem">This item is currently unavailable.</p>
                    </div>
                    <button class="button" onclick="closeItemModal()" style="background:var(--color-surface-alt);border:1px solid var(--color-border)">❌ Close</button>`;
        }
        if (playerData.level < item.level_req) {
            return `<div style="flex:1;text-align:center;padding:1rem;background:rgba(255,193,7,.1);border-radius:calc(var(--user-radius)*0.5);color:#ffc107">
                        <h4 style="margin:0">🔒 Level Requirement Not Met</h4>
                        <p style="margin:.5rem 0 0;font-size:.875rem">Reach level ${item.level_req} to purchase.</p>
                    </div>
                    <button class="button" onclick="closeItemModal()" style="background:var(--color-surface-alt);border:1px solid var(--color-border)">❌ Close</button>`;
        }
        if (playerData.gold < item.final_price) {
            const need = item.final_price - playerData.gold;
            return `<div style="flex:1;text-align:center;padding:1rem;background:rgba(158,158,158,.1);border-radius:calc(var(--user-radius)*0.5);color:#9e9e9e">
                        <h4 style="margin:0">💸 Insufficient Gold</h4>
                        <p style="margin:.5rem 0 0;font-size:.875rem">Need ${formatGold(need)} more.</p>
                    </div>
                    <button class="button" onclick="closeItemModal()" style="background:var(--color-surface-alt);border:1px solid var(--color-border)">❌ Close</button>`;
        }
        /* purchasable */
        return `<button class="buy-button" onclick="purchaseItem(${item.id})" style="flex:1;max-width:none">🛒 Purchase for ${formatGold(item.final_price)}</button>
                <button class="button" onclick="closeItemModal()" style="background:var(--color-surface-alt);border:1px solid var(--color-border)">❌ Close</button>`;
    }

    function closeItemModal(evt) {
        if (evt && evt.target !== evt.currentTarget) return;
        const modal = document.getElementById('item-modal');
        modal.style.opacity = '0';
        setTimeout(() => { modal.style.display = 'none'; selectedItem = null; }, 300);
    }

    /* -----------------------------------------------------------
       Purchase
    ----------------------------------------------------------- */
    async function purchaseItem(itemId) {
        const item = shopItems.find(i => i.id === itemId);
        if (!item) return XRPGPlayer.showStatus('Item not found.', 'error', 3000);

        /* re-validate client-side rules */
        if (!item.in_stock)            return XRPGPlayer.showStatus('This item is out of stock.', 'error', 3000);
        if (playerData.level < item.level_req) return XRPGPlayer.showStatus(`Reach level ${item.level_req} first.`, 'warning', 4000);
        if (playerData.gold  < item.final_price) return XRPGPlayer.showStatus('Not enough gold.', 'warning', 4000);

        if (!confirm(`Buy ${item.name} for ${formatGold(item.final_price)}?`)) return;

        const btns = document.querySelectorAll(`[onclick*="purchaseItem(${itemId})"]`);
        btns.forEach(b => { b.disabled = true; b.textContent = '🔄 Purchasing…'; });

        try {
            const res  = await fetch('/api/shop.php', {
                method : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body   : JSON.stringify({ action:'purchase', item_id:itemId, quantity:1 })
            });
            const data = await res.json();

            if (!res.ok || !data.success) throw new Error(data.message || 'Unknown error');

            playerData.gold = data.new_gold_amount;
            document.querySelector('.gold-amount').textContent = formatGold(playerData.gold);

            XRPGPlayer.showStatus(`Purchased ${item.name}! 🎉`, 'success', 5000);
            closeItemModal();
            showPurchaseAnimation(item);
            setTimeout(() => location.reload(), 2000);

        } catch (err) {
            console.error(err);
            XRPGPlayer.showStatus(err.message, 'error', 5000);
        } finally {
            btns.forEach(b => { b.disabled = false; b.textContent = '🛒 Buy Now'; });
        }
    }

    function showPurchaseAnimation(item) {
        const div = document.createElement('div');
        div.style.cssText = `
            position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);
            background:linear-gradient(135deg,#20D74B,#32d74b);
            color:#fff;padding:1rem 2rem;border-radius:20px;font-weight:bold;
            z-index:10000;box-shadow:0 10px 30px rgba(32,215,75,.3);
            animation:purchaseSuccess 3s ease-out forwards;
        `;
        div.innerHTML = `<div style="text-align:center">
                            <div style="font-size:2rem;margin-bottom:.5rem">🎉</div>
                            <div>Purchase Successful!</div>
                            <div style="font-size:.875rem;opacity:.9">${item.name} added to inventory</div>
                         </div>`;

        if (!document.getElementById('purchase-animation-styles')) {
            const style = document.createElement('style');
            style.id = 'purchase-animation-styles';
            style.textContent = `@keyframes purchaseSuccess{
                0%{opacity:0;transform:translate(-50%,-50%) scale(.5)}
                20%{opacity:1;transform:translate(-50%,-50%) scale(1.1)}
                80%{opacity:1;transform:translate(-50%,-50%) scale(1)}
                100%{opacity:0;transform:translate(-50%,-50%) scale(.8) translateY(-50px)}
            }`;
            document.head.appendChild(style);
        }
        document.body.appendChild(div);
        setTimeout(() => div.remove(), 3000);
    }

    /* -----------------------------------------------------------
       Helpers
    ----------------------------------------------------------- */
    function getItemIcon(type) {
        const map = {
            weapon    : '⚔️',
            armor     : '🛡️',
            consumable: '🧪',
            material  : '🔧',
            house     : '🏠',
            misc      : '📦'
        };
        return map[type] || '📦';
    }

    function formatGold(n) { return new Intl.NumberFormat().format(n) + ' 💰'; }

    /* -----------------------------------------------------------
       QoL – shortcuts, search, opening animation
    ----------------------------------------------------------- */
    function setupKeyboardShortcuts() {
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeItemModal();

            if (e.key >= '1' && e.key <= '6') {
                const cats = ['all','weapon','armor','consumable','material','house'];
                const cat  = cats[parseInt(e.key,10)-1];
                document.querySelector(`[onclick*="filterCategory('${cat}')"]`)?.click();
            }
        });
    }

    function animateShopLoad() {
        document.querySelectorAll('.shop-item').forEach((item,i) => {
            item.style.opacity='0'; item.style.transform='translateY(20px) scale(.95)';
            setTimeout(()=>{
                item.style.transition='all .6s ease';
                item.style.opacity='1'; item.style.transform='translateY(0) scale(1)';
            }, i*100);
        });
    }

    function initializeShop() {
        setupKeyboardShortcuts();
        animateShopLoad();

        setTimeout(() => {
            if (!localStorage.getItem('shop-welcome-shown')) {
                let msg = '🏪 Welcome to the shop! Use number keys 1-6 to switch categories quickly.';
                if (playerData.merchantDiscount) msg += ` You have a ${playerData.merchantDiscount}% discount!`;
                XRPGPlayer.showStatus(msg, 'info', 7000);
                localStorage.setItem('shop-welcome-shown', 'true');
            }
        }, 1500);

        console.log('Shop system initialized');
    }

    /* -----------------------------------------------------------
       Expose for inline handlers
    ----------------------------------------------------------- */
    window.filterCategory  = filterCategory;
    window.showItemDetails = showItemDetails;
    window.closeItemModal  = closeItemModal;
    window.purchaseItem    = purchaseItem;

    document.addEventListener('DOMContentLoaded', initializeShop);
})();
