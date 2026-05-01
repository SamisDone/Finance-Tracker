<?php
$initial = strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1));
?>
<header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-zinc-800 bg-zinc-950/80 px-4 backdrop-blur sm:px-6">
    <div class="flex items-center gap-3">
        <button data-sidebar-open class="rounded-md p-2 text-zinc-400 hover:bg-zinc-800 hover:text-zinc-50 lg:hidden">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
        </button>
        <h1 class="text-sm font-medium text-zinc-400"><?php echo htmlspecialchars($pageTitle ?? 'Dashboard'); ?></h1>
    </div>
    <div class="flex items-center gap-2">
        <a href="profile.php" class="flex items-center gap-2 rounded-full border border-zinc-800 bg-zinc-900/60 p-1 pr-3 transition hover:border-zinc-700">
            <span class="grid h-7 w-7 place-items-center rounded-full bg-gradient-to-br from-violet-500 to-cyan-400 text-xs font-semibold text-zinc-950"><?php echo $initial; ?></span>
            <span class="hidden text-xs text-zinc-300 sm:inline"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
        </a>
    </div>
</header>
