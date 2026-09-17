<div class="py-12 bg-slate-950">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-10">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 text-indigo-400 text-xs font-semibold uppercase mb-3">
                REST API v1 / v2
            </div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">API Documentation</h1>
            <p class="text-sm text-slate-400 mt-2">
                Connect your website, bot, or panel directly to our high-speed fulfillment network using our standard reseller API.
            </p>
        </div>

        <!-- Connection Overview Card -->
        <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl mb-8 space-y-4">
            <h3 class="text-base font-bold text-white">General Information</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div class="p-3 bg-slate-950 rounded-xl border border-slate-800">
                    <span class="text-slate-400 block mb-1">HTTP Method:</span>
                    <span class="font-mono text-indigo-400 font-bold">POST (application/x-www-form-urlencoded or multipart/form-data)</span>
                </div>
                <div class="p-3 bg-slate-950 rounded-xl border border-slate-800">
                    <span class="text-slate-400 block mb-1">API Endpoint URL:</span>
                    <span class="font-mono text-emerald-400 font-bold"><?= e(config('app.url', 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost:3000'))) ?>/api/v1</span>
                </div>
                <div class="p-3 bg-slate-950 rounded-xl border border-slate-800">
                    <span class="text-slate-400 block mb-1">Response Format:</span>
                    <span class="font-mono text-purple-400 font-bold">JSON</span>
                </div>
                <div class="p-3 bg-slate-950 rounded-xl border border-slate-800">
                    <span class="text-slate-400 block mb-1">Authentication:</span>
                    <span class="font-mono text-slate-200">Include <code class="text-indigo-400">key</code> parameter with your API key</span>
                </div>
            </div>
        </div>

        <!-- Action: Services List -->
        <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl mb-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-white">1. Service List</h3>
                <span class="px-2.5 py-1 rounded bg-indigo-500/10 text-indigo-400 text-xs font-mono">action: services</span>
            </div>
            <p class="text-xs text-slate-400 mb-4">Returns a full list of all available active services, categories, rates, and limits.</p>

            <div class="text-xs font-semibold text-slate-300 mb-2">Request Parameters:</div>
            <div class="bg-slate-950 p-3 rounded-xl border border-slate-800 mb-4 font-mono text-xs text-slate-300">
                key: YOUR_API_KEY<br>
                action: services
            </div>

            <div class="text-xs font-semibold text-slate-300 mb-2">Sample JSON Response:</div>
            <pre class="bg-slate-950 p-4 rounded-xl border border-slate-800 text-emerald-400 font-mono text-xs overflow-x-auto">[
  {
    "service": 1,
    "name": "Instagram Real Followers [Instant HQ]",
    "type": "Default",
    "category": "Instagram Followers",
    "rate": "120.00000000",
    "min": 100,
    "max": 50000,
    "dripfeed": true,
    "refill": true,
    "cancel": true
  }
]</pre>
        </div>

        <!-- Action: Add Order -->
        <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl mb-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-white">2. Place New Order</h3>
                <span class="px-2.5 py-1 rounded bg-emerald-500/10 text-emerald-400 text-xs font-mono">action: add</span>
            </div>
            <p class="text-xs text-slate-400 mb-4">Debits your wallet atomically and dispatches the order to fulfillment.</p>

            <div class="text-xs font-semibold text-slate-300 mb-2">Request Parameters:</div>
            <div class="bg-slate-950 p-3 rounded-xl border border-slate-800 mb-4 font-mono text-xs text-slate-300 space-y-1">
                <div><span class="text-indigo-400">key:</span> YOUR_API_KEY</div>
                <div><span class="text-indigo-400">action:</span> add</div>
                <div><span class="text-indigo-400">service:</span> Service ID (e.g. 1)</div>
                <div><span class="text-indigo-400">link:</span> Target URL / username (e.g. https://instagram.com/p/xxx)</div>
                <div><span class="text-indigo-400">quantity:</span> Quantity needed (e.g. 1000)</div>
            </div>

            <div class="text-xs font-semibold text-slate-300 mb-2">Sample JSON Response:</div>
            <pre class="bg-slate-950 p-4 rounded-xl border border-slate-800 text-emerald-400 font-mono text-xs overflow-x-auto">{
  "order": 10429
}</pre>
        </div>

        <!-- Action: Check Status -->
        <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl mb-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-white">3. Order Status</h3>
                <span class="px-2.5 py-1 rounded bg-purple-500/10 text-purple-400 text-xs font-mono">action: status</span>
            </div>

            <div class="text-xs font-semibold text-slate-300 mb-2">Single Order:</div>
            <div class="bg-slate-950 p-3 rounded-xl border border-slate-800 mb-4 font-mono text-xs text-slate-300">
                key: YOUR_API_KEY<br>
                action: status<br>
                order: 10429
            </div>

            <pre class="bg-slate-950 p-4 rounded-xl border border-slate-800 text-emerald-400 font-mono text-xs overflow-x-auto">{
  "charge": "120.00",
  "start_count": "1420",
  "status": "COMPLETED",
  "remains": "0",
  "currency": "INR"
}</pre>
        </div>

        <!-- Action: User Balance -->
        <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-white">4. User Balance</h3>
                <span class="px-2.5 py-1 rounded bg-amber-500/10 text-amber-400 text-xs font-mono">action: balance</span>
            </div>

            <div class="bg-slate-950 p-3 rounded-xl border border-slate-800 mb-4 font-mono text-xs text-slate-300">
                key: YOUR_API_KEY<br>
                action: balance
            </div>

            <pre class="bg-slate-950 p-4 rounded-xl border border-slate-800 text-emerald-400 font-mono text-xs overflow-x-auto">{
  "balance": "4850.50",
  "currency": "INR"
}</pre>
        </div>
    </div>
</div>
