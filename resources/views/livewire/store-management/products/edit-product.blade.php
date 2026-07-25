<div>
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('assets/vendor_assets/css/select2.min.css') }}">
        <style>
            [x-cloak] {
                display: none !important;
            }
        </style>
    @endpush

    <x-page title="ویرایش محصول">

        @session('success')
            <x-alert type="success" message="{{ session('success') }}" />
        @endsession

        <div class="flex flex-col gap-10">
            <div class="grid lg:grid-cols-2 gap-7">

                <x-form.select wire:model="form.category_id" name="form.category_id" label="دسته بندی">
                    <option value="">انتخاب دسته بندی</option>
                    @php
                        $parentCategories = $categories->reject(function ($category) {
                            return $category->parent_id != null;
                        });
                    @endphp

                    @foreach ($parentCategories as $category)
                        <optgroup label="{{ $category->name }}">
                            @foreach ($category->children as $child)
                                <x-partials.category-option :category="$child" :level="1" />
                            @endforeach
                        </optgroup>
                    @endforeach
                </x-form.select>

                <x-form.text wire:model="form.sku" name="form.sku" label="شناسه محصول" />
                <x-form.text wire:model="form.name" name="form.name" label="نام" />
                <x-form.text wire:model="form.slug" name="form.slug" label="نامک" />
                <x-form.text wire:model="form.price" name="form.price" label="قیمت عادی" />
                <x-form.text wire:model="form.sale_price" name="form.sale_price" label="قیمت فروش ویژه" />

                {{-- Alpine owns visibility; Livewire owns field values. Avoid class="hidden" + style.display conflicts. --}}
                <div class="flex flex-col gap-5"
                    x-data="{ open: {{ $form->stock_status || $form->quantity > 0 ? 'true' : 'false' }} }">
                    <div class="flex gap-5 items-center">
                        <input type="checkbox" class="w-5 h-5" id="showStockInputs" x-model="open">
                        <label for="showStockInputs" class="flex flex-col gap-5">محصول برای متارنگ است؟</label>
                    </div>

                    <div id="stockInputs" class="flex flex-col gap-5" x-show="open" x-cloak>
                        <x-form.select wire:model="form.stock_status" name="form.stock_status" label="وضعیت انبار">
                            <option value="1">موجود</option>
                            <option value="0">ناموجود</option>
                        </x-form.select>

                        <x-form.text wire:model="form.quantity" name="form.quantity" label="تعداد موجود در انبار" />
                        <x-form.text wire:model="form.delivery_time" name="form.delivery_time" label="مدت زمان تحویل" />
                    </div>
                </div>

            </div>

            <div class="grid lg:grid-cols-2 gap-7">

                <x-form.select wire:model="form.customer_can_add_review" name="form.customer_can_add_review"
                    label="مشتری می تواند دیدگاه بنویسد؟">
                    <option value="1">بله</option>
                    <option value="0">خیر</option>
                </x-form.select>

                <x-form.select wire:model="form.published" name="form.published" label="محصول انتشار داده شود؟">
                    <option value="0">خیر</option>
                    <option value="1">بله</option>
                </x-form.select>

                <div>
                    <x-form.file wire:model="form.images" name="form.images" label="تصاویر محصول" multiple />
                    <div class="grid md:grid-cols-2 2xl:grid-cols-4 gap-5 p-2">
                        @foreach ($this->form->product->images as $image)
                            <div
                                class="w-full bg-[#F8F9FA] dark:bg-[#4A4E7C] aspect-square rounded-lg overflow-hidden border relative"
                                wire:key="product-image-{{ $image->id }}">
                                <img src="{{ $image->url }}" alt="" class="relative">
                                <div class="absolute z-50 w-[60%] flex gap-1" style="top: 4px;right: 4px;">
                                    <button type="button"
                                        class="rounded-full w-1/2 flex items-center justify-center"
                                        style="background-color: red"
                                        wire:click="removeImage({{ $image->id }})"
                                        wire:confirm="آیا از حذف این تصویر مطمئن هستید؟">
                                        <div class="flex justify-center items-center w-full">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="currentColor" class="size-6">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex flex-col gap-7">
                    <div class="flex flex-col gap-3">
                        <x-file-upload-modal id="edit-product-files" wire-property="form.files"
                            label="افزودن فایل‌های جدید" />
                    </div>

                    <div class="flex flex-col gap-3">
                        <label class="form-col-label col-sm-4">فایل‌های موجود</label>
                        <div class="flex flex-col gap-2">
                            @forelse ($form->getProduct()->files as $file)
                                <div class="flex justify-between items-center gap-3 bg-[#F8F9FA] dark:bg-[#4A4E7C] rounded-[10px] p-3"
                                    wire:key="product-file-{{ $file->id }}">
                                    <span class="text-sm break-all">{{ $file->name }}
                                        @if ($file->size)
                                            ({{ $file->size }})
                                        @endif
                                    </span>
                                    <button type="button" wire:click="removeFile({{ $file->id }})"
                                        wire:confirm="آیا از حذف این فایل مطمئن هستید؟"
                                        class="text-red-500 text-sm font-bold">حذف</button>
                                </div>
                            @empty
                                <span class="text-sm text-gray-500">فایلی ثبت نشده است.</span>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="mt-10 mb-10 flex flex-col gap-4 w-full" wire:ignore>
                    <label for="select-tag" class="flex flex-col gap-5">برچسب ها</label>
                    <div class="w-full">
                        <select name="tags" id="select-tag"
                            class="bg-[#F8F9FA] dark:bg-[#4A4E7C] rounded-[10px] p-4 space-y-2 w-full"
                            style="width: 100%;" multiple="multiple">
                            <option value="">انتخاب برچسب ها</option>
                            @foreach ($tags as $tag)
                                <option value="{{ $tag->id }}"
                                    @selected(in_array($tag->id, $form->product->tags->pluck('id')->toArray()))>
                                    {{ $tag->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @error('form.tags')
                    <span
                        style="color:red;padding:14px;background-color:rgba(207, 117, 117, 0.47);border-radius:10px">{{ $message }}</span>
                @enderror

            </div>
        </div>

        <hr>

        <h4 class="mb-5 mt-5">ویژگی ها</h4>

        {{-- Plain inputs are JS-collected on save; remorph after removeImage/removeFile would wipe edits. --}}
        <div wire:ignore>
            @foreach ($productAttributes->chunk(2) as $items)
                <div class="grid lg:grid-cols-2 gap-7 mt-5">
                    @foreach ($items as $item)
                        <div class="w-full flex flex-col gap-7">
                            <div id="attribute-box-{{ $item->id }}">
                                <div class="flex flex-col gap-5">
                                    <label for="attribute-{{ $item->id }}"
                                        class="col-sm-4 form-col-label">{{ $item->name }}</label>
                                    <div class="col-sm-8">
                                        <input type="text"
                                            class="w-full bg-[#F8F9FA] dark:bg-[#4A4E7C] rounded-[10px] p-4"
                                            id="attribute-{{ $item->id }}"
                                            value="{{ $form->product->attributes->contains($item)
                                                ? $form->product->attributes->where('id', $item->id)->first()->pivot->value
                                                : '' }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>

        <hr>

        <div>
            <div class="flex flex-col gap-5 mt-5">

                <div class="flex flex-col gap-5">
                    <label for="short_desciption">توضیحات کوتاه</label>
                    <textarea wire:model="form.short_description" name="form.short_description"
                        class="form-control @error('form.short_description') is-invalid @enderror w-full text-gray-400 py-3 rounded-[10px] border-2 border-gray-300 ring-offset-0 focus:ring-offset-0 ring-0 !focus:ring-0 bg-transparent"
                        id="short_desciption" rows="3"></textarea>
                    @error('form.short_description')
                        <span
                            style="color:red;padding:14px;background-color:rgba(207, 117, 117, 0.47);border-radius:10px">{{ $message }}</span>
                    @enderror
                </div>
                <div class="flex flex-col gap-5">
                    <label for="meta_desciption">توضیحات متا</label>
                    <textarea wire:model="form.meta_description" name="form.meta_description"
                        class="form-control @error('form.meta_description') is-invalid @enderror w-full text-gray-400 py-3 rounded-[10px] border-2 border-gray-300 ring-offset-0 focus:ring-offset-0 ring-0 !focus:ring-0 bg-transparent"
                        id="meta_desciption" rows="3"></textarea>
                    @error('form.meta_description')
                        <span
                            style="color:red;padding:14px;background-color:rgba(207, 117, 117, 0.47);border-radius:10px">{{ $message }}</span>
                    @enderror
                </div>

                <div class="flex flex-col gap-5">
                    <label for="meta_keywords">کلمات کلیدی متا</label>
                    <textarea wire:model="form.meta_keywords" name="form.meta_keywords"
                        class="form-control @error('form.meta_keywords') is-invalid @enderror w-full text-gray-400 py-3 rounded-[10px] border-2 border-gray-300 ring-offset-0 focus:ring-offset-0 ring-0 !focus:ring-0 bg-transparent"
                        id="meta_keywords" rows="3"></textarea>
                    @error('form.meta_keywords')
                        <span
                            style="color:red;padding:14px;background-color:rgba(207, 117, 117, 0.47);border-radius:10px">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <div class="mt-5 flex flex-col gap-5" wire:ignore>
            <label for="summernote2">توضیحات کامل</label>
            <div id="summernote2" class="dark:text-gray-300"></div>
        </div>
        @error('form.long_description')
            <span
                style="color:red;padding:14px;background-color:rgba(207, 117, 117, 0.47);border-radius:10px">{{ $message }}</span>
        @enderror

        <x-button type="submit" id="update-btn" style="margin-top:50px">بروزرسانی</x-button>

    </x-page>

</div>

@script
    <script>
        let tags = null;

        function ensureSummernote() {
            const $el = $('#summernote2');
            if (!$el.length) {
                return;
            }

            if (!$el.next('.note-editor').length) {
                $el.summernote({
                    height: 300,
                    disableDragAndDrop: true,
                });
                $el.summernote('code', $wire.form.long_description || '');
            }
        }

        function ensureSelect2() {
            const $el = $('#select-tag');
            if (!$el.length) {
                return;
            }

            if (!$el.hasClass('select2-hidden-accessible')) {
                $el.select2({
                    placeholder: 'انتخاب برچسب ها',
                    allowClear: true,
                    width: '100%',
                });
            }

            $el.off('change.select2-sync select2:unselect.select2-sync')
                .on('change.select2-sync select2:unselect.select2-sync', function() {
                    tags = $el.select2('val');
                });
        }

        function ensureJsWidgets() {
            ensureSummernote();
            ensureSelect2();
        }

        ensureJsWidgets();

        // After Livewire remorphs (save / validation), restore JS widgets if DOM was replaced.
        Livewire.hook('morph.updated', ({ component }) => {
            if (component.id !== $wire.$id) {
                return;
            }
            ensureJsWidgets();
        });

        const updateBtn = document.getElementById('update-btn');

        updateBtn.addEventListener('click', async function() {
            updateBtn.classList.add('disabled');
            updateBtn.innerText = 'در حال ذخیره سازی ...';

            try {
                ensureJsWidgets();

                const longDescription = $('#summernote2').summernote('code');
                const selectedTags = tags || $('#select-tag').select2('val') || [];

                const attributes = [];
                document.querySelectorAll('[id^="attribute-box-"]').forEach(function(box) {
                    const attributeId = box.id.split('-')[2];
                    const attributeInput = document.getElementById('attribute-' + attributeId);
                    const attributeValue = attributeInput ? attributeInput.value : '';

                    if (attributeValue) {
                        attributes.push({
                            id: attributeId,
                            name: box.querySelector('label').innerText,
                            value: attributeValue,
                        });
                    }
                });

                // Defer sync so intermediate remorphs don't destroy Select2/Summernote before save.
                // @see https://livewire.laravel.com/docs/4.x/javascript — $set(name, value, live=false)
                $wire.$set('form.long_description', longDescription, false);
                $wire.$set('form.tags', selectedTags, false);
                $wire.$set('form.attributes', attributes, false);

                await $wire.call('update');
                ensureJsWidgets();
            } finally {
                updateBtn.classList.remove('disabled');
                updateBtn.innerText = 'بروزرسانی';
            }
        });
    </script>
@endscript
