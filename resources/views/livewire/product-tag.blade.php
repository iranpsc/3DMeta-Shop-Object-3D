@section('title')
@section('description', 'محصولات برچسب ' . $tag->name)
@section('keywords', implode(',', ['برچسب', $tag->name, 'محصولات']))
@section('og:title', $tag->name)
@section('og:description', 'محصولات برچسب ' . $tag->name)
@section('og:image', asset('home-page/images/3d-Strawberry-3dmodel.jpg'))
@section('og:type', 'website')
<div>
    <main>
        <section>
            <div class="bg-[#000BEEF7] dark:bg-[#E59819] w-full py-[10px] text-white text-sm hidden lg:block px-5"
                style="font-family: rokh">
                <div class="flex items-center justify-between max-w-[1500px] mx-auto">
                    <div>
                        <a href="#" class="px-4">قوانین و مجوزات</a>
                        <a href="#" class="px-4 border-x"> سوالات متداول </a>
                        <a href="#" class="px-4"> سیاست حفظ حریم خصوصی </a>
                    </div>
                    <div class="flex gap-4">
                        <div><a href="#"><img src="https://3d.irpsc.com/home-page/images/Union (1).png"
                                    alt="telegram"></a></div>
                        <div><a href="https://www.instagram.com/modelify3d_com/"><img
                                    src="https://3d.irpsc.com/home-page/images/Union (2).png" alt="instagram"></a></div>
                        <div><a href="whatsapp://send?text=http://+989127855049"><img
                                    src="https://3d.irpsc.com/home-page/images/Union (3).png" alt="whatsapp"></a></div>
                        <div><a href="mailto:dmeta.irpsc@gmail.com"><img
                                    src="https://3d.irpsc.com/home-page/images/Union (4).png" alt="email"></a></div>
                    </div>
                </div>
            </div>
        </section>
        <section class="max-w-[1500px] mx-auto p-4 lg:p-9 lg:pt-0 mt-24 lg:mt-4">
            <div class="lg:flex gap-5 hidden">
                <div
                    class="flex gap-1 text-[#828282] p-3 items-center lg:w-[70%] xl:w-[80%] bg-white dark:bg-[#1A1A18] rounded-[10px]">
                    <a href="{{ route('home') }}" class="text-[#828282] !font-medium">خانه</a>
                    <span>/</span>
                    <a href="{{ route('products') }}" class="text-[#828282] !font-medium">محصولات</a>
                    <span>/</span>
                    <span class="text-[#828282] font-bold">{{ $tag->name }}</span>
                </div>
                <div
                    class="flex items-center justify-center lg:w-[30%] xl:w-[20%] bg-white dark:bg-[#1A1A18] rounded-[10px] gap-1 text-[#828282] p-3">
                    <span>برچسب : </span>
                    <span>{{ $tag->name }}</span>
                </div>
            </div>
            <div class="w-full relative ">
                <div class="swiper-slider swiper-container overflow-x-hidden" dir="ltr" wire:ignore>
                    <div class="swiper-wrapper">
                        <livewire:top-level-category-slider />
                    </div>
                </div>
            </div>
        </section>
        <section class="max-w-[1500px] mx-auto lg:p-9 mt-24 lg:mt-14 flex flex-col gap-5">
            <div class="px-4 sm:px-5">
                <div class="flex flex-col gap-3 p-4 sm:p-5 rounded-[20px] bg-[#ECF4FE] dark:bg-[#1A1A18]">
                    <h1 class="font-bold text-xl sm:text-2xl md:text-[30px] text-[#000BEE] dark:text-white">
                        برچسب {{ $tag->name }}
                    </h1>
                    <p class="text-[#868B90] dark:text-[#989898] text-sm sm:text-base md:text-xl">
                        محصولات مرتبط با این برچسب
                    </p>
                </div>
            </div>
            <div class="w-full space-y-5 px-4 sm:px-5 mx-auto" id="products-list">
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-5 transition-[5s] duration-500">
                    @forelse ($products as $product)
                        <livewire:product-item :product="$product" :key="'product-' . $product->id" />
                    @empty
                        <div class="col-span-full">
                            <x-alert type="warning" message="محصولی یافت نشد" />
                        </div>
                    @endforelse
                </div>
                @if ($products->hasPages())
                    <div class="pt-2">
                        {{ $products->links(data: ['scrollTo' => '#products-list']) }}
                    </div>
                @endif
            </div>
        </section>
    </main>
</div>
