@section('title')
@section('description', $category->description)
@section('keywords', implode(',', ['کلمه کلیدی 1', 'کلمه کلیدی 2', $category->name]))
@section('og:title', $category->name)
@section('og:description', $category->description)
@section('og:image', $category->image->url ?? asset('home-page/images/3d-Strawberry-3dmodel.jpg'))
@section('og:type', 'product')
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
                    {!! $category->breadcrumb !!}
                </div>
                <div
                    class="flex items-center justify-center lg:w-[30%] xl:w-[20%] bg-white dark:bg-[#1A1A18] rounded-[10px] gap-1 text-[#828282] p-3">
                    <span>دسته بندی : </span>
                    <span>{{ $category->name }}</span>
                </div>
            </div>
            <div class="w-full relative ">
                <!-- Swiper -->
                <div class="swiper-slider swiper-container  overflow-x-hidden " dir="ltr" wire:ignore>
                    <div class="swiper-wrapper ">
                        <livewire:top-level-category-slider />
                    </div>
                    <!-- Add Pagination -->
                </div>
            </div>
        </section>
        <section class="max-w-[1500px] mx-auto  lg:p-9 mt-24 lg:mt-14 flex flex-col gap-5 ">
            <div class="px-5">
                <div class="flex flex-col md:flex-row gap-5 p-3 rounded-[20px] bg-[#ECF4FE] dark:bg-[#1A1A18]">
                    <div class="w-full md:w-1/3 xl:w-1/4">
                        <img class="w-full aspect-square rounded-[10px]" alt="دسته بندی {{ $category->name }}"
                            src="{{ $category->image->url ?? asset('home-page/images/default-product.jpg') }}"
                            onerror="this.onerror=null; this.src='{{ asset('home-page/images/default-product.jpg') }}';" />

                    </div>
                    <div class="flex flex-col gap-5 px-5 py-2 w-full md:w-2/3 xl:w-3/4">
                        <h1 class="font-bold text-xl md:text-[30px] text-[#000BEE] dark:text-white">
                            {{ $category->name }}</h1>
                        <p class="text-[#868B90] dark:text-[#989898] md:text-xl text-justify">
                            {{ $category->description }}</p>
                    </div>
                </div>
            </div>
            <div class="w-full space-y-5 px-5 mx-auto" id="products-list">
                <div class="">
                    <div class="grid lg:grid-cols-2 xl:grid-cols-4 gap-5 transition-[5s] duration-500 ">
                        @forelse ($category->children as $child)
                            @php
                                $children_links = implode('/', array_merge($categorySlugs, [$child->slug]));
                            @endphp
                            <div class=" product">
                                <div
                                    class="bg-[#ECF4FE] dark:bg-[#1A1A18] w-full flex flex-col overflow-hidden rounded-[20px]  items-center text-center p-2   duration-500 transition-all">
                                    <a href="{{ route('categories.show', ['category_link' => $children_links]) }}"
                                        class="p-1 w-full ">
                                        <img class="rounded-[10px]"
                                            src="{{ $child->image->url ?? asset('home-page/images/default-product.jpg') }}"
                                            loading="lazy" alt="دسته {{ $child->name }} ">
                                    </a>
                                    <div class="w-full flex flex-col justify-center items-center gap-3">
                                        <a href="{{ route('categories.show', ['category_link' => $children_links]) }}"
                                            class="font-bold text-sm lg:text-xl text-stone-800 dark:text-white py-[10px] ">{{ $child->name }}</a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <!-- start card -->
                            @if ($products->count())
                                @foreach ($products as $product)
                                    <livewire:product-item :product="$product" :key="'product-' . $product->id" />
                                @endforeach
                                {{ $products->links() }}
                            @else
                                <x-alert type="warning" message="محصولی یافت نشد" />
                            @endif
                        @endforelse
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>
