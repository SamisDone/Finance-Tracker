<?php
require_once 'includes/db.php';
require_once 'includes/auth_functions.php';

// If not yet set up, redirect to setup wizard
if (!file_exists(__DIR__ . '/.env')) {
    header('Location: setup.php');
    exit;
}

// Only redirect to dashboard if session is fully valid
if (isset($_SESSION['user_id'], $_SESSION['username'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!doctype html>
<html lang="en" class="dark">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>FinPulse — Take control of your finances</title>
<meta name="description" content="FinPulse is a premium personal finance tracker for income, expenses, budgets and savings goals." />
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script src="assets/app.js"></script>
<style>body{font-family:Inter,ui-sans-serif,system-ui,sans-serif}</style>
</head>
<body class="min-h-screen bg-zinc-950 text-zinc-50 antialiased selection:bg-violet-500/30">
  <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
    <div class="absolute -top-40 left-1/2 h-[600px] w-[900px] -translate-x-1/2 rounded-full bg-violet-600/20 blur-3xl"></div>
    <div class="absolute top-40 right-0 h-[400px] w-[400px] rounded-full bg-cyan-500/10 blur-3xl"></div>
  </div>

  <header class="sticky top-0 z-40 border-b border-zinc-800/60 bg-zinc-950/70 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
      <a href="index.php" class="flex items-center gap-2">
        <span class="grid h-8 w-8 place-items-center rounded-lg bg-gradient-to-br from-violet-500 to-cyan-400 font-bold text-zinc-950">F</span>
        <span class="font-semibold tracking-tight">FinPulse</span>
      </a>
      <nav class="hidden items-center gap-8 text-sm text-zinc-400 md:flex">
        <a class="hover:text-zinc-50" href="#features">Features</a>
        <a class="hover:text-zinc-50" href="#how">How it works</a>
        <a class="hover:text-zinc-50" href="#faq">FAQ</a>
      </nav>
      <div class="flex items-center gap-2">
        <a href="login.php" class="rounded-md px-3 py-1.5 text-sm text-zinc-300 hover:text-zinc-50">Login</a>
        <a href="register.php" class="rounded-md bg-zinc-50 px-3 py-1.5 text-sm font-medium text-zinc-950 transition hover:scale-[1.03] hover:bg-white">Get Started</a>
      </div>
    </div>
  </header>

  <section class="mx-auto max-w-7xl px-6 pb-24 pt-20 text-center sm:pt-28">
    <span class="inline-flex items-center gap-2 rounded-full border border-zinc-800 bg-zinc-900/60 px-3 py-1 text-xs text-zinc-400">
      <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> New · AI-powered insights
    </span>
    <h1 class="mx-auto mt-6 max-w-4xl text-5xl font-bold tracking-tight sm:text-7xl">
      Take control of your finances with
      <span class="bg-gradient-to-r from-violet-400 via-fuchsia-400 to-cyan-300 bg-clip-text text-transparent">FinPulse</span>
    </h1>
    <p class="mx-auto mt-6 max-w-2xl text-lg text-zinc-400">
      Track income, master your budget, and crush savings goals — all from a single, beautifully crafted dashboard.
    </p>
    <div class="mt-10 flex flex-wrap items-center justify-center gap-3">
      <a href="register.php" class="group inline-flex items-center gap-2 rounded-md bg-zinc-50 px-5 py-3 text-sm font-medium text-zinc-950 transition hover:scale-[1.03] hover:bg-white hover:shadow-glow">
        Get Started
        <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
      </a>
      <a href="login.php" class="inline-flex items-center gap-2 rounded-md border border-zinc-800 bg-zinc-900/60 px-5 py-3 text-sm font-medium text-zinc-100 transition hover:border-zinc-700 hover:bg-zinc-900">
        Login
      </a>
    </div>

    <div class="mx-auto mt-16 max-w-5xl rounded-2xl border border-zinc-800 bg-zinc-900/40 p-2 shadow-2xl shadow-violet-900/20">
      <div class="overflow-hidden rounded-xl border border-zinc-800 bg-zinc-950">
        <div class="flex items-center gap-1.5 border-b border-zinc-800 px-4 py-2.5">
          <span class="h-2.5 w-2.5 rounded-full bg-zinc-700"></span>
          <span class="h-2.5 w-2.5 rounded-full bg-zinc-700"></span>
          <span class="h-2.5 w-2.5 rounded-full bg-zinc-700"></span>
        </div>
        <div class="grid gap-4 p-6 md:grid-cols-3">
          <div class="rounded-lg border border-zinc-800 bg-zinc-900/60 p-4 text-left">
            <p class="text-xs text-zinc-500">Total Balance</p>
            <p class="mt-2 text-2xl font-semibold">$24,580.00</p>
            <p class="mt-1 text-xs text-emerald-400">▲ 12.4% this month</p>
          </div>
          <div class="rounded-lg border border-zinc-800 bg-zinc-900/60 p-4 text-left">
            <p class="text-xs text-zinc-500">Monthly Income</p>
            <p class="mt-2 text-2xl font-semibold text-emerald-300">$8,200.00</p>
            <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-zinc-800"><div class="h-full bg-gradient-to-r from-emerald-400 to-cyan-400" style="width:78%"></div></div>
          </div>
          <div class="rounded-lg border border-zinc-800 bg-zinc-900/60 p-4 text-left">
            <p class="text-xs text-zinc-500">Monthly Expenses</p>
            <p class="mt-2 text-2xl font-semibold text-rose-300">$3,142.00</p>
            <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-zinc-800"><div class="h-full bg-gradient-to-r from-rose-400 to-orange-400" style="width:42%"></div></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="features" class="mx-auto max-w-7xl px-6 py-20">
    <div class="mb-14 text-center">
      <h2 class="text-3xl font-semibold tracking-tight sm:text-4xl">Everything you need, nothing you don't</h2>
      <p class="mt-3 text-zinc-400">A focused toolkit built for clarity and control.</p>
    </div>
    <div class="grid gap-6 md:grid-cols-3">
      <div class="group relative overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-900/40 p-6 transition hover:-translate-y-1 hover:border-zinc-700">
        <div class="mb-5 grid h-11 w-11 place-items-center rounded-lg bg-emerald-500/10 text-emerald-300">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5"/><path d="M5 12l7-7 7 7"/></svg>
        </div>
        <h3 class="text-lg font-semibold">Income Tracking</h3>
        <p class="mt-2 text-sm text-zinc-400">Log salaries, freelance gigs, and side hustles. Watch your inflow grow over time.</p>
      </div>
      <div class="group relative overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-900/40 p-6 transition hover:-translate-y-1 hover:border-zinc-700">
        <div class="mb-5 grid h-11 w-11 place-items-center rounded-lg bg-violet-500/10 text-violet-300">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18"/><path d="M8 15h4"/></svg>
        </div>
        <h3 class="text-lg font-semibold">Smart Budgeting</h3>
        <p class="mt-2 text-sm text-zinc-400">Set monthly category limits with live progress bars and overspend alerts.</p>
      </div>
      <div class="group relative overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-900/40 p-6 transition hover:-translate-y-1 hover:border-zinc-700">
        <div class="mb-5 grid h-11 w-11 place-items-center rounded-lg bg-cyan-500/10 text-cyan-300">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3 6 6 .9-4.5 4.2L18 20l-6-3.5L6 20l1.5-6.9L3 8.9 9 8z"/></svg>
        </div>
        <h3 class="text-lg font-semibold">Savings Goals</h3>
        <p class="mt-2 text-sm text-zinc-400">Visualize progress toward a new car, house, or that dream vacation.</p>
      </div>
    </div>
  </section>

  <section class="mx-auto max-w-7xl px-6 py-20">
    <div class="relative overflow-hidden rounded-3xl border border-zinc-800 bg-gradient-to-br from-zinc-900 to-zinc-950 p-12 text-center">
      <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top,rgba(167,139,250,.15),transparent_60%)]"></div>
      <h3 class="text-3xl font-semibold tracking-tight sm:text-4xl">Start tracking in 60 seconds</h3>
      <p class="mx-auto mt-3 max-w-xl text-zinc-400">No credit card required. Cancel anytime.</p>
      <a href="register.php" class="mt-8 inline-flex rounded-md bg-zinc-50 px-5 py-3 text-sm font-medium text-zinc-950 hover:scale-[1.03] hover:bg-white">Create free account</a>
    </div>
  </section>

  <footer class="border-t border-zinc-800/60">
    <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-6 py-8 text-sm text-zinc-500 sm:flex-row">
      <div class="flex items-center gap-2">
        <span class="grid h-6 w-6 place-items-center rounded bg-gradient-to-br from-violet-500 to-cyan-400 text-xs font-bold text-zinc-950">F</span>
        <span>© 2026 FinPulse. All rights reserved.</span>
      </div>
      <div class="flex gap-6">
        <a class="hover:text-zinc-200" href="#">Privacy</a>
        <a class="hover:text-zinc-200" href="#">Terms</a>
        <a class="hover:text-zinc-200" href="#">Contact</a>
      </div>
    </div>
  </footer>
</body>
</html>
