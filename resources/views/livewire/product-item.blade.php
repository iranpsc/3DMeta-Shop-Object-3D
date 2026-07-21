<div class="w-full">
    <!-- start card -->
    <div class=" product w-full">
        <div
            class="bg-white dark:bg-[#1A1A18] w-full flex flex-col overflow-hidden rounded-xl justify-between items-center text-center  gap-2  duration-500 transition-all">
            <a href="{{ $product->url }}" style="width: 90% ;margin-top: 16px" class="overflow-hidden rounded-lg">
                <img src="{{ $product->oldestImage->url ?? asset('home-page/images/default.jpg') }}" alt="محصول {{ $product->category->name }}" loading="lazy" onerror="this.onerror=null; this.src='{{ asset('home-page/images/default.jpg') }}';">
            </a>
            <div class="w-full flex flex-col justify-center items-center gap-3 p-3">
                <p class="text-[#000BEE] dark:text-[#D1D1D1]  text-xs lg:text-sm font-bold p-0 m-0">
                    @if ($product->category->parent)
                        <a href="{{ route('categories.show', ['category_link' => $product->category->parent->url]) }}"> {{ $product->category->parent->name }} </a>
                        /
                    @endif
                    <a href="{{ route('categories.show', ['category_link' => $product->category->url]) }}"> {{ $product->category->name }} </a>
                </p>
                <p class="text-[#000BEE] dark:text-[#D1D1D1] text-xs lg:text-sm font-bold p-0 m-0">
                    {{ $product->sku }}
                </p>
                <a href="{{ $product->url }}"
                    class="font-bold text-sm lg:text-xl text-stone-800 dark:text-white ">{{ $product->name }}</a>
                <p class="font-bold text-sm lg:text-xl text-stone-800 dark:text-white ">
                    @if ($product->is_free)
                        رایگان
                    @else
                        {{ number_format($product->final_price, 0) }} تومان
                    @endif
                </p>

                <div class="w-full flex justify-between gap-2 ">
                    @if ($product->is_free)
                        <x-button wire:click="download({{ $product->id }})" color="light"
                            style="display: flex; justify-content: center; align-items: center;border-radius: 10px; padding:10px 20px; gap:20px;color:#00a367;width: 60%; background-color: #06cc8360"><img src="{{ asset('img/svg/download.svg') }}" alt="download" class="w-6 svg">
                            دانلود
                        </x-button>
                    @else
                        <button type="button" @disabled(in_array($product->id, array_column(session('cart', []), 'product_id'))) wire:click="addToCart"
                            onclick="cartAlert()"
                            class="rounded-lg w-[60%] px-2 py-3 bg-[#FFE3E3] dark:bg-[#381715]  text-[#FF0000] dark:text-[#FF0000] m-0 text-xs lg:text-sm font-bold">
                            افزودن به سبد خرید
                        </button>
                    @endif
                    <a type="button" href="{{ $product->url }}"
                        class="rounded-lg w-[40%]  px-2 py-3 bg-[#D8E5FD] dark:bg-[#493718]  text-[#000BEE] dark:text-[#E59819] m-0 text-xs lg:text-sm font-bold z-10 ">مشاهده
                        سریع</a>
                </div>
            </div>
        </div>
    </div>
    <!-- end card -->
</div>
