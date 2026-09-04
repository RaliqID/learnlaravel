@php
    $nav = [
        ['label' => 'Home', 'href' => url('/'), 'active' => request()->is('/')],
        ['label' => 'Topics', 'href' => url('/topics'), 'active' => request()->is('topics*')],
        ['label' => 'Discovery', 'href' => url('/discovery'), 'active' => request()->is('discovery')],
    ];
@endphp

<header id="site-navbar" class="bg-background border-b border-outline-variant sticky top-0 z-40 transition-transform duration-300 ease-out will-change-transform">
    <div class="w-full max-w-desktop mx-auto px-margin-mobile md:px-margin-desktop flex justify-between items-center h-20">
        <!-- Brand -->
        <div class="flex items-center gap-4">
            <a class="font-page-title-mobile text-page-title-mobile font-bold text-on-background tracking-tighter" href="{{ url('/') }}" aria-label="LaraNews home">LaraNews</a>
        </div>

        <!-- Navigation Links (Desktop) -->
        <nav class="hidden md:flex gap-gutter items-center" aria-label="Primary">
            @foreach ($nav as $item)
                <a href="{{ $item['href'] }}"
                    @class([
                        'font-body-md text-body-md hover:text-primary transition-colors duration-200 cursor-pointer active:opacity-80',
                        'text-primary font-bold border-b-2 border-primary pb-1' => $item['active'],
                        'text-on-surface-variant' => ! $item['active'],
                    ])>{{ $item['label'] }}</a>
            @endforeach
        </nav>

        <!-- Actions -->
        <div class="flex items-center gap-2 sm:gap-4">
            <form action="{{ url('/search') }}" method="GET" role="search" class="hidden md:flex items-center border-b border-outline-variant focus-within:border-primary transition-colors pb-1">
                <span class="material-symbols-outlined text-on-surface-variant mr-2 text-sm" aria-hidden="true">search</span>
                <input name="q" value="{{ request('q') }}" aria-label="Search LaraNews" placeholder="Search..." type="text"
                    class="bg-transparent border-none outline-none text-body-md font-body-md text-on-background placeholder-on-surface-variant p-0 w-32 focus:ring-0">
            </form>
            <a href="{{ url('/chat') }}" aria-label="Messages" class="hidden md:inline-flex items-center gap-2 px-3 py-2 border border-outline-variant text-on-surface font-label text-label hover:bg-surface-container-high transition-colors">
                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">chat</span>
                <span>Chat</span>
            </a>
            <a href="{{ url('/notifications') }}" aria-label="Notifications" class="text-on-surface-variant hover:text-primary transition-colors p-1.5 sm:p-2">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 0;">notifications</span>
            </a>
            <a href="{{ url('/profile') }}" aria-label="Profile" data-profile-link class="text-on-surface-variant hover:text-primary transition-colors p-1.5 sm:p-2">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 0;">person</span>
            </a>
            <button type="button" id="mobile-menu-toggle" aria-expanded="false" aria-controls="mobile-menu" aria-label="Open menu" class="md:hidden text-on-surface-variant hover:text-primary transition-colors p-1.5 sm:p-2">
                <span class="material-symbols-outlined">menu</span>
            </button>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div id="mobile-menu" class="hidden border-t border-outline-variant bg-background md:hidden">
        <div class="px-margin-mobile py-stack-md flex flex-col gap-1">
            @foreach ($nav as $item)
                <a href="{{ $item['href'] }}"
                    @class([
                        'block border-l-2 px-3 py-2 font-body-md text-body-md font-semibold',
                        'border-primary text-primary' => $item['active'],
                        'border-transparent text-on-surface-variant hover:text-primary' => ! $item['active'],
                    ])>{{ $item['label'] }}</a>
            @endforeach
            <a href="{{ url('/notifications') }}" class="flex items-center gap-2 border-l-2 border-transparent px-3 py-2 font-body-md text-body-md font-semibold text-on-surface-variant hover:text-primary">
                <span class="material-symbols-outlined text-sm" aria-hidden="true">notifications</span> Notifications
            </a>
            <a href="{{ url('/profile') }}" data-profile-link class="flex items-center gap-2 border-l-2 border-transparent px-3 py-2 font-body-md text-body-md font-semibold text-on-surface-variant hover:text-primary">
                <span class="material-symbols-outlined text-sm" aria-hidden="true">person</span> Profile
            </a>
        </div>
    </div>
</header>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const toggle = document.getElementById('mobile-menu-toggle');
        const menu = document.getElementById('mobile-menu');
        if (!toggle || !menu) return;

        toggle.addEventListener('click', () => {
            const open = menu.classList.toggle('hidden') === false;
            toggle.setAttribute('aria-expanded', String(open));
            toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                menu.classList.add('hidden');
                toggle.setAttribute('aria-expanded', 'false');
                toggle.setAttribute('aria-label', 'Open menu');
            }
        });

        // Navbar hide on scroll
        const navbar = document.getElementById('site-navbar');
        let lastScroll = 0;
        let ticking = false;

        if (!navbar) return;

        window.addEventListener('scroll', () => {
            if (!ticking) {
                window.requestAnimationFrame(() => {
                    const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
                    if (currentScroll > 80 && currentScroll > lastScroll) {
                        navbar.style.transform = 'translateY(-100%)';
                    } else {
                        navbar.style.transform = 'translateY(0)';
                    }
                    lastScroll = currentScroll;
                    ticking = false;
                });
                ticking = true;
            }
        }, { passive: true });
    });
</script>
@endpush
