<aside
    class="fixed top-16 left-0 w-56 h-[calc(100vh-4rem)]
           flex flex-col border-r border-gray-200
           bg-white/95 backdrop-blur-lg text-gray-800">
    {{-- Welcome Section --}}
    <div class="px-4 pt-6 pb-2 border-b border-gray-100/60 bg-gray-50/60">
        <p class="font-semibold text-gray-700">Welcome, {{ Auth::user()->name }}</p>
        <p class="text-xs text-gray-500 mt-1">
            <span id="now-datetime">
                {{ \Carbon\Carbon::now()->format('l, d M Y — H:i') }}
            </span>
        </p>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 pt-4 pb-6 space-y-1">
        <x-nav-link href="{{ route('dashboard') }}"
                    :active="request()->routeIs('dashboard')"
                    class="group flex items-center gap-3 px-4 py-2 rounded-md hover:bg-gray-100 transition-colors">
            <x-heroicon-o-home class="w-5 h-5 text-gray-400 group-hover:text-indigo-500"/>
            <span class="font-medium">Dashboard</span>
        </x-nav-link>

        @can('manage customers')
            <x-nav-link href="{{ route('customers.index') }}"
                        :active="request()->routeIs('customers*')"
                        class="group flex items-center gap-3 px-4 py-2 rounded-md hover:bg-gray-100 transition-colors">
                <x-heroicon-o-users class="w-5 h-5 text-gray-400 group-hover:text-indigo-500"/>
                <span class="font-medium">Customers</span>
            </x-nav-link>
        @endcan

        @can('manage tickets')
            @php $ticketCount = \App\Models\Ticket::count(); @endphp
            <x-nav-link href="{{ route('tickets.index') }}"
                        :active="request()->routeIs('tickets*')"
                        class="group flex items-center gap-3 px-4 py-2 rounded-md hover:bg-gray-100 transition-colors">
                <x-heroicon-o-ticket class="w-5 h-5 text-gray-400 group-hover:text-indigo-500"/>
                <span class="font-medium">Tickets</span>
                <span class="ml-auto text-xs font-medium rounded-full px-2 py-0.5 bg-indigo-500/10 text-indigo-600">
                    {{ $ticketCount }}
                </span>
            </x-nav-link>
        @endcan

        @can('view reports')
            <x-nav-link href="{{ route('reports.index') }}"
                        :active="request()->routeIs('reports*')"
                        class="group flex items-center gap-3 px-4 py-2 rounded-md hover:bg-gray-100 transition-colors">
                <x-heroicon-o-chart-bar class="w-5 h-5 text-gray-400 group-hover:text-indigo-500"/>
                <span class="font-medium">Reports &amp; Analytics</span>
            </x-nav-link>
        @endcan

        @can('view sla')
            <x-nav-link href="{{ route('sla.index') }}"
                        :active="request()->routeIs('sla*')"
                        class="group flex items-center gap-3 px-4 py-2 rounded-md hover:bg-gray-100 transition-colors">
                <x-heroicon-o-clock class="w-5 h-5 text-gray-400 group-hover:text-indigo-500"/>
                <span class="font-medium">SLA Performance</span>
            </x-nav-link>
        @endcan

        @can('view performance')
            <x-nav-link href="{{ route('performance.index') }}"
                        :active="request()->routeIs('performance*')"
                        class="group flex items-center gap-3 px-4 py-2 rounded-md hover:bg-gray-100 transition-colors">
                <x-heroicon-o-chart-pie class="w-5 h-5 text-gray-400 group-hover:text-indigo-500"/>
                <span class="font-medium">Performance Evaluation</span>
            </x-nav-link>
        @endcan

        {{-- === MENU EKSCALASI === --}}
        @can('manage escalation')
            <x-nav-link href="{{ route('escalations.index') }}"
                        :active="request()->routeIs('escalations*')"
                        class="group flex items-center gap-3 px-4 py-2 rounded-md hover:bg-gray-100 transition-colors">
                <x-heroicon-o-arrow-trending-up class="w-5 h-5 text-gray-400 group-hover:text-indigo-500"/>
                <span class="font-medium">Escalation</span>
            </x-nav-link>
        @endcan

        @can('manage noc')
            <div class="px-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">NOC Management</p>
                <div class="mt-1 space-y-1 pl-2">
                    <x-nav-link href="{{ route('noc.manageShifts') }}"
                                :active="request()->routeIs('noc.manageShifts')"
                                class="group flex items-center gap-3 px-4 py-2 rounded-md hover:bg-gray-100 transition-colors">
                        <x-heroicon-o-cog class="w-5 h-5 text-gray-400 group-hover:text-indigo-500"/>
                        <span class="font-medium">Manage Shifts</span>
                    </x-nav-link>
                    <x-nav-link href="{{ route('noc.handover') }}"
                                :active="request()->routeIs('noc.handover')"
                                class="group flex items-center gap-3 px-4 py-2 rounded-md hover:bg-gray-100 transition-colors">
                        <x-heroicon-o-arrow-right class="w-5 h-5 text-gray-400 group-hover:text-indigo-500"/>
                        <span class="font-medium">Handover Shift</span>
                    </x-nav-link>
                </div>
            </div>
        @endcan

@can('manage settings')
  <x-nav-link href="{{ route('settings.mail.edit') }}"
              :active="request()->routeIs('settings.mail*')"
              class="group flex items-center gap-3 px-4 py-2 rounded-md hover:bg-gray-100 transition-colors">
    <x-heroicon-o-envelope class="w-5 h-5 text-gray-400 group-hover:text-indigo-500"/>
    <span class="font-medium">Email Settings</span>
  </x-nav-link>
@endcan

        {{-- === MENU ROLE MANAGEMENT === --}}
        @can('manage roles')
            <x-nav-link href="{{ route('roles.index') }}"
                        :active="request()->routeIs('roles*')"
                        class="group flex items-center gap-3 px-4 py-2 rounded-md hover:bg-gray-100 transition-colors">
                <x-heroicon-o-user-circle class="w-5 h-5 text-gray-400 group-hover:text-indigo-500"/>
                <span class="font-medium">Role Management</span>
            </x-nav-link>
        @endcan

        {{-- === MENU USER MANAGEMENT === --}}
        @can('manage users')
            <x-nav-link href="{{ route('users.index') }}"
                        :active="request()->routeIs('users*')"
                        class="group flex items-center gap-3 px-4 py-2 rounded-md hover:bg-gray-100 transition-colors">
                <x-heroicon-o-user-circle class="w-5 h-5 text-gray-400 group-hover:text-indigo-500"/>
                <span class="font-medium">User Management</span>
            </x-nav-link>
        @endcan

    </nav>

    {{-- User Profile / Sign Out --}}
    <div class="px-4 py-4 border-t border-gray-100/60 bg-gray-50/60">
        <div class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-100 transition-colors">
            <img class="h-9 w-9 rounded-full border-2 border-white shadow"
                 src="https://labsyahrul.tech/cdn/images/avatar.png"
                 alt="{{ Auth::user()->name }}">
            <div>
                <p class="text-sm font-medium">{{ Auth::user()->name }}</p>
                <p class="text-xs text-gray-500">{{ Auth::user()->email }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit"
                    class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-md bg-gray-100 text-sm font-medium hover:bg-gray-200 transition-colors">
                <x-heroicon-o-arrow-left-on-rectangle class="w-5 h-5 text-gray-500"/>
                Sign Out
            </button>
        </form>
    </div>
</aside>

<script>
    // Update datetime every minute
    setInterval(() => {
        document.getElementById('now-datetime').textContent = new Date().toLocaleString('en-US', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
    }, 60000);
</script>
