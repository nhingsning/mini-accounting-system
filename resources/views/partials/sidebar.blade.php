<aside class="sidebar">
  <div class="brand"><i class="bi bi-flower1"></i> {{ __('ui.brand') }}</div>

  <div class="section-title">{{ __('ui.menu.general') }}</div>
  <nav class="nav flex-column">
    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
      <i class="bi bi-speedometer2"></i>{{ __('ui.menu.dashboard') }}
    </a>
    <a class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}" href="{{ route('invoices.index') }}">
      <i class="bi bi-receipt"></i>{{ __('ui.menu.invoices') }}
    </a>
    <a class="nav-link {{ request()->routeIs('credit-notes.*') ? 'active' : '' }}" href="{{ route('credit-notes.index') }}">
      <i class="bi bi-arrow-left-right"></i>{{ __('ui.menu.credit_notes') }}
    </a>
    <a class="nav-link {{ request()->routeIs('receipts.*') ? 'active' : '' }}" href="{{ route('receipts.index') }}">
      <i class="bi bi-journal-check"></i>{{ __('ui.menu.receipts') }}
    </a>
    <a class="nav-link {{ request()->routeIs('payments.*') || request()->routeIs('bank-statements.*') ? 'active' : '' }}" href="{{ route('payments.index') }}">
      <i class="bi bi-credit-card"></i>{{ __('ui.menu.payments') }}
    </a>
    <a class="nav-link {{ request()->routeIs('po.*') ? 'active' : '' }}" href="{{ route('po.index') }}">
      <i class="bi bi-file-earmark-arrow-down"></i>{{ __('ui.menu.po') }}
    </a>
    <a class="nav-link {{ request()->routeIs('quotations.*') ? 'active' : '' }}" href="{{ route('quotations.index') }}">
      <i class="bi bi-file-earmark-text"></i>{{ __('ui.menu.quotations') }}
    </a>
    <a class="nav-link {{ request()->is('customers*') ? 'active' : '' }}" href="{{ route('customers.index') }}">
      <i class="bi bi-people"></i> {{ __('ui.menu.customers') }}
    </a>
    <a class="nav-link" href="#"><i class="bi bi-bank"></i>{{ __('ui.menu.banks') }}</a>
    <a class="nav-link" href="#"><i class="bi bi-cash-stack"></i>{{ __('ui.menu.payroll') }}</a>
    <a class="nav-link" href="#"><i class="bi bi-graph-up"></i>{{ __('ui.menu.reports') }}</a>
  </nav>

  <div class="section-title">{{ __('ui.menu.account') }}</div>
  <nav class="nav flex-column">
    <a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}">
      <i class="bi bi-gear"></i>{{ __('ui.menu.settings') }}
    </a>
    <a class="nav-link" href="#"><i class="bi bi-life-preserver"></i>{{ __('ui.menu.help') }}</a>
    <li class="nav-item mt-auto">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="nav-link text-danger border-0 bg-transparent w-100 text-start">
                <i class="bi bi-box-arrow-right me-2"></i> {{ __('ui.menu.logout') }}
            </button>
        </form>
    </li>
  </nav>
</aside>
