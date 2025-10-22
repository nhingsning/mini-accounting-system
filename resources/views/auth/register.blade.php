<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Create account</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  :root{ --brand:#2B4A72; --radius:16px; --shadow:0 10px 24px rgba(0,0,0,.08); }
  body{ background:#f4f6f8; min-height:100svh; display:grid; place-items:center; font-family:Inter,system-ui,Segoe UI,Roboto,Helvetica,Arial; }
  .card{ width:min(92vw,560px); border:none; border-radius:var(--radius); box-shadow:var(--shadow); }
  .form-control{ height:52px; background:#f5f7fa; border-color:#eef0f3; }
  .btn-brand{ background:var(--brand); color:#fff; border:none; padding:.9rem 2.2rem; border-radius:999px; font-weight:700; width:100%; }
  a.link-muted{ color:#6b7280; text-decoration:none; }
  a.link-muted:hover{ color:#111; }
</style>
</head>
<body>
  <div class="card p-4 p-md-5 bg-white">
    <h1 class="h3 fw-bold mb-3 text-center">Create account</h1>
    <form method="POST" action="{{ route('register') }}">
      @csrf
      <div class="mb-3">
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="Full name" value="{{ old('name') }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="mb-3">
        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="Email" value="{{ old('email') }}" required>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="mb-3">
        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Password" required>
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="mb-4">
        <input type="password" name="password_confirmation" class="form-control" placeholder="Confirm password" required>
      </div>
      <button class="btn-brand" type="submit">CREATE ACCOUNT</button>
      <div class="text-center mt-3">
        <a href="{{ route('auth.page') }}" class="link-muted">Back to sign in</a>
      </div>
    </form>
  </div>
</body>
</html>
