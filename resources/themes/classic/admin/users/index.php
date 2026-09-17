<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Users & Wallets</h1>
            <p class="text-xs text-slate-400 mt-1">Audit customer balances, custom discount rates, and account access.</p>
        </div>

        <form action="/admin/users" method="GET" class="w-full sm:w-72 flex items-center gap-2">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search username, email..."
                class="w-full px-3.5 py-1.5 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
            <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-750 text-white text-xs font-medium transition">
                Search
            </button>
        </form>
    </div>

    <!-- Users Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/70 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-800">
                    <tr>
                        <th class="py-3.5 px-4">User ID</th>
                        <th class="py-3.5 px-4">Username</th>
                        <th class="py-3.5 px-4">Email</th>
                        <th class="py-3.5 px-4">Balance</th>
                        <th class="py-3.5 px-4">Discount Rate</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4">Registered</th>
                        <th class="py-3.5 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <?php if (empty($users['items'])): ?>
                        <tr><td colspan="8" class="py-12 text-center text-slate-500">No users found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users['items'] as $u): ?>
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-rose-400">#<?= $u['id'] ?></td>
                                <td class="py-3.5 px-4 font-semibold text-white">
                                    <a href="/admin/users/<?= $u['id'] ?>" class="hover:text-rose-400 transition">
                                        <?= e($u['username']) ?>
                                    </a>
                                </td>
                                <td class="py-3.5 px-4 text-slate-400"><?= e($u['email']) ?></td>
                                <td class="py-3.5 px-4 font-mono font-bold text-emerald-400">
                                    ₹<?= number_format((float)$u['balance'], 2) ?>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-slate-400"><?= (float)($u['custom_rate'] ?? 0) ?>%</td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold <?= $u['status'] === 'active' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' ?>">
                                        <?= ucfirst($u['status']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-400"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                                <td class="py-3.5 px-4 text-right">
                                    <a href="/admin/users/<?= $u['id'] ?>" class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition">
                                        Manage &rarr;
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($users['last_page'] > 1): ?>
            <div class="p-4 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
                <div>Page <?= $users['page'] ?> of <?= $users['last_page'] ?> (<?= $users['total'] ?> total)</div>
                <div class="flex items-center gap-2">
                    <?php if ($users['page'] > 1): ?>
                        <a href="/admin/users?page=<?= $users['page'] - 1 ?>&search=<?= urlencode($search) ?>" class="px-3 py-1 rounded bg-slate-800 hover:bg-slate-700 text-white transition">Prev</a>
                    <?php endif; ?>
                    <?php if ($users['page'] < $users['last_page']): ?>
                        <a href="/admin/users?page=<?= $users['page'] + 1 ?>&search=<?= urlencode($search) ?>" class="px-3 py-1 rounded bg-slate-800 hover:bg-slate-700 text-white transition">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
