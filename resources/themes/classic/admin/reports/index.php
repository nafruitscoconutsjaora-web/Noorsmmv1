<div class="space-y-6 max-w-6xl mx-auto" id="admin-reports-export">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <a href="/admin" class="hover:text-white transition">&larr; Admin</a>
                <span class="text-slate-600">/</span>
                <span class="text-indigo-400 font-semibold">Reports</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white mt-1">Platform Reports & Data Export Center</h1>
            <p class="text-sm text-slate-400 mt-1">Download complete database dumps in certified CSV format for external auditing and accounting.</p>
        </div>
    </div>

    <!-- Export Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Orders Dump -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 flex flex-col justify-between shadow-sm">
            <div>
                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-xl border border-indigo-500/20 mb-3">
                    📦
                </div>
                <h2 class="text-base font-bold text-white">Full Orders Ledger</h2>
                <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                    Export all platform orders with customer usernames, service IDs, target links, initial and remain counters, charges, and provider statuses.
                </p>
            </div>
            <a href="/admin/reports/export/orders" class="mt-6 w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition shadow-sm text-center">
                Download Orders CSV
            </a>
        </div>

        <!-- Users Dump -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 flex flex-col justify-between shadow-sm">
            <div>
                <div class="w-10 h-10 rounded-xl bg-cyan-500/10 text-cyan-400 flex items-center justify-center text-xl border border-cyan-500/20 mb-3">
                    👥
                </div>
                <h2 class="text-base font-bold text-white">Customer Database</h2>
                <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                    Export customer records including User ID, username, email address, account role, current wallet balance, and registration timestamp.
                </p>
            </div>
            <a href="/admin/reports/export/users" class="mt-6 w-full py-2.5 px-4 bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-bold rounded-xl transition shadow-sm text-center">
                Download Users CSV
            </a>
        </div>

        <!-- Financial Statement Dump -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 flex flex-col justify-between shadow-sm">
            <div>
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-xl border border-emerald-500/20 mb-3">
                    💰
                </div>
                <h2 class="text-base font-bold text-white">Financial Statement</h2>
                <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                    Export all customer deposit payments, gateway transaction references, payment statuses, and net amounts credited to balance.
                </p>
            </div>
            <a href="/admin/reports/export/payments" class="mt-6 w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl transition shadow-sm text-center">
                Download Payments CSV
            </a>
        </div>
    </div>
</div>
