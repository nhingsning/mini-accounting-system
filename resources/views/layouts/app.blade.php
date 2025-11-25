<!doctype html>
<html lang="{{ str_replace('_','-',app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>@yield('title','App')</title>

  {{-- Bootstrap CDN (ถ้าเน็ตมีจะช่วยเรื่อง table/btn ทันที) --}}
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  {{-- === Universal CSS (INLINE เพื่อกันพังแม้โหลดไฟล์ไม่ได้) === --}}
  <style>
    :root{ --brand:#2B4A72; --brand-2:#6f9ad0; --bg:#f4f6fb; --card:#fff; --muted:#6b7280;
           --shadow:0 10px 24px rgba(0,0,0,.06); --radius:16px }
    *{box-sizing:border-box} html{scroll-behavior:smooth}
    body{background:var(--bg); font-family:Inter,system-ui,Roboto,Arial; color:#1f2937}

    /* Layout + sidebar */
    .layout{display:grid; grid-template-columns:260px 1fr; min-height:100svh}
    .sidebar{background:#fff; padding:24px 16px; box-shadow:var(--shadow)}
    .brand{color:var(--brand); font-weight:700; font-size:20px; margin-bottom:1.2rem; display:flex; gap:8px; align-items:center}
    .nav.flex-column .nav-link{color:#444; border-radius:10px; padding:10px 12px; display:flex; gap:10px; align-items:center}
    .nav.flex-column .nav-link:hover{background:#f0f3fa}
    .nav.flex-column .nav-link.active{background:#e8eef9; color:var(--brand); font-weight:600}
    .section-title{color:var(--muted); font-size:12px; text-transform:uppercase; margin:20px 0 6px}

    /* Topbar */
    .topbar{display:flex; align-items:center; gap:12px; padding:16px 24px}
    .topbar .search{flex:1; position:relative}
    .topbar .search input{height:44px; border-radius:999px; padding-left:42px; background:#eef3fb; border:1px solid #e5edf9}
    .topbar .search i{position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#90a3c0}
    .btn-soft{background:#eef3fb; color:#2c3e58; border:none}
    .btn-brand{background:linear-gradient(135deg,var(--brand),var(--brand-2)); color:#fff; border:none; border-radius:12px; padding:10px 18px; font-weight:700; box-shadow:var(--shadow)}
    .btn-brand:hover{filter:brightness(.98); color:#fff}
    .btn-outline-ghost{border:1px solid #dce3ef; color:#2c3e58; background:#fff; border-radius:12px; padding:10px 16px; font-weight:600}
    .avatar{width:36px;height:36px;border-radius:50%; background:linear-gradient(135deg,var(--brand),var(--brand-2)); color:#fff; display:grid; place-items:center; font-weight:700}

    /* Panels */
    .panel{background:var(--card); border-radius:var(--radius); box-shadow:var(--shadow)}
    .panel-header{padding:16px 20px; border-bottom:1px solid #eef2f8; display:flex; justify-content:space-between; align-items:center}
    .panel-body{padding:20px}
    .kpi .value{font-size:clamp(20px,2.2vw,28px); font-weight:800}
    .mini{font-size:12px; color:var(--muted)}
    .badge-dot{width:10px;height:10px;border-radius:50%;display:inline-block;margin-right:8px}

    /* Tables (มือถืออ่านง่าย) */
    .table-responsive{border-radius:var(--radius); overflow:hidden}
    @media (max-width:640px){
      .layout{grid-template-columns:1fr}
      .sidebar{position:fixed; inset:0 auto 0 0; width:min(80vw,300px); transform:translateX(-100%); transition:transform .25s; z-index:1040; border-right:1px solid #eaeef6}
      .sidebar.show{transform:translateX(0)}
      .sidebar-backdrop{content:""; position:fixed; inset:0; background:rgba(2,7,17,.35); opacity:0; pointer-events:none; transition:opacity .25s; z-index:1035}
      .sidebar-backdrop.show{opacity:1; pointer-events:auto}
      .topbar{padding:12px 16px}
      .table thead{display:none}
      .table tr{display:grid; grid-template-columns:1fr auto; border-bottom:1px solid #eef1f7; padding:10px 8px}
      .table td{border:0; padding:6px 8px}
      .table td.text-end{text-align:right!important}
    }

    /* Chart containers */
    .chart-container{position:relative; width:100%}
    .chart-container canvas{width:100%!important; height:auto!important}

    /* Language switcher */
    .language-switcher{position:fixed; bottom:18px; right:18px; z-index:1200; display:flex; flex-direction:column; align-items:flex-end; gap:8px}
    .language-switcher .lang-toggle{display:inline-flex; align-items:center; gap:8px; border:none; background:linear-gradient(135deg,var(--brand),var(--brand-2)); color:#fff; padding:10px 14px; border-radius:999px; box-shadow:var(--shadow); font-weight:600}
    .language-switcher .lang-label{white-space:nowrap; font-size:14px}
    .language-switcher .lang-panel{position:absolute; bottom:52px; right:0; width:220px; background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:12px; box-shadow:var(--shadow); opacity:0; pointer-events:none; transform:translateY(8px); transition:all .15s ease}
    .language-switcher.open .lang-panel{opacity:1; pointer-events:auto; transform:translateY(0)}
    .language-switcher select{border:1px solid #dce3ef; border-radius:10px; padding:6px 8px; font-size:13px; background:#f8fafc}
    @media(max-width:640px){ .language-switcher{bottom:12px; right:12px} }
  </style>

  @stack('head')
</head>
<body>
  @include('partials.language-switcher')
  @yield('body')
@yield('content')

  {{-- Sidebar toggle (มือถือ) --}}
  <script>
    (function(){
      const btn=document.getElementById('menuToggle');
      const sidebar=document.querySelector('.sidebar');
      if(!sidebar) return;
      let backdrop=document.querySelector('.sidebar-backdrop');
      if(!backdrop){ backdrop=document.createElement('div'); backdrop.className='sidebar-backdrop'; document.body.appendChild(backdrop); }
      btn && btn.addEventListener('click',()=>{ sidebar.classList.add('show'); backdrop.classList.add('show'); });
      backdrop.addEventListener('click',()=>{ sidebar.classList.remove('show'); backdrop.classList.remove('show'); });
      document.addEventListener('keydown',(e)=>{ if(e.key==='Escape'){ sidebar.classList.remove('show'); backdrop.classList.remove('show'); }});
    })();
  </script>

  @stack('scripts')
</body>
</html>
