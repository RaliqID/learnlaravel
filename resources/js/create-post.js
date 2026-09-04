/**
 * Create post controller.
 * Handles post creation with tab-based type switching, drag & drop image
 * upload with preview, inline validation, and loading states.
 */

import { postsApi, topicsApi, isAuthenticated } from './api/client.js';

const MAX_IMAGE_SIZE = 10 * 1024 * 1024; // 10MB
const TITLE_MAX = 300;
const TITLE_MIN = 10;
const CONTENT_MAX = 10000;

function escapeHtml(str = '') {
    return String(str ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

function formatBytes(bytes) {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export default async function initCreatePostPage() {
    const root = document.getElementById('create-post-page');
    if (!root) return;

    // Auth check
    if (!isAuthenticated()) {
        window.location.href = '/login';
        return;
    }

    const form = document.getElementById('create-post-form');
    if (!form) return;

    // --- Element refs ---
    const typeInput = document.getElementById('post-type-input');
    const typeTabs = Array.from(document.querySelectorAll('.post-type-tab'));
    const contentWrap = document.getElementById('post-content-wrap');
    const urlWrap = document.getElementById('post-url-wrap');
    const imageWrap = document.getElementById('post-image-wrap');
    const titleInput = document.getElementById('post-title-input');
    const urlInput = document.getElementById('post-url');
    const contentInput = document.getElementById('post-content-input');
    const imageInput = document.getElementById('post-image');
    const dropZone = document.getElementById('image-drop-zone');
    const previewContainer = document.getElementById('image-preview-container');
    const previewImg = document.getElementById('image-preview');
    const removeImageBtn = document.getElementById('remove-image-btn');
    const imageDimensionsEl = document.getElementById('image-dimensions');
    const imageSizeEl = document.getElementById('image-size');
    const titleCounter = document.getElementById('title-counter');
    const contentCounter = document.getElementById('content-counter');
    const messageEl = document.getElementById('create-post-message');
    const submitBtn = document.getElementById('submit-btn');
    const submitText = document.getElementById('submit-text');
    const submitIcon = document.getElementById('submit-icon');
    const submitSpinner = document.getElementById('submit-spinner');

    let selectedImageFile = null;
    let previewObjectUrl = null;

    // --- Inline field errors ---
    function setFieldError(fieldId, message) {
        const errorEl = document.getElementById(`${fieldId}-error`);
        const inputEl = document.getElementById(fieldId);
        if (errorEl) {
            errorEl.textContent = message || '';
            errorEl.classList.toggle('hidden', !message);
        }
        if (inputEl) {
            inputEl.classList.toggle('border-error', Boolean(message));
            inputEl.classList.toggle('border-outline-variant', !message);
        }
    }

    function clearFieldError(fieldId) {
        setFieldError(fieldId, null);
    }

    function clearAllErrors() {
        ['post-title-input', 'post-url', 'post-image', 'post-content-input']
            .forEach(clearFieldError);
    }

    // Clear error on user input
    titleInput?.addEventListener('input', () => clearFieldError('post-title-input'));
    urlInput?.addEventListener('input', () => clearFieldError('post-url'));
    contentInput?.addEventListener('input', () => clearFieldError('post-content-input'));

    // --- Global message banner ---
    function showMessage(message, isError = false) {
        if (!messageEl) return;
        messageEl.textContent = message;
        messageEl.className = isError
            ? 'font-body-md text-body-md px-4 py-3 border-l-2 border-error bg-error-container/20 text-error'
            : 'font-body-md text-body-md px-4 py-3 border-l-2 border-success bg-success-soft text-on-surface';
        messageEl.classList.remove('hidden');
    }

    function clearMessage() {
        if (!messageEl) return;
        messageEl.classList.add('hidden');
        messageEl.textContent = '';
    }

    // --- Post type tabs ---
    function updateTypeVisibility() {
        const selectedType = typeInput?.value || 'text';

        // Tab active states
        typeTabs.forEach(tab => {
            const isActive = tab.dataset.postType === selectedType;
            tab.classList.toggle('active', isActive);
            tab.classList.toggle('text-primary', isActive);
            tab.classList.toggle('border-primary', isActive);
            tab.classList.toggle('text-on-surface-variant', !isActive);
            tab.classList.toggle('border-transparent', !isActive);
            tab.setAttribute('aria-pressed', String(isActive));
        });

        // Section visibility with fade transition
        const sections = [
            { el: contentWrap, show: true },
            { el: urlWrap, show: selectedType === 'link' },
            { el: imageWrap, show: selectedType === 'image' },
        ];

        sections.forEach(({ el, show }) => {
            if (!el) return;
            if (show) {
                el.classList.remove('hidden');
                // Trigger fade-in on next frame
                el.classList.add('section-enter');
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => el.classList.remove('section-enter'));
                });
            } else {
                el.classList.add('hidden');
            }
        });
    }

    typeTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            if (typeInput) typeInput.value = tab.dataset.postType;
            clearMessage();
            updateTypeVisibility();
        });
    });

    updateTypeVisibility();

    // --- Character counters ---
    function updateCounter(inputEl, counterEl, max) {
        if (!inputEl || !counterEl) return;
        const len = inputEl.value.length;
        counterEl.textContent = `${len} / ${max}`;
        counterEl.classList.toggle('text-error', len >= max);
        counterEl.classList.toggle('text-on-surface-variant', len < max);
    }

    titleInput?.addEventListener('input', () => updateCounter(titleInput, titleCounter, TITLE_MAX));
    contentInput?.addEventListener('input', () => updateCounter(contentInput, contentCounter, CONTENT_MAX));
    updateCounter(titleInput, titleCounter, TITLE_MAX);
    updateCounter(contentInput, contentCounter, CONTENT_MAX);

    // --- Image upload: preview, drag & drop, remove ---
    function showImagePreview(file) {
        selectedImageFile = file;

        if (previewObjectUrl) URL.revokeObjectURL(previewObjectUrl);
        previewObjectUrl = URL.createObjectURL(file);

        if (previewImg) previewImg.src = previewObjectUrl;
        if (imageSizeEl) imageSizeEl.textContent = formatBytes(file.size);

        // Read natural dimensions once loaded
        if (previewImg) {
            previewImg.onload = () => {
                if (imageDimensionsEl) {
                    imageDimensionsEl.textContent = `${previewImg.naturalWidth} × ${previewImg.naturalHeight}px`;
                }
            };
        }

        dropZone?.classList.add('hidden');
        previewContainer?.classList.remove('hidden');
        clearFieldError('post-image');
    }

    function clearImage() {
        selectedImageFile = null;
        if (previewObjectUrl) {
            URL.revokeObjectURL(previewObjectUrl);
            previewObjectUrl = null;
        }
        if (imageInput) imageInput.value = '';
        if (previewImg) previewImg.src = '';
        if (imageDimensionsEl) imageDimensionsEl.textContent = '';
        if (imageSizeEl) imageSizeEl.textContent = '';
        previewContainer?.classList.add('hidden');
        dropZone?.classList.remove('hidden');
    }

    function handleImageFile(file) {
        if (!file) return;

        if (!file.type.startsWith('image/')) {
            setFieldError('post-image', 'Please select a valid image file.');
            return;
        }

        if (file.size > MAX_IMAGE_SIZE) {
            setFieldError('post-image', `Image is too large (${formatBytes(file.size)}). Maximum size is 10MB.`);
            return;
        }

        showImagePreview(file);
    }

    // Click to browse
    dropZone?.addEventListener('click', () => imageInput?.click());

    // Keyboard accessibility
    dropZone?.setAttribute('tabindex', '0');
    dropZone?.setAttribute('role', 'button');
    dropZone?.setAttribute('aria-label', 'Upload image. Press Enter to browse files.');
    dropZone?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            imageInput?.click();
        }
    });

    imageInput?.addEventListener('change', () => {
        handleImageFile(imageInput.files?.[0]);
    });

    removeImageBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        clearImage();
    });

    // Drag & drop with visual feedback
    let dragCounter = 0;

    dropZone?.addEventListener('dragenter', (e) => {
        e.preventDefault();
        dragCounter++;
        dropZone.classList.add('drag-over');
    });

    dropZone?.addEventListener('dragover', (e) => {
        e.preventDefault();
    });

    dropZone?.addEventListener('dragleave', (e) => {
        e.preventDefault();
        dragCounter--;
        if (dragCounter <= 0) {
            dragCounter = 0;
            dropZone.classList.remove('drag-over');
        }
    });

    dropZone?.addEventListener('drop', (e) => {
        e.preventDefault();
        dragCounter = 0;
        dropZone.classList.remove('drag-over');
        const file = e.dataTransfer?.files?.[0];
        handleImageFile(file);
    });

    // --- Submit state helpers ---
    function setSubmitting(isSubmitting) {
        if (!submitBtn) return;
        submitBtn.disabled = isSubmitting;
        submitText.textContent = isSubmitting ? 'Publishing...' : 'Publish';
        submitIcon.classList.toggle('hidden', isSubmitting);
        submitSpinner.classList.toggle('hidden', !isSubmitting);
    }

    // --- Form submit ---
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearMessage();
        clearAllErrors();

        const title = titleInput?.value.trim();
        const postType = typeInput?.value || 'text';
        const content = contentInput?.value.trim();
        const url = urlInput?.value.trim();
        const imageFile = selectedImageFile;

        // Validation (mirrors StorePostRequest rules) — inline per field
        let hasError = false;

        if (!title) {
            setFieldError('post-title-input', 'Title is required.');
            hasError = true;
        } else if (title.length < TITLE_MIN) {
            setFieldError('post-title-input', `Title must be at least ${TITLE_MIN} characters.`);
            hasError = true;
        }

        if (postType === 'text' && !content) {
            setFieldError('post-content-input', 'Content is required for text posts.');
            hasError = true;
        }

        if (postType === 'link' && !url) {
            setFieldError('post-url', 'URL is required for link posts.');
            hasError = true;
        } else if (postType === 'link' && url && !url.match(/^https?:\/\/.+/)) {
            setFieldError('post-url', 'Please enter a valid URL starting with http:// or https://.');
            hasError = true;
        }

        if (postType === 'image' && !imageFile) {
            setFieldError('post-image', 'Please upload an image.');
            hasError = true;
        }

        if (hasError) {
            // Focus first errored field
            const firstError = form.querySelector('.border-error');
            firstError?.focus();
            return;
        }

        setSubmitting(true);

        try {
            let response;

            if (postType === 'image') {
                // Multipart upload — backend expects file via $request->file('image')
                const formData = new FormData();
                formData.append('title', title);
                formData.append('post_type', postType);
                formData.append('status', 'published');
                formData.append('image', imageFile);
                if (content) formData.append('content', content);

                response = await postsApi.create(formData, { formData: true });
            } else {
                const postData = {
                    title,
                    post_type: postType,
                    status: 'published',
                };

                if (postType === 'text') {
                    postData.content = content;
                } else if (postType === 'link') {
                    postData.url = url;
                    if (content) postData.content = content;
                }

                response = await postsApi.create(postData);
            }

            const postIdentifier = response.data?.post?.slug || response.data?.post?.id;

            showMessage('Post published! Redirecting...', false);

            setTimeout(() => {
                window.location.href = `/posts/${postIdentifier}`;
            }, 1000);
        } catch (err) {
            let message = err.payload?.message || err.message || 'Failed to create post. Please try again.';

            // Map server validation errors to inline fields where possible
            const serverErrors = err.payload?.errors;
            if (serverErrors) {
                const fieldMap = {
                    title: 'post-title-input',
                    url: 'post-url',
                    image: 'post-image',
                    content: 'post-content-input',
                };
                let mapped = false;
                Object.entries(serverErrors).forEach(([field, messages]) => {
                    const targetId = fieldMap[field];
                    if (targetId && Array.isArray(messages) && messages.length) {
                        setFieldError(targetId, messages[0]);
                        mapped = true;
                    }
                });
                if (!mapped) {
                    const details = Object.values(serverErrors).flat().join(' ');
                    if (details) message = details;
                }
            }

            showMessage(message, true);
            setSubmitting(false);
        }
    });

    // Cleanup object URL on page leave
    window.addEventListener('beforeunload', () => {
        if (previewObjectUrl) URL.revokeObjectURL(previewObjectUrl);
    });
}
