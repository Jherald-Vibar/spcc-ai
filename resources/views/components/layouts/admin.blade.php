@props(['title' => 'Dashboard', 'active' => 'dashboard'])
@php
    $nav = [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home',       'route' => 'admin.dashboard'],
        ['key' => 'users',     'label' => 'Users',     'icon' => 'users',      'route' => 'admin.users'],
        ['key' => 'subjects',  'label' => 'Subjects',  'icon' => 'academic-cap', 'route' => 'admin.subjects'],
        ['key' => 'lessons',   'label' => 'Lessons',   'icon' => 'book-open',  'route' => 'admin.lessons'],
        ['key' => 'reports',   'label' => 'Reports',   'icon' => 'chart-bar',  'route' => 'admin.reports'],
        ['key' => 'settings',  'label' => 'Settings',  'icon' => 'cog',        'route' => 'admin.settings'],
    ];
    $menu = [
        ['label' => 'Settings', 'route' => 'admin.settings'],
    ];
@endphp
<x-layouts.panel :title="$title" :active="$active" :nav="$nav" :menu="$menu" role-label="Administrator">
    {{ $slot }}
</x-layouts.panel>
