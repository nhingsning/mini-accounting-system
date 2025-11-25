<div class="language-switcher shadow-lg">
  <button type="button" class="lang-toggle" aria-label="Switch language" aria-expanded="false">
    <i class="bi bi-translate"></i>
    <span class="lang-label">{{ __('ui.language_label') }}</span>
  </button>

  <div class="lang-panel">
    <div class="d-flex align-items-center justify-content-between mb-2">
      <p class="mb-0 small text-muted">{{ __('ui.language_label') }}</p>
      <button type="button" class="btn btn-link btn-sm text-muted p-0 close-lang">&times;</button>
    </div>
    <form action="{{ route('locale.update') }}" method="POST" class="d-flex flex-column gap-2">
      @csrf
      <input type="hidden" name="redirect" value="{{ url()->full() }}">
      <select name="locale" id="app-language" class="form-select form-select-sm" onchange="this.form.submit()">
        @foreach(['en','th'] as $locale)
          <option value="{{ $locale }}" @selected(app()->getLocale() === $locale)>
            {{ __('ui.language.'.$locale) }}
          </option>
        @endforeach
      </select>
      <button type="submit" class="btn btn-primary btn-sm w-100">{{ __('ui.save') }}</button>
    </form>
  </div>
</div>

<script>
  (() => {
    const container = document.querySelector('.language-switcher');
    if (!container) return;
    const toggle = container.querySelector('.lang-toggle');
    const closeBtn = container.querySelector('.close-lang');

    const close = () => {
      container.classList.remove('open');
      toggle.setAttribute('aria-expanded', 'false');
    };

    toggle.addEventListener('click', () => {
      container.classList.toggle('open');
      toggle.setAttribute('aria-expanded', container.classList.contains('open'));
    });

    closeBtn?.addEventListener('click', close);

    document.addEventListener('click', (e) => {
      if (!container.contains(e.target)) {
        close();
      }
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') close();
    });
  })();
</script>
