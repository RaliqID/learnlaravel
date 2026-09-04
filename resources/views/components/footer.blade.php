<footer class="bg-surface-container w-full mt-section-gap border-t border-outline-variant">
    <div class="max-w-desktop mx-auto px-margin-mobile md:px-margin-desktop py-stack-lg flex flex-col md:flex-row justify-between items-center gap-gutter transition-all duration-300">
        <div class="font-article-title-sm text-article-title-sm text-on-surface font-bold tracking-tight">
            LaraNews
        </div>

        <nav class="flex flex-wrap justify-center gap-x-6 gap-y-2">
            <a class="font-metadata text-metadata text-on-surface-variant hover:text-primary underline underline-offset-4 transition-all duration-300" href="{{ route('about') }}">About</a>
            <a class="font-metadata text-metadata text-on-surface-variant hover:text-primary underline underline-offset-4 transition-all duration-300" href="{{ route('privacy') }}">Privacy Policy</a>
            <a class="font-metadata text-metadata text-on-surface-variant hover:text-primary underline underline-offset-4 transition-all duration-300" href="{{ route('terms') }}">Terms of Service</a>
            <a class="font-metadata text-metadata text-on-surface-variant hover:text-primary underline underline-offset-4 transition-all duration-300" href="{{ route('contact') }}">Contact</a>
            <a class="font-metadata text-metadata text-on-surface-variant hover:text-primary underline underline-offset-4 transition-all duration-300" href="{{ route('newsletter') }}">Newsletter</a>
        </nav>

        <div class="font-metadata text-metadata text-on-surface-variant">
            &copy; {{ date('Y') }} LaraNews. All rights reserved.
        </div>
    </div>
</footer>