@props(['title' => 'Dashboard', 'active' => 'dashboard'])
@php
    $nav = [
        ['key' => 'dashboard', 'label' => 'Dashboard',   'icon' => 'home',      'route' => 'teacher.dashboard'],
        ['key' => 'subjects',  'label' => 'My Subjects', 'icon' => 'book-open', 'route' => 'teacher.subjects'],
        ['key' => 'profile',   'label' => 'Profile',     'icon' => 'user',      'route' => 'teacher.profile'],
        ['key' => 'settings',  'label' => 'Settings',    'icon' => 'cog',       'route' => 'teacher.settings'],
    ];
    $menu = [
        ['label' => 'Profile',  'route' => 'teacher.profile'],
        ['label' => 'Settings', 'route' => 'teacher.settings'],
    ];
@endphp
<x-layouts.panel :title="$title" :active="$active" :nav="$nav" :menu="$menu" role-label="Teacher">
    {{ $slot }}
</x-layouts.panel>
