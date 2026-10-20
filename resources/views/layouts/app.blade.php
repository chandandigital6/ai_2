@props(['title' => null, 'business' => null, 'workspaceRole' => null])
<x-layouts::app.sidebar :title="$title" :business="$business" :workspace-role="$workspaceRole">
    <main id="main-content" class="g-main" tabindex="-1">{{ $slot }}</main>
</x-layouts::app.sidebar>
