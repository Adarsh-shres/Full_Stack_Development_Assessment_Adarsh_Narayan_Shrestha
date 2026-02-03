async function fetchJSON(url, options = {}) {
  const r = await fetch(url, options);
  if (!r.ok) throw new Error("Request failed");
  return r.json();
}

function $(sel) { return document.querySelector(sel); }

function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

const searchCache = new Map();

async function refreshLowStockBadge() {
  const badge = document.querySelector("#lowStockBadge");
  if (!badge) return;

  try {
    const url = `${window.APP_BASE_URL}/../api/low_stock_count.php`;
    const r = await fetchJSON(url);

    badge.textContent = r.count;
    badge.style.display = (r.count > 0) ? "inline-flex" : "none";
  } catch {}
}

async function updateStock(productId, qtyChange, movementType = "adjustment", note = "") {
  const fd = new FormData();
  fd.append("csrf", csrfToken());
  fd.append("product_id", productId);
  fd.append("movement_type", movementType);
  fd.append("qty_change", qtyChange);
  fd.append("note", note);

  const url = `${window.APP_BASE_URL}/../api/update_stock.php`;
  return fetchJSON(url, { method: "POST", body: fd });
}

function renderResults(rows) {
  const tbody = $("#resultsBody");
  if (!tbody) return;

  if (!rows.length) {
    tbody.innerHTML = `<tr><td colspan="9" class="muted">No results</td></tr>`;
    return;
  }

  tbody.innerHTML = rows.map(r => {
    const low = (parseInt(r.stock_qty, 10) <= parseInt(r.low_stock_threshold, 10));

    return `
      <tr data-row-id="${r.id}">
        <td>${r.sku}</td>
        <td>${r.name}</td>
        <td>${r.category_name}</td>
        <td>${r.supplier_name}</td>
        <td>${r.price}</td>

        <td class="stock-cell"><span class="stock-val">${r.stock_qty}</span></td>

        <td>
          ${low ? "<span class='badge low'>LOW</span>" : "<span class='badge ok'>OK</span>"}
        </td>

        <td>
          <div class="quick-stock">
            <select class="qs-select" aria-label="Stock action">
              <option value="sale">Sale (-)</option>
              <option value="restock">Restock (+)</option>
            </select>

            <input class="qs-input" type="number" step="1" value="1" min="1" />
            <button type="button" class="btn qs-apply">Apply</button>

            <span class="qs-msg muted"></span>
          </div>
        </td>

        <td><a class="btn btn-secondary" href="${window.APP_BASE_URL}/product.php?id=${r.id}">View</a></td>
      </tr>
    `;
  }).join("");

  tbody.querySelectorAll("tr").forEach(tr => {
    const productId = tr.getAttribute("data-row-id");
    const input = tr.querySelector(".qs-input");
    const select = tr.querySelector(".qs-select");
    const msg = tr.querySelector(".qs-msg");
    const stockVal = tr.querySelector(".stock-val");
    const applyBtn = tr.querySelector(".qs-apply");

    applyBtn.addEventListener("click", async () => {
      const qty = Math.max(1, parseInt(input.value || "1", 10));
      const mode = select.value;
      const qtyChange = (mode === "sale") ? (-qty) : (qty);

      msg.textContent = "Updating...";

      try {
        const r = await updateStock(productId, qtyChange, mode, "Dashboard quick update");

        stockVal.textContent = r.newQty;

        const badgeCell = tr.children[6];
        badgeCell.innerHTML = r.isLow
          ? "<span class='badge low'>LOW</span>"
          : "<span class='badge ok'>OK</span>";

        msg.textContent = "Saved";
        setTimeout(() => { msg.textContent = ""; }, 1100);

        searchCache.clear();
        refreshLowStockBadge();
      } catch {
        msg.textContent = "Failed";
      }
    });

    input.addEventListener("keydown", (e) => {
      if (e.key === "Enter") applyBtn.click();
    });
  });
}

