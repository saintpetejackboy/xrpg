// /players/js/inventory.js  – XRPG full rewrite 2025-05-26
(function () {
  'use strict';

  /* ---------- 1.  CONSTANTS ---------- */
  const TAB_ORDER = ['all', 'weapon', 'armor', 'consumable', 'material', 'house'];
  const ICON = {
    weapon: '⚔️', armor: '🛡️', consumable: '🧪',
    currency: '💰', material: '🔧', house: '🏠', misc: '📦'
  };

  /* ---------- 2.  NORMALISE DATA FROM PHP ---------- */
  ['itemData', 'equippedBySlot'].forEach(key => {
    if (window[key]) {
      if (Array.isArray(window[key])) {
        window[key] = window[key].map(o => ({ ...o, id: o.id ?? o.instance_id }));
      } else {
        // equippedBySlot = object keyed by slot id
        Object.values(window[key]).forEach(o => {
          if (o) o.id = o.id ?? o.instance_id;
        });
      }
    }
  });

  if (!window.XRPGPlayer || !Array.isArray(window.itemData)) {
    console.error('XRPG inventory failed to initialise – missing globals.');
    return;
  }

  /* ---------- 3.  LOCAL STATE ---------- */
  let currentTab = 'all';
  let draggedId = null;

  /* ---------- 4.  HELPER:  API WRAPPER ---------- */
  async function api(action, instanceId) {
    if (!Number.isInteger(instanceId) || instanceId <= 0) {
      throw new Error('Invalid item ID');
    }
    const res = await fetch('/api/equipment.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ action, instance_id: instanceId })
    });
    const body = await res.json().catch(() => ({}));
    if (!res.ok || !body.success) {
      throw new Error(body.message || res.statusText);
    }
    return body;
  }

  /* ---------- 5.  INVENTORY FILTERING ---------- */
  function switchTab(name) {
    currentTab = name;
    document.querySelectorAll('.inventory-tab').forEach(b =>
      b.classList.toggle('active', b.dataset.tab === name)
    );
    document.querySelectorAll('.inventory-item').forEach(el => {
      const show = name === 'all' || el.dataset.itemType === name;
      el.style.display = show ? '' : 'none';
    });
    updateEmptyState();
  }

  function updateEmptyState() {
    const grid = document.getElementById('inventory-grid');
    const none = ![...grid.querySelectorAll('.inventory-item')].some(el => el.style.display !== 'none');
    let div = grid.querySelector('.no-items');
    if (none) {
      if (!div) {
        div = document.createElement('div');
        div.className = 'no-items';
        div.style.gridColumn = '1 / -1';
        grid.appendChild(div);
      }
      const msg = {
        weapon: 'No weapons here!',
        armor: 'You own no armor.',
        consumable: 'Out of potions.',
        material: 'No crafting mats.',
        house: 'Nothing to decorate with.'
      }[currentTab] || 'Inventory empty!';
      div.innerHTML = `<p>🎒 ${msg}</p><p>Visit the <a href="/players/shop.php">shop</a>.</p>`;
      div.style.display = '';
    } else if (div) div.style.display = 'none';
  }

  /* ---------- 6.  MODAL RENDER ---------- */
  function openModal(item) {
    const modal = document.getElementById('item-modal');
    const title = document.getElementById('modal-item-name');
    const body = document.getElementById('modal-item-details');
    const actions = document.getElementById('modal-item-actions');

    title.textContent = item.item_name;
    title.style.color = item.rarity_color || '';

    let html = `
      <div style="display:flex;gap:.8rem;align-items:center">
        <div style="font-size:3rem">${ICON[item.item_type] ?? '📦'}</div>
        <div>
          <div style="font-weight:bold">${item.rarity_emoji || ''} ${item.rarity_name}</div>
          <div style="color:var(--color-text-secondary)">Lv ${item.level} • ${item.item_type}</div>
          ${item.quantity > 1 ? `<div>Qty ${item.quantity}</div>` : ''}
        </div>
      </div>
      <p style="margin:.8rem 0;font-style:italic;color:var(--color-text-secondary)">
        "${item.item_description || 'A mysterious item.'}"
      </p>
    `;
    const attr = window.itemAttributes[item.id] || [];
    if (attr.length) {
      html += '<h4>Attributes</h4>';
      html += attr.map(a =>
        `<div>${a.attr_name}: <b>${a.value}${a.unit || ''}</b></div>`
      ).join('');
    }
    body.innerHTML = html;

    // build actions
    actions.innerHTML = '';
    const isEquipped = Object.values(window.equippedBySlot || {})
      .some(eq => eq && eq.id === item.id);

    if (isEquipped) addBtn(actions, '⬇️ Unequip', 'warning', () => unequip(item.id));
    if (!isEquipped && ['weapon','armor'].includes(item.item_type))
      addBtn(actions, '⬆️ Equip', 'success', () => equip(item.id));
    if (item.item_type === 'consumable')
      addBtn(actions, '🧪 Use', 'primary', () => useItem(item.id));
    if (!isEquipped)
      addBtn(actions, '💰 Sell', 'danger', () => sell(item.id));
    addBtn(actions, '❌ Close', 'secondary', closeModal);

    modal.style.display = 'flex';
    requestAnimationFrame(() => modal.style.opacity = '1');
  }

  function addBtn(parent, txt, cls, fn) {
    const b = document.createElement('button');
    b.className = `button ${cls}`;
    b.textContent = txt;
    b.onclick = fn;
    parent.appendChild(b);
  }
  function closeModal() {
    const m = document.getElementById('item-modal');
    m.style.opacity = '0';
    setTimeout(() => (m.style.display = 'none'), 300);
  }

  /* ---------- 7.  ACTIONS ---------- */
  async function equip(id)    { await doAction('equip',    id, '⚔️ Equipped!'); }
  async function unequip(id)  { await doAction('unequip',  id, '⬇️ Unequipped!'); }
  async function sell(id)     { 
    if (!confirm('Sell this item?')) return;
    const res = await doAction('sell', id);
    XRPGPlayer.showStatus(`💰 Sold for ${res.gold_gained}g`, 'success', 3000);
  }
  async function useItem(id)  { await doAction('use',      id, '🧪 Used!'); }

  async function doAction(action, id, okMsg = '') {
    try {
      const res = await api(action, id);
      XRPGPlayer.showStatus(okMsg || res.message, 'success', 2500);
      location.reload();
      return res;
    } catch (e) {
      XRPGPlayer.showStatus(e.message, 'error', 4000);
    }
  }

  /* ---------- 8.  DRAG & DROP ---------- */
  function enableDnD() {
    document.querySelectorAll('.inventory-item').forEach(el => {
      el.draggable = true;
      el.addEventListener('dragstart', e => {
        draggedId = parseInt(el.dataset.instanceId, 10);
        el.style.opacity = '.5';
      });
      el.addEventListener('dragend', () => (el.style.opacity = '1'));
    });

    document.querySelectorAll('.equipment-slot').forEach(slot => {
      slot.addEventListener('dragover', e => e.preventDefault());
      slot.addEventListener('drop', async e => {
        e.preventDefault();
        const targetCode = slot.dataset.slotCode;
        const item = window.itemData.find(i => i.id === draggedId);
        if (item && item.slot_code === targetCode) {
          await equip(item.id);
        } else {
          XRPGPlayer.showStatus('Cannot equip here', 'warning', 2500);
        }
      });
    });
  }

  /* ---------- 9.  EVENT BINDINGS ---------- */
  function bindClicks() {
    document.querySelectorAll('.inventory-item').forEach(el =>
      el.addEventListener('click', () => openModal(
        window.itemData.find(i => i.id === parseInt(el.dataset.instanceId, 10))
      ))
    );
    document.querySelectorAll('.inventory-tab').forEach(el =>
      el.addEventListener('click', () => switchTab(el.dataset.tab))
    );
    document.querySelectorAll('.equipment-slot').forEach(el =>
      el.addEventListener('click', () => {
        const id = window.equippedBySlot?.[el.dataset.slotId]?.id;
        if (id) openModal(window.itemData.find(i => i.id === id));
      })
    );
    document.getElementById('item-modal')
      .addEventListener('click', closeModal);
    document.querySelector('.item-modal-content')
      .addEventListener('click', e => e.stopPropagation());
  }

  /* ---------- 10.  INIT ---------- */
  document.addEventListener('DOMContentLoaded', () => {
    bindClicks();
    enableDnD();
    switchTab('all');
    XRPGPlayer.showStatus(
      '💡 Drag items to slots or press 1-6 to filter.',
      'info', 6000
    );
  });

  /* ---------- 11.  KEYBOARD SHORTCUTS ---------- */
  document.addEventListener('keydown', e => {
    if (document.getElementById('item-modal').style.display === 'flex') {
      if (e.key === 'Escape') closeModal();
      return;
    }
    const idx = parseInt(e.key, 10) - 1;
    if (idx >= 0 && idx < TAB_ORDER.length) {
      const btn = document.querySelector(`.inventory-tab[data-tab="${TAB_ORDER[idx]}"]`);
      if (btn) btn.click();
    }
  });
})();
