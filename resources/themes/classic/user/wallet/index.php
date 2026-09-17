<div class="space-y-8">
    <div>
        <h1 class="text-2xl font-bold text-white tracking-tight">Wallet & Funds</h1>
        <p class="text-xs text-slate-400 mt-1">Manage your deposit balance, inspect ledger statements, and fund your account.</p>
    </div>

    <!-- Balance Banner & Deposit Form Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left: Current Balance Card & Quick Info -->
        <div class="space-y-4">
            <div class="p-6 rounded-2xl bg-gradient-to-br from-indigo-950/90 via-slate-900 to-slate-950 border border-indigo-500/20 shadow-xl">
                <div class="text-xs font-semibold text-indigo-300 uppercase tracking-wider">Current Balance</div>
                <div class="text-4xl font-extrabold text-emerald-400 mt-2">₹<?= number_format((float)$user['balance'], 2) ?></div>
                <div class="text-xs text-slate-400 mt-2">All payments are credited in INR without hidden conversion surcharges.</div>
            </div>

            <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl text-xs text-slate-300 space-y-3">
                <div class="font-bold text-white flex items-center gap-2">
                    <span class="text-indigo-400">🛡️</span> Instant Razorpay Integration
                </div>
                <p class="text-slate-400 text-[11px] leading-relaxed">
                    Supports Google Pay, PhonePe, Paytm, BHIM UPI, Netbanking, Rupay, Mastercard, and Visa. Funds appear in your account immediately upon payment completion.
                </p>
            </div>
        </div>

        <!-- Right: Deposit Form Card -->
        <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-xl">
            <h2 class="text-lg font-bold text-white mb-1">Add Funds to Wallet</h2>
            <p class="text-xs text-slate-400 mb-6">Choose an amount or enter a custom amount (Minimum deposit: ₹10.00)</p>

            <!-- Quick Amount Chips -->
            <div class="flex flex-wrap gap-2.5 mb-6">
                <?php foreach ([100, 250, 500, 1000, 2000, 5000] as $preset): ?>
                    <button type="button" onclick="selectAmount(<?= $preset ?>)"
                        class="amount-chip px-4 py-2 rounded-xl bg-slate-950 border border-slate-750 hover:border-indigo-500 text-xs font-bold text-slate-200 transition">
                        ₹<?= number_format($preset) ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Initiate Razorpay Payment Form -->
            <form action="/wallet/initiate" method="POST" id="depositForm" class="space-y-5">
                <?= csrf_field() ?>

                <div>
                    <label for="depositAmount" class="block text-xs font-semibold text-slate-300 mb-1.5">Deposit Amount (INR ₹)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400 font-bold text-sm">₹</span>
                        <input type="number" id="depositAmount" name="amount" required min="10" step="1" placeholder="500" value="500"
                            class="w-full pl-8 pr-4 py-3 rounded-xl bg-slate-950 border border-slate-750 text-slate-100 text-base font-bold focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-slate-950/70 border border-slate-800 flex items-center justify-between text-xs">
                    <span class="text-slate-400">Payment Gateway:</span>
                    <span class="font-bold text-white flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span> Razorpay (UPI, Cards, Netbanking)
                    </span>
                </div>

                <button type="submit" class="w-full py-3.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-sm shadow-lg shadow-indigo-600/25 transition">
                    Proceed to Pay with Razorpay &rarr;
                </button>
            </form>

            <!-- Test Sandbox Deposit Simulator (for rapid verification in dev/sandbox) -->
            <?php if (config('payments.razorpay.mode') === 'test'): ?>
                <div class="mt-6 pt-6 border-t border-slate-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-amber-400">Developer Test Mode:</span>
                            <p class="text-[11px] text-slate-400">Simulate instant deposit without real gateway authorization</p>
                        </div>
                        <form action="/wallet/test-deposit" method="POST" class="flex items-center gap-2">
                            <?= csrf_field() ?>
                            <input type="hidden" name="amount" id="testAmount" value="500">
                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/20 text-xs font-semibold transition">
                                Simulate ₹500 Credit
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Transaction Ledger Statement -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
        <div class="p-5 border-b border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-white">Wallet Transactions Ledger</h3>
                <p class="text-xs text-slate-400 mt-0.5">Immutable audit trail of debits, credits, and refunds</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/70 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-800">
                    <tr>
                        <th class="py-3.5 px-4">Txn ID</th>
                        <th class="py-3.5 px-4">Date</th>
                        <th class="py-3.5 px-4">Type</th>
                        <th class="py-3.5 px-4">Amount</th>
                        <th class="py-3.5 px-4">Balance Before</th>
                        <th class="py-3.5 px-4">Balance After</th>
                        <th class="py-3.5 px-4">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <?php if (empty($transactions['items'])): ?>
                        <tr>
                            <td colspan="7" class="py-10 text-center text-slate-500">No transactions recorded yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions['items'] as $tx): ?>
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="py-3.5 px-4 font-mono text-slate-500">#<?= $tx['id'] ?></td>
                                <td class="py-3.5 px-4 text-slate-400 whitespace-nowrap"><?= date('M d, H:i:s', strtotime($tx['created_at'])) ?></td>
                                <td class="py-3.5 px-4">
                                    <?php if ($tx['type'] === 'credit'): ?>
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Credit</span>
                                    <?php elseif ($tx['type'] === 'refund'): ?>
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">Refund</span>
                                    <?php else: ?>
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-500/10 text-rose-400 border border-rose-500/20">Debit</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 font-bold font-mono <?= in_array($tx['type'], ['credit', 'refund']) ? 'text-emerald-400' : 'text-rose-400' ?>">
                                    <?= in_array($tx['type'], ['credit', 'refund']) ? '+' : '-' ?>₹<?= number_format((float)$tx['amount'], 2) ?>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-slate-400">₹<?= number_format((float)$tx['balance_before'], 2) ?></td>
                                <td class="py-3.5 px-4 font-mono text-white font-semibold">₹<?= number_format((float)$tx['balance_after'], 2) ?></td>
                                <td class="py-3.5 px-4 text-slate-300 max-w-sm truncate"><?= e($tx['description'] ?? '&mdash;') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function selectAmount(val) {
        const input = document.getElementById('depositAmount');
        input.value = val;
        const testInput = document.getElementById('testAmount');
        if (testInput) testInput.value = val;
    }
</script>
