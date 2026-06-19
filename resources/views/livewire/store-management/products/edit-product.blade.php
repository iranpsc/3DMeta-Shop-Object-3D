<div>
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
                    <div class="grid md:grid-cols-2 2xl:grid-cols-4 gap-5 p-2  ">
                        @foreach ($this->form->product->images as $image)
                            <div
                                class=" w-full bg-[#F8F9FA] dark:bg-[#4A4E7C] aspect-square rounded-lg overflow-hidden border relative">
                                <img src="{{ $image->url }}" alt=""
                                    class="relative">
                                <div class="absolute  z-50 w-[60%] flex gap-1" style="top: 4px;right: 4px;">
                                    <button class="rounded-full  w-1/2 flex items-center justify-center"
                                        style="background-color: red" wire:click="removeImage({{ $image->id }})">
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

                <div class="flex flex-col gap-3 ">
                    <label for="fbx_file" class="form-col-label col-sm-4">فایل FBX</label>
                    <div class="flex flex-col gap-5">
                        <span class="form-control w-full  bg-[#F8F9FA] dark:bg-[#4A4E7C] rounded-[10px] p-4 border-0"
                            id="fbx_file" wire:ignore>انتخاب فایل</span>
                        @error('form.fbx_file')
                            <span
                                style="color:red;padding:14px;background-color:rgba(207, 117, 117, 0.47);border-radius:10px">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="mt-10 mb-10 flex flex-col gap-4">
                    <label for="tags" class="flex flex-col gap-5">برچسب ها</label>
                    <div class="col-sm-8">
                        <div wire:ignore>
                            <select name="tags" id="select-tag"
                                class="bg-[#F8F9FA] dark:bg-[#4A4E7C] rounded-[10px] p-4 space-y-2 " label="برچسب ها"
                                multiple="multiple">
                                <option value="">انتخاب برچسب ها</option>
                                @foreach ($tags as $tag)
                                    <option value="{{ $tag->id }}" @selected(in_array($tag->id, $form->product->tags->pluck('id')->toArray()))>{{ $tag->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @error('form.tags')
                            <span
                                style="color:red;padding:14px;background-color:rgba(207, 117, 117, 0.47);border-radius:10px">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

            </div>
        </div>

        <hr>

        <h4 class="mb-5 mt-5">ویژگی ها</h4>

        @foreach ($productAttributes->chunk(2) as $items)
            <div class="grid lg:grid-cols-2  gap-7 mt-5  ">
                @foreach ($items as $item)
                    <div class="w-full flex flex-col gap-7">
                        <div id="attribute-box-{{ $item->id }}" wire:key="{{ $item->id }}">
                            <div class="flex flex-col gap-5">
                                <label for="attribute-{{ $item->id }}"
                                    class="col-sm-4 form-col-label">{{ $item->name }}</label>
                                <div class="col-sm-8">
                                    <input type="text"
                                        class="w-full bg-[#F8F9FA] dark:bg-[#4A4E7C] rounded-[10px] p-4 "
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

        <hr>

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
                        class="form-control @error('form.short_description') is-invalid @enderror  w-full text-gray-400 py-3 rounded-[10px] border-2 border-gray-300 ring-offset-0 focus:ring-offset-0 ring-0 !focus:ring-0 bg-transparent"
                        id="meta_desciption" rows="3"></textarea>
                    @error('form.meta_description')
                        <span
                            style="color:red;padding:14px;background-color:rgba(207, 117, 117, 0.47);border-radius:10px">{{ $message }}</span>
                    @enderror
                </div>

                <div class="flex flex-col gap-5">
                    <label for="meta_keywords">کلمات کلیدی متا</label>
                    <textarea wire:model="form.meta_keywords" name="form.meta_keywords"
                        class="form-control @error('form.short_description') is-invalid @enderror  w-full text-gray-400 py-3 rounded-[10px] border-2 border-gray-300 ring-offset-0 focus:ring-offset-0 ring-0 !focus:ring-0 bg-transparent"
                        id="meta_keywords" rows="3"></textarea>
                    @error('form.meta_keywords')
                        <span
                            style="color:red;padding:14px;background-color:rgba(207, 117, 117, 0.47);border-radius:10px">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-5" wire:ignore>
            <label for="summernote2">توضیحات کامل</label>
            <div id="summernote2"></div>
        </div>

        <x-button type="submit" id="update-btn" style="margin-top:50px">بروزرسانی</x-button>

    </x-page>

</div>

@script
    <script>
        let updateBtn = document.getElementById('update-btn');
        let tags;
        let attributes = [];
        let fbxFileBrowse = document.getElementById('fbx_file');
        let fbxFile;

        $('#summernote2').summernote('code', $wire.form.long_description);

        $('#select-tag').select2({
            placeholder: 'انتخاب برچسب ها',
            allowClear: true
        });

        $('#select-tag').on('change', function(e) {
            var data = $('#select-tag').select2("val");
            tags = data;
        });

        $('#select-tag').on('select2:unselect', function(e) {
            var data = $('#select-tag').select2("val");
            tags = data;
        });

        updateBtn.addEventListener('click', function() {

            updateBtn.classList.add('disabled');
            updateBtn.innerText = 'در حال ذخیره سازی ...';

            setTimeout(function() {
                updateBtn.classList.remove('disabled');
                updateBtn.innerText = 'ذخیره';
            }, 3000);

            // Get summer note value
            $wire.set('form.long_description', $('#summernote2').summernote('code'));

            // if tags is empty get tags from #select-tag
            if (!tags) {
                tags = $('#select-tag').select2("val");
            }

            $wire.set('form.tags', tags);

            let attributeBoxes = document.querySelectorAll('[id^="attribute-box-"]');

            attributeBoxes.forEach(function(box) {
                let attributeId = box.id.split('-')[2];
                let attributeValue = document.getElementById('attribute-' + attributeId).value;

                if (attributeValue) {
                    // if attrribute with the same id exists replace it
                    let attributeIndex = attributes.findIndex(attribute => attribute.id == attributeId);

                    if (attributeIndex != -1) {
                        attributes[attributeIndex].value = attributeValue;
                        return;
                    }

                    attributes.push({
                        id: attributeId,
                        name: box.querySelector('label').innerText,
                        value: attributeValue,
                    });
                }
            });

            $wire.set('form.attributes', attributes);

            $wire.set('form.fbx_file', fbxFile);

            $wire.call('update');
        });

        let showStockInputs = document.getElementById('showStockInputs');
        let stockInputs = document.getElementById('stockInputs');

        showStockInputs.addEventListener('click', function() {
            if (showStockInputs.checked) {
                stockInputs.style.display = 'block';
            } else {
                stockInputs.style.display = 'none';
            }
        });

        // Initialize Resumable.js
        let resumable = new Resumable({
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            target: '/upload', // Replace with your upload endpoint
            chunkSize: 1 * 1024 * 1024, // 1MB chunk size
            simultaneousUploads: 4, // Number of simultaneous uploads
            testChunks: false, // Disable chunk testing
            throttleProgressCallbacks: 1, // Trigger progress event every 1 second
            maxFiles: 1, // Max number of files
        });

        resumable.assignBrowse(document.getElementById('fbx_file'));

        // Add event listeners
        resumable.on('fileAdded', function(file) {
            // Start uploading the file
            resumable.upload();
        });

        resumable.on('fileProgress', function(file) {
            // Handle file upload progress
            var progress = Math.floor(file.progress() * 100);

            // Update the progress percentage
            fbxFileBrowse.innerText = 'در حال آپلود ' + progress + '%';
        });

        resumable.on('fileSuccess', function(file, message) {
            message = JSON.parse(message);
            fbxFileBrowse.innerText = message['name'];

            fbxFile = message;
        });

        resumable.on('fileError', function(file, message) {
            // Handle file upload error
            console.error('File upload error:', message);
        });
    </script>
@endscript