async function runSearch(opts = {}) {
  const force = !!opts.force;

  const q = $("#q")?.value ?? "";
  const category_id = $("#category_id")?.value ?? 0;
  const supplier_id = $("#supplier_id")?.value ?? 0;
  const min_price = $("#min_price")?.value ?? "";
  const max_price = $("#max_price")?.value ?? "";
  const min_stock = $("#min_stock")?.value ?? "";
  const max_stock = $("#max_stock")?.value ?? "";
  const low_only = $("#low_only")?.checked ? 1 : 0;

  const baseUrl = `${window.APP_BASE_URL}/../api/search_products.php?` +
    `q=${encodeURIComponent(q)}&category_id=${category_id}&supplier_id=${supplier_id}` +
    `&min_price=${encodeURIComponent(min_price)}&max_price=${encodeURIComponent(max_price)}` +
    `&min_stock=${encodeURIComponent(min_stock)}&max_stock=${encodeURIComponent(max_stock)}` +
    `&low_only=${low_only}`;

  if (!force && searchCache.has(baseUrl)) {
    renderResults(searchCache.get(baseUrl));
    return;
  }

  const url = force ? `${baseUrl}&_ts=${Date.now()}` : baseUrl;

  const rows = await fetchJSON(url, force ? { cache: "no-store" } : {});
  searchCache.set(baseUrl, rows);
  renderResults(rows);
}

async function doAutocomplete(inputSel, boxSel, onPick) {
  const input = $(inputSel);
  const box = $(boxSel);
  if (!input || !box) return;

  const term = input.value.trim();
  if (term.length < 2) { box.style.display = "none"; box.innerHTML = ""; return; }

  const url = `${window.APP_BASE_URL}/../api/autocomplete.php?term=${encodeURIComponent(term)}`;
  const items = await fetchJSON(url);

  if (!items.length) { box.style.display = "none"; box.innerHTML = ""; return; }

  box.innerHTML = items.map(i => `<div class="ac-item" data-id="${i.id}">${i.label}</div>`).join("");
  box.style.display = "block";

  box.onclick = (e) => {
    const el = e.target.closest(".ac-item");
    if (!el) return;

    onPick(el.dataset.id, el.textContent);

    box.style.display = "none";
    box.innerHTML = "";
  };
}

function setupDashboard() {
  if (!$("#q")) return;

  let t = null;

  $("#q").addEventListener("input", () => {
    clearTimeout(t);
    t = setTimeout(() => {
      doAutocomplete("#q", "#autocomplete", (id, label) => {
        const skuOnly = label.split(" — ")[0];
        $("#q").value = skuOnly;

        runSearch({ force: true }).catch(()=>{});
      }).catch(()=>{});

      runSearch().catch(()=>{});
    }, 200);
  });

  document.addEventListener("click", (e) => {
    const box = $("#autocomplete");
    const input = $("#q");
    if (!box || !input) return;

    if (box.contains(e.target) || input.contains(e.target)) return;
    box.style.display = "none";
  });

  ["#category_id","#supplier_id","#low_only"].forEach(sel => {
    const el = $(sel);
    if (el) el.addEventListener("change", () => runSearch().catch(()=>{}));
  });

  ["#min_price","#max_price","#min_stock","#max_stock"].forEach(sel => {
    const el = $(sel);
    if (!el) return;

    let tt = null;
    el.addEventListener("input", () => {
      clearTimeout(tt);
      tt = setTimeout(() => runSearch().catch(()=>{}), 250);
    });
  });

  // Refresh button = clear cache + force fetch
  $("#runSearchBtn")?.addEventListener("click", () => {
    searchCache.clear();
    runSearch({ force: true }).catch(()=>{});
  });

  runSearch().catch(()=>{});
}

function setupStockPage() {
  const input = $("#stock_search");
  const box = $("#stock_autocomplete");
  const form = $("#stockForm");
  if (!input || !box || !form) return;

  let t = null;
  input.addEventListener("input", () => {
    clearTimeout(t);
    t = setTimeout(() => {
      doAutocomplete("#stock_search", "#stock_autocomplete", (id, label) => {
        $("#product_id").value = id;
        $("#stock_search").value = label;
      }).catch(()=>{});
    }, 200);
  });

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    if (!$("#product_id").value) {
      alert("Pick a product from the autocomplete list first.");
      return;
    }

    const fd = new FormData(form);
    const url = `${window.APP_BASE_URL}/../api/update_stock.php`;

    const msg = $("#stockResult");
    msg.style.display = "block";

    try {
      const r = await fetchJSON(url, { method: "POST", body: fd });
      msg.className = "alert success";
      msg.textContent = `Updated. New stock: ${r.newQty} (${r.isLow ? "LOW" : "OK"})`;
      form.reset();
      $("#product_id").value = "";
      searchCache.clear();
      refreshLowStockBadge();
    } catch {
      msg.className = "alert error";
      msg.textContent = "Update failed.";
    }
  });
}

document.addEventListener("DOMContentLoaded", () => {
  setupDashboard();
  setupStockPage();
  refreshLowStockBadge();
});
