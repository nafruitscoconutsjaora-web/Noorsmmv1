<div class="space-y-6 max-w-4xl mx-auto" id="user-reports-export-page">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <a href="/dashboard" class="hover:text-white transition">&larr; Dashboard</a>
                <span class="text-slate-600">/</span>
                <span class="text-indigo-400 font-semibold">Reports</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white mt-1">Data Export & Accounting Reports</h1>
            <p class="text-sm text-slate-400 mt-1">Generate and download certified CSV spreadsheets of your orders and financial statements.</p>
        </div>
    </div>

    <!-- Export Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Orders Export -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 flex flex-col justify-between shadow-sm">
            <div>
                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-lg border border-indigo-500/20 mb-4">
                    📦
                </div>
                <h2 class="text-base font-bold text-white">Export Order Book (CSV)</h2>
                <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                    Download full dispatch records including Order IDs, Service Name, Link, Quantity, Charged Amount, Starting Counter, Remains, and Delivery Statuses.
                </p>
            </div>
            <form action="/reports/export/orders" method="GET" class="mt-6 space-y-3">
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-400 mb-1">From Date</label>
                        <input type="date" name="start_date" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-2.5 py-1.5 text-xs text-white">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-400 mb-1">To Date</label>
                        <input type="date" name="end_date" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-2.5 py-1.5 text-xs text-white">
                    </div>
                </div>
                <button type="submit" class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition shadow-sm flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Download Orders CSV
                </button>
            </form>
        </div>

        <!-- Wallet Statement Export -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 flex flex-col justify-between shadow-sm">
            <div>
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-lg border border-emerald-500/20 mb-4">
                    💰
                </div>
                <h2 class="text-base font-bold text-white">Wallet Financial Statement (CSV)</h2>
                <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                    Download complete audit ledger including deposit credits, order debits, refunds, opening and closing balance checkpoints, and transaction timestamps.
                </p>
            </div>
            <form action="/reports/export/wallet" method="GET" class="mt-6 space-y-3">
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-400 mb-1">From Date</label>
                        <input type="date" name="start_date" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-2.5 py-1.5 text-xs text-white">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-400 mb-1">To Date</label>
                        <input type="date" name="end_date" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-2.5 py-1.5 text-xs text-white">
                    </div>
                </div>
                <button type="submit" class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl transition shadow-sm flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Download Statement CSV
                </button>
            </form>
        </div>
    </div>
</div>
