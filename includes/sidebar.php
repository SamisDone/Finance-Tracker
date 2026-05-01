<?php
if (!isset($pageTitle)) $pageTitle = '';
?>
<div id="sidebar-backdrop" class="fixed inset-0 z-40 hidden bg-black/60 backdrop-blur-sm lg:hidden"></div>
<aside id="sidebar" class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col border-r border-zinc-800 bg-zinc-950/95 backdrop-blur transition-transform duration-300 lg:translate-x-0">
    <div class="flex h-16 items-center justify-between border-b border-zinc-800 px-5">
        <a href="dashboard.php" class="flex items-center gap-2">
            <span class="grid h-8 w-8 place-items-center rounded-lg bg-gradient-to-br from-violet-500 to-cyan-400 text-zinc-950 font-bold">F</span>
            <span class="font-semibold tracking-tight text-zinc-50">FinPulse</span>
        </a>
        <button data-sidebar-close class="rounded-md p-1.5 text-zinc-400 hover:bg-zinc-800 hover:text-zinc-50 lg:hidden">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>
    <nav class="flex-1 space-y-1 overflow-y-auto p-3">
        <a href="dashboard.php" class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-all duration-200 <?php echo ($pageTitle === 'Dashboard') ? 'bg-zinc-800/80 text-zinc-50 shadow-glow' : 'text-zinc-400 hover:text-zinc-50 hover:bg-zinc-800/50'; ?>">
            <svg class="h-4 w-4 transition-transform group-hover:scale-110" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12l9-9 9 9"/><path d="M5 10v10h14V10"/></svg>
            <span>Dashboard</span>
        </a>
        <a href="income.php" class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-all duration-200 <?php echo ($pageTitle === 'Income') ? 'bg-zinc-800/80 text-zinc-50 shadow-glow' : 'text-zinc-400 hover:text-zinc-50 hover:bg-zinc-800/50'; ?>">
            <svg class="h-4 w-4 transition-transform group-hover:scale-110" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5"/><path d="M5 12l7-7 7 7"/></svg>
            <span>Income</span>
        </a>
        <a href="expenses.php" class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-all duration-200 <?php echo ($pageTitle === 'Expenses') ? 'bg-zinc-800/80 text-zinc-50 shadow-glow' : 'text-zinc-400 hover:text-zinc-50 hover:bg-zinc-800/50'; ?>">
            <svg class="h-4 w-4 transition-transform group-hover:scale-110" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M19 12l-7 7-7-7"/></svg>
            <span>Expenses</span>
        </a>
        <a href="budgets.php" class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-all duration-200 <?php echo ($pageTitle === 'Budgets') ? 'bg-zinc-800/80 text-zinc-50 shadow-glow' : 'text-zinc-400 hover:text-zinc-50 hover:bg-zinc-800/50'; ?>">
            <svg class="h-4 w-4 transition-transform group-hover:scale-110" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18"/></svg>
            <span>Budgets</span>
        </a>
        <a href="savings.php" class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-all duration-200 <?php echo ($pageTitle === 'Savings') ? 'bg-zinc-800/80 text-zinc-50 shadow-glow' : 'text-zinc-400 hover:text-zinc-50 hover:bg-zinc-800/50'; ?>">
            <svg class="h-4 w-4 transition-transform group-hover:scale-110" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3 6 6 .9-4.5 4.2L18 20l-6-3.5L6 20l1.5-6.9L3 8.9 9 8z"/></svg>
            <span>Savings</span>
        </a>
        <a href="reports.php" class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-all duration-200 <?php echo ($pageTitle === 'Reports') ? 'bg-zinc-800/80 text-zinc-50 shadow-glow' : 'text-zinc-400 hover:text-zinc-50 hover:bg-zinc-800/50'; ?>">
            <svg class="h-4 w-4 transition-transform group-hover:scale-110" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 16l4-8 4 4 4-8"/></svg>
            <span>Reports</span>
        </a>
        <a href="profile.php" class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-all duration-200 <?php echo ($pageTitle === 'Profile') ? 'bg-zinc-800/80 text-zinc-50 shadow-glow' : 'text-zinc-400 hover:text-zinc-50 hover:bg-zinc-800/50'; ?>">
            <svg class="h-4 w-4 transition-transform group-hover:scale-110" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-7 8-7s8 3 8 7"/></svg>
            <span>Profile</span>
        </a>
    </nav>
    <div class="border-t border-zinc-800 p-3">
        <a href="logout.php" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-zinc-400 hover:bg-zinc-800/50 hover:text-zinc-50">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
            <span>Logout</span>
        </a>
    </div>
</aside>
