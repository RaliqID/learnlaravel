@props([
    'id' => 'toast-root',
])

<div id="{{ $id }}" class="fixed bottom-4 left-4 right-4 sm:left-auto sm:right-4 sm:w-[calc(100vw-2rem)] sm:max-w-80 z-toast flex flex-col items-stretch gap-2"
    aria-live="polite" role="status" aria-relevant="additions"></div>

@once
    @push('scripts')
        <script>
            (function () {
                window.laranewsToast = window.laranewsToast || (function () {
                    const root = document.getElementById('{{ $id }}');

                    function create(title, description, tone) {
                        if (!root) return;

                        const colors = {
                            success: 'border-success/40',
                            error: 'border-danger/40',
                            info: 'border-primary/40',
                        };

                        const el = document.createElement('div');
                        el.className = 'relative w-full max-w-80 border bg-surface-container-high shadow-lg p-4 transition-all duration-200 translate-y-1 opacity-0';
                        el.className += ' ' + (colors[tone] || colors.info);

                        el.innerHTML = `
                            <p class="text-sm font-semibold text-on-background">${title}</p>
                            ${description ? `<p class="mt-0.5 text-xs text-on-surface-variant">${description}</p>` : ''}
                            <button type="button" class="absolute top-2 right-2 text-outline hover:text-on-background" aria-label="Close">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>`;

                        requestAnimationFrame(() => {
                            el.classList.remove('translate-y-1', 'opacity-0');
                        });

                        const remove = () => {
                            el.classList.add('opacity-0', 'translate-y-1');
                            setTimeout(() => el.remove(), 200);
                        };

                        el.querySelector('button').addEventListener('click', remove);
                        setTimeout(remove, 4200);
                        root.appendChild(el);
                    }

                    return {
                        success: (t, d) => create(t, d, 'success'),
                        error: (t, d) => create(t, d, 'error'),
                        info: (t, d) => create(t, d, 'info'),
                    };
                })();
            })();
        </script>
    @endpush
@endonce
