<div class="space-y-8">

    <!-- Top Greeting & Stats Grid -->
    <div>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-white tracking-tight">Welcome back, <?= e($user['username']) ?>!</h1>
                <p class="text-xs text-slate-400 mt-1">Here is a summary of your account balance and active marketing campaigns.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="/wallet" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold shadow-md shadow-emerald-600/20 transition">
                    💳 Add Funds
                </a>
                <a href="/orders/new" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/20 transition">
                    + New Order
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Balance Card -->
            <div class="p-5 rounded-2xl bg-gradient-to-br from-indigo-950/80 to-slate-900 border border-indigo-500/20 shadow-lg">
                <div class="text-xs font-semibold text-indigo-300 uppercase tracking-wider">Account Balance</div>
                <div class="text-3xl font-extrabold text-emerald-400 mt-2">₹<?= number_format((float)$user['balance'], 2) ?></div>
                <div class="text-[11px] text-slate-400 mt-1">Ready for automated orders</div>
            </div>

            <!-- Total Orders -->
            <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800">
                <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Orders</div>
                <div class="text-3xl font-extrabold text-white mt-2"><?= number_format((int)($stats['total'] ?? 0)) ?></div>
                <div class="text-[11px] text-slate-400 mt-1">Lifetime campaigns placed</div>
            </div>

            <!-- In Progress / Pending -->
            <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800">
                <div class="text-xs font-semibold text-amber-400 uppercase tracking-wider">In Progress</div>
                <div class="text-3xl font-extrabold text-amber-400 mt-2"><?= number_format((int)($stats['in_progress'] ?? 0) + (int)($stats['pending'] ?? 0)) ?></div>
                <div class="text-[11px] text-slate-400 mt-1">Pending or actively delivering</div>
            </div>

            <!-- Completed -->
            <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800">
                <div class="text-xs font-semibold text-emerald-400 uppercase tracking-wider">Completed</div>
                <div class="text-3xl font-extrabold text-emerald-400 mt-2"><?= number_format((int)($stats['completed'] ?? 0)) ?></div>
                <div class="text-[11px] text-slate-400 mt-1">Successfully fulfilled</div>
            </div>
        </div>
    </div>

    <!-- Quick New Order Card -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
        <div class="flex items-center justify-between pb-4 border-b border-slate-800 mb-6">
            <div>
                <h2 class="text-lg font-bold text-white">Fast Order Placement</h2>
                <p class="text-xs text-slate-400 mt-0.5">Submit an order immediately using your available balance</p>
            </div>
            <a href="/services-list" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition">
                Browse Full Catalog &rarr;
            </a>
        </div>

        <form action="/orders" method="POST" class="space-y-5" id="dashboardOrderForm">
            <?= csrf_field() ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="category_id" class="block text-xs font-semibold text-slate-300 mb-1.5">Category</label>
                    <select id="category_id" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                        <option value="">-- Choose Category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="service_id" class="block text-xs font-semibold text-slate-300 mb-1.5">Service</label>
                    <select id="service_id" name="service_id" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                        <option value="">-- Select Category First --</option>
                    </select>
                </div>
            </div>

            <!-- Service Details Info Box -->
            <div id="serviceInfoBox" class="hidden p-4 rounded-xl bg-slate-950/70 border border-slate-800 text-xs text-slate-300 space-y-2">
                <div class="flex flex-wrap items-center gap-4 text-slate-400">
                    <div>Rate per 1,000: <strong class="text-emerald-400 font-mono text-sm" id="infoRate">₹0.00</strong></div>
                    <div>Min: <span class="text-slate-200 font-mono" id="infoMin">0</span></div>
                    <div>Max: <span class="text-slate-200 font-mono" id="infoMax">0</span></div>
                </div>
                <p id="infoDesc" class="text-slate-400 text-[11px] leading-relaxed italic"></p>
            </div>

            <div>
                <label for="link" class="block text-xs font-semibold text-slate-300 mb-1.5">Target Link / URL</label>
                <input type="url" id="link" name="link" required placeholder="https://instagram.com/yourprofile or post link"
                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 items-end">
                <div>
                    <label for="quantity" class="block text-xs font-semibold text-slate-300 mb-1.5">Quantity</label>
                    <input type="number" id="quantity" name="quantity" required min="1" placeholder="e.g. 1000"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                </div>

                <div class="p-3 bg-slate-950 rounded-xl border border-slate-800 flex items-center justify-between">
                    <div>
                        <div class="text-[11px] text-slate-400">Total Charge:</div>
                        <div class="text-xl font-extrabold text-emerald-400" id="totalCharge">₹0.00</div>
                    </div>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm shadow-md shadow-indigo-600/20 transition">
                        Place Order
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Recent Orders Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
        <div class="p-5 border-b border-slate-800 flex items-center justify-between">
            <h3 class="text-base font-bold text-white">Recent Orders</h3>
            <a href="/orders" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition">
                View All Orders &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/60 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-800">
                    <tr>
                        <th class="py-3.5 px-4">Order ID</th>
                        <th class="py-3.5 px-4">Date</th>
                        <th class="py-3.5 px-4">Service</th>
                        <th class="py-3.5 px-4">Link</th>
                        <th class="py-3.5 px-4">Qty</th>
                        <th class="py-3.5 px-4">Charge</th>
                        <th class="py-3.5 px-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <?php if (empty($recent_orders)): ?>
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-500">No orders placed yet. Place your first order above!</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_orders as $ord): ?>
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-indigo-400">#<?= $ord['id'] ?></td>
                                <td class="py-3.5 px-4 text-slate-400"><?= date('M d, H:i', strtotime($ord['created_at'])) ?></td>
                                <td class="py-3.5 px-4 font-semibold text-white max-w-xs truncate"><?= e($ord['service_name'] ?? 'Service #' . $ord['service_id']) ?></td>
                                <td class="py-3.5 px-4 max-w-xs truncate text-slate-400">
                                    <a href="<?= e($ord['link']) ?>" target="_blank" class="hover:text-indigo-400 hover:underline"><?= e($ord['link']) ?></a>
                                </td>
                                <td class="py-3.5 px-4 font-mono"><?= number_format((int)$ord['quantity']) ?></td>
                                <td class="py-3.5 px-4 font-bold text-emerald-400">₹<?= number_format((float)$ord['charge'], 2) ?></td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider <?= order_status_badge($ord['status']) ?>">
                                        <?= e(str_replace('_', ' ', $ord['status'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const allServices = <?= json_encode($services) ?>;
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

    catSelect.addEventListener('change', function() {
        const catId = this.value;
        serviceSelect.innerHTML = '<option value="">-- Choose Service --</option>';
        if (!catId) return;

        const filtered = allServices.filter(s => s.category_id == catId);
        filtered.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = `#${s.id} - ${s.name} (₹${parseFloat(s.rate).toFixed(2)}/1k)`;
            serviceSelect.appendChild(opt);
        });
    });

    serviceSelect.addEventListener('change', function() {
        const sId = this.value;
        if (!sId) {
            infoBox.classList.add('hidden');
            currentRate = 0;
            calcTotal();
            return;
        }

        const s = allServices.find(item => item.id == sId);
        if (s) {
            currentRate = parseFloat(s.rate);
            infoRate.textContent = '₹' + currentRate.toFixed(2);
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
</script>
