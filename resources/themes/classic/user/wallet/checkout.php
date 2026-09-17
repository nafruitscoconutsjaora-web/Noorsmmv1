<div class="max-w-xl mx-auto space-y-6">
    <div>
        <a href="/wallet" class="inline-flex items-center gap-1 text-xs text-slate-400 hover:text-white transition mb-3">
            &larr; Return to Wallet
        </a>
        <h1 class="text-2xl font-bold text-white tracking-tight">Complete Deposit</h1>
        <p class="text-xs text-slate-400 mt-1">Pay securely via Razorpay gateway using UPI, Card, or Netbanking.</p>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-xl space-y-6">
        <div class="flex items-center justify-between border-b border-slate-800 pb-5">
            <div>
                <span class="text-xs text-slate-400">Order Reference</span>
                <div class="font-mono text-xs font-semibold text-slate-200 mt-0.5"><?= e($intent['gateway_order_id']) ?></div>
            </div>
            <div class="text-right">
                <span class="text-xs text-slate-400">Amount Due</span>
                <div class="text-2xl font-extrabold text-emerald-400 font-mono">₹<?= number_format((float)$intent['amount'], 2) ?></div>
            </div>
        </div>

        <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800 space-y-2 text-xs text-slate-300">
            <div class="flex items-center justify-between">
                <span class="text-slate-400">Payer Name:</span>
                <span class="font-semibold text-white"><?= e($user['username']) ?></span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-slate-400">Email:</span>
                <span class="font-semibold text-white"><?= e($user['email']) ?></span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-slate-400">Currency:</span>
                <span class="font-semibold text-white"><?= e($intent['currency'] ?? 'INR') ?></span>
            </div>
        </div>

        <!-- Hidden Razorpay Callback Form -->
        <form id="razorpayVerifyForm" action="/wallet/verify" method="POST" class="hidden">
            <?= csrf_field() ?>
            <input type="hidden" name="razorpay_order_id" id="rzp_order_id" value="">
            <input type="hidden" name="razorpay_payment_id" id="rzp_payment_id" value="">
            <input type="hidden" name="razorpay_signature" id="rzp_signature" value="">
        </form>

        <button type="button" id="rzp-button"
            class="w-full py-3.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-sm shadow-lg shadow-indigo-600/25 transition flex items-center justify-center gap-2">
            <span>Pay ₹<?= number_format((float)$intent['amount'], 2) ?> with Razorpay</span>
        </button>

        <?php if (!empty($intent['mock'])): ?>
            <!-- Fallback Mock / Simulator Button if running test keys -->
            <div class="pt-4 border-t border-slate-800 text-center">
                <div class="text-xs text-slate-400 mb-2">Sandbox Simulator (Auto-approves without card entry)</div>
                <button type="button" onclick="simulateSuccess()" class="px-4 py-2 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/20 text-xs font-semibold transition">
                    Simulate Payment Success &rarr;
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    const options = {
        "key": "<?= e($intent['key_id'] ?? '') ?>",
        "amount": <?= (int)round(((float)$intent['amount']) * 100) ?>,
        "currency": "<?= e($intent['currency'] ?? 'INR') ?>",
        "name": "<?= e(config('app.name', 'Apex SMM')) ?>",
        "description": "Wallet Deposit",
        "order_id": "<?= e($intent['gateway_order_id']) ?>",
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
        <?php if (!empty($intent['mock'])): ?>
            simulateSuccess();
        <?php else: ?>
            rzp.open();
            e.preventDefault();
        <?php endif; ?>
    };

    function simulateSuccess() {
        const orderId = "<?= e($intent['gateway_order_id']) ?>";
        const fakePaymentId = "pay_" + Math.random().toString(36).substring(2, 14);
        const fakeSig = "sig_" + Math.random().toString(36).substring(2, 14);

        document.getElementById('rzp_order_id').value = orderId;
        document.getElementById('rzp_payment_id').value = fakePaymentId;
        document.getElementById('rzp_signature').value = fakeSig;
        document.getElementById('razorpayVerifyForm').submit();
    }
</script>
