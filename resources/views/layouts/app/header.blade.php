@props(['title' => null])
<x-layouts::app.sidebar :title="$title">{{ $slot }}</x-layouts::app.sidebar>
