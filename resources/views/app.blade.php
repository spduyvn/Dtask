<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dtask</title>
    <script>
      (function() {
        var k = 'dtask_theme';
        try {
          var s = localStorage.getItem(k);
          var d = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
          var t = (s === 'dark' || s === 'light') ? s : (d ? 'dark' : 'light');
          document.documentElement.setAttribute('data-theme', t);
        } catch (_) {}
      })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div id="app"></div>
</body>
</html>
