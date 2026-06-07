<x-layouts.app title="خطای سرور داخلی">
    <main class="w-full main-content-smallNav ">
        <div class="w-[85%] 2xl:w-[70%] mx-auto bg-white dark:bg-[#1A1A18] rounded-2xl overflow-hidden mt-24 lg:mt-10">
            <div class="flex flex-col lg:flex-row justify-between w-full">
                <div
                    class="text-[#000BEE] flex flex-col justify-between gap-8 w-full lg:w-[50%] dark:text-white mt-7 lg:mt-12">
                    <div class="flex flex-col gap-8 p-5">
                        <p class="text-xl lg:text-3xl">خطای سرور داخلی..!</p>
                        <p class="lg:text-xl">
                            مشکلی در سرور رخ داده است. لطفاً بعداً دوباره تلاش کنید یا با پشتیبانی تماس بگیرید.
                        </p>
                        <div class="flex gap-5  mt-5 bg-[#D8E5FD] dark:bg-[#302818] rounded-full  p-2 ">
                            <div class="flex justify-center items-center p-3 w-min">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path class="dark:stroke-[#E59819]"
                                        d="M11.4582 21.7501C17.1421 21.7501 21.7498 17.1423 21.7498 11.4584C21.7498 5.77448 17.1421 1.16675 11.4582 1.16675C5.77424 1.16675 1.1665 5.77448 1.1665 11.4584C1.1665 17.1423 5.77424 21.7501 11.4582 21.7501Z"
                                        stroke="#000BEE" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                    <path class="dark:stroke-[#E59819]" d="M22.8332 22.8334L20.6665 20.6667"
                                        stroke="#000BEE" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </div>
                            <input type="text" wire:model="searchTerm" placeholder="جستجوی محصول"
                                class=" w-full  text-[#ACB9FA] font-bold ring-transparent outline-transparent dark:placeholder:text-[#614618] dark:text-[#E59819]   focus:!outline-0  focus:!right-0 border-0 focus:border-0 ring-offset-0  focus:ring-transparent     bg-transparent">
                            <button wire:click="search"
                                class="bg-[#000BEE] dark:bg-[#E59819] dark:text-black text-white font-bold md:text-xl pb-4 pt-[15px]  px-5 w-[30%] lg:w-[20%] rounded-[32px]  text-center  min-w-max ">جستجو</button>
                        </div>
                    </div>
                    <img src="{{ asset('home-page/images/Rectangle 23998zzzz.png') }}"
                        class="float-right mt-32 hidden lg:block dark:hidden">
                    <div class="hidden dark:block">
                        <img src="{{ asset('home-page/images/Rectangle 23998.png') }}"
                            class="float-right mt-32 hidden lg:block  ">
                    </div>
                </div>
                <div class="w-full h-full lg:w-[50%] lg:ml-[-50px]">
                    <div class="relative hidden lg:block">
                        <img src="{{ asset('home-page/images/Polygon 1.png') }}" class="relative dark:hidden">
                        <img src="{{ asset('home-page/images/Polygon 1d.png') }}" class="relative  hidden dark:block">
                        <img src="{{ asset('home-page/images/g10.png') }}" alt="500 error"
                            class="absolute top-[23%] left-[16%] w-[58%]">


                    </div>
                    <div class="w-full flex justify-end lg:hidden relative">
                        <img src="{{ asset('home-page/images/Rectangle 23999 (1).png') }}"
                            class="relative w-[90%] dark:hidden">
                        <img src="{{ asset('home-page/images/Rectangle 23999.png') }}"
                            class="relative w-[90%] hidden dark:block">
                        <img src="{{ asset('home-page/images/g10.png') }}" alt="500 error"
                            class="absolute top-[21%] left-[4%] w-[58%] rotate-[-25deg]">

                    </div>

                </div>

            </div>
        </div>
    </main>
</x-layouts.app>
