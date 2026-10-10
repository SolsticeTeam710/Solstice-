(() => {
  const productsEl = document.getElementById("products"), cart = /* @__PURE__ */ new Map();
  let products = [], category = "all", query = "", payment = "CASH";
  const tableParam = new URLSearchParams(window.location.search).get("meja");
  const tableNumber = /^0?[1-4]$/.test(tableParam || "") ? Number(tableParam) : null;
  let lastOrderId = sessionStorage.getItem("solsticeLastOrderId");
  let trackingRequestInFlight = false;
  const trackLastOrderButton = document.getElementById("track-last-order");

  function syncTrackLastOrderButton() {
    if (trackLastOrderButton) {
      trackLastOrderButton.hidden = !lastOrderId;
    }
  }

  syncTrackLastOrderButton();
  const format = (value) => "Rp " + new Intl.NumberFormat("id-ID").format(Number(value || 0));
  const escapeHtml = (value) => String(value ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c]);
  const itemId = (item) => String(item.id_menu ?? item.id ?? item.nama_menu);
  const itemName = (item) => item.nama_menu ?? item.name ?? "Menu Solstice";
  const itemPrice = (item) => Number(item.harga ?? item.price ?? 0);
  const categoryKey = (item) => {
    const value = String(item.nama_kategori ?? item.kategori ?? "").toLowerCase();
    if (value.includes("non")) return "non-coffee";
    if (value.includes("makan") || value.includes("snack")) return "makanan";
    if (value.includes("coffee") || value.includes("kopi")) return "coffee";
    return "lainnya";
  };
  const quantity = () => [...cart.values()].reduce((sum, line) => sum + line.quantity, 0);
  const subtotal = () => [...cart.values()].reduce((sum, line) => sum + itemPrice(line.item) * line.quantity, 0);
  const tax = () => Math.round(subtotal() * 0.1);
  const total = () => subtotal() + tax();
  const toast = (message) => {
    const node = document.getElementById("toast");
    node.textContent = message;
    node.classList.add("show");
    clearTimeout(toast.timer);
    toast.timer = setTimeout(() => node.classList.remove("show"), 2400);
  };
  const picture = (item) => {
    const name = itemName(item).trim().toLowerCase();
    const mapped = name.includes("arak") ? "/images/menu/Arak.webp" : name.includes("matcha latte") ? "/images/menu/Matcha%20Latte.png" : null;
    const photo = item.foto;
    const fallback = categoryKey(item) === "coffee" ? "\u2615" : categoryKey(item) === "makanan" ? "\u{1F950}" : "\u{1F375}";
    let url = mapped;
    if (!url && photo) {
      const value = String(photo).replace(/\\/g, "/").replace(/^public\//i, "").replace(/^\/+/, "");
      url = /^https?:\/\//i.test(photo) ? photo : /^(images|storage)\//i.test(value) ? `/${value}` : `/storage/${value}`;
    }
    if (!url) return `<span class="fallback" aria-hidden="true">${fallback}</span>`;
    return `<img loading="lazy" src="${escapeHtml(url)}" alt="${escapeHtml(itemName(item))}" onerror="this.hidden=true;this.nextElementSibling.hidden=false"><span class="fallback" aria-hidden="true" hidden>${fallback}</span>`;
  };
  function renderProducts() {
    const shown = products.filter((item) => (category === "all" || categoryKey(item) === category) && `${itemName(item)} ${item.deskripsi || ""}`.toLowerCase().includes(query.toLowerCase()));
    productsEl.innerHTML = shown.length ? shown.map((item) => {
      const id = itemId(item), amount = cart.get(id)?.quantity || 0;
      return `<article class="product"><div class="product-photo">${picture(item)}</div><div class="product-body"><h2 class="product-title">${escapeHtml(itemName(item))}</h2><div class="product-desc">${escapeHtml(item.deskripsi || item.nama_kategori || "Dibuat segar untuk Anda")}</div><div class="product-bottom"><span class="price">${format(itemPrice(item))}</span><button class="add" data-add="${escapeHtml(id)}" aria-label="Tambah ${escapeHtml(itemName(item))} ke keranjang">${amount ? `+ Tambah (${amount})` : "+ Tambah"}</button></div></div></article>`;
    }).join("") : `<div class="empty">${products.length ? "Menu tidak ditemukan. Coba kata kunci lain." : "Menu belum tersedia saat ini."}</div>`;
  }
  function renderCart() {
    const lines = [...cart.values()];
    document.getElementById("cart-count").textContent = `${quantity()} item dipilih`;
    document.getElementById("cart-sum").textContent = format(total());
    const rows = lines.map(({ item, quantity: q }) => `<div class="cart-item"><div><div class="item-name">${escapeHtml(itemName(item))}</div><div class="item-price">${format(itemPrice(item))} per item</div></div><div class="qty"><button data-qty="${escapeHtml(itemId(item))}" data-delta="-1" aria-label="Kurangi">\u2212</button><strong>${q}</strong><button data-qty="${escapeHtml(itemId(item))}" data-delta="1" aria-label="Tambah">+</button></div><div class="item-total">${format(itemPrice(item) * q)}</div></div>`).join("");
    document.getElementById("cart-items").innerHTML = rows || '<div class="empty">Keranjang masih kosong. Yuk, pilih menu favoritmu!</div>';
    document.getElementById("cart-totals").innerHTML = totalsMarkup();
    document.getElementById("checkout-items").innerHTML = rows || '<div class="empty">Belum ada item.</div>';
    document.getElementById("checkout-totals").innerHTML = totalsMarkup();
    renderCashChange();
    document.getElementById("go-checkout").disabled = !lines.length;
    document.getElementById("go-checkout").style.opacity = lines.length ? "1" : ".55";
    renderProducts();
  }
  function renderCashChange() {
    const panel = document.getElementById("cash-change-panel");
    const input = document.getElementById("cash-amount");
    const result = document.getElementById("cash-change-result");
    if (!panel || !input || !result) return;
    panel.hidden = payment !== "CASH";
    if (payment !== "CASH") return;
    const amount = Number(input.value);
    if (!input.value || !Number.isFinite(amount) || amount < 0) {
      result.textContent = "Masukkan jumlah uang untuk melihat kembalian.";
    } else if (amount < total()) {
      result.textContent = `Uang masih kurang ${format(total() - amount)}.`;
    } else {
      result.textContent = `Kembalian: ${format(amount - total())}`;
    }
  }
  function totalsMarkup() {
    return `<div class="total-row"><span>Subtotal</span><strong>${format(subtotal())}</strong></div><div class="total-row"><span>PB1 (Pajak 10%)</span><strong>${format(tax())}</strong></div><div class="total-row grand"><span>Total pembayaran</span><strong>${format(total())}</strong></div>`;
  }

  async function refreshTrackingStatus() {
    const message = document.getElementById("tracking-message");

    if (!lastOrderId) {
      if (message) message.textContent = "Belum ada pesanan yang bisa dilacak.";
      return;
    }

    if (trackingRequestInFlight) return;
    trackingRequestInFlight = true;

    try {
      const response = await fetch(`/api/pesanan/${encodeURIComponent(lastOrderId)}`, {
        headers: { Accept: "application/json" },
      });
      const result = await response.json().catch(() => ({}));

      if (!response.ok) {
        throw new Error(result.message || `Gagal memuat status (${response.status}).`);
      }

      const states = {
        pending: {
          currentStep: 2,
          message: "Pesanan diterima. Menunggu konfirmasi kasir.",
          step2Title: "Menunggu konfirmasi kasir",
          step2Description: "Kasir akan mengonfirmasi pesanan dan pembayaran Anda.",
        },
        diproses: {
          currentStep: 3,
          message: "Pembayaran dikonfirmasi. Pesanan sedang disiapkan.",
          step2Title: "Pesanan dikonfirmasi kasir",
          step2Description: "Pembayaran Anda sudah diverifikasi.",
        },
        siap: {
          currentStep: 3,
          message: "Pesanan siap diambil.",
          step2Title: "Pesanan dikonfirmasi kasir",
          step2Description: "Pembayaran Anda sudah diverifikasi.",
        },
        selesai: {
          currentStep: 3,
          finished: true,
          message: "Pesanan selesai dan sudah diserahkan. Terima kasih!",
          step2Title: "Pesanan dikonfirmasi kasir",
          step2Description: "Pembayaran Anda sudah diverifikasi.",
        },
      };
      const state = states[result.status];

      if (!state) throw new Error(`Status pesanan tidak dikenal: ${result.status || "kosong"}.`);

      if (message) message.textContent = state.message;

      const step2 = document.querySelector('[data-track-step="2"]');
      if (step2) {
        step2.querySelector("strong").textContent = state.step2Title;
        step2.querySelector("p").textContent = state.step2Description;
      }

      document.querySelectorAll("[data-track-step]").forEach((step) => {
        const number = Number(step.dataset.trackStep);
        const done = state.finished || number < state.currentStep;
        step.classList.toggle("done", done);
        step.classList.toggle("current", !state.finished && number === state.currentStep);

        const mark = step.querySelector(".step-mark");
        if (mark) mark.textContent = done ? "✓" : String(number);
      });
    } catch (error) {
      if (message) message.textContent = error.message || "Status pesanan gagal dimuat. Coba lagi.";
    } finally {
      trackingRequestInFlight = false;
    }
  }

  function showPage(name) {
    if (!document.getElementById(`page-${name}`)) return;
    if (name === "cart" && !quantity()) {
      toast("Keranjang masih kosong. Pilih menu dahulu.");
      return;
    }
    document.querySelectorAll(".page").forEach((page) => page.classList.toggle("active", page.id === `page-${name}`));
    document.getElementById("cartbar").style.display = ["menu", "cart"].includes(name) ? "flex" : "none";
    window.scrollTo({ top: 0, behavior: "smooth" });
    if (name === "cart") renderCart();
    if (name === "checkout") renderCart();
    if (name === "tracking") void refreshTrackingStatus();
  }
  async function loadMenu() {
    try {
      const response = await fetch("/menu", { headers: { Accept: "application/json" } });
      if (!response.ok) throw new Error("Tidak dapat memuat menu");
      const data = await response.json();
      products = Array.isArray(data) ? data : data.data || [];
      renderProducts();
    } catch (error) {
      productsEl.innerHTML = '<div class="empty">Menu belum dapat dimuat. Periksa koneksi lalu coba lagi.</div>';
    }
  }
  document.addEventListener("click", (event) => {
    const filter = event.target.closest("[data-category]");
    if (filter) {
      category = filter.dataset.category;
      document.querySelectorAll(".filter").forEach((button) => {
        const selected = button === filter;
        button.classList.toggle("active", selected);
        button.setAttribute("aria-pressed", String(selected));
      });
      renderProducts();
    }
    const add = event.target.closest("[data-add]");
    if (add) {
      const item = products.find((product) => itemId(product) === add.dataset.add);
      if (!item) return;
      const line = cart.get(add.dataset.add);
      cart.set(add.dataset.add, { item, quantity: (line?.quantity || 0) + 1 });
      renderCart();
    }
    const change = event.target.closest("[data-qty]");
    if (change) {
      const line = cart.get(change.dataset.qty);
      if (line) {
        line.quantity += Number(change.dataset.delta);
        if (line.quantity <= 0) cart.delete(change.dataset.qty);
        renderCart();
      }
    }
    const page = event.target.closest("[data-page]");
    if (page) showPage(page.dataset.page);
    const trackLastOrder = event.target.closest("#track-last-order");
    if (trackLastOrder && lastOrderId) {
      document.getElementById("track-order").textContent = `#${lastOrderId}`;
      showPage("tracking");
    }
    const option = event.target.closest("[data-payment]");
    if (option) {
      payment = option.dataset.payment;
      document.querySelectorAll(".payment-option").forEach((button) => {
        const selected = button === option;
        button.classList.toggle("active", selected);
        button.setAttribute("aria-pressed", String(selected));
      });
      renderCashChange();
    }
  });
  document.getElementById("menu-search").addEventListener("input", (event) => {
    query = event.target.value.trim();
    renderProducts();
  });
  document.getElementById("cash-amount").addEventListener("input", renderCashChange);
  document.getElementById("go-checkout").addEventListener("click", () => {
    if (quantity()) showPage("checkout");
  });
  document.getElementById("confirm-order").addEventListener("click", async (event) => {
    if (!quantity()) {
      showPage("menu");
      return;
    }

    if (tableNumber === null) {
      toast("Nomor meja tidak valid. Silakan pindai QR di meja Anda.");
      return;
    }

    const name = document.getElementById("customer-name").value.trim();
    if (!name) {
      document.getElementById("customer-name").focus();
      toast("Masukkan nama pelanggan terlebih dahulu.");
      return;
    }

    const cashAmount = Number(document.getElementById("cash-amount").value);
    if (payment === "CASH" && (!document.getElementById("cash-amount").value || cashAmount < total())) {
      document.getElementById("cash-amount").focus();
      toast("Masukkan uang tunai minimal sebesar total pembayaran.");
      return;
    }

    const items = [...cart.values()].map(({ item, quantity }) => ({
      id_menu: Number(item.id_menu),
      jumlah_pesanan: quantity,
    }));

    if (items.some((item) => !Number.isInteger(item.id_menu))) {
      toast("ID menu tidak valid. Coba refresh halaman.");
      return;
    }

    const button = event.currentTarget;
    button.disabled = true;

    try {
      const response = await fetch("/api/pesanan", {
        method: "POST",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({
  nama_pelanggan: name,
  nomor_meja: Number(new URLSearchParams(window.location.search).get("meja")),
  metode_pembayaran: payment,
  nominal_bayar: payment === "CASH" ? cashAmount : total(),
  catatan: document.getElementById("order-note").value.trim(),
  items,
}),
      });

      const result = await response.json().catch(() => ({}));
      if (!response.ok) {
        const validationError = Object.values(result.errors || {}).flat()[0];
        throw new Error(validationError || result.message || "Pesanan gagal disimpan.");
      }
      if (!result.order?.order_id) throw new Error("Server tidak mengirim ID pesanan.");

      lastOrderId = result.order.order_id;
      sessionStorage.setItem("solsticeLastOrderId", lastOrderId);
      syncTrackLastOrderButton();

      document.getElementById("success-name").textContent = name;
      document.getElementById("success-order").textContent = `#${lastOrderId}`;
      document.getElementById("track-order").textContent = `#${lastOrderId}`;
      document.getElementById("success-total").textContent = format(result.order.total_harga);

      cart.clear();
      renderCart();
      showPage("success");
    } catch (error) {
      toast(error.message || "Pesanan gagal dikirim. Coba lagi.");
    } finally {
      button.disabled = false;
    }
  });
  document.querySelectorAll("[data-category]").forEach((button) => button.setAttribute("aria-pressed", button.classList.contains("active")));
  renderCart();
  loadMenu();
  setInterval(() => {
    const trackingPage = document.getElementById("page-tracking");
    if (trackingPage?.classList.contains("active")) void refreshTrackingStatus();
  }, 5000);
})();
