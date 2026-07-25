@props([
    'id' => 'product-file-upload',
    'wireProperty' => 'form.files',
    'label' => 'فایل‌ها',
    'maxFiles' => 20,
    'maxFileSizeMb' => 100,
])

@php
    $allowedExtensionsLabel = implode(', ', ['jpg', 'jpeg', 'png', 'fbx', 'gltf', 'glb', 'bin']);
@endphp

{{--
    wire:ignore: Livewire must not remorph this third-party/JS-managed UI.
    @see https://livewire.laravel.com/docs/4.x/wire-ignore
--}}
<div class="flex flex-col gap-3">
    <div wire:ignore data-file-upload-root="{{ $id }}">
        <label class="form-col-label col-sm-4">{{ $label }}</label>

        <button type="button" id="{{ $id }}-open"
            class="form-control w-full bg-[#F8F9FA] dark:bg-[#4A4E7C] rounded-[10px] p-4 border-0 text-right cursor-pointer">
            انتخاب و آپلود فایل‌ها
        </button>

        <div id="{{ $id }}-summary" class="flex flex-col gap-2 mt-3"></div>

        <div id="{{ $id }}-modal"
            class="hidden fixed inset-0 z-[90] flex items-center justify-center bg-black/50 p-4">
            <div
                class="bg-white dark:bg-[#001448] rounded-xl shadow-lg w-full max-w-2xl max-h-[90vh] flex flex-col pointer-events-auto">
                <div class="flex justify-between items-center p-5 border-b dark:border-gray-700">
                    <h6 class="font-bold text-gray-800 dark:text-white">آپلود فایل‌ها</h6>
                    <button type="button" id="{{ $id }}-close"
                        class="flex justify-center items-center size-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                        <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18" />
                            <path d="m6 6 12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-5 overflow-y-auto flex flex-col gap-4 flex-1">
                    <p class="text-sm text-gray-500 dark:text-gray-300">
                        حداکثر {{ $maxFiles }} فایل، هر فایل تا {{ $maxFileSizeMb }} مگابایت.
                        پسوندهای مجاز: {{ $allowedExtensionsLabel }}
                    </p>

                    <div id="{{ $id }}-dropzone"
                        class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-8 text-center cursor-pointer hover:border-[#06CC85] transition-colors">
                        <p class="text-gray-600 dark:text-gray-300 mb-3">فایل‌ها را اینجا رها کنید</p>
                        <button type="button" id="{{ $id }}-browse"
                            class="inline-flex items-center justify-center px-4 py-2 rounded-[10px] bg-[#06CC85] text-white font-bold">
                            انتخاب از سیستم
                        </button>
                    </div>

                    <div id="{{ $id }}-error" class="hidden text-red-600 text-sm"></div>

                    <div id="{{ $id }}-list" class="flex flex-col gap-3"></div>
                </div>

                <div class="flex justify-end items-center gap-x-2 p-5 border-t dark:border-gray-700">
                    <button type="button" id="{{ $id }}-done"
                        class="px-4 py-2 rounded-[10px] bg-[#06CC85] text-white font-bold disabled:opacity-50">
                        تأیید
                    </button>
                </div>
            </div>
        </div>
    </div>

    @error($wireProperty)
        <span
            style="color:red;padding:14px;background-color:rgba(207, 117, 117, 0.47);border-radius:10px">{{ $message }}</span>
    @enderror
</div>

<script>
    (function () {
        const id = @json($id);
        const wireProperty = @json($wireProperty);
        const maxFiles = @json($maxFiles);
        const maxFileSizeMb = @json($maxFileSizeMb);
        const allowedExtensions = ['jpg', 'jpeg', 'png', 'fbx', 'gltf', 'glb', 'bin'];
        const maxFileSizeBytes = maxFileSizeMb * 1024 * 1024;
        const csrfToken = @json(csrf_token());

        function initProductFileUpload() {
            if (typeof Resumable === 'undefined') {
                return;
            }

            const root = document.querySelector('[data-file-upload-root="' + id + '"]');
            const modal = document.getElementById(id + '-modal');
            const openBtn = document.getElementById(id + '-open');
            const closeBtn = document.getElementById(id + '-close');
            const doneBtn = document.getElementById(id + '-done');
            const browseBtn = document.getElementById(id + '-browse');
            const dropzone = document.getElementById(id + '-dropzone');
            const listEl = document.getElementById(id + '-list');
            const summaryEl = document.getElementById(id + '-summary');
            const errorEl = document.getElementById(id + '-error');

            if (!root || !modal || !openBtn || openBtn.dataset.initialized === '1') {
                return;
            }
            openBtn.dataset.initialized = '1';

            function getWire() {
                const wireEl = root.closest('[wire\\:id]');
                if (!wireEl || typeof Livewire === 'undefined') {
                    return null;
                }
                return Livewire.find(wireEl.getAttribute('wire:id'));
            }

            let uploadedFiles = [];
            let uploadingCount = 0;

            function showError(message) {
                errorEl.textContent = message;
                errorEl.classList.remove('hidden');
            }

            function clearError() {
                errorEl.textContent = '';
                errorEl.classList.add('hidden');
            }

            function isUploading() {
                return uploadingCount > 0;
            }

            function setCloseEnabled(enabled) {
                closeBtn.disabled = !enabled;
                doneBtn.disabled = !enabled;
                closeBtn.classList.toggle('opacity-50', !enabled);
                doneBtn.classList.toggle('opacity-50', !enabled);
            }

            /**
             * Sync files to Livewire without a live remorph while the modal is open.
             * $set(name, value, live=false) updates client state only until the next request.
             * @see https://livewire.laravel.com/docs/4.x/javascript
             */
            function syncToLivewire(live) {
                const wire = getWire();
                if (!wire) {
                    return;
                }

                const payload = uploadedFiles.filter(function (item) {
                    return item.status === 'done' && item.response;
                }).map(function (item) {
                    return item.response;
                });

                if (typeof wire.$set === 'function') {
                    wire.$set(wireProperty, payload, live === true);
                } else {
                    wire.set(wireProperty, payload, live === true);
                }

                renderSummary();
                renderList();
            }

            function renderSummary() {
                const doneFiles = uploadedFiles.filter(function (item) {
                    return item.status === 'done' && item.response;
                });

                if (!doneFiles.length) {
                    summaryEl.innerHTML = '';
                    openBtn.textContent = 'انتخاب و آپلود فایل‌ها';
                    return;
                }

                openBtn.textContent = doneFiles.length + ' فایل انتخاب شده — افزودن یا ویرایش';
                summaryEl.innerHTML = doneFiles.map(function (item) {
                    const index = uploadedFiles.indexOf(item);
                    return '<div class="flex justify-between items-center gap-3 bg-[#F8F9FA] dark:bg-[#4A4E7C] rounded-[10px] p-3">' +
                        '<span class="text-sm break-all">' + item.response.name + ' (' + item.response.size + ')</span>' +
                        '<button type="button" data-remove-summary="' + index + '" class="text-red-500 text-sm font-bold">حذف</button>' +
                        '</div>';
                }).join('');

                summaryEl.querySelectorAll('[data-remove-summary]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        removeFileAt(parseInt(btn.getAttribute('data-remove-summary'), 10));
                    });
                });
            }

            function renderList() {
                listEl.innerHTML = uploadedFiles.map(function (item, index) {
                    const progressHtml = item.status === 'uploading'
                        ? '<div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-2"><div class="bg-[#06CC85] h-2 rounded-full" style="width:' + item.progress + '%"></div></div><p class="text-xs mt-1">' + item.progress + '%</p>'
                        : (item.status === 'done'
                            ? '<p class="text-xs text-green-600 mt-1">آپلود شد (' + item.response.size + ')</p>'
                            : '<p class="text-xs text-red-600 mt-1">خطا در آپلود</p>');

                    return '<div class="border border-gray-200 dark:border-gray-600 rounded-[10px] p-3">' +
                        '<div class="flex justify-between items-start gap-3">' +
                        '<p class="text-sm font-bold break-all">' + item.name + '</p>' +
                        (item.status !== 'uploading'
                            ? '<button type="button" data-remove-list="' + index + '" class="text-red-500 text-sm font-bold">حذف</button>'
                            : '') +
                        '</div>' + progressHtml +
                        '</div>';
                }).join('');

                listEl.querySelectorAll('[data-remove-list]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        removeFileAt(parseInt(btn.getAttribute('data-remove-list'), 10));
                    });
                });
            }

            function removeFileAt(index) {
                const item = uploadedFiles[index];
                if (!item || item.status === 'uploading') {
                    return;
                }

                if (item.response && item.response.path && item.response.name) {
                    const wire = getWire();
                    if (wire) {
                        wire.call('discardTempUpload', item.response.path, item.response.name);
                    }
                }

                uploadedFiles.splice(index, 1);
                syncToLivewire(false);
            }

            function openModal() {
                clearError();
                resetSummernoteDropzone();
                modal.classList.remove('hidden');
                renderList();
            }

            function closeModal() {
                if (isUploading()) {
                    showError('تا پایان آپلود همه فایل‌ها صبر کنید.');
                    return;
                }
                modal.classList.add('hidden');
                resetSummernoteDropzone();
                syncToLivewire(false);
            }

            /**
             * Summernote listens for document-level drag events and shows its dropzone overlay.
             * Dragging/dropping onto this modal never reaches Summernote's drop/dragleave handlers,
             * so the overlay can get stuck. Contain drag events here and force-hide the overlay.
             */
            function resetSummernoteDropzone() {
                document.querySelectorAll('.note-dropzone').forEach(function (el) {
                    el.classList.remove('hover');
                    el.style.display = 'none';
                });
            }

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function (eventName) {
                modal.addEventListener(eventName, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    resetSummernoteDropzone();
                });
            });

            const resumable = new Resumable({
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                target: '/upload',
                chunkSize: 1 * 1024 * 1024,
                simultaneousUploads: 3,
                testChunks: false,
                throttleProgressCallbacks: 1,
                fileType: allowedExtensions,
            });

            resumable.assignBrowse(browseBtn);
            resumable.assignDrop(dropzone);

            dropzone.addEventListener('click', function (e) {
                if (e.target === browseBtn || browseBtn.contains(e.target)) {
                    return;
                }
                browseBtn.click();
            });

            resumable.on('fileAdded', function (file) {
                clearError();
                resetSummernoteDropzone();

                const extension = (file.fileName.split('.').pop() || '').toLowerCase();
                if (!allowedExtensions.includes(extension)) {
                    resumable.removeFile(file);
                    showError('پسوند فایل مجاز نیست: ' + extension);
                    return;
                }

                if (file.size > maxFileSizeBytes) {
                    resumable.removeFile(file);
                    showError('حجم فایل نباید بیشتر از ' + maxFileSizeMb + ' مگابایت باشد.');
                    return;
                }

                const doneOrUploading = uploadedFiles.filter(function (item) {
                    return item.status === 'done' || item.status === 'uploading';
                }).length;

                if (doneOrUploading >= maxFiles) {
                    resumable.removeFile(file);
                    showError('حداکثر ' + maxFiles + ' فایل در هر آپلود مجاز است.');
                    return;
                }

                uploadedFiles.push({
                    uid: file.uniqueIdentifier,
                    name: file.fileName,
                    progress: 0,
                    status: 'uploading',
                    response: null,
                });
                uploadingCount++;
                setCloseEnabled(false);
                renderList();
                resumable.upload();
            });

            resumable.on('fileProgress', function (file) {
                const item = uploadedFiles.find(function (entry) {
                    return entry.uid === file.uniqueIdentifier;
                });
                if (!item) {
                    return;
                }
                item.progress = Math.floor(file.progress() * 100);
                renderList();
            });

            resumable.on('fileSuccess', function (file, message) {
                const item = uploadedFiles.find(function (entry) {
                    return entry.uid === file.uniqueIdentifier;
                });
                if (item) {
                    item.status = 'done';
                    item.progress = 100;
                    item.response = JSON.parse(message);
                }
                uploadingCount = Math.max(0, uploadingCount - 1);
                resumable.removeFile(file);
                if (!isUploading()) {
                    setCloseEnabled(true);
                    syncToLivewire(false);
                } else {
                    renderList();
                }
            });

            resumable.on('fileError', function (file, message) {
                const item = uploadedFiles.find(function (entry) {
                    return entry.uid === file.uniqueIdentifier;
                });
                if (item) {
                    item.status = 'error';
                }
                uploadingCount = Math.max(0, uploadingCount - 1);
                resumable.removeFile(file);
                showError('خطا در آپلود فایل: ' + (message || file.fileName));
                if (!isUploading()) {
                    setCloseEnabled(true);
                }
                renderList();
            });

            openBtn.addEventListener('click', openModal);
            closeBtn.addEventListener('click', closeModal);
            doneBtn.addEventListener('click', closeModal);

            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeModal();
                }
            });

            if (typeof Livewire !== 'undefined') {
                Livewire.on('product-files-cleared', function () {
                    uploadedFiles = [];
                    uploadingCount = 0;
                    setCloseEnabled(true);
                    clearError();
                    renderSummary();
                    renderList();
                    modal.classList.add('hidden');
                });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initProductFileUpload);
        } else {
            initProductFileUpload();
        }

        document.addEventListener('livewire:navigated', initProductFileUpload);
    })();
</script>
