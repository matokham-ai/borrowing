<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · LendFlow</title>
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
    @stack('styles')
</head>
<body>
<script>if(window.innerWidth>768&&localStorage.getItem('sbCollapsed')==='1')document.documentElement.classList.add('sb-collapsed');</script>
<style>
@media(min-width:769px){
  .sb-collapsed .admin-main{margin-left:62px!important}
  .sb-collapsed .sidebar{width:62px!important}
}
</style>
<div class="admin-shell">
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
    @include('layouts.sidebar')
    <div class="admin-main">
        @include('layouts.topnav')
        <main class="admin-content">
            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
