<div class="space-y-8">
    <div>
        <h1 class="text-2xl font-bold text-white tracking-tight">Wallet & Funds</h1>
        <p class="text-xs text-slate-400 mt-1">Manage your deposit balance, inspect ledger statements, and fund your account securely.</p>
    </div>

    <!-- Balance Banner & Deposit Form Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left: Current Balance Card & Quick Info -->
        <div class="space-y-4">
            <div class="p-6 rounded-2xl bg-gradient-to-br from-indigo-950/90 via-slate-900 to-slate-950 border border-indigo-500/20 shadow-xl">
                <div class="text-xs font-semibold text-indigo-300 uppercase tracking-wider">Current Balance</div>
                <div class="text-4xl font-extrabold text-emerald-400 mt-2">₹<?= number_format((float)$user['balance'], 2) ?></div>
                <div class="text-xs text-slate-400 mt-2">Instant deposit processing with automated wallet balance crediting.</div>
            </div>

            <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl text-xs text-slate-300 space-y-3">
                <div class="font-bold text-white flex items-center gap-2">
                    <span class="text-indigo-400">🛡️</span> Multi-Gateway Security
                </div>
                <p class="text-slate-400 text-[11px] leading-relaxed">
                    All payment processing happens over end-to-end 256-bit encrypted TLS connections. Transactions are audited and reconciled idempotently.
                </p>
            </div>
        </div>

        <!-- Right: Deposit Form Card -->
        <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-xl">
            <h2 class="text-lg font-bold text-white mb-1">Add Funds to Wallet</h2>
            <p class="text-xs text-slate-400 mb-6">Choose a payment gateway and enter the deposit amount to continue.</p>

            <!-- Quick Amount Chips -->
            <div class="flex flex-wrap gap-2.5 mb-6">
                <?php foreach ([100, 250, 500, 1000, 2000, 5000] as $preset): ?>
                    <button type="button" onclick="selectAmount(<?= $preset ?>)"
                        class="amount-chip px-4 py-2 rounded-xl bg-slate-950 border border-slate-750 hover:border-indigo-500 text-xs font-bold text-slate-200 transition">
                        ₹<?= number_format($preset) ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Initiate Payment Form -->
            <form action="/wallet/initiate" method="POST" id="depositForm" class="space-y-5">
                <?= csrf_field() ?>

                <!-- Gateway Selection -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-2">Select Payment Method</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="gatewayList">
                        <?php if (empty($activeGateways)): ?>
                            <div class="sm:col-span-2 p-4 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-300 text-xs">
                                No payment gateways are currently active. Please contact administrator.
                            </div>
                        <?php else: ?>
                            <?php foreach ($activeGateways as $idx => $gw): ?>
                                <?php
                                    $isFirst = $idx === 0;
                                    $feeText = '';
                                    if ((float)$gw['percent_fee'] > 0 && (float)$gw['fixed_fee'] > 0) {
                                        $feeText = "Fee: {$gw['percent_fee']}% + ₹{$gw['fixed_fee']}";
                                    } elseif ((float)$gw['percent_fee'] > 0) {
                                        $feeText = "Fee: {$gw['percent_fee']}%";
                                    } elseif ((float)$gw['fixed_fee'] > 0) {
                                        $feeText = "Fee: ₹{$gw['fixed_fee']}";
                                    } else {
                                        $feeText = "No Fee (0%)";
                                    }

                                    $hasBonus = !empty($gw['bonus_enabled']) && (float)$gw['bonus_value'] > 0;
                                ?>
                                <label class="gateway-option relative flex items-center p-3.5 rounded-xl border cursor-pointer transition <?= $isFirst ? 'border-indigo-500 bg-indigo-500/10' : 'border-slate-800 bg-slate-950/60 hover:border-slate-700' ?>">
                                    <input type="radio" name="gateway" value="<?= e($gw['code']) ?>" <?= $isFirst ? 'checked' : '' ?>
                                        data-min="<?= (float)$gw['min_amount'] ?>"
                                        data-max="<?= (float)$gw['max_amount'] ?>"
                                        data-percent-fee="<?= (float)$gw['percent_fee'] ?>"
                                        data-fixed-fee="<?= (float)$gw['fixed_fee'] ?>"
                                        data-bonus-enabled="<?= $hasBonus ? '1' : '0' ?>"
                                        data-bonus-type="<?= e($gw['bonus_type'] ?? 'percentage') ?>"
                                        data-bonus-val="<?= (float)$gw['bonus_value'] ?>"
                                        class="sr-only" onchange="updateGatewaySelection(this)">
                                    <div class="flex items-center justify-between w-full">
                                        <div class="space-y-0.5">
                                            <div class="font-bold text-xs text-white flex items-center gap-1.5">
                                                <span class="gateway-radio-dot w-2 h-2 rounded-full <?= $isFirst ? 'bg-indigo-400' : 'bg-slate-600' ?>"></span>
                                                <?= e($gw['name']) ?>
                                            </div>
                                            <div class="text-[11px] text-slate-400 pl-3.5">
                                                <?= e($feeText) ?> &bull; Min: ₹<?= number_format((float)$gw['min_amount'], 0) ?>
                                            </div>
                                        </div>
                                        <?php if ($hasBonus): ?>
                                            <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
                                                +<?= (float)$gw['bonus_value'] ?><?= $gw['bonus_type'] === 'percentage' ? '%' : '₹' ?> Bonus
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Deposit Amount -->
                <div>
                    <label for="depositAmount" class="block text-xs font-semibold text-slate-300 mb-1.5">Deposit Amount (INR ₹)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400 font-bold text-sm">₹</span>
                        <input type="number" id="depositAmount" name="amount" required min="10" step="1" placeholder="500" value="500"
                            class="w-full pl-8 pr-4 py-3 rounded-xl bg-slate-950 border border-slate-750 text-slate-100 text-base font-bold focus:ring-2 focus:ring-indigo-500 focus:outline-none transition"
                            oninput="recalculateSummary()">
                    </div>
                </div>

                <!-- Live Summary Breakdown Card -->
                <div class="p-4 rounded-xl bg-slate-950/80 border border-slate-800 space-y-2 text-xs">
                    <div class="flex items-center justify-between text-slate-400">
                        <span>Deposit Amount:</span>
                        <span class="font-mono text-slate-200" id="summaryBaseAmount">₹500.00</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-400">
                        <span>Gateway Processing Fee:</span>
                        <span class="font-mono text-slate-200" id="summaryFee">₹0.00</span>
                    </div>
                    <div class="flex items-center justify-between text-emerald-400" id="summaryBonusRow" style="display: none;">
                        <span>Bonus Credit:</span>
                        <span class="font-mono font-bold" id="summaryBonus">+₹0.00</span>
                    </div>
                    <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between font-bold text-sm">
                        <span class="text-white">Total Payable Amount:</span>
                        <span class="text-indigo-400 font-mono" id="summaryPayable">₹500.00</span>
                    </div>
                    <div class="flex items-center justify-between text-[11px] text-slate-400">
                        <span>Credited to Wallet:</span>
                        <span class="text-emerald-400 font-mono font-bold" id="summaryCredit">₹500.00</span>
                    </div>
                </div>

                <button type="submit" id="submitBtn" class="w-full py-3.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-sm shadow-lg shadow-indigo-600/25 transition flex items-center justify-center gap-2">
                    <span>Proceed to Payment</span>
                    <span>&rarr;</span>
                </button>
            </form>

            <!-- Test Sandbox Simulator in Dev/Test Mode -->
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
    recalculateSummary();
}

function updateGatewaySelection(radio) {
    document.querySelectorAll('.gateway-option').forEach(el => {
        el.classList.remove('border-indigo-500', 'bg-indigo-500/10');
        el.classList.add('border-slate-800', 'bg-slate-950/60');
        const dot = el.querySelector('.gateway-radio-dot');
        if (dot) {
            dot.classList.remove('bg-indigo-400');
            dot.classList.add('bg-slate-600');
        }
    });

    const parent = radio.closest('.gateway-option');
    if (parent) {
        parent.classList.remove('border-slate-800', 'bg-slate-950/60');
        parent.classList.add('border-indigo-500', 'bg-indigo-500/10');
        const dot = parent.querySelector('.gateway-radio-dot');
        if (dot) {
            dot.classList.remove('bg-slate-600');
            dot.classList.add('bg-indigo-400');
        }
    }

    recalculateSummary();
}

function recalculateSummary() {
    const amountInput = document.getElementById('depositAmount');
    const amount = parseFloat(amountInput.value) || 0;

    const selectedGateway = document.querySelector('input[name="gateway"]:checked');
    let percentFee = 0;
    let fixedFee = 0;
    let bonusEnabled = 0;
    let bonusType = 'percentage';
    let bonusVal = 0;

    if (selectedGateway) {
        percentFee = parseFloat(selectedGateway.dataset.percentFee) || 0;
        fixedFee = parseFloat(selectedGateway.dataset.fixedFee) || 0;
        bonusEnabled = parseInt(selectedGateway.dataset.bonusEnabled) || 0;
        bonusType = selectedGateway.dataset.bonusType || 'percentage';
        bonusVal = parseFloat(selectedGateway.dataset.bonusVal) || 0;
    }

    const fee = (amount * (percentFee / 100)) + fixedFee;
    const payable = amount + fee;

    let bonus = 0;
    if (bonusEnabled && bonusVal > 0) {
        if (bonusType === 'percentage') {
            bonus = amount * (bonusVal / 100);
        } else {
            bonus = bonusVal;
        }
    }
    const credit = amount + bonus;

    document.getElementById('summaryBaseAmount').textContent = '₹' + amount.toFixed(2);
    document.getElementById('summaryFee').textContent = '₹' + fee.toFixed(2);
    document.getElementById('summaryPayable').textContent = '₹' + payable.toFixed(2);
    document.getElementById('summaryCredit').textContent = '₹' + credit.toFixed(2);

    const bonusRow = document.getElementById('summaryBonusRow');
    if (bonus > 0) {
        bonusRow.style.display = 'flex';
        document.getElementById('summaryBonus').textContent = '+₹' + bonus.toFixed(2);
    } else {
        bonusRow.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    recalculateSummary();
});
</script>
