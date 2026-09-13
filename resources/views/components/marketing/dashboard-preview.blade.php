@props([])

<div {{ $attributes->class(['mk-dashboard-preview relative w-full overflow-hidden']) }} role="img" aria-label="Algos CRM dashboard preview showing pipeline, tasks, and analytics">
    <div class="mk-dashboard-chrome overflow-hidden rounded-t-xl border border-b-0 border-slate-200 bg-slate-100">
        <div class="flex items-center gap-2 border-b border-slate-200 px-4 py-3">
            <span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span>
            <span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span>
            <span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span>
            <div class="ml-3 flex-1 rounded-md bg-white px-3 py-1.5 text-xs text-slate-400 ring-1 ring-slate-200">
                app.algos.test/dashboard
            </div>
        </div>
    </div>

    <div class="mk-dashboard-body overflow-hidden rounded-b-xl border border-slate-200" style="background: var(--mk-bg);">
        <div class="grid min-h-[320px] grid-cols-[56px_1fr] sm:min-h-[400px] sm:grid-cols-[200px_1fr] lg:min-h-[460px]">
            {{-- Sidebar --}}
            <aside class="border-r border-slate-200 bg-slate-900 p-3 text-slate-300 sm:p-4">
                <div class="mb-5 hidden items-center gap-2 sm:flex">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-slate-800 text-sky-400">
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 4 L5 20" /><path d="M12 4 L19 20" /><path d="M8.5 14.5 H15.5" />
                        </svg>
                    </span>
                    <span class="text-sm font-semibold text-white">algos<span class="text-sky-400">.</span></span>
                </div>
                <div class="space-y-2">
                    <div class="rounded-md bg-sky-500/20 px-2 py-2 text-[10px] font-medium text-sky-300 sm:text-xs">Dashboard</div>
                    <div class="rounded-md px-2 py-2 text-[10px] text-slate-400 sm:text-xs">Leads</div>
                    <div class="rounded-md px-2 py-2 text-[10px] text-slate-400 sm:text-xs">Customers</div>
                    <div class="rounded-md px-2 py-2 text-[10px] text-slate-400 sm:text-xs">Tasks</div>
                    <div class="rounded-md px-2 py-2 text-[10px] text-slate-400 sm:text-xs">Reports</div>
                </div>
            </aside>

            {{-- Main --}}
            <div class="p-3 sm:p-5">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold text-slate-900 sm:text-base">Revenue overview</div>
                        <div class="text-[11px] text-slate-500 sm:text-xs">This month · your pipeline at a glance</div>
                    </div>
                    <div class="hidden rounded-lg bg-white px-3 py-1.5 text-xs font-medium text-slate-600 ring-1 ring-slate-200 sm:block">
                        Export
                    </div>
                </div>

                <div class="mb-4 grid grid-cols-3 gap-2 sm:gap-3">
                    <div class="rounded-lg bg-white p-2.5 ring-1 ring-slate-200 sm:p-3">
                        <div class="text-[10px] text-slate-500 sm:text-xs">Open leads</div>
                        <div class="mt-1 text-base font-bold text-slate-900 sm:text-xl">128</div>
                        <div class="mt-0.5 text-[10px] font-medium text-emerald-600">+12%</div>
                    </div>
                    <div class="rounded-lg bg-white p-2.5 ring-1 ring-slate-200 sm:p-3">
                        <div class="text-[10px] text-slate-500 sm:text-xs">Won deals</div>
                        <div class="mt-1 text-base font-bold text-slate-900 sm:text-xl">46</div>
                        <div class="mt-0.5 text-[10px] font-medium text-emerald-600">+8%</div>
                    </div>
                    <div class="rounded-lg bg-white p-2.5 ring-1 ring-slate-200 sm:p-3">
                        <div class="text-[10px] text-slate-500 sm:text-xs">Tasks due</div>
                        <div class="mt-1 text-base font-bold text-slate-900 sm:text-xl">19</div>
                        <div class="mt-0.5 text-[10px] font-medium text-amber-600">Today</div>
                    </div>
                </div>

                <div class="grid gap-3 lg:grid-cols-[1.4fr_1fr]">
                    <div class="rounded-lg bg-white p-3 ring-1 ring-slate-200 sm:p-4">
                        <div class="mb-3 text-xs font-semibold text-slate-800">Pipeline</div>
                        <div class="flex h-28 items-end gap-2 sm:h-32">
                            <div class="flex-1 rounded-t bg-sky-100" style="height: 42%"></div>
                            <div class="flex-1 rounded-t bg-sky-200" style="height: 58%"></div>
                            <div class="flex-1 rounded-t bg-sky-300" style="height: 71%"></div>
                            <div class="flex-1 rounded-t bg-sky-500" style="height: 88%"></div>
                            <div class="flex-1 rounded-t bg-slate-900" style="height: 64%"></div>
                        </div>
                        <div class="mt-2 flex justify-between text-[10px] text-slate-400">
                            <span>New</span><span>Qualified</span><span>Proposal</span><span>Won</span><span>Lost</span>
                        </div>
                    </div>

                    <div class="rounded-lg bg-white p-3 ring-1 ring-slate-200 sm:p-4">
                        <div class="mb-3 text-xs font-semibold text-slate-800">Today’s tasks</div>
                        <ul class="space-y-2.5">
                            <li class="flex items-center gap-2 text-[11px] text-slate-600 sm:text-xs">
                                <span class="h-2 w-2 rounded-full bg-sky-500"></span>
                                Follow up with Acme Corp
                            </li>
                            <li class="flex items-center gap-2 text-[11px] text-slate-600 sm:text-xs">
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                Send proposal to Northline
                            </li>
                            <li class="flex items-center gap-2 text-[11px] text-slate-600 sm:text-xs">
                                <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                                Demo call · Cascade Labs
                            </li>
                            <li class="flex items-center gap-2 text-[11px] text-slate-600 sm:text-xs">
                                <span class="h-2 w-2 rounded-full bg-slate-300"></span>
                                Update Q3 forecast
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
