@php
    use Modules\Product\Entities\Product;
    use Modules\Budget\Entities\MasterBudget;
    use Modules\Purchase\Entities\Purchase;
@endphp

{{-- 🔹 Tombol Sidebar --}}
<button class="c-header-toggler c-class-toggler d-lg-none mfe-auto" type="button"
    data-target="#sidebar" data-class="c-sidebar-show">
    <i class="bi bi-list" style="font-size: 2rem;"></i>
</button>

<button class="c-header-toggler c-class-toggler mfs-3 d-md-down-none" type="button"
    data-target="#sidebar" data-class="c-sidebar-lg-show" responsive="true">
    <i class="bi bi-list" style="font-size: 2rem;"></i>
</button>

<ul class="c-header-nav ml-auto"></ul>

<ul class="c-header-nav ml-auto mr-4">

    {{-- ========================================================= --}}
    {{-- 🔔 1. NOTIFIKASI PRODUK STOK RENDAH --}}
    {{-- ========================================================= --}}
    @can('show_notifications')
    @php
        $low_quantity_products = Product::select('id', 'product_quantity', 'product_stock_alert', 'product_code')
            ->whereColumn('product_quantity', '<=', 'product_stock_alert')
            ->get();
    @endphp

    <li class="c-header-nav-item dropdown d-md-down-none mr-3">
        <a class="c-header-nav-link" data-toggle="dropdown" href="#" role="button"
           aria-haspopup="true" aria-expanded="false">
            <i class="bi bi-bell text-danger" style="font-size: 20px;"></i>
            @if($low_quantity_products->count() > 0)
                <span class="badge badge-pill badge-danger">{{ $low_quantity_products->count() }}</span>
            @endif
        </a>

        <div class="dropdown-menu dropdown-menu-right dropdown-menu-lg pt-0">
            <div class="dropdown-header bg-light">
                <strong>Low Stock ({{ $low_quantity_products->count() }})</strong>
            </div>

            @forelse($low_quantity_products as $product)
                <a class="dropdown-item" href="{{ route('products.show', $product->id) }}">
                    <i class="bi bi-exclamation-triangle text-warning mr-1"></i>
                    Product <strong>{{ $product->product_code }}</strong> is low in quantity.
                </a>
            @empty
                <a class="dropdown-item text-muted" href="#">
                    <i class="bi bi-check2-circle text-success mr-2"></i>
                    No low stock alerts.
                </a>
            @endforelse
        </div>
    </li>
    @endcan


    {{-- ========================================================= --}}
    {{-- 💰 2. PENDING MASTER BUDGET --}}
    {{-- ========================================================= --}}
    @can('show_notifications')
    @php
        $pending_master_budgets = MasterBudget::select('id', 'no_budgeting', 'status')
            ->where('status', 'pending')
            ->get();
    @endphp

    <li class="c-header-nav-item dropdown d-md-down-none mr-3">
        <a class="c-header-nav-link" data-toggle="dropdown" href="#" role="button"
           aria-haspopup="true" aria-expanded="false">
            <i class="bi bi-wallet2 text-info" style="font-size: 20px;"></i>
            @if($pending_master_budgets->count() > 0)
                <span class="badge badge-pill badge-info">{{ $pending_master_budgets->count() }}</span>
            @endif
        </a>

        <div class="dropdown-menu dropdown-menu-right dropdown-menu-lg pt-0">
            <div class="dropdown-header bg-light">
                <strong>Master Budget Pending ({{ $pending_master_budgets->count() }})</strong>
            </div>

            @forelse($pending_master_budgets as $budget)
                <a class="dropdown-item" href="{{ route('master_budget.show', $budget->id) }}">
                    <i class="bi bi-hourglass-split text-info mr-1"></i>
                    Master Budget <strong>{{ $budget->no_budgeting }}</strong> pending approval.
                </a>
            @empty
                <a class="dropdown-item text-muted" href="#">
                    <i class="bi bi-check2-circle text-success mr-2"></i>
                    No pending Master Budget.
                </a>
            @endforelse
        </div>
    </li>
    @endcan


    {{-- ========================================================= --}}
    {{-- 📄 3. PENDING PURCHASE REQUEST --}}
    {{-- ========================================================= --}}
    @can('show_notifications')
    @php
        $pending_purchase_requests = Purchase::select('id', 'reference', 'status')
            ->where('status', 'pending')
            ->get();
    @endphp

    <li class="c-header-nav-item dropdown d-md-down-none mr-3">
        <a class="c-header-nav-link" data-toggle="dropdown" href="#" role="button"
           aria-haspopup="true" aria-expanded="false">
            <i class="bi bi-file-earmark-text text-warning" style="font-size: 20px;"></i>
            @if($pending_purchase_requests->count() > 0)
                <span class="badge badge-pill badge-warning">{{ $pending_purchase_requests->count() }}</span>
            @endif
        </a>

        <div class="dropdown-menu dropdown-menu-right dropdown-menu-lg pt-0">
            <div class="dropdown-header bg-light">
                <strong>Purchase Request Pending ({{ $pending_purchase_requests->count() }})</strong>
            </div>

            @forelse($pending_purchase_requests as $pr)
                <a class="dropdown-item" href="{{ route('purchase_request.show', $pr->id) }}">
                    <i class="bi bi-hourglass-split text-warning mr-1"></i>
                    Purchase Request <strong>{{ $pr->reference }}</strong> pending approval.
                </a>
            @empty
                <a class="dropdown-item text-muted" href="#">
                    <i class="bi bi-check2-circle text-success mr-2"></i>
                    No pending Purchase Request.
                </a>
            @endforelse
        </div>
    </li>
    @endcan


    {{-- ========================================================= --}}
    {{-- 👤 4. DROPDOWN PROFIL USER --}}
    {{-- ========================================================= --}}
    <li class="c-header-nav-item dropdown">
        <a class="c-header-nav-link" data-toggle="dropdown" href="#" role="button"
           aria-haspopup="true" aria-expanded="false">
            <div class="c-avatar mr-2">
                <img class="c-avatar rounded-circle"
                     src="{{ auth()->user()->getFirstMediaUrl('avatars') }}"
                     alt="Profile Image">
            </div>
            <div class="d-flex flex-column">
                <span class="font-weight-bold">{{ auth()->user()->name }}</span>
                <span class="font-italic">
                    Online <i class="bi bi-circle-fill text-success" style="font-size: 11px;"></i>
                </span>
            </div>
        </a>

        <div class="dropdown-menu dropdown-menu-right pt-0">
            <div class="dropdown-header bg-light py-2"><strong>Account</strong></div>
            <a class="dropdown-item" href="{{ route('profile.edit') }}">
                <i class="mfe-2 bi bi-person" style="font-size: 1.2rem;"></i> Profile
            </a>
            <a class="dropdown-item" href="#"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="mfe-2 bi bi-box-arrow-left" style="font-size: 1.2rem;"></i> Logout
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>
        </div>
    </li>

</ul>
