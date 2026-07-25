<div>
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('assets/vendor_assets/css/select2.min.css') }}">
    @endpush
    <x-page title="ایجاد محصول">

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

                <x-form.text wire:model="form.sku" name="form.sku" label="شناسه" />
                <x-form.text wire:model="form.name" name="form.name" label="نام" />
                <x-form.text wire:model="form.slug" name="form.slug" label="نامک" />
                <x-form.text wire:model="form.price" name="form.price" label="قیمت عادی" />
                <x-form.text wire:model="form.sale_price" name="form.sale_price" label="قیمت فروش ویژه" />

                <div class="flex flex-col gap-3">
                    <x-form.file wire:model="form.images" name="form.images" label="تصاویر" multiple />
                    @foreach (collect($errors->get('form.images.*'))->flatten() as $message)
                        <span
                            style="color:red;padding:14px;background-color:rgba(207, 117, 117, 0.47);border-radius:10px">{{ $message }}</span>
                    @endforeach
                </div>

                <div class="flex flex-col gap-3">
                    <x-file-upload-modal id="create-product-files" wire-property="form.files" label="فایل‌ها" />
                </div>

                <div class="flex flex-col gap-5">
                    <div class="flex gap-5 items-center">
                        <input type="checkbox" class="w-5 h-5" id="showStockInputs">
                        <label for="showStockInputs" class="flex flex-col gap-5">محصول برای متارنگ است؟</label>
                    </div>

                    <div id="stockInputs" class="flex flex-col gap-5 hidden">
                        <x-form.select wire:model="form.stock_status" name="form.stock_status" label="وضعیت انبار">
                            <option value="1" selected>موجود</option>
                            <option value="0">ناموجود</option>
                        </x-form.select>

                        <x-form.text wire:model="form.quantity" name="form.quantity" label="تعداد موجود در انبار" />
                        <x-form.text wire:model="form.delivery_time" name="form.delivery_time" label="مدت زمان تحویل" />
                    </div>
                </div>

                <div class="mt-10 mb-10 flex flex-col gap-4 w-full" wire:ignore>
                    <label for="tags" class="flex flex-col gap-5">برچسب ها</label>
                    <div class="flex flex-col gap-5 w-full">
                        <select name="tags" id="select-tag"
                            class="bg-[#F8F9FA] dark:bg-[#4A4E7C] rounded-[10px] p-4 space-y-2 w-full"
                            style="width: 100%;" label="برچسب ها" multiple="multiple">
                            <option value="">انتخاب برچسب ها</option>
                            @foreach ($tags as $tag)
                                <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @error('form.tags')
                    <span
                        style="color:red;padding:14px;background-color:rgba(207, 117, 117, 0.47);border-radius:10px">{{ $message }}</span>
                @enderror
                @foreach (collect($errors->get('form.tags.*'))->flatten() as $message)
                    <span
                        style="color:red;padding:14px;background-color:rgba(207, 117, 117, 0.47);border-radius:10px">{{ $message }}</span>
                @endforeach

                <x-form.select wire:model="form.customer_can_add_review" name="form.customer_can_add_review"
                    label="مشتری می تواند دیدگاه بنویسد؟">
                    <option value="1">بله</option>
                    <option value="0">خیر</option>
                </x-form.select>

                <x-form.select wire:model="form.published" name="form.published" label="محصول انتشار داده شود؟">
                    <option value="0">خیر</option>
                    <option value="1">بله</option>
                </x-form.select>

            </div>
        </div>

        <hr>

        <h4 class="mb-5 mt-5">ویژگی ها</h4>

        @php $attributeIndex = 0; @endphp
        @forelse ($productAttributes->chunk(2) as $items)
            <div class="grid lg:grid-cols-2  gap-7 mt-5  " id="stockInputs">
                @foreach ($items as $item)
                    <div class="w-full flex flex-col gap-7">
                        <div id="attribute-box-{{ $item->id }}" wire:key="{{ $item->id }}">
                            <div class="flex flex-col gap-5">
                                <label for="attribute-{{ $item->id }}">{{ $item->name }}</label>
                                <div class="w-full flex flex-col gap-5">
                                    <input type="text"
                                        class="w-full bg-[#F8F9FA] dark:bg-[#4A4E7C] rounded-[10px] p-4 @error('form.attributes.' . $attributeIndex . '.value') is-invalid @enderror"
                                        id="attribute-{{ $item->id }}">
                                    @error('form.attributes.' . $attributeIndex . '.value')
                                        <span
                                            style="color:red;padding:14px;background-color:rgba(207, 117, 117, 0.47);border-radius:10px">{{ $message }}</span>
                                    @enderror
                                    @error('form.attributes.' . $attributeIndex . '.id')
                                        <span
                                            style="color:red;padding:14px;background-color:rgba(207, 117, 117, 0.47);border-radius:10px">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    @php $attributeIndex++; @endphp
                @endforeach
            </div>
        @empty
            <x-alert type="warning" message="ویژگی ای برای این دسته بندی ثبت نشده است." />
        @endforelse

        @error('form.attributes')
            <span
                style="color:red;padding:14px;background-color:rgba(207, 117, 117, 0.47);border-radius:10px">{{ $message }}</span>
        @enderror

        <hr class="mt-5 mb-5" style="margin-bottom:20px">

        <div>
            <div class="flex flex-col gap-5 mt-5">

                <div class="flex flex-col gap-5">
                    <label for="short_desciption">توضیحات کوتاه</label>
                    <textarea wire:model="form.short_description" name="form.short_description"
                        class="form-control @error('form.short_description') is-invalid @enderror  w-full text-gray-400 py-3 rounded-[10px] border-2 border-gray-300 ring-offset-0 focus:ring-offset-0 ring-0 !focus:ring-0 bg-transparent"
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
                        class="form-control @error('form.meta_keywords') is-invalid @enderror  w-full text-gray-400 py-3 rounded-[10px] border-2 border-gray-300 ring-offset-0 focus:ring-offset-0 ring-0 !focus:ring-0 bg-transparent"
                        id="meta_keywords" rows="3"></textarea>
                    @error('form.meta_keywords')
                        <span
                            style="color:red;padding:14px;background-color:rgba(207, 117, 117, 0.47);border-radius:10px">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <div class="mt-5 flex flex-col gap-5" wire:ignore>
            <label for="summernote2">توضیحات محصول</label>
            <div id="summernote2" class="dark:text-gray-300"></div>
        </div>
        @error('form.long_description')
            <span
                style="color:red;padding:14px;background-color:rgba(207, 117, 117, 0.47);border-radius:10px">{{ $message }}</span>
        @enderror

        <x-button style="margin-top:50px" type="submit" id="save-btn">ذخیره</x-button>

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

        Livewire.hook('morph.updated', ({ component }) => {
            if (component.id !== $wire.$id) {
                return;
            }
            ensureJsWidgets();
        });

        const saveBtn = document.getElementById('save-btn');
        const showStockInputs = document.getElementById('showStockInputs');
        const stockInputs = document.getElementById('stockInputs');

        if (showStockInputs && stockInputs) {
            showStockInputs.addEventListener('click', function() {
                if (showStockInputs.checked) {
                    stockInputs.classList.remove('hidden');
                } else {
                    stockInputs.classList.add('hidden');
                }
            });
        }

        saveBtn.addEventListener('click', async function() {
            saveBtn.classList.add('disabled');
            saveBtn.innerText = 'در حال ذخیره سازی ...';

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

                $wire.$set('form.long_description', longDescription, false);
                $wire.$set('form.tags', selectedTags, false);
                $wire.$set('form.attributes', attributes, false);

                await $wire.call('save');
                ensureJsWidgets();
            } finally {
                saveBtn.classList.remove('disabled');
                saveBtn.innerText = 'ذخیره';
            }
        });
    </script>
@endscript
