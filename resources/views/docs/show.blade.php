<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Documentation"
            :subtitle="$title"
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('dashboard')],
                ['label' => 'Docs', 'url' => route('docs.index')],
                ['label' => $title],
            ]"
        />
    </x-slot>

    @include('docs._content')
</x-app-layout>
