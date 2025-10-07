<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <title>{{ $title ?? 'Mini Accounting' }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family: system-ui, -apple-system, 'Noto Sans Thai', sans-serif; padding:20px; max-width: 980px; margin:auto;}
    nav a{margin-right:12px}
    table{border-collapse:collapse; width:100%}
    th,td{border:1px solid #ddd; padding:8px}
    .right{text-align:right}
    .btn{display:inline-block; padding:6px 10px; border:1px solid #333; border-radius:6px; text-decoration:none}
    .mt{margin-top:12px}
    .ok{color:green}
  </style>
</head>
<body>
<nav>
  <a href="{{ route('invoices.index') }}">Invoices</a>
</nav>
@if(session('ok')) <p class="ok">{{ session('ok') }}</p> @endif
@yield('content')
</body>
</html>
