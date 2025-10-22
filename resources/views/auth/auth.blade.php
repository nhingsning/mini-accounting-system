<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sign in</title>

<!-- CDN (เลี่ยงปัญหา Vite) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
  :root{ --brand:#2B4A72; --radius:16px; --shadow:0 10px 24px rgba(0,0,0,.08); }
  body{ background:#f4f6f8; min-height:100svh; display:grid; place-items:center; font-family:Inter,system-ui,Segoe UI,Roboto,Helvetica,Arial; }
  .wrap{ width:min(96vw,1040px); background:#fff; border-radius:var(--radius); overflow:hidden; box-shadow:var(--shadow); }
  .split{ display:flex; flex-wrap:wrap; }
  .left{ flex:1 1 520px; padding:clamp(24px,4vw,56px); }
  .right{ flex:1 1 520px; padding:clamp(24px,4vw,56px);
          background:linear-gradient(135deg, #2B4A72, #3a6aa6);
          color:#fff; display:flex; align-items:center; justify-content:center; text-align:center; }
  h1{ font-weight:800; letter-spacing:.2px; }
  .social .btn{ width:48px;height:48px;border-radius:50%; border:1px solid #e6e6e6; background:#fff; }
  .hint{ color:#6b7280; }
  .form-control{ height:52px; background:#f5f7fa; border-color:#eef0f3; }
  .btn-brand{ background:var(--brand); color:#fff; border:none; padding:.9rem 2.2rem; border-radius:999px; font-weight:700; width:100%; }
  .btn-brand:hover{ filter:brightness(.97); }
  a.link-muted{ color:#6b7280; text-decoration:none; }
  a.link-muted:hover{ color:#111; }
</style>
</head>
<body>
  <div class="wrap">
    <div class="split">
      <!-- Left: Sign in -->
      <section class="left">
        <h1 class="mb-4">Sign in</h1>

        {{-- Social --}}
        <div class="d-flex gap-2 social mb-3">
          <button class="btn" type="button" aria-label="Facebook"><i class="bi bi-facebook"></i></button>
          <button class="btn" type="button" aria-label="Google"><i class="bi bi-google"></i></button>
          <button class="btn" type="button" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></button>
        </div>

        <p class="hint mb-4">or use your account</p>

        {{-- Alert: status / errors --}}
        @if (session('status'))
          <div class="alert alert-success py-2">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
          <div class="alert alert-danger py-2">
            <ul class="mb-0 ps-3">
              @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        {{-- IMPORTANT: ใช้ชื่อ route ให้ตรงกับ web.php --}}
        <form method="POST" action="{{ route('login.attempt') }}">
          @csrf
          <div class="mb-3">
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="Email" value="{{ old('email') }}" required autofocus>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="mb-2">
            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Password" required>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="remember" id="remember">
              <label class="form-check-label" for="remember">Remember me</label>
            </div>
            <a href="{{ url('/forgot-password') }}" class="small link-muted">Forgot password?</a>
          </div>

          <button class="btn-brand" type="submit">SIGN IN</button>

          {{-- ลิงก์สมัคร แสดงเมื่อมี route นี้จริง ๆ เท่านั้น --}}
          @if (Route::has('register.form'))
            <div class="text-center mt-3">
              <span class="hint">Don’t have an account?</span>
              <a href="{{ route('register.form') }}" class="fw-semibold ms-1">Sign up</a>
            </div>
          @endif
        </form>
      </section>

      <!-- Right: Simple welcome with CTA -->
      <aside class="right">
        <div>
          <h2 class="fw-bold mb-2">Hello, Friend!</h2>
          <p class="mb-4">Create an account and start your journey with us.</p>

          @if (Route::has('register.form'))
            <a href="{{ route('register.form') }}" class="btn btn-outline-light px-4 py-2 rounded-pill fw-semibold">SIGN UP</a>
          @else
            <button class="btn btn-outline-light px-4 py-2 rounded-pill fw-semibold" disabled title="Registration is not available">SIGN UP</button>
          @endif
        </div>
      </aside>
    </div>
  </div>
</body>
</html>
