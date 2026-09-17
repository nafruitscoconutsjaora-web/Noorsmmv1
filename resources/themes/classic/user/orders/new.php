<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-white tracking-tight">Place New Order</h1>
        <p class="text-xs text-slate-400 mt-1">Select your desired service, enter the target link, and specify quantity.</p>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-xl">
        <form action="/orders" method="POST" class="space-y-6" id="orderForm">
            <?= csrf_field() ?>

            <!-- Category -->
            <div>
                <label for="category_id" class="block text-xs font-semibold text-slate-300 mb-1.5">1. Select Platform & Category</label>
                <select id="category_id" class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                    <option value="">-- Choose Category --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Service -->
            <div>
                <label for="service_id" class="block text-xs font-semibold text-slate-300 mb-1.5">2. Select Service</label>
                <select id="service_id" name="service_id" required class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                    <option value="">-- Select Category First --</option>
                </select>
            </div>

            <!-- Service Info Box -->
            <div id="serviceInfoBox" class="hidden p-5 rounded-xl bg-slate-950/80 border border-slate-800 space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-800/80 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-slate-400">Rate:</span>
                        <span class="text-base font-extrabold text-emerald-400" id="infoRate">₹0.00 / 1k</span>
                    </div>
                    <div class="flex items-center gap-4 text-xs font-mono text-slate-300">
                        <div>Min: <span id="infoMin" class="text-white font-bold">0</span></div>
                        <div>Max: <span id="infoMax" class="text-white font-bold">0</span></div>
                    </div>
                </div>
                <p id="infoDesc" class="text-xs text-slate-400 leading-relaxed"></p>
            </div>

            <!-- Target Link -->
            <div>
                <label for="link" class="block text-xs font-semibold text-slate-300 mb-1.5">3. Target Link or Username</label>
                <input type="url" id="link" name="link" required placeholder="https://..." 
                    class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                <div class="text-[11px] text-slate-500 mt-1">Make sure account is PUBLIC before submitting. Private links will fail.</div>
            </div>

            <!-- Quantity & Calculated Price -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 items-end">
                <div>
                    <label for="quantity" class="block text-xs font-semibold text-slate-300 mb-1.5">4. Quantity</label>
                    <input type="number" id="quantity" name="quantity" required min="1" placeholder="e.g. 1000"
                        class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                </div>

                <div class="p-3.5 bg-slate-950 rounded-xl border border-slate-800 flex items-center justify-between">
                    <div>
                        <div class="text-[11px] text-slate-400 uppercase tracking-wider">Total Charge</div>
                        <div class="text-2xl font-extrabold text-emerald-400" id="totalCharge">₹0.00</div>
                    </div>
                    <?php $u = auth_user(); ?>
                    <div class="text-right text-[11px] text-slate-400">
                        Wallet: <span class="text-white font-semibold">₹<?= number_format((float)($u['balance'] ?? 0), 2) ?></span>
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full py-3.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-base shadow-lg shadow-indigo-600/25 transition flex items-center justify-center gap-2">
                <span>Submit Order</span>
            </button>
        </form>
    </div>
</div>

<script>
    const servicesData = <?= json_encode($services) ?>;
    const preselectedServiceId = <?= (int)$preselected_service_id ?>;

    const catSelect = document.getElementById('category_id');
    const serviceSelect = document.getElementById('service_id');
    const qtyInput = document.getElementById('quantity');
    const totalChargeEl = document.getElementById('totalCharge');
    const infoBox = document.getElementById('serviceInfoBox');
    const infoRate = document.getElementById('infoRate');
    const infoMin = document.getElementById('infoMin');
    const infoMax = document.getElementById('infoMax');
    const infoDesc = document.getElementById('infoDesc');

    let currentRate = 0;

    function populateServices(catId, selectedId = null) {
        serviceSelect.innerHTML = '<option value="">-- Choose Service --</option>';
        if (!catId) return;

        const filtered = servicesData.filter(s => s.category_id == catId);
        filtered.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = `#${s.id} - ${s.name} (₹${parseFloat(s.rate).toFixed(2)} / 1k)`;
            if (selectedId && s.id == selectedId) {
                opt.selected = true;
            }
            serviceSelect.appendChild(opt);
        });

        if (selectedId) {
            loadService(selectedId);
        }
    }

    function loadService(sId) {
        if (!sId) {
            infoBox.classList.add('hidden');
            currentRate = 0;
            calcTotal();
            return;
        }

        const s = servicesData.find(item => item.id == sId);
        if (s) {
            currentRate = parseFloat(s.rate);
            infoRate.textContent = '₹' + currentRate.toFixed(2) + ' / 1,000';
            infoMin.textContent = Number(s.min_quantity).toLocaleString();
            infoMax.textContent = Number(s.max_quantity).toLocaleString();
            qtyInput.min = s.min_quantity;
            qtyInput.max = s.max_quantity;
            if (s.description) {
                infoDesc.textContent = s.description;
                infoDesc.classList.remove('hidden');
            } else {
                infoDesc.classList.add('hidden');
            }
            infoBox.classList.remove('hidden');
            calcTotal();
        }
    }

    catSelect.addEventListener('change', function() {
        populateServices(this.value);
    });

    serviceSelect.addEventListener('change', function() {
        loadService(this.value);
    });

    qtyInput.addEventListener('input', calcTotal);

    function calcTotal() {
        const qty = parseInt(qtyInput.value) || 0;
        if (currentRate > 0 && qty > 0) {
            const total = (qty / 1000) * currentRate;
            totalChargeEl.textContent = '₹' + total.toFixed(4);
        } else {
            totalChargeEl.textContent = '₹0.00';
        }
    }

    // Handle preselected service ID if navigated from services catalog
    if (preselectedServiceId > 0) {
        const preSrv = servicesData.find(s => s.id == preselectedServiceId);
        if (preSrv) {
            catSelect.value = preSrv.category_id;
            populateServices(preSrv.category_id, preselectedServiceId);
        }
    }
</script>
