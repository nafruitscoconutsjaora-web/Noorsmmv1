<!-- Hero Section -->
<div class="relative overflow-hidden py-16 sm:py-24 border-b border-slate-800/80">
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_80%_80%_at_50%_-20%,rgba(99,102,241,0.18),rgba(255,255,255,0))]"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="text-center max-w-3xl mx-auto">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-semibold uppercase tracking-wider mb-6">
                <span class="w-2 h-2 rounded-full bg-indigo-400 animate-ping"></span>
                #1 Wholesale SMM Reseller Platform
            </div>
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-white leading-tight">
                Scale Your Social Presence <span class="gradient-text">At Automated Speed</span>
            </h1>
            <p class="mt-6 text-lg text-slate-300 leading-relaxed">
                Empowering digital marketing agencies, influencers, and resellers with automated API fulfillment, rock-bottom wholesale prices in INR, and 24/7 dedicated support.
            </p>
            <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="/register" class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-base shadow-lg shadow-indigo-600/30 transition text-center">
                    Create Free Account
                </a>
                <a href="/services" class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-slate-900 border border-slate-800 hover:bg-slate-800 text-slate-200 font-semibold text-base transition text-center">
                    View Services & Prices
                </a>
            </div>
        </div>

        <!-- Metric Counters -->
        <div class="mt-16 grid grid-cols-2 md:grid-cols-4 gap-4 max-w-4xl mx-auto">
            <div class="bg-slate-900/80 border border-slate-800/80 p-6 rounded-2xl text-center">
                <div class="text-3xl font-extrabold text-white tracking-tight"><?= number_format((int)$total_orders) ?></div>
                <div class="text-xs font-medium text-slate-400 mt-1 uppercase tracking-wider">Orders Completed</div>
            </div>
            <div class="bg-slate-900/80 border border-slate-800/80 p-6 rounded-2xl text-center">
                <div class="text-3xl font-extrabold text-indigo-400 tracking-tight"><?= number_format((int)$total_services) ?></div>
                <div class="text-xs font-medium text-slate-400 mt-1 uppercase tracking-wider">Active Services</div>
            </div>
            <div class="bg-slate-900/80 border border-slate-800/80 p-6 rounded-2xl text-center">
                <div class="text-3xl font-extrabold text-emerald-400 tracking-tight"><?= number_format((int)$total_users) ?></div>
                <div class="text-xs font-medium text-slate-400 mt-1 uppercase tracking-wider">Happy Resellers</div>
            </div>
            <div class="bg-slate-900/80 border border-slate-800/80 p-6 rounded-2xl text-center">
                <div class="text-3xl font-extrabold text-white tracking-tight">99.9%</div>
                <div class="text-xs font-medium text-slate-400 mt-1 uppercase tracking-wider">API Uptime</div>
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="py-16 sm:py-20 bg-slate-950">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <h2 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">Engineered For High-Volume Resellers</h2>
            <p class="mt-3 text-slate-400 text-sm">Everything you need to automate order dispatch, manage customer campaigns, and maximize margins.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-slate-900/90 border border-slate-800 p-6 rounded-2xl">
                <div class="w-10 h-10 rounded-xl bg-indigo-600/10 text-indigo-400 flex items-center justify-center font-bold text-xl mb-4">
                    ⚡
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Automated Instant Dispatch</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Our platform communicates directly with Tier-1 upstream providers using resilient background queues. Orders execute within seconds of checkout.
                </p>
            </div>

            <div class="bg-slate-900/90 border border-slate-800 p-6 rounded-2xl">
                <div class="w-10 h-10 rounded-xl bg-emerald-600/10 text-emerald-400 flex items-center justify-center font-bold text-xl mb-4">
                    💳
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Instant Razorpay Deposits</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Deposit via UPI, Google Pay, PhonePe, Cards, and Netbanking with immediate wallet credit verified by cryptographic signatures.
                </p>
            </div>

            <div class="bg-slate-900/90 border border-slate-800 p-6 rounded-2xl">
                <div class="w-10 h-10 rounded-xl bg-purple-600/10 text-purple-400 flex items-center justify-center font-bold text-xl mb-4">
                    🔌
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Industry Standard API v2</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Seamlessly connect your own custom SMM panel or website using our standard JSON endpoints for service catalogs, balance checks, and order tracking.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Popular Services Preview -->
<div class="py-16 bg-slate-900/50 border-t border-b border-slate-800/80">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
            <div>
                <h2 class="text-2xl font-bold text-white tracking-tight">Popular Services Catalog</h2>
                <p class="text-xs text-slate-400 mt-1">Explore real-time rates per 1,000 quantity with instant delivery</p>
            </div>
            <a href="/services" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 flex items-center gap-1 transition">
                View All <?= count($services) ?> Services &rarr;
            </a>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-950/60 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="py-3.5 px-4 font-semibold">ID</th>
                            <th class="py-3.5 px-4 font-semibold">Service Name</th>
                            <th class="py-3.5 px-4 font-semibold">Rate / 1000</th>
                            <th class="py-3.5 px-4 font-semibold">Min / Max</th>
                            <th class="py-3.5 px-4 font-semibold text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300 text-xs">
                        <?php foreach (array_slice($services, 0, 8) as $srv): ?>
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="py-3.5 px-4 font-mono text-slate-500">#<?= $srv['id'] ?></td>
                                <td class="py-3.5 px-4">
                                    <div class="font-semibold text-white"><?= e($srv['name']) ?></div>
                                    <div class="text-[11px] text-slate-400"><?= e($srv['category_name']) ?></div>
                                </td>
                                <td class="py-3.5 px-4 font-bold text-emerald-400">
                                    ₹<?= number_format((float)$srv['rate'], 2) ?>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-slate-400">
                                    <?= number_format((int)$srv['min_quantity']) ?> - <?= number_format((int)$srv['max_quantity']) ?>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <a href="/orders/new?service_id=<?= $srv['id'] ?>" class="inline-flex items-center px-3 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-xs transition">
                                        Order
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- FAQ Section -->
<div class="py-16 bg-slate-950">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">Frequently Asked Questions</h2>
            <p class="text-xs text-slate-400 mt-2">Clear answers to get you started smoothly</p>
        </div>

        <div class="space-y-4">
            <div class="bg-slate-900/90 border border-slate-800 p-5 rounded-xl">
                <h3 class="text-sm font-bold text-white mb-2">How fast do orders start processing?</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Most orders begin processing automatically within 0 to 60 seconds of submission. High-volume services and custom drip-feeds are monitored via real-time cron workers.
                </p>
            </div>

            <div class="bg-slate-900/90 border border-slate-800 p-5 rounded-xl">
                <h3 class="text-sm font-bold text-white mb-2">What payment options do you support?</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    We support instant deposits via Razorpay, including all Indian UPI apps (Google Pay, PhonePe, Paytm, BHIM), debit/credit cards, and netbanking.
                </p>
            </div>

            <div class="bg-slate-900/90 border border-slate-800 p-5 rounded-xl">
                <h3 class="text-sm font-bold text-white mb-2">What happens if an order fails or gets cancelled?</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Our atomic wallet service automatically refunds the exact remaining charge back to your panel wallet immediately so you never lose funds.
                </p>
            </div>
        </div>
    </div>
</div>
