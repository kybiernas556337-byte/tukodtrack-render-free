(function () {
  const $ = (s, r = document) => r.querySelector(s);

  // open / close modals ------------------------------------------------------
  document.addEventListener('click', e => {
    const o = e.target.closest('[data-open]');
    if (o) { const d = $(o.dataset.open); d && d.showModal(); return; }
    if (e.target.closest('[data-close]')) { e.target.closest('dialog').close(); return; }
    if (e.target.tagName === 'DIALOG') e.target.close();           // click on backdrop
    if (e.target.closest('[data-toggle-side]')) document.body.classList.toggle('side-collapsed');
    if (e.target.closest('[data-reload]')) location.reload();
    if (e.target.closest('[data-print]')) window.print();
  });
  document.querySelectorAll('[data-toast]').forEach(t => setTimeout(() => t.remove(), 6000));

  // add / edit modal for products, suppliers, users --------------------------
  function crudModal(id, key, fields, titles) {
    const m = document.getElementById(id); if (!m) return;
    const f = m.querySelector('form');
    document.addEventListener('click', e => {
      const b = e.target.closest(`[data-${key}-add],[data-${key}-edit]`); if (!b) return;
      const edit = b.hasAttribute(`data-${key}-edit`);
      const d = edit ? JSON.parse(b.getAttribute(`data-${key}-edit`)) : {};
      f.reset();
      f.action = edit ? `${f.dataset.base}/${d.id}` : f.dataset.store;
      f.elements._method.value = edit ? 'PUT' : 'POST';
      m.querySelector('h2').textContent = titles[edit ? 1 : 0];
      if (edit) fields.forEach(n => { const el = f.elements[n]; if (!el) return;
        if (el.tagName === 'SELECT' && d[n] && ![...el.options].some(o => o.value === d[n])) el.add(new Option(d[n], d[n]));   // keep units/values that are not in the list
        el.type === 'checkbox' ? (el.checked = !!d[n]) : (el.value = d[n] ?? ''); });
      m.querySelectorAll('[data-add-only]').forEach(x => x.hidden = edit);
      m.querySelectorAll('[data-edit-only]').forEach(x => x.hidden = !edit);
      if (f.elements.password) f.elements.password.required = !edit;
      m.showModal();
    });
  }
  crudModal('productModal', 'product', ['name', 'code', 'category', 'base_unit', 'selling_price', 'reorder_level'], ['Add product', 'Edit product']);
  crudModal('supplierModal', 'supplier', ['name', 'contact_person', 'phone', 'email', 'address'], ['Add supplier', 'Edit supplier']);
  crudModal('userModal', 'user', ['name', 'email', 'role', 'is_active'], ['Add account', 'Edit access']);
  if (new URLSearchParams(location.search).has('add')) document.querySelector('[data-product-add]')?.click();

  // line builder for the New sale / New purchase order modals ----------------
  function builder(formId, mode) {
    const f = document.getElementById(formId); if (!f) return;
    const products = JSON.parse(f.dataset.products), lines = [];
    const search = $('[data-search]', f), box = $('[data-lines]', f), submit = $('[data-submit]', f);
    const totalEl = $('[data-total]', f), changeEl = $('[data-change]', f), paid = f.elements.amount_paid;
    const emptyHtml = box.innerHTML;
    const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const money = n => '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const price = l => mode === 'sale' ? l.p.price : l.cost;
    const sum = () => lines.reduce((s, l) => s + l.qty * price(l), 0);

    function update() {
      const t = sum();
      if (totalEl) totalEl.textContent = money(t);
      if (mode === 'sale') {
        const pd = parseFloat(paid.value);
        changeEl.textContent = 'Change: ' + money(isNaN(pd) ? 0 : Math.max(0, pd - t));
        submit.disabled = !lines.length || isNaN(pd) || pd + 0.0001 < t;
      } else {
        submit.disabled = !lines.length || !f.elements.supplier_id.value;
      }
    }
    function render() {
      if (!lines.length) { box.innerHTML = emptyHtml; box.classList.add('lines'); return update(); }
      box.classList.remove('lines');
      box.innerHTML = '<table class="tbl ln"><thead><tr><th>Product</th><th>' + (mode === 'sale' ? 'Price' : 'Unit cost (₱)') + '</th><th>Quantity</th><th class="r">Line total</th><th></th></tr></thead><tbody>' +
        lines.map((l, i) => `<tr><td><b>${esc(l.p.name)}</b><br><span class="muted">${esc(l.p.code)} · ${esc(l.p.qty)} ${esc(l.p.unit)} on hand</span><input type="hidden" name="items[${i}][product_id]" value="${l.p.id}"></td>
          <td>${mode === 'sale' ? money(l.p.price) : `<input class="input sm" type="number" step="0.01" min="0" name="items[${i}][unit_cost]" value="${l.cost}" data-i="${i}" data-f="cost">`}</td>
          <td><input class="input sm" type="number" step="any" min="0.001" name="items[${i}][quantity]" value="${l.qty}" data-i="${i}" data-f="qty"> ${esc(l.p.unit)}</td>
          <td class="r lt">${money(l.qty * price(l))}</td><td><button type="button" class="link bad" data-rm="${i}">Remove</button></td></tr>`).join('') + '</tbody></table>';
      update();
    }
    function add(p) {
      const ex = lines.find(l => l.p.id === p.id);
      ex ? ex.qty += 1 : lines.push({ p, qty: 1, cost: 0 });
      render();
    }
    function match() {
      const v = search.value.trim().toLowerCase(); if (!v) return;
      const p = products.find(p => (p.code + ' — ' + p.name).toLowerCase() === v || p.code.toLowerCase() === v);
      if (p) { add(p); search.value = ''; }
    }
    search.addEventListener('input', match);
    search.addEventListener('change', match);
    search.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); match(); } });
    box.addEventListener('input', e => {
      const i = e.target.dataset.i; if (i === undefined) return;
      lines[i][e.target.dataset.f] = parseFloat(e.target.value) || 0;
      e.target.closest('tr').querySelector('.lt').textContent = money(lines[i].qty * price(lines[i]));
      update();
    });
    box.addEventListener('click', e => { const r = e.target.closest('[data-rm]'); if (r) { lines.splice(+r.dataset.rm, 1); render(); } });
    paid && paid.addEventListener('input', update);
    f.elements.supplier_id && f.elements.supplier_id.addEventListener('change', update);
    f.closest('dialog').addEventListener('close', () => { lines.length = 0; f.reset(); render(); });
    update();
  }
  builder('saleForm', 'sale');
  builder('poForm', 'purchase');

  // confirmations ------------------------------------------------------------
  window.voidPrompt = form => {
    const r = prompt('Reason for voiding this sale?');
    if (!r) return false;
    form.void_reason.value = r;
    return true;
  };
  window.confirmRestore = () => confirm('Current business records will be replaced by the backup. Account access is preserved. Continue?');
})();
