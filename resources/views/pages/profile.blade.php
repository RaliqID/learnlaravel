@extends('layouts.app')

@section('title', 'Profile')

@section('content')
    <div id="profile-page" data-username="{{ $username }}" class="bg-background min-h-screen">
        <main class="pt-24 pb-section-gap px-margin-mobile md:px-margin-tablet lg:px-margin-desktop max-w-[1440px] mx-auto">

            <!-- Profile Header - Two-column editorial layout (avatar left, info center, action right) -->
            <header class="mb-section-gap">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-gutter items-end">
                    <!-- Avatar -->
                    <div class="col-span-1 md:col-span-2">
                        <button type="button" id="profile-avatar-btn" class="w-24 h-24 md:w-32 md:h-32 shrink-0 rounded-full overflow-hidden bg-surface-container-high border-2 border-outline-variant transition-all hover:border-primary focus-visible:border-primary">
                            <div id="profile-avatar" class="size-full">
                                <div class="size-full animate-pulse bg-surface-container-highest"></div>
                            </div>
                        </button>
                    </div>

                    <!-- Identity -->
                    <div class="col-span-1 md:col-span-7 flex flex-col gap-stack-md">
                        <div class="flex items-center gap-stack-md">
                            <span class="font-metadata text-metadata text-primary bg-surface-container-high px-2 py-1 uppercase tracking-widest">Author</span>
                            <span id="profile-joined" class="font-metadata text-metadata text-on-surface-variant"></span>
                        </div>
                        <h1 id="profile-display-name" class="font-display text-page-title-mobile md:text-page-title text-on-surface m-0 leading-none">
                            <span class="inline-block h-10 w-56 animate-pulse bg-surface-container-high"></span>
                        </h1>
                        <p id="profile-username" class="font-metadata text-metadata text-primary uppercase tracking-wider"></p>
                        <p id="profile-bio" class="font-body-lg text-on-surface-variant max-w-2xl mt-stack-sm"></p>
                    </div>

                    <!-- Action Button -->
                    <div class="col-span-1 md:col-span-3 flex md:justify-end items-end h-full pt-stack-lg md:pt-0">
                        <div id="profile-follow-wrap" class="hidden w-full md:w-auto">
                            <button type="button" id="follow-btn" aria-pressed="false" class="w-full md:w-auto font-label text-label px-8 py-3 transition-colors flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-[20px]" data-icon="person_add">person_add</span>
                                <span id="follow-label">Follow</span>
                            </button>
                        </div>
                        <div id="profile-edit-wrap" class="hidden w-full md:w-auto">
                            <button type="button" id="edit-profile-btn" class="w-full md:w-auto font-label text-label px-8 py-3 transition-colors flex items-center justify-center gap-2 border border-outline-variant text-on-surface hover:bg-surface-container">
                                <span class="material-symbols-outlined text-[20px]" aria-hidden="true">edit</span>
                                <span>Edit Profile</span>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content Split Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-content-gap">

                <!-- Left Column: Posts & Activity -->
                <section class="lg:col-span-8 flex flex-col gap-content-gap">
                    <div class="border-b border-outline-variant pb-stack-md mb-stack-md flex gap-stack-sm">
                        <!-- Note: JS dynamically manages active/inactive classes -->
                        <button type="button" role="tab" data-profile-tab="posts" id="tab-posts" aria-selected="true" class="font-label text-label px-6 py-2 transition-colors bg-primary-container text-on-primary-container rounded-none">Posts</button>
                        <button type="button" role="tab" data-profile-tab="comments" id="tab-comments" aria-selected="false" class="font-label text-label px-6 py-2 transition-colors text-on-surface-variant hover:text-on-surface rounded-none">Activity</button>
                    </div>

                    <div id="panel-posts">
                        <div id="posts-list" class="flex flex-col gap-stack-md" aria-live="polite">
                            @for ($i = 0; $i < 3; $i++)
                                 <div class="border border-outline-variant p-4">
                                    <div class="h-3 w-24 animate-pulse bg-surface-container-high mb-3"></div>
                                    <div class="h-5 w-3/4 animate-pulse bg-surface-container-high mb-2"></div>
                                    <div class="h-4 w-1/4 animate-pulse bg-surface-container-high"></div>
                                </div>
                            @endfor
                        </div>
                    </div>

                    <div id="panel-comments" class="hidden">
                        <div id="comments-list" class="flex flex-col" aria-live="polite"></div>
                    </div>
                </section>

                <!-- Right Column: Meta / Profile Stats -->
                <aside class="lg:col-span-4 flex flex-col gap-content-gap">
                    <section>
                        <h2 class="font-section-title text-section-title text-on-surface mb-stack-md">Profile Stats</h2>
                        <div class="grid grid-cols-2 gap-px bg-outline-variant">
                            <button type="button" id="stat-posts-cell" class="bg-surface-container-low p-stack-lg flex flex-col gap-stack-sm text-left group hover:bg-surface-container transition-colors">
                                <span id="stat-posts" class="font-display text-[32px] leading-none text-primary group-hover:text-primary">0</span>
                                <span class="font-metadata text-metadata text-on-surface-variant uppercase tracking-wider">Posts</span>
                            </button>
                            <button type="button" id="stat-followers-btn" class="bg-surface-container-low p-stack-lg flex flex-col gap-stack-sm text-left group hover:bg-surface-container transition-colors">
                                <span id="stat-followers" class="font-display text-[32px] leading-none text-primary group-hover:text-primary">0</span>
                                <span class="font-metadata text-metadata text-on-surface-variant uppercase tracking-wider">Followers</span>
                            </button>
                            <button type="button" id="stat-following-btn" class="bg-surface-container-low p-stack-lg flex flex-col gap-stack-sm text-left group hover:bg-surface-container transition-colors">
                                <span id="stat-following" class="font-display text-[32px] leading-none text-primary group-hover:text-primary">0</span>
                                <span class="font-metadata text-metadata text-on-surface-variant uppercase tracking-wider">Following</span>
                            </button>
                            <button type="button" class="bg-surface-container-low p-stack-lg flex flex-col gap-stack-sm text-left">
                                <span id="stat-likes" class="font-display text-[32px] leading-none text-primary">0</span>
                                <span class="font-metadata text-metadata text-on-surface-variant uppercase tracking-wider">Likes</span>
                            </button>
                        </div>
                    </section>
                </aside>

                <!-- Account Section -->
                <section class="mt-content-gap border-t border-outline-variant pt-content-gap">
                    <h2 class="font-section-title text-section-title text-on-surface mb-stack-md">Account</h2>
                    <div class="bg-surface-container-low border border-outline-variant p-6 max-w-md">
                        <form id="password-form" class="flex flex-col gap-stack-md">
                            <div>
                                <label for="current-password" class="font-metadata text-metadata text-on-surface-variant block mb-1">Current Password</label>
                                <input id="current-password" type="password" class="w-full bg-transparent border-b border-outline-variant focus:border-primary px-0 py-2 text-on-surface outline-none transition-colors" placeholder="Enter current password">
                                <p class="err-current-password text-xs text-danger hidden mt-1"></p>
                            </div>
                            <div>
                                <label for="new-password" class="font-metadata text-metadata text-on-surface-variant block mb-1">New Password</label>
                                <input id="new-password" type="password" class="w-full bg-transparent border-b border-outline-variant focus:border-primary px-0 py-2 text-on-surface outline-none transition-colors" placeholder="Min 8 characters">
                                <p class="err-new-password text-xs text-danger hidden mt-1"></p>
                            </div>
                            <div>
                                <label for="new-password-confirm" class="font-metadata text-metadata text-on-surface-variant block mb-1">Confirm Password</label>
                                <input id="new-password-confirm" type="password" class="w-full bg-transparent border-b border-outline-variant focus:border-primary px-0 py-2 text-on-surface outline-none transition-colors" placeholder="Retype new password">
                            </div>
                            <p id="password-message" class="hidden text-sm"></p>
                            <div class="flex flex-wrap gap-4 pt-2">
                                <button type="submit" class="px-6 py-2.5 bg-primary-container text-on-primary-container hover:bg-inverse-primary transition-colors font-label text-label rounded-none">Update Password</button>
                                <button type="button" id="profile-logout-btn" class="px-6 py-2.5 border border-outline-variant text-danger hover:bg-surface-container hover:border-danger transition-colors font-label text-label rounded-none">Logout</button>
                            </div>
                        </form>
                    </div>
                </section>

            </div>
        </main>

        <!-- Error States -->
        <div id="profile-error" class="hidden pt-24 pb-section-gap px-margin-mobile text-center h-[50vh] flex-col items-center justify-center">
            <h1 class="font-display text-page-title-mobile md:text-page-title text-on-surface">Couldn't load this profile.</h1>
            <button type="button" data-retry="profile" class="mt-stack-lg bg-primary text-on-primary font-label text-label px-8 py-3 hover:opacity-90 transition-colors">Retry</button>
        </div>

        <div id="profile-notfound" class="hidden pt-24 pb-section-gap px-margin-mobile text-center h-[50vh] flex-col items-center justify-center">
            <h1 class="font-display text-page-title-mobile md:text-page-title text-on-surface">User not found</h1>
            <p class="font-body-lg text-on-surface-variant mt-stack-md">This profile may have been removed.</p>
            <a href="{{ url('/') }}" class="mt-stack-lg border border-outline-variant text-on-surface font-label text-label px-8 py-3 hover:bg-surface-container transition-colors inline-flex">Back to home</a>
        </div>

        <!-- Edit Profile Modal -->
        <div id="edit-profile-modal" class="hidden fixed inset-0 z-modal flex items-end md:items-center justify-center" role="dialog" aria-modal="true" aria-labelledby="edit-profile-title">
            <div id="edit-profile-backdrop" class="absolute inset-0 bg-black/60"></div>
            <div class="relative z-10 w-full md:max-w-lg bg-surface-container-low border border-outline-variant max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant">
                    <h2 id="edit-profile-title" class="font-section-title text-section-title text-on-surface">Edit Profile</h2>
                    <button type="button" id="edit-profile-close" aria-label="Close" class="text-on-surface-variant hover:text-on-surface transition-colors p-1">
                        <span class="material-symbols-outlined text-[20px]" aria-hidden="true">close</span>
                    </button>
                </div>

                <form id="edit-profile-form" class="flex flex-col gap-stack-lg px-6 py-6">
                    <!-- Avatar upload -->
                    <div class="flex flex-col items-center gap-stack-md pb-stack-lg border-b border-outline-variant">
                        <div id="edit-avatar-preview" class="w-24 h-24 rounded-full overflow-hidden bg-surface-container-high border border-outline-variant">
                            <span class="inline-flex items-center justify-center size-full text-2xl font-semibold text-on-surface-variant">?</span>
                        </div>
                        <div class="flex items-center gap-stack-sm">
                            <button type="button" id="edit-avatar-change" class="font-label text-label px-5 py-2 border border-outline-variant text-on-surface hover:bg-surface-container transition-colors">Change Photo</button>
                            <button type="button" id="edit-avatar-remove" class="hidden font-label text-label px-5 py-2 text-on-surface-variant hover:text-danger transition-colors">Remove</button>
                        </div>
                        <input id="edit-avatar-input" type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden" />
                        <p id="edit-avatar-hint" class="font-metadata text-metadata text-outline">JPG, PNG, GIF, WEBP up to 5MB</p>
                        <div id="edit-avatar-progress" class="hidden w-full max-w-xs">
                            <div class="h-1 bg-surface-container-highest overflow-hidden">
                                <div id="edit-avatar-progress-bar" class="h-full bg-primary transition-all duration-300" style="width: 0%"></div>
                            </div>
                            <p id="edit-avatar-progress-label" class="font-metadata text-metadata text-on-surface-variant mt-1 text-center">Uploading…</p>
                        </div>
                    </div>

                    <div>
                        <label for="edit-display-name" class="font-metadata text-metadata uppercase tracking-wider text-on-surface-variant block mb-1">Display name</label>
                        <input id="edit-display-name" name="display_name" type="text" required
                            class="w-full bg-transparent border-0 border-b border-outline-variant focus:border-primary focus:ring-0 px-0 py-2 font-body-md text-body-md text-on-surface transition-colors outline-none rounded-none" />
                        <p id="err-display_name" class="hidden mt-1 text-xs text-danger"></p>
                    </div>

                    <div>
                        <label for="edit-username" class="font-metadata text-metadata uppercase tracking-wider text-on-surface-variant block mb-1">Username</label>
                        <input id="edit-username" name="username" type="text" required
                            class="w-full bg-transparent border-0 border-b border-outline-variant focus:border-primary focus:ring-0 px-0 py-2 font-body-md text-body-md text-on-surface transition-colors outline-none rounded-none" />
                        <p id="err-username" class="hidden mt-1 text-xs text-danger"></p>
                    </div>

                    <div>
                        <label for="edit-bio" class="font-metadata text-metadata uppercase tracking-wider text-on-surface-variant block mb-1">Bio</label>
                        <textarea id="edit-bio" name="bio" rows="3" maxlength="500"
                            class="w-full bg-transparent border border-outline-variant focus:border-primary focus:ring-0 px-3 py-2 font-body-md text-body-md text-on-surface transition-colors outline-none rounded-none resize-none"
                            placeholder="A short line about yourself."></textarea>
                        <p class="mt-1 text-xs text-outline text-right"><span id="bio-count">0</span>/500</p>
                        <p id="err-bio" class="hidden mt-1 text-xs text-danger"></p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-stack-lg">
                        <div>
                            <label for="edit-website" class="font-metadata text-metadata uppercase tracking-wider text-on-surface-variant block mb-1">Website</label>
                            <input id="edit-website" name="website" type="url"
                                class="w-full bg-transparent border-0 border-b border-outline-variant focus:border-primary focus:ring-0 px-0 py-2 font-body-md text-body-md text-on-surface transition-colors outline-none rounded-none"
                                placeholder="https://" />
                            <p id="err-website" class="hidden mt-1 text-xs text-danger"></p>
                        </div>
                        <div>
                            <label for="edit-location" class="font-metadata text-metadata uppercase tracking-wider text-on-surface-variant block mb-1">Location</label>
                            <input id="edit-location" name="location" type="text"
                                class="w-full bg-transparent border-0 border-b border-outline-variant focus:border-primary focus:ring-0 px-0 py-2 font-body-md text-body-md text-on-surface transition-colors outline-none rounded-none" />
                            <p id="err-location" class="hidden mt-1 text-xs text-danger"></p>
                        </div>
                    </div>

                    <p id="edit-profile-message" class="hidden text-sm"></p>

                    <div class="flex items-center justify-end gap-stack-sm pt-stack-sm border-t border-outline-variant">
                        <button type="button" id="edit-profile-cancel" class="font-label text-label px-6 py-3 text-on-surface-variant hover:text-on-surface transition-colors">Cancel</button>
                        <button type="submit" id="edit-profile-save" class="font-label text-label px-8 py-3 bg-primary-container text-on-primary-container hover:bg-inverse-primary transition-colors flex items-center gap-2">
                            <span id="edit-profile-save-label">Save changes</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Followers Modal -->
        <div id="followers-modal" class="hidden fixed inset-0 z-modal flex items-end md:items-center justify-center" role="dialog" aria-modal="true" aria-labelledby="followers-modal-title">
            <div data-modal-backdrop="followers" class="absolute inset-0 bg-black/60"></div>
            <div class="relative z-10 w-full md:max-w-md bg-surface-container-low border border-outline-variant max-h-[85vh] flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant shrink-0">
                    <h2 id="followers-modal-title" class="font-section-title text-section-title text-on-surface">Followers</h2>
                    <button type="button" data-modal-close="followers" aria-label="Close" class="text-on-surface-variant hover:text-on-surface transition-colors p-1">
                        <span class="material-symbols-outlined text-[20px]" aria-hidden="true">close</span>
                    </button>
                </div>
                <div id="followers-list" class="overflow-y-auto px-6 py-4 flex flex-col" aria-live="polite"></div>
            </div>
        </div>

        <!-- Following Modal -->
        <div id="following-modal" class="hidden fixed inset-0 z-modal flex items-end md:items-center justify-center" role="dialog" aria-modal="true" aria-labelledby="following-modal-title">
            <div data-modal-backdrop="following" class="absolute inset-0 bg-black/60"></div>
            <div class="relative z-10 w-full md:max-w-md bg-surface-container-low border border-outline-variant max-h-[85vh] flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant shrink-0">
                    <h2 id="following-modal-title" class="font-section-title text-section-title text-on-surface">Following</h2>
                    <button type="button" data-modal-close="following" aria-label="Close" class="text-on-surface-variant hover:text-on-surface transition-colors p-1">
                        <span class="material-symbols-outlined text-[20px]" aria-hidden="true">close</span>
                    </button>
                </div>
                <div id="following-list" class="overflow-y-auto px-6 py-4 flex flex-col" aria-live="polite"></div>
            </div>
        </div>

        <!-- Avatar Lightbox -->
        <div id="avatar-lightbox" class="hidden fixed inset-0 z-modal flex items-center justify-center" role="dialog" aria-modal="true" aria-label="Avatar full size">
            <div id="avatar-lightbox-backdrop" class="absolute inset-0 bg-black/80"></div>
            <button type="button" id="avatar-lightbox-close" aria-label="Close" class="absolute top-4 right-4 z-10 text-on-surface-variant hover:text-on-surface transition-colors p-2">
                <span class="material-symbols-outlined text-[24px]" aria-hidden="true">close</span>
            </button>
            <div id="avatar-lightbox-content" class="relative z-10 max-w-[90vw] max-h-[90vh]"></div>
        </div>
    </div>
@endsection
