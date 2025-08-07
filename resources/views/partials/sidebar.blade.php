<aside
    class="fixed top-16 left-0 w-56 h-[calc(100vh-4rem)]
           flex flex-col border-r border-gray-200
           bg-white/95 backdrop-blur-lg text-gray-800">

    {{-- Welcome Section --}}
    <div class="px-4 pt-6 pb-2 border-b border-gray-100/60 bg-gray-50/60">
        <p class="font-semibold text-gray-700">{{ __('sidebar.welcome', ['name' => Auth::user()->name]) }}</p>
        <p class="text-xs text-gray-500 mt-1">
            {{-- This will be populated by JavaScript to ensure it's in the user's locale and timezone --}}
            <span id="now-datetime"></span>
        </p>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 pt-4 pb-6 space-y-1">

        @can('dashboard')
            <x-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')"
                        class="group flex items-center gap-3 px-4 py-2 rounded-md hover:bg-gray-100 transition-colors">
                <x-heroicon-o-home class="w-5 h-5 text-gray-400 group-hover:text-indigo-500"/>
                <span class="font-medium">{{ __('sidebar.dashboard') }}</span>
            </x-nav-link>
        @endcan

        @can('customers.index')
            <x-nav-link href="{{ route('customers.index') }}" :active="request()->routeIs('customers*')"
                        class="group flex items-center gap-3 px-4 py-2 rounded-md hover:bg-gray-100 transition-colors">
                <x-heroicon-o-user-group class="w-5 h-5 text-gray-400 group-hover:text-indigo-500"/>
                <span class="font-medium">{{ __('sidebar.customers') }}</span>
            </x-nav-link>
        @endcan

        @can('tickets.index')
            {{-- The $ticketCount variable should be provided by a View Composer for better performance --}}
            <x-nav-link href="{{ route('tickets.index') }}" :active="request()->routeIs('tickets*')"
                        class="group flex items-center gap-3 px-4 py-2 rounded-md hover:bg-gray-100 transition-colors">
                <x-heroicon-o-ticket class="w-5 h-5 text-gray-400 group-hover:text-indigo-500"/>
                <span class="font-medium">{{ __('sidebar.tickets') }}</span>
                @if(isset($ticketCount) && $ticketCount > 0)
                <span class="ml-auto text-xs font-medium rounded-full px-2 py-0.5 bg-indigo-500/10 text-indigo-600">
                    {{ $ticketCount }}
                </span>
                @endif
            </x-nav-link>
        @endcan

        @can('reports.index')
            <x-nav-link href="{{ route('reports.index') }}" :active="request()->routeIs('reports*')"
                        class="group flex items-center gap-3 px-4 py-2 rounded-md hover:bg-gray-100 transition-colors">
                <x-heroicon-o-chart-bar class="w-5 h-5 text-gray-400 group-hover:text-indigo-500"/>
                <span class="font-medium">{{ __('sidebar.reports_analytics') }}</span>
            </x-nav-link>
        @endcan

        @can('sla.index')
            <x-nav-link href="{{ route('sla.index') }}" :active="request()->routeIs('sla*')"
                        class="group flex items-center gap-3 px-4 py-2 rounded-md hover:bg-gray-100 transition-colors">
                <x-heroicon-o-clock class="w-5 h-5 text-gray-400 group-hover:text-indigo-500"/>
                <span class="font-medium">{{ __('sidebar.sla_performance') }}</span>
            </x-nav-link>
        @endcan

        @can('performance.index')
            <x-nav-link href="{{ route('performance.index') }}" :active="request()->routeIs('performance*')"
                        class="group flex items-center gap-3 px-4 py-2 rounded-md hover:bg-gray-100 transition-colors">
                <x-heroicon-o-chart-pie class="w-5 h-5 text-gray-400 group-hover:text-indigo-500"/>
                <span class="font-medium">{{ __('sidebar.performance_evaluation') }}</span>
            </x-nav-link>
        @endcan

        @can('escalations.index')
            <x-nav-link href="{{ route('escalations.index') }}" :active="request()->routeIs('escalations*')"
                        class="group flex items-center gap-3 px-4 py-2 rounded-md hover:bg-gray-100 transition-colors">
                <x-heroicon-o-arrow-trending-up class="w-5 h-5 text-gray-400 group-hover:text-indigo-500"/>
                <span class="font-medium">{{ __('sidebar.escalation') }}</span>
            </x-nav-link>
        @endcan

        @canany(['noc.manageShifts','noc.handover','noc.history'])
            <div class="px-4 pt-3">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('sidebar.noc_management') }}</p>
                <div class="mt-1 space-y-1 pl-2 border-l border-gray-200 ml-1">
                    @can('noc.manageShifts')
                        <x-nav-link href="{{ route('noc.manageShifts') }}" :active="request()->routeIs('noc.manageShifts')"
                                      class="group flex items-center gap-3 px-4 py-2 rounded-md hover:bg-gray-100 transition-colors">
                            <x-heroicon-o-cog class="w-5 h-5 text-gray-400 group-hover:text-indigo-500"/>
                            <span class="font-medium">{{ __('sidebar.manage_shifts') }}</span>
                        </x-nav-link>
                    @endcan
                    @can('noc.handover')
                        <x-nav-link href="{{ route('noc.handover') }}" :active="request()->routeIs('noc.handover')"
                                      class="group flex items-center gap-3 px-4 py-2 rounded-md hover:bg-gray-100 transition-colors">
                            <x-heroicon-o-arrow-right-on-rectangle class="w-5 h-5 text-gray-400 group-hover:text-indigo-500"/>
                            <span class="font-medium">{{ __('sidebar.handover_shift') }}</span>
                        </x-nav-link>
                    @endcan
                    @can('noc.history')
                        <x-nav-link href="{{ route('noc.history') }}" :active="request()->routeIs('noc.history')"
                                      class="group flex items-center gap-3 px-4 py-2 rounded-md hover:bg-gray-100 transition-colors">
                            <x-heroicon-o-clock class="w-5 h-5 text-gray-400 group-hover:text-indigo-500"/>
                            <span class="font-medium">{{ __('sidebar.handover_history') }}</span>
                        </x-nav-link>
                    @endcan
                </div>
            </div>
        @endcanany

        @can('manage settings')
            <x-nav-link
               href="{{ route('settings.mail') }}"
               :active="request()->routeIs('settings.mail*')"
               class="group flex items-center gap-3 px-4 py-2 rounded-md hover:bg-gray-100 transition-colors">
                <x-heroicon-o-envelope class="w-5 h-5 text-gray-400 group-hover:text-indigo-500"/>
                <span class="font-medium">Email</span>
            </x-nav-link>
        @endcan

        @can('cacti.graphs.index') {{-- Atur permission sesuai kebutuhan --}}
        <x-nav-link href="{{ route('monitoring.index') }}" :active="request()->routeIs('monitoring.index')"
            class="group flex items-center gap-3 px-4 py-2 rounded-md hover:bg-gray-100 transition-colors">
            <x-heroicon-o-presentation-chart-bar class="w-5 h-5 text-gray-400 group-hover:text-indigo-500"/>
            <span class="font-medium">Network Monitoring</span>
        </x-nav-link>
        @endcan


        @can('roles.index')
            <x-nav-link href="{{ route('roles.index') }}" :active="request()->routeIs('roles*')"
                        class="group flex items-center gap-3 px-4 py-2 rounded-md hover:bg-gray-100 transition-colors">
                <x-heroicon-o-shield-check class="w-5 h-5 text-gray-400 group-hover:text-indigo-500"/>
                <span class="font-medium">{{ __('sidebar.role_management') }}</span>
            </x-nav-link>
        @endcan

        @can('users.index')
            <x-nav-link href="{{ route('users.index') }}" :active="request()->routeIs('users*')"
                        class="group flex items-center gap-3 px-4 py-2 rounded-md hover:bg-gray-100 transition-colors">
                <x-heroicon-o-user class="w-5 h-5 text-gray-400 group-hover:text-indigo-500"/>
                <span class="font-medium">{{ __('sidebar.user_management') }}</span>
            </x-nav-link>
        @endcan
        @can('whatsapp.bot')
            <x-nav-link href="{{ route('whatsapp.bot') }}" :active="request()->routeIs('whatsapp.bot')"
                        class="group flex items-center gap-3 px-4 py-2 rounded-md hover:bg-gray-100 transition-colors">
                <x-heroicon-o-chat-bubble-left-right class="w-5 h-5 text-gray-400 group-hover:text-indigo-500"/>
                <span class="font-medium">WhatsApp Bot</span>
            </x-nav-link>
        @endcan
    </nav>

    {{-- User Profile / Sign Out --}}
    <div class="px-4 py-4 border-t border-gray-100/60 bg-gray-50/60">
        <a href="#" class="block group"> {{-- Consider linking to a profile page --}}
            <div class="flex items-center gap-3 px-3 py-2 rounded-md group-hover:bg-gray-100 transition-colors">
                <img class="h-9 w-9 rounded-full border-2 border-white shadow"
                     src="https://labsyahrul.tech/cdn/images/avatar.png"
                     alt="{{ Auth::user()->name }}">
                <div>
                    <p class="text-sm font-medium">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-gray-500">{{ Auth::user()->email }}</p>
                </div>
            </div>
        </a>

        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit"
                    class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-md bg-gray-100 text-sm font-medium hover:bg-gray-200 transition-colors">
                <x-heroicon-o-arrow-left-on-rectangle class="w-5 h-5 text-gray-500"/>
                {{ __('sidebar.sign_out') }}
            </button>
        </form>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const datetimeElement = document.getElementById('now-datetime');

    const updateTime = () => {
        // Using 'en-GB' for a format closer to the original, but can be 'en-US' or others.
        // This ensures the time format is consistent.
        datetimeElement.textContent = new Date().toLocaleString('en-GB', {
            weekday: 'long',
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        }).replace(',', ' —');
    };

    updateTime(); // Run once immediately on page load
    setInterval(updateTime, 60000); // Then update every minute
});
</script>
