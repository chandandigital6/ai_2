@props(['title' => null])
<x-layouts::auth :title="$title">{{ $slot }}</x-layouts::auth>
