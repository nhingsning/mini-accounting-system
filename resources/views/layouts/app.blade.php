<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Mini Accounting</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  {{-- Tailwind (CDN แบบเล่นเร็ว) --}}
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    /* โทนปุ่ม/อินพุตให้เหมือน SaaS */
    .btn{ @apply inline-flex items-center px-4 py-2 rounded-lg border text-sm font-medium shadow-sm transition; }
    .btn-primary{ @apply bg-indigo-600 text-white border-indigo-600 hover:bg-indigo-700; }
    .btn-ghost{ @apply border-gray-300 hover:bg-gray-50; }
    .btn-danger{ @apply bg-rose-600 text-white border-rose-600 hover:bg-rose-700; }
    .input{ @apply w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500; }
    .table{ @apply min-w-full text-sm; }
    .th{ @apply px-3 py-2 text-left font-semibold text-gray-700; }
    .td{ @apply px-3 py-2 align-top; }
    .card{ @apply bg-white rounded-2xl shadow p-5; }
    .pill{ @apply inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium bg-gray-100 text-gray-800; }
    .right-panel{ @apply sticky top-4; }
  </style>
</head>
<body class="bg-gray-100 text-gray-900">
  <nav class="flex items-center gap-6">
  <a href="{{ route('invoices.index') }}"
     class="{{ request()->routeIs('invoices.*') ? 'text-indigo-600 font-semibold' : 'text-gray-600' }}">
     Invoices
  </a>

  <a href="{{ route('quotes.index') }}"
     class="{{ request()->routeIs('quotes.*') ? 'text-indigo-600 font-semibold' : 'text-gray-600' }}">
     Quotations
  </a>
</nav>

  <div class="max-w-7xl mx-auto p-6">
    <nav class="mb-6">
      <a href="{{ route('invoices.index') }}" class="text-indigo-600 hover:underline font-semibold">Invoices</a>
      @if(session('ok'))
        <span class="pill ml-2 text-green-700 bg-green-100">{{ session('ok') }}</span>
      @endif
    </nav>
    

    @yield('content')
  </div>
</body>
</html>
