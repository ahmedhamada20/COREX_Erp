<aside class="app-sidebar app-light-sidebar cairo-font">
    <div class="app-navbar-wrapper">
        <div class="brand-link brand-logo">
            <a href="{{route('admin')}}" class="b-brand">
                <!-- ========   change your logo hear   ============ -->
                <img src="{{asset('dash//assets/images/logo.png')}}" alt="" class="logo logo-lg"/>
            </a>
        </div>
        <div class="navbar-content">

            <ul class="app-navbar">

                <li class="nav-item nav-hasmenu   {{ request()->routeIs('setting.*', 'treasuries.*','sales_material_types.*','shifts.*') ? 'active nav-provoke' : '' }}">

                    <a href="#!" class="nav-link">
                    <span class="nav-icon">
                        <i class="ti ti-layout-2"></i>
                    </span>
                        <span class="nav-text">الإعدادات العامة</span>
                        <span class="nav-arrow">
                        <i data-feather="chevron-right"></i>
                    </span>
                    </a>


                    <ul class="nav-submenu">

                        <li class="nav-item {{ request()->routeIs('setting.*') ? ' active' : '' }}">
                            <a class="nav-link {{ request()->routeIs('setting.*') ? 'active' : '' }}"
                               href="{{ route('setting') }}">
                                إعدادات النظام
                            </a>
                        </li>

                        <li class="nav-item {{ request()->routeIs('treasuries.*') ? ' active' : '' }}">
                            <a class="nav-link {{ request()->routeIs('treasuries.*') ? ' active' : '' }}"
                               href="{{ route('treasuries.index') }}">
                                الخزن
                            </a>
                        </li>

                        <li class="nav-item {{ request()->routeIs('shifts.*') ? ' active' : '' }}">
                            <a class="nav-link {{ request()->routeIs('shifts.*') ? ' active' : '' }}"
                               href="{{ route('shifts.index') }}">
                                شفتات المستخدمين
                            </a>
                        </li>

                        <li class="nav-item {{ request()->routeIs('sales_material_types.*') ? ' active' : '' }}">
                            <a class="nav-link {{ request()->routeIs('sales_material_types.*') ? ' active' : '' }}"
                               href="{{ route('sales_material_types.index') }}">
                                فئات مواد المبيعات
                            </a>
                        </li>


                    </ul>
                </li>


                <li class="nav-item nav-hasmenu {{ request()->routeIs('stores.*','units.*','item_categories.*','items.*') ? 'active nav-provoke' : '' }}">

                    <a href="#!" class="nav-link">
                        <span class="nav-icon">
                            <i class="fa fa-warehouse"></i>
                        </span>
                        <span class="nav-text">إعدادات المخازن</span>
                        <span class="nav-arrow">
                            <i data-feather="chevron-right"></i>
                        </span>
                    </a>

                    <ul class="nav-submenu">

                        <li class="nav-item {{ request()->routeIs('stores.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('stores.index') }}">
                                المخازن
                            </a>
                        </li>

                        <li class="nav-item {{ request()->routeIs('units.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('units.index') }}">
                                الوحدات
                            </a>
                        </li>

                        <li class="nav-item {{ request()->routeIs('item_categories.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('item_categories.index') }}">
                                فئات الأصناف
                            </a>
                        </li>

                        <li class="nav-item {{ request()->routeIs('items.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('items.index') }}">
                                الأصناف
                            </a>
                        </li>

                    </ul>
                </li>


                <li class="nav-item nav-hasmenu {{ request()->routeIs('account_types.*','accounts.*','journal_entries.*') ? 'active nav-provoke' : '' }}">
                    <a href="#!" class="nav-link">
                        <span class="nav-icon">
                            <i class="ti ti-cash"></i>
                        </span>

                        <span class="nav-text">الحسابات المالية</span>
                        <span class="nav-arrow">
                            <i data-feather="chevron-right"></i>
                        </span>
                    </a>
                    <ul class="nav-submenu">
                        <li class="nav-item {{ request()->routeIs('account_types.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('account_types.index') }}">
                                انواع الحسابات الماليه
                            </a>
                        </li>


                        <li class="nav-item {{ request()->routeIs('accounts.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('accounts.index') }}">
                                الحسابات الماليه
                            </a>
                        </li>

                        <li class="nav-item {{ request()->routeIs('journal_entries.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('journal_entries.index') }}">
                                سندات القيد اليدوي
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item nav-hasmenu {{ request()->routeIs('customers.*') ? 'active nav-provoke' : '' }}">
                    <a href="#!" class="nav-link">
                        <span class="nav-icon">
                            <i class="ti ti-users"></i>
                        </span>

                        <span class="nav-text">العملاء </span>
                        <span class="nav-arrow">
                                <i data-feather="chevron-right"></i>
                            </span>
                    </a>


                    <ul class="nav-submenu">


                        <li class="nav-item {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('customers.index') }}">
                                العملاء
                            </a>
                        </li>
                    </ul>
                </li>


                <li class="nav-item nav-hasmenu {{ request()->routeIs('supplier_categories.*','suppliers.*') ? 'active nav-provoke' : '' }}">
                    <a href="#!" class="nav-link">
                        <span class="nav-icon">
                            <i class="ti ti-truck-delivery"></i>
                        </span>

                        <span class="nav-text">الموردين </span>
                        <span class="nav-arrow">
                            <i data-feather="chevron-right"></i>
                        </span>
                    </a>
                    <ul class="nav-submenu">
                        <li class="nav-item {{ request()->routeIs('supplier_categories.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('supplier_categories.index') }}">
                                فئات الموردين
                            </a>
                        </li>

                        <li class="nav-item {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('suppliers.index') }}">
                                الموردين
                            </a>
                        </li>


                    </ul>
                </li>

                <li class="nav-item nav-hasmenu {{ request()->routeIs('purchase_orders.*','purchase_invoices.*','purchase_returns.*') ? 'active nav-provoke' : '' }}">
                    <a href="#!" class="nav-link">
                        <span class="nav-icon">
                            <i class="ti ti-shopping-cart"></i>
                        </span>

                        <span class="nav-text">المشتريات</span>
                        <span class="nav-arrow">
                            <i data-feather="chevron-right"></i>
                        </span>
                    </a>

                    <ul class="nav-submenu">

                        <li class="nav-item {{ request()->routeIs('purchase_orders.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('purchase_orders.index') }}">
                                أوامر الشراء
                            </a>
                        </li>

                        <li class="nav-item {{ request()->routeIs('purchase_invoices.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('purchase_invoices.index') }}">
                                فاتور المشتريات
                            </a>
                        </li>

                        <li class="nav-item {{ request()->routeIs('purchase_returns.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('purchase_returns.index') }}">
                                فاتور مرتجع المشتريات
                            </a>
                        </li>


                    </ul>
                </li>

                <li class="nav-item nav-hasmenu {{ request()->routeIs('sales_invoices.*','sales_returns.*') ? 'active nav-provoke' : '' }}">
                    <a href="#!" class="nav-link">
                        <span class="nav-icon">
                            <i class="ti ti-cash"></i>
                        </span>

                        <span class="nav-text">المبيعات</span>
                        <span class="nav-arrow">
                            <i data-feather="chevron-right"></i>
                        </span>
                    </a>

                    <ul class="nav-submenu">

                        <li class="nav-item {{ request()->routeIs('sales_invoices.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('sales_invoices.index') }}">
                                فواتير المبيعات
                            </a>
                        </li>

                        <li class="nav-item {{ request()->routeIs('sales_returns.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('sales_returns.index') }}">
                                مرتجع المبيعات
                            </a>
                        </li>

                    </ul>
                </li>

                <li class="nav-item {{ request()->routeIs('stock_adjustments.*') ? 'active' : '' }}">
                    <a href="{{ route('stock_adjustments.index') }}" class="nav-link">
                        <span class="nav-icon"><i class="ti ti-arrows-diff"></i></span>
                        <span class="nav-text">تسوية المخزون</span>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('fixed_assets.*') ? 'active' : '' }}">
                    <a href="{{ route('fixed_assets.index') }}" class="nav-link">
                        <span class="nav-icon"><i class="ti ti-building"></i></span>
                        <span class="nav-text">الأصول الثابتة</span>
                    </a>
                </li>

                {{-- التقارير المالية --}}
                <li class="nav-item nav-hasmenu {{ request()->routeIs('reports.*') ? 'active nav-provoke' : '' }}">
                    <a href="#!" class="nav-link">
                        <span class="nav-icon">
                            <i class="ti ti-chart-bar"></i>
                        </span>
                        <span class="nav-text">التقارير المالية</span>
                        <span class="nav-arrow">
                            <i data-feather="chevron-right"></i>
                        </span>
                    </a>
                    <ul class="nav-submenu">
                        <li class="nav-item {{ request()->routeIs('reports.balance_sheet') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('reports.balance_sheet') }}">الميزانية العمومية</a>
                        </li>
                        <li class="nav-item {{ request()->routeIs('reports.income_statement') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('reports.income_statement') }}">قائمة الدخل</a>
                        </li>
                        <li class="nav-item {{ request()->routeIs('reports.trial_balance') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('reports.trial_balance') }}">ميزان المراجعة</a>
                        </li>
                        <li class="nav-item {{ request()->routeIs('reports.account_statement') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('reports.account_statement') }}">كشف حساب</a>
                        </li>
                        <li class="nav-item {{ request()->routeIs('reports.cash_flow_statement') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('reports.cash_flow_statement') }}">التدفقات النقدية</a>
                        </li>
                    </ul>
                </li>

            </ul>


        </div>
    </div>
</aside>
