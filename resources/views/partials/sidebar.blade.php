<aside class="sidebar">
  <div class="brand"><i class="bi bi-flower1"></i> Flow Accounting</div>

  <div class="section-title">General</div>
  <nav class="nav flex-column">
    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
      <i class="bi bi-speedometer2"></i>Dashboard
    </a>
    <a class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}" href="{{ route('invoices.index') }}">
      <i class="bi bi-receipt"></i>Invoices
    </a>
    <a class="nav-link {{ request()->routeIs('receipts.*') ? 'active' : '' }}" href="{{ route('receipts.index') }}">
      <i class="bi bi-journal-check"></i>Receipts
    </a>
    <a class="nav-link {{ request()->routeIs('po.*') ? 'active' : '' }}" href="{{ route('po.index') }}">
      <i class="bi bi-file-earmark-arrow-down"></i>PO
    </a>
    <a class="nav-link {{ request()->routeIs('quotations.*') ? 'active' : '' }}" href="{{ route('quotations.index') }}">
      <i class="bi bi-file-earmark-text"></i>Quotation
    </a>
    <a class="nav-link {{ request()->is('customers*') ? 'active' : '' }}" href="{{ route('customers.index') }}">
      <i class="bi bi-people"></i> Customers
    </a>
    <a class="nav-link" href="#"><i class="bi bi-bank"></i>Banks</a>
    <a class="nav-link" href="#"><i class="bi bi-cash-stack"></i>Payroll</a>
    <a class="nav-link" href="#"><i class="bi bi-graph-up"></i>Reports</a>
  </nav>

  <div class="section-title">Account</div>
  <nav class="nav flex-column">
    <a class="nav-link" href="#"><i class="bi bi-gear"></i>Settings</a>
    <a class="nav-link" href="#"><i class="bi bi-life-preserver"></i>Help</a>
<li class="nav-item mt-auto">
    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="nav-link text-danger border-0 bg-transparent w-100 text-start">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
        </button>
    </form>
</li>
  </nav>
</aside>
