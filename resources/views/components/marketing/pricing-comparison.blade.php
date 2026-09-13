@props([
    'rows' => [],
    'plans' => [],
])

@php
    $planIds = collect($plans)->pluck('id')->all();
@endphp

<div {{ $attributes->class(['mk-card overflow-hidden']) }}>
    <div class="overflow-x-auto">
        <table class="mk-compare-table w-full min-w-[640px] border-collapse text-left">
            <caption class="sr-only">Plan feature comparison</caption>
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50">
                    <th scope="col" class="px-4 py-4 text-sm font-semibold text-slate-700 sm:px-6">Feature</th>
                    @foreach ($plans as $plan)
                        <th scope="col" class="px-4 py-4 text-sm font-semibold text-slate-900 sm:px-6">
                            {{ data_get($plan, 'name') }}
                            @if (data_get($plan, 'is_featured', data_get($plan, 'highlighted', false)))
                                <span class="mt-1 block text-xs font-medium text-sky-700">Most popular</span>
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr class="border-b border-slate-100 last:border-0">
                        <th scope="row" class="px-4 py-3.5 text-sm font-medium text-slate-800 sm:px-6">
                            {{ $row['feature'] }}
                        </th>
                        @foreach ($planIds as $planId)
                            @php $value = $row[$planId] ?? false; @endphp
                            <td class="px-4 py-3.5 text-sm text-slate-600 sm:px-6">
                                @if ($value === true)
                                    <span class="inline-flex items-center gap-1.5 font-medium text-emerald-700">
                                        <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                                            <x-marketing.icon name="check" size="sm" />
                                        </span>
                                        <span class="sr-only">Included</span>
                                    </span>
                                @elseif ($value === false)
                                    <span class="text-slate-300" aria-label="Not included">—</span>
                                @else
                                    <span>{{ $value }}</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
