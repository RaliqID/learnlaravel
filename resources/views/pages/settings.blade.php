@extends('layouts.app')

@section('title', 'Settings')

@section('content')
    <main id="settings-page" class="flex-grow py-content-gap min-h-screen w-full max-w-desktop mx-auto px-margin-mobile md:px-margin-desktop">
        <div class="mb-content-gap">
            <h1 class="font-page-title-mobile md:font-page-title text-page-title-mobile md:text-page-title text-on-surface">Settings</h1>
            <p class="font-body-lg text-body-lg text-on-surface-variant mt-2">Manage your account preferences and personal information.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-gutter">
            <!-- Left Navigation -->
            <aside class="md:col-span-3 lg:col-span-2 flex flex-col gap-stack-sm mb-content-gap md:mb-0 border-r-0 md:border-r md:border-outline-variant pr-stack-lg">
                <nav class="flex flex-col gap-2">
                    <a href="#profile-settings" class="font-label text-label py-2 px-3 text-primary font-bold bg-surface-container-highest flex items-center gap-3">
                        <span class="material-symbols-outlined text-[20px]">person</span> Profile
                    </a>
                    <a href="#notification-settings" class="font-label text-label py-2 px-3 text-on-surface-variant hover:bg-surface-container transition-colors flex items-center gap-3">
                        <span class="material-symbols-outlined text-[20px]">notifications</span> Notifications
                    </a>
                </nav>
            </aside>

            <!-- Content Area -->
            <div class="md:col-span-9 lg:col-span-8 md:pl-gutter">
                <form id="settings-form" class="flex flex-col gap-section-gap">
                    <!-- Profile Settings -->
                    <section id="profile-settings">
                        <h2 class="font-section-title text-section-title text-on-surface mb-stack-lg pb-4 border-b border-outline-variant">Profile Information</h2>
                        
                        <div class="flex items-start gap-gutter mb-content-gap">
                            <div id="settings-avatar" class="w-24 h-24 bg-surface-container-high overflow-hidden flex-shrink-0 flex items-center justify-center">
                                <span class="material-symbols-outlined text-[48px] text-on-surface-variant">person</span>
                            </div>
                            <div class="flex flex-col justify-center h-24 gap-stack-sm">
                                <button type="button" class="font-label text-label bg-surface-container-highest text-on-surface px-4 py-2 hover:bg-surface-bright transition-colors cursor-not-allowed opacity-50">Change Avatar (Soon)</button>
                            </div>
                        </div>

                        <div class="flex flex-col gap-6 max-w-2xl">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="font-label text-label text-on-surface">Public Profile</h3>
                                    <p class="font-metadata text-metadata text-on-surface-variant mt-1">Allow others to see your email.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" id="show_email" name="show_email" class="sr-only peer">
                                    <div class="w-12 h-6 bg-surface-container-high peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-6 peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-on-surface-variant peer-checked:after:bg-background after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary border border-outline-variant peer-checked:border-primary"></div>
                                </label>
                            </div>
                        </div>
                    </section>

                    <!-- Notification Settings -->
                    <section id="notification-settings">
                        <h2 class="font-section-title text-section-title text-on-surface mb-stack-lg pb-4 border-b border-outline-variant">Notification Preferences</h2>
                        
                        <div class="flex flex-col gap-6 max-w-2xl">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="font-label text-label text-on-surface">Email Notifications</h3>
                                    <p class="font-metadata text-metadata text-on-surface-variant mt-1">Receive notification digests by email.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" id="email_notifications" name="email_notifications" class="sr-only peer">
                                    <div class="w-12 h-6 bg-surface-container-high peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-6 peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-on-surface-variant peer-checked:after:bg-background after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary border border-outline-variant peer-checked:border-primary"></div>
                                </label>
                            </div>
                        </div>
                    </section>

                    <div class="flex flex-col items-end pt-stack-lg border-t border-outline-variant max-w-2xl gap-4">
                        <p id="settings-message" class="hidden text-sm font-metadata"></p>
                        <button type="submit" class="bg-primary text-on-primary font-label text-label px-6 py-3 hover:bg-primary-fixed transition-colors">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
@endsection
