<?php
$actionType = $intent['action_type'] ?? 'sdk';
$gatewayCode = $intent['gateway_code'] ?? ($gateway ?? 'razorpay');
?>

<div class="max-w-xl mx-auto space-y-6">
    <div>
        <a href="/wallet" class="inline-flex items-center gap-1 text-xs text-slate-400 hover:text-white transition mb-3">
            &larr; Return to Wallet
        </a>
        <h1 class="text-2xl font-bold text-white tracking-tight">Complete Deposit</h1>
        <p class="text-xs text-slate-400 mt-1">Payment via <?= e($intent['gateway_name'] ?? ucfirst($gatewayCode)) ?></p>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-xl space-y-6">
        <div class="flex items-center justify-between border-b border-slate-800 pb-5">
            <div>
                <span class="text-xs text-slate-400">Order Reference</span>
                <div class="font-mono text-xs font-semibold text-slate-200 mt-0.5"><?= e($intent['order_id'] ?? $intent['gateway_order_id'] ?? '') ?></div>
            </div>
            <div class="text-right">
                <span class="text-xs text-slate-400">Total Payable</span>
                <div class="text-2xl font-extrabold text-emerald-400 font-mono">
                    <?= e($intent['currency'] === 'INR' ? '₹' : ($intent['currency'] . ' ')) ?><?= number_format((float)($intent['payable_amount'] ?? $intent['amount'] ?? 0), 2) ?>
                </div>
            </div>
        </div>

        <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800 space-y-2 text-xs text-slate-300">
            <div class="flex items-center justify-between">
                <span class="text-slate-400">Base Deposit:</span>
                <span class="font-semibold text-white">₹<?= number_format((float)($intent['requested_amount'] ?? $intent['amount'] ?? 0), 2) ?></span>
            </div>
            <?php if (!empty($intent['fee']) && (float)$intent['fee'] > 0): ?>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400">Gateway Fee:</span>
                    <span class="font-semibold text-slate-300">₹<?= number_format((float)$intent['fee'], 2) ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($intent['bonus']) && (float)$intent['bonus'] > 0): ?>
                <div class="flex items-center justify-between text-emerald-400">
                    <span>Bonus Credit Added:</span>
                    <span class="font-bold">+₹<?= number_format((float)$intent['bonus'], 2) ?></span>
                </div>
            <?php endif; ?>
            <div class="flex items-center justify-between pt-1 border-t border-slate-800/80">
                <span class="text-slate-400">Credited to Balance:</span>
                <span class="font-bold text-emerald-400">₹<?= number_format((float)($intent['wallet_credit'] ?? $intent['amount'] ?? 0), 2) ?></span>
            </div>
        </div>

        <?php if ($actionType === 'qr'): ?>
            <!-- QR Code Mode -->
            <div class="text-center space-y-4 py-4">
                <div class="inline-block p-4 rounded-2xl bg-white shadow-2xl">
                    <?php if (!empty($intent['qr_code_url'])): ?>
                        <img src="<?= e($intent['qr_code_url']) ?>" alt="Payment QR Code" class="w-48 h-48 mx-auto">
                    <?php else: ?>
                        <div class="w-48 h-48 bg-slate-100 flex items-center justify-center text-slate-400 text-xs font-mono">
                            <?= e($intent['qr_code_data'] ?? 'Scan QR Code') ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="space-y-1">
                    <p class="text-xs text-slate-400">Scan using any supported app or send payment to:</p>
                    <div class="font-mono text-xs font-bold text-white bg-slate-950 p-2.5 rounded-xl border border-slate-800 select-all">
                        <?= e($intent['address'] ?? $intent['upi_id'] ?? $intent['payment_address'] ?? 'apex-smm@upi') ?>
                    </div>
                </div>
            </div>

            <form action="/wallet/verify" method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="gateway" value="<?= e($gatewayCode) ?>">
                <input type="hidden" name="order_id" value="<?= e($intent['order_id'] ?? '') ?>">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Transaction ID / Reference UTR</label>
                    <input type="text" name="transaction_id" required placeholder="Enter 12-digit UTR or TxHash"
                        class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 text-xs font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <button type="submit" class="w-full py-3.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm shadow-lg shadow-emerald-600/25 transition">
                    Confirm Payment & Credit Balance &rarr;
                </button>
            </form>

        <?php elseif ($actionType === 'instructions'): ?>
            <!-- Manual / Bank Transfer Instructions Mode -->
            <div class="space-y-4">
                <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-2 text-xs text-slate-300">
                    <div class="font-bold text-white text-sm mb-1">Deposit Instructions</div>
                    <div class="text-slate-400 text-xs leading-relaxed whitespace-pre-wrap"><?= nl2br(e($intent['instructions'] ?? 'Please transfer the exact amount and enter the transaction reference below.')) ?></div>
                </div>

                <form action="/wallet/verify" method="POST" class="space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="gateway" value="<?= e($gatewayCode) ?>">
                    <input type="hidden" name="order_id" value="<?= e($intent['order_id'] ?? '') ?>">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Transaction Reference / UTR Number</label>
                        <input type="text" name="transaction_id" required placeholder="Enter bank reference number"
                            class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 text-xs font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <button type="submit" class="w-full py-3.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-sm shadow-lg shadow-indigo-600/25 transition">
                        Submit Payment Verification &rarr;
                    </button>
                </form>
            </div>

        <?php elseif ($actionType === 'form'): ?>
            <!-- Form Auto-Post Mode -->
            <form id="gatewayForm" action="<?= e($intent['form_action'] ?? '') ?>" method="<?= e($intent['form_method'] ?? 'POST') ?>">
                <?php foreach (($intent['form_fields'] ?? []) as $k => $v): ?>
                    <input type="hidden" name="<?= e($k) ?>" value="<?= e((string)$v) ?>">
                <?php endforeach; ?>
                <div class="text-center py-6">
                    <p class="text-xs text-slate-400 mb-4">Redirecting you securely to payment portal...</p>
                    <button type="submit" class="px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs transition">
                        Click here if not redirected automatically &rarr;
                    </button>
                </div>
            </form>
            <script>document.getElementById('gatewayForm').submit();</script>

        <?php else: ?>
            <!-- SDK Checkout Mode (Razorpay & others) -->
            <?php if ($gatewayCode === 'razorpay'): ?>
                <!-- Hidden Razorpay Callback Form -->
                <form id="razorpayVerifyForm" action="/wallet/verify" method="POST" class="hidden">
                    <?= csrf_field() ?>
                    <input type="hidden" name="gateway" value="razorpay">
                    <input type="hidden" name="order_id" value="<?= e($intent['order_id'] ?? $intent['gateway_order_id'] ?? '') ?>">
                    <input type="hidden" name="razorpay_order_id" id="rzp_order_id" value="">
                    <input type="hidden" name="razorpay_payment_id" id="rzp_payment_id" value="">
                    <input type="hidden" name="razorpay_signature" id="rzp_signature" value="">
                </form>

                <button type="button" id="rzp-button"
                    class="w-full py-3.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-sm shadow-lg shadow-indigo-600/25 transition flex items-center justify-center gap-2">
                    <span>Pay with Razorpay &rarr;</span>
                </button>

                <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
                <script>
                    const options = {
                        "key": "<?= e($intent['checkout_data']['key'] ?? $intent['key_id'] ?? '') ?>",
                        "amount": <?= (int)round(((float)($intent['payable_amount'] ?? $intent['amount'])) * 100) ?>,
                        "currency": "<?= e($intent['currency'] ?? 'INR') ?>",
                        "name": "<?= e(config('app.name', 'Apex SMM')) ?>",
                        "description": "Wallet Deposit",
                        "order_id": "<?= e($intent['gateway_order_id'] ?? $intent['order_id'] ?? '') ?>",
                        "prefill": {
                            "name": "<?= e($user['username']) ?>",
                            "email": "<?= e($user['email']) ?>"
                        },
                        "theme": {
                            "color": "#6366f1"
                        },
                        "handler": function (response) {
                            document.getElementById('rzp_order_id').value = response.razorpay_order_id;
                            document.getElementById('rzp_payment_id').value = response.razorpay_payment_id;
                            document.getElementById('rzp_signature').value = response.razorpay_signature;
                            document.getElementById('razorpayVerifyForm').submit();
                        }
                    };

                    const rzp = new Razorpay(options);
                    document.getElementById('rzp-button').onclick = function(e){
                        rzp.open();
                        e.preventDefault();
                    };
                </script>

            <?php else: ?>
                <!-- Universal verification button for SDK / manual gateway -->
                <form action="/wallet/verify" method="POST" class="space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="gateway" value="<?= e($gatewayCode) ?>">
                    <input type="hidden" name="order_id" value="<?= e($intent['order_id'] ?? '') ?>">
                    <button type="submit" class="w-full py-3.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-sm shadow-lg shadow-indigo-600/25 transition">
                        Proceed with <?= e($intent['gateway_name'] ?? ucfirst($gatewayCode)) ?> &rarr;
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
