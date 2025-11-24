<div class="language-switcher">
  <label for="app-language" class="mb-0">{{ __('ui.language_label') }}</label>
  <form action="{{ route('locale.update') }}" method="POST" class="d-flex align-items-center gap-2 w-100">
    @csrf
    <input type="hidden" name="redirect" value="{{ url()->full() }}">
    <select name="locale" id="app-language" class="form-select form-select-sm" onchange="this.form.submit()">
      @foreach(['en','th'] as $locale)
        <option value="{{ $locale }}" @selected(app()->getLocale() === $locale)>
          {{ __('ui.language.'.$locale) }}
        </option>
      @endforeach
    </select>
  </form>
</div>
