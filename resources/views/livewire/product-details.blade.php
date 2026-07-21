@section('title', $product->name . ' - ' . $product->sku . ' - فروشگاه آنلاین')
@section('description', $product->description)
@section('keywords', implode(',', ['کلمه کلیدی 1', 'کلمه کلیدی 2', $product->name]))
@section('og:title', $product->name)
@section('og:description', $product->short_description)
@section('og:image', $product->images->first()?->url)
@section('og:type', 'product')

@section('extera-schema')
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "Product",
      "name": "{{ $product->name }}",
      "sku": "{{ $product->sku }}",
      "category": "{{ $product->category->name }}",
      "image": "{{ $product->images->first()?->url }}",
      "description": "{{ $product->short_description }}",
      "offers": {
        "@@type": "Offer",
        "url": "{{ url()->current() }}",
        "priceCurrency": "IRR",
        "price": "{{ $product->price }}",
        "availability": "https://schema.org/{{ $product->stock > 0 ? 'InStock' : 'OutOfStock' }}",
        "itemCondition": "https://schema.org/NewCondition",
        "seller": {
          "@@type": "Organization",
          "name": "سه بعدی متا"
        }
      },
      "aggregateRating": {
        "@@type": "AggregateRating",
        "ratingValue": "{{ number_format($product->rating_avg) }}",
        "reviewCount": "{{ $product->approved_reviews_count }}"
      }

    }
    </script>

@endsection




<div>
    <main>
        <section class="max-w-[1500px] mx-auto p-4 lg:p-9 mt-20 lg:mt-0">
            <div class="flex flex-col items-center justify-center  ">
                <div id="hs-slide-up-animation-modal"
                    class="hs-overlay hidden w-[100%] fixed top-14  start-0 z-[80] overflow-x-hidden overflow-y-auto pointer-events-none mx-auto   flex flex-col justify-center items-center">
                    <div
                        class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-14 opacity-0 ease-out transition-all  px-5  ">
                        <div
                            class="flex flex-col bg-white  shadow-sm rounded-xl pointer-events-auto dark:bg-[#1A1A18]  dark:shadow-slate-700/[.7]  ">
                            <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
                                <h3 class="font-bold text-gray-800 dark:text-white">
                                    اشتراک این صفحه
                                </h3>
                                <button type="button"
                                    class="hs-dropup-toggle flex justify-center items-center size-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none dark:text-white dark:hover:bg-gray-700 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                                    data-hs-overlay="#hs-slide-up-animation-modal">
                                    <span class="sr-only">Close</span>
                                    <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24"
                                        height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M18 6 6 18" />
                                        <path d="m6 6 12 12" />
                                    </svg>
                                </button>
                            </div>
                            <div class="p-4  text-gray-800 dark:text-white  overflow-y-auto flex flex-wrap gap-5">
                                <a class="rounded-xl px-5 py-3 dark:bg-[#0f0f0f] bg-[#EFEFEF]"
                                    href="https://twitter.com/share?url=<URL>&text=<TEXT>via=<USERNAME>">
                                    Twitter
                                </a>
                                <!-- Facebook (url) -->
                                <a class="rounded-xl px-5 py-3 dark:bg-[#0f0f0f] bg-[#EFEFEF]"
                                    href="https://www.facebook.com/sharer/sharer.php?u=<URL>">
                                    Facebook
                                </a>
                                <!-- Reddit (url, title) -->
                                <a class="rounded-xl px-5 py-3 dark:bg-[#0f0f0f] bg-[#EFEFEF]"
                                    href="https://reddit.com/submit?url=<URL>&title=<TITLE>">
                                    Reddit
                                </a>
                                <!-- Hacker News (url, title) -->
                                <a class="rounded-xl px-5 py-3 dark:bg-[#0f0f0f] bg-[#EFEFEF]"
                                    href="https://news.ycombinator.com/submitlink?u=<URL>&t=<TITLE>">
                                    Hacker News
                                </a>
                                <!-- LinkedIn (url, title, summary, source url) -->
                                <a class="rounded-xl px-5 py-3 dark:bg-[#0f0f0f] bg-[#EFEFEF]"
                                    href="https://www.linkedin.com/shareArticle?url=<URL>&title=<TITLE>&summary=<SUMMARY>&source=<SOURCE_URL>">
                                    LinkedIn
                                </a>
                                <!-- Email (subject, body) -->
                                <a class="rounded-xl px-5 py-3 dark:bg-[#0f0f0f] bg-[#EFEFEF]"
                                    href="mailto:?subject=<SUBJECT>&body=<BODY>">
                                    Email
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="w-full flex flex-col justify-start md:flex-row gap-5">
                <div class="flex justify-start items-center flex-col gap-5 w-full md:w-1/3 xl:w-[35%] ">
                    <div wire:ignore
                        class=" zoomable rounded-lg overflow-hidden w-full bg-white aspect-square flex items-center justify-center">
                        <img class="aspect-square zoomable__img" id="square" alt="محصول {{ $product->name }} " loading="lazy" onerror="this.onerror=null; this.src='{{ asset('home-page/images/default.jpg') }}';"/>
                    </div>
                    <div class="grid grid-cols-3 gap-5 ">
                        @foreach ($product->images as $image)
                            <button class="image-btn w-full bg-white aspect-square rounded-lg overflow-hidden"
                                data-src="{{ $image->url }}">
                                <img class="aspect-square" src="{{ $image->url }}" alt="محصول {{ $product->name }} " loading="lazy" onerror="this.onerror=null; this.src='{{ asset('home-page/images/default.jpg') }}';">
                            </button>
                        @endforeach
                    </div>
                </div>
                <div class="w-full md:w-2/3 xl:w-[65%] flex flex-col gap-5 mt-14 md:mt-0">
                    <p class="text-[#3A4980] text-2xl font-bold dark:text-white px-5 ">
                        {{ $product->name }}
                    </p>
                    <div class="flex justify-between h-min w-full  px-5 pb-5 border-b border-gray-300">

                        <div class="flex flex-col gap-4 justify-center">

                            <span class="text-[#1A1A18] dark:text-gray-200">{{ $product->sku }}</span>
                        </div>
                        <div class="flex items-start justify-end gap-2" wire:ignore>
                            <div
                                class="p-1 h-8 bg-[#91B3FA29] dark:bg-[#1A1A18] dark:text-white rounded-lg flex items-center gap-3 w-max select-none text-[#3A4980]">
                                <div>
                                    <p>{{ $product->likes_count }}</p>
                                </div>
                                <div class="like">
                                    <input id="like" type="checkbox" class="peer" wire:click="toggleLike"
                                        @checked($product->user_liked)>
                                    <label for="like" class="like-label peer-checked:[&>svg>path]:fill-red-600">
                                        <svg width="18" height="16" viewBox="0 0 18 16" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path class="dark:stroke-white"
                                                d="M16.5 4.875C16.5 2.80417 14.7508 1.125 12.5933 1.125C10.9808 1.125 9.59583 2.06333 9 3.4025C8.40417 2.06333 7.01917 1.125 5.40583 1.125C3.25 1.125 1.5 2.80417 1.5 4.875C1.5 10.8917 9 14.875 9 14.875C9 14.875 16.5 10.8917 16.5 4.875Z"
                                                stroke="#3A4980" stroke-width="1.5" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>
                                    </label>
                                </div>
                            </div>
                            <div
                                class="like p-1 h-8 w-8 aspect-square bg-white dark:bg-[#1A1A18] rounded-lg flex items-center gap-3 justify-center select-none text-[#3A4980]">
                                <input id="save" type="checkbox" class="peer" wire:click="toggleBookmark"
                                    @checked($product->user_bookmarked)>
                                <label for="save" class="like-label peer-checked:[&>svg>path]:fill-black">
                                    <svg width="14" height="18" viewBox="0 0 14 18" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path class="dark:stroke-white"
                                            d="M11.6608 1.76838C12.5775 1.87505 13.25 2.66588 13.25 3.58922V16.5L7 13.375L0.75 16.5V3.58922C0.75 2.66588 1.42167 1.87505 2.33917 1.76838C5.43599 1.40891 8.56401 1.40891 11.6608 1.76838Z"
                                            stroke="#3A4980" stroke-width="1.5" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                </label>
                            </div>
                            <button data-hs-overlay="#hs-slide-up-animation-modal"
                                class="p-1 h-8 w-8 aspect-square bg-white dark:bg-[#1A1A18] rounded-lg select-none flex items-center justify-center">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path class="dark:stroke-white"
                                        d="M6.01415 9.08916C5.81237 8.72608 5.49574 8.44029 5.11396 8.27664C4.73218 8.11299 4.30686 8.08075 3.90478 8.18497C3.50269 8.2892 3.14659 8.52399 2.89238 8.85249C2.63817 9.181 2.50024 9.58462 2.50024 9.99999C2.50024 10.4154 2.63817 10.819 2.89238 11.1475C3.14659 11.476 3.50269 11.7108 3.90478 11.815C4.30686 11.9192 4.73218 11.887 5.11396 11.7233C5.49574 11.5597 5.81237 11.2739 6.01415 10.9108M6.01415 9.08916C6.16415 9.35916 6.24998 9.66916 6.24998 9.99999C6.24998 10.3308 6.16415 10.6417 6.01415 10.9108M6.01415 9.08916L13.9858 4.66082M6.01415 10.9108L13.9858 15.3392M13.9858 4.66082C14.1027 4.88094 14.2623 5.07555 14.4552 5.23327C14.6482 5.39098 14.8707 5.50865 15.1097 5.57939C15.3486 5.65012 15.5993 5.67251 15.847 5.64524C16.0948 5.61796 16.3346 5.54158 16.5524 5.42054C16.7703 5.29951 16.9618 5.13626 17.1159 4.94034C17.2699 4.74441 17.3833 4.51975 17.4495 4.27948C17.5157 4.03921 17.5333 3.78815 17.5013 3.54099C17.4694 3.29382 17.3885 3.05552 17.2633 2.83999C17.0167 2.41528 16.6133 2.10427 16.1398 1.97384C15.6663 1.8434 15.1606 1.90394 14.7313 2.14245C14.3019 2.38096 13.9833 2.7784 13.8439 3.24932C13.7046 3.72024 13.7555 4.22706 13.9858 4.66082ZM13.9858 15.3392C13.8662 15.5545 13.7902 15.7912 13.7621 16.0359C13.7339 16.2806 13.7543 16.5285 13.822 16.7653C13.8897 17.0021 14.0033 17.2233 14.1565 17.4162C14.3096 17.6091 14.4993 17.77 14.7146 17.8896C14.9299 18.0092 15.1666 18.0852 15.4113 18.1133C15.656 18.1414 15.9039 18.1211 16.1407 18.0534C16.3775 17.9857 16.5987 17.8721 16.7916 17.7189C16.9845 17.5658 17.1454 17.3761 17.265 17.1608C17.5065 16.726 17.5655 16.213 17.4288 15.7347C17.2921 15.2564 16.9711 14.852 16.5362 14.6104C16.1014 14.3688 15.5884 14.3099 15.1101 14.4466C14.6318 14.5832 14.2274 14.9043 13.9858 15.3392Z"
                                        stroke="#3A4980" stroke-width="1.5" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="flex justify-between h-min w-full  px-5 pb-5 border-b border-gray-300">
                        <div class="flex flex-col gap-4 justify-center">
                            <p class="text-[#3A4980] text-2xl font-bold dark:text-white">
                                @if ($product->is_free)
                                    رایگان
                                @else
                                    {{ $product->final_price }}<sub>(تومان)</sub>
                                @endif
                            </p>
                            @if ($product->price)
                                <p class="text-[#3A498087] dark:text-gray-400 text-xs line-through">
                                    {{ $product->price }}<sub>(تومان)</sub>
                                </p>
                            @endif
                        </div>
                        <div class="flex flex-col gap-5">
                            <div class="flex items-start justify-end gap-2">
                                <div
                                    class="flex items-center w-max gap-3 px-3 py-1 text-[#3A4980] dark:text-white bg-white dark:bg-[#1A1A18] rounded-full">
                                    <p>{{ number_format($product->rating_avg) }} </p>
                                    <svg width="16" height="17" viewBox="0 0 16 17" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path class="dark:stroke-white"
                                            d="M7.65335 2.83265C7.68155 2.76387 7.72957 2.70503 7.7913 2.66362C7.85303 2.62221 7.92568 2.6001 8.00002 2.6001C8.07435 2.6001 8.14701 2.62221 8.20874 2.66362C8.27047 2.70503 8.31849 2.76387 8.34669 2.83265L9.76335 6.23998C9.78987 6.30374 9.83346 6.35894 9.88933 6.39952C9.9452 6.4401 10.0112 6.46448 10.08 6.46998L13.7587 6.76465C14.0914 6.79131 14.226 7.20665 13.9727 7.42331L11.17 9.82465C11.1177 9.86944 11.0786 9.92778 11.0572 9.99328C11.0358 10.0588 11.0329 10.1289 11.0487 10.196L11.9054 13.786C11.9226 13.858 11.9181 13.9335 11.8924 14.003C11.8667 14.0724 11.821 14.1327 11.7611 14.1763C11.7012 14.2198 11.6297 14.2445 11.5557 14.2475C11.4817 14.2504 11.4085 14.2313 11.3454 14.1926L8.19535 12.2693C8.13651 12.2335 8.06893 12.2145 8.00002 12.2145C7.93111 12.2145 7.86353 12.2335 7.80469 12.2693L4.65469 14.1933C4.59152 14.232 4.51832 14.2511 4.44432 14.2481C4.37033 14.2452 4.29885 14.2204 4.23893 14.1769C4.17901 14.1334 4.13333 14.0731 4.10765 14.0036C4.08198 13.9342 4.07747 13.8587 4.09469 13.7866L4.95135 10.196C4.96724 10.1289 4.96432 10.0588 4.94291 9.99325C4.92151 9.92773 4.88244 9.86939 4.83002 9.82465L2.02735 7.42331C1.97123 7.37505 1.93063 7.31128 1.91065 7.24C1.89068 7.16873 1.89222 7.09314 1.9151 7.02274C1.93797 6.95235 1.98114 6.89028 2.0392 6.84436C2.09725 6.79844 2.16758 6.7707 2.24135 6.76465L5.92002 6.46998C5.98885 6.46448 6.05483 6.4401 6.1107 6.39952C6.16657 6.35894 6.21017 6.30374 6.23669 6.23998L7.65335 2.83265Z"
                                            stroke="#3A4980" stroke-width="1.5" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <div
                                    class="flex items-center w-max gap-3 px-3 py-1 text-[#3A4980] dark:text-white bg-white dark:bg-[#1A1A18] rounded-full">
                                    <p>{{ $product->approved_reviews_count }} &nbsp بررسی</p>
                                    <svg width="16" height="17" viewBox="0 0 16 17" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path class="dark:stroke-white"
                                            d="M5.75 7C5.75 7.06631 5.72366 7.12989 5.67678 7.17678C5.62989 7.22366 5.5663 7.25 5.5 7.25C5.4337 7.25 5.37011 7.22366 5.32322 7.17678C5.27634 7.12989 5.25 7.06631 5.25 7C5.25 6.9337 5.27634 6.87011 5.32322 6.82322C5.37011 6.77634 5.4337 6.75 5.5 6.75C5.5663 6.75 5.62989 6.77634 5.67678 6.82322C5.72366 6.87011 5.75 6.9337 5.75 7ZM5.75 7H5.5M8.25 7C8.25 7.06631 8.22366 7.12989 8.17678 7.17678C8.12989 7.22366 8.0663 7.25 8 7.25C7.9337 7.25 7.87011 7.22366 7.82322 7.17678C7.77634 7.12989 7.75 7.06631 7.75 7C7.75 6.9337 7.77634 6.87011 7.82322 6.82322C7.87011 6.77634 7.9337 6.75 8 6.75C8.0663 6.75 8.12989 6.77634 8.17678 6.82322C8.22366 6.87011 8.25 6.9337 8.25 7ZM8.25 7H8M10.75 7C10.75 7.06631 10.7237 7.12989 10.6768 7.17678C10.6299 7.22366 10.5663 7.25 10.5 7.25C10.4337 7.25 10.3701 7.22366 10.3232 7.17678C10.2763 7.12989 10.25 7.06631 10.25 7C10.25 6.9337 10.2763 6.87011 10.3232 6.82322C10.3701 6.77634 10.4337 6.75 10.5 6.75C10.5663 6.75 10.6299 6.77634 10.6768 6.82322C10.7237 6.87011 10.75 6.9337 10.75 7ZM10.75 7H10.5M1.5 9.00667C1.5 10.0733 2.24867 11.0027 3.30467 11.158C4.02933 11.2647 4.76133 11.3467 5.5 11.404V14.5L8.28933 11.7113C8.42744 11.5738 8.61312 11.4945 8.808 11.49C10.1091 11.458 11.407 11.3471 12.6947 11.158C13.7513 11.0027 14.5 10.074 14.5 9.006V4.994C14.5 3.926 13.7513 2.99733 12.6953 2.842C11.1406 2.61381 9.57135 2.49951 8 2.5C6.40533 2.5 4.83733 2.61667 3.30467 2.842C2.24867 2.99733 1.5 3.92667 1.5 4.994V9.006V9.00667Z"
                                            stroke="#3A4980" stroke-width="1.5" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                </div>
                            </div>
                            <div class="text-black/40 dark:text-white/75 ">
                                @if ($product->review)
                                    <p>{{ ($product->review->rating * 100) / 5 }} درصد از خریداران این را توصیه کرده
                                        اند.
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-5 justify-between h-min w-full  px-5 pb-5 border-b border-gray-300">
                        <div>
                            <p class="text-[#8E9ABC] dark:text-white">فرمت قابل دانلود</p>
                        </div>
                        <div class="flex gap-4">
                            <div
                                class="flex items-center gap-3 p-2 rounded-lg bg-white dark:bg-[#1A1A18] text-[#3A4980] dark:text-white">
                                <p>png </p>
                            </div>
                            <div
                                class="flex items-center gap-3 p-2 rounded-lg bg-white dark:bg-[#1A1A18] text-[#3A4980] dark:text-white">

                                <p>fbx </p>
                            </div>
                        </div>
                    </div>
                    <div class="flex  gap-2   justify-between ">
                        @if ($product->stock_status && $product->quantity > 1)
                            <div class="flex flex-col md:flex-row items-center gap-4 text-[#3A4980]  dark:text-gray-300 text-xs w-[25%] "
                                style="margin-right: 10px">
                                <div class="flex flex-row rounded-lg relative bg-transparent ">
                                    <button data-action="decrement"
                                        class=" bg-white text-[#3A4980]  h-12 w-10 rounded-r-full cursor-pointer outline-none dark:bg-[#1A1A18] dark:text-white">
                                        <span class="m-auto text-2xl font-thin">−</span>
                                    </button>
                                    <input type="number"
                                        class="focus:border-0  focus:ring-0 border-0 text-center w-10 h-12 bg-white dark:text-white dark:bg-[#1A1A18] font-semibold text-md   flex items-center text-[#3A4980] "
                                        name="custom-input-number" min="1" max="{{ $product->quantity }}"
                                        value="1" id="custom-input-number">
                                    <button data-action="increment"
                                        class="bg-white dark:text-white text-[#3A4980] dark:bg-[#1A1A18]  h-12 w-10 rounded-l-full cursor-pointer">
                                        <span class="m-auto text-2xl font-thin">+</span>
                                    </button>
                                </div>
                                <div class="flex gap-2">
                                    <span>{{ $product->stock_status ? 'در انبار' : 'ناموجود' }}</span>
                                    <p>{{ $product->quantity }}</p>
                                </div>
                            </div>
                        @else
                            <div class="flex flex-col md:flex-row items-center gap-4 text-[#3A4980] dark:text-gray-300 text-xs w-[25%] "
                                style="margin-right: 10px">
                            </div>
                        @endif
                        <div class="w-[60%] md:w-[30%] lg:w-[40%]">
                            @if ($product->is_free)
                                <x-button wire:click="download" color="info" size="block"
                                    style="display: flex; justify-content: center; align-items: center;border-radius: 900px ; gap:20px"><img
                                        src="{{ asset('img/svg/download.svg') }}" alt="download" class="svg">
                                    دانلود
                                </x-button>
                            @elseif($product->stock_status && $product->quantity > 1)
                                <button type="button" @disabled(session('cart') && in_array($product->id, array_column(session('cart'), 'product_id'))) id="addToCartBtn"
                                    class="bg-[#E3000F] text-white text-sm font-bold text-center w-full h-12  rounded-full flex items-center gap-3 flex-row-reverse justify-center">
                                    <p>افزودن به سبد خرید</p>
                                    <svg width="21" height="22" viewBox="0 0 21 22" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path d="M13.7812 9.6875V5.75C13.7812 4.87976 13.4355 4.04516 12.8202 3.42981C12.2048 2.81445 11.3702 2.46875 10.5 2.46875C9.62971 2.46875 8.79512 2.81445 8.17976 3.42981C7.56441 4.04516 7.21871 4.87976 7.21871 5.75V9.6875M17.1552 7.94362L18.2603 18.4436C18.3216 19.0255 17.8666 19.5312 17.2812 19.5312H3.71871C3.58064 19.5314 3.44408 19.5025 3.31791 19.4464C3.19174 19.3904 3.07877 19.3084 2.98635 19.2058C2.89393 19.1032 2.82412 18.9824 2.78145 18.8511C2.73879 18.7197 2.72422 18.5809 2.73871 18.4436L3.84471 7.94362C3.87022 7.70174 3.98438 7.47786 4.16518 7.31516C4.34597 7.15246 4.5806 7.06246 4.82383 7.0625H16.1761C16.6801 7.0625 17.1027 7.44312 17.1552 7.94362ZM7.54683 9.6875C7.54683 9.77452 7.51226 9.85798 7.45073 9.91952C7.38919 9.98106 7.30573 10.0156 7.21871 10.0156C7.13168 10.0156 7.04822 9.98106 6.98669 9.91952C6.92515 9.85798 6.89058 9.77452 6.89058 9.6875C6.89058 9.60048 6.92515 9.51702 6.98669 9.45548C7.04822 9.39394 7.13168 9.35938 7.21871 9.35938C7.30573 9.35938 7.38919 9.39394 7.45073 9.45548C7.51226 9.51702 7.54683 9.60048 7.54683 9.6875ZM14.1093 9.6875C14.1093 9.77452 14.0748 9.85798 14.0132 9.91952C13.9517 9.98106 13.8682 10.0156 13.7812 10.0156C13.6942 10.0156 13.6107 9.98106 13.5492 9.91952C13.4877 9.85798 13.4531 9.77452 13.4531 9.6875C13.4531 9.60048 13.4877 9.51702 13.5492 9.45548C13.6107 9.39394 13.6942 9.35938 13.7812 9.35938C13.8682 9.35938 13.9517 9.39394 14.0132 9.45548C14.0748 9.51702 14.1093 9.60048 14.1093 9.6875Z
                                            9.35938C7.30573 9.35938 7.38919 9.39394 7.45073 9.45548C7.51226 9.51702
                                            7.54683 9.60048 7.54683 9.6875ZM14.1093 9.6875C14.1093 9.77452 14.0748
                                            9.85798 14.0132 9.91952C13.9517 9.98106 13.8682 10.0156 13.7812
                                            10.0156C13.6942 10.0156 13.6107 9.98106 13.5492 9.91952C13.4877 9.85798
                                            13.4531 9.77452 13.4531 9.6875C13.4531 9.60048 13.4877 9.51702 13.5492
                                            9.45548C13.6107 9.39394 13.6942 9.35938 13.7812 9.35938C13.8682 9.35938
                                            13.9517 9.39394 14.0132 9.45548C14.0748 9.51702 14.1093 9.60048 14.1093
                                            9.6875Z" stroke="white" stroke-width="1.5" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                </button>
                            @else
                                <button type="button" @disabled(in_array($product->id, array_column(session('cart', []), 'product_id'))) wire:click="addToCart"
                                    wire:click="addToCart"
                                    class="bg-[#E3000F] text-white text-sm font-bold text-center w-full h-12  rounded-full flex items-center gap-3 flex-row-reverse justify-center">
                                    <p>افزودن به سبد خرید</p>
                                    <svg width="21" height="22" viewBox="0 0 21 22" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path d="M13.7812 9.6875V5.75C13.7812 4.87976 13.4355 4.04516 12.8202 3.42981C12.2048 2.81445 11.3702 2.46875 10.5 2.46875C9.62971 2.46875 8.79512 2.81445 8.17976 3.42981C7.56441 4.04516 7.21871 4.87976 7.21871 5.75V9.6875M17.1552 7.94362L18.2603 18.4436C18.3216 19.0255 17.8666 19.5312 17.2812 19.5312H3.71871C3.58064 19.5314 3.44408 19.5025 3.31791 19.4464C3.19174 19.3904 3.07877 19.3084 2.98635 19.2058C2.89393 19.1032 2.82412 18.9824 2.78145 18.8511C2.73879 18.7197 2.72422 18.5809 2.73871 18.4436L3.84471 7.94362C3.87022 7.70174 3.98438 7.47786 4.16518 7.31516C4.34597 7.15246 4.5806 7.06246 4.82383 7.0625H16.1761C16.6801 7.0625 17.1027 7.44312 17.1552 7.94362ZM7.54683 9.6875C7.54683 9.77452 7.51226 9.85798 7.45073 9.91952C7.38919 9.98106 7.30573 10.0156 7.21871 10.0156C7.13168 10.0156 7.04822 9.98106 6.98669 9.91952C6.92515 9.85798 6.89058 9.77452 6.89058 9.6875C6.89058 9.60048 6.92515 9.51702 6.98669 9.45548C7.04822 9.39394 7.13168 9.35938 7.21871 9.35938C7.30573 9.35938 7.38919 9.39394 7.45073 9.45548C7.51226 9.51702 7.54683 9.60048 7.54683 9.6875ZM14.1093 9.6875C14.1093 9.77452 14.0748 9.85798 14.0132 9.91952C13.9517 9.98106 13.8682 10.0156 13.7812 10.0156C13.6942 10.0156 13.6107 9.98106 13.5492 9.91952C13.4877 9.85798 13.4531 9.77452 13.4531 9.6875C13.4531 9.60048 13.4877 9.51702 13.5492 9.45548C13.6107 9.39394 13.6942 9.35938 13.7812 9.35938C13.8682 9.35938 13.9517 9.39394 14.0132 9.45548C14.0748 9.51702 14.1093 9.60048 14.1093 9.6875Z
                                            9.35938C7.30573 9.35938 7.38919 9.39394 7.45073 9.45548C7.51226 9.51702
                                            7.54683 9.60048 7.54683 9.6875ZM14.1093 9.6875C14.1093 9.77452 14.0748
                                            9.85798 14.0132 9.91952C13.9517 9.98106 13.8682 10.0156 13.7812
                                            10.0156C13.6942 10.0156 13.6107 9.98106 13.5492 9.91952C13.4877 9.85798
                                            13.4531 9.77452 13.4531 9.6875C13.4531 9.60048 13.4877 9.51702 13.5492
                                            9.45548C13.6107 9.39394 13.6942 9.35938 13.7812 9.35938C13.8682 9.35938
                                            13.9517 9.39394 14.0132 9.45548C14.0748 9.51702 14.1093 9.60048 14.1093
                                            9.6875Z" stroke="white" stroke-width="1.5" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="bg-[#ffffff8a] rounded-[10px] flex items-center gap-4 p-5 dark:bg-[#1A1A18]">
                        <div>
                            <svg width="30" height="36" viewBox="0 0 30 36" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path class="dark:stroke-white"
                                    d="M1 11.3846V6.76923C1 3.58297 3.46243 1 6.5 1C9.53757 1 12 3.58297 12 6.76923V13.1154C12 14.7085 10.7688 16 9.25 16C7.73121 16 6.5 14.7085 6.5 13.1154V6.76923"
                                    stroke="#6E8092" stroke-width="2" stroke-linecap="round" />
                                <path class="dark:stroke-white"
                                    d="M15.8235 1H16.2727C21.6441 1 24.3298 1 26.1949 2.35633C26.7294 2.74493 27.2037 3.20578 27.6038 3.72491C29 5.53674 29 8.14571 29 13.3636V17.6909C29 22.7284 29 25.2469 28.2277 27.2585C26.986 30.4926 24.3601 33.0435 21.0309 34.2496C18.9602 35 16.3674 35 11.1818 35C8.21865 35 6.73707 35 5.55376 34.5713C3.65142 33.8821 2.15088 32.4243 1.44135 30.5764C1 29.4269 1 27.9877 1 25.1091V18"
                                    stroke="#6E8092" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <path class="dark:stroke-white"
                                    d="M29 18C29 21.1295 26.4629 23.6666 23.3334 23.6666C22.2015 23.6666 20.8672 23.4684 19.7666 23.7632C18.7889 24.0251 18.0251 24.7889 17.7632 25.7666C17.4684 26.8672 17.6666 28.2015 17.6666 29.3334C17.6666 32.4629 15.1295 35 12 35"
                                    stroke="#6E8092" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </div>
                        <div class="flex flex-col justify-between gap-5">
                            <p class="text-[#726C6C] dark:text-white/50 text-xs">توضیح کوتاه</p>
                            <p class="text-[#1D364D] dark:text-white/70 font-bold">{{ $product->short_description }}
                            </p>
                        </div>
                    </div>
                    <div class="bg-[#ffffff8a] rounded-[10px] flex items-center gap-4 p-5 dark:bg-[#1A1A18]">
                        <div>
                            <svg width="30" height="36" viewBox="0 0 30 36" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path class="dark:stroke-white"
                                    d="M1 11.3846V6.76923C1 3.58297 3.46243 1 6.5 1C9.53757 1 12 3.58297 12 6.76923V13.1154C12 14.7085 10.7688 16 9.25 16C7.73121 16 6.5 14.7085 6.5 13.1154V6.76923"
                                    stroke="#6E8092" stroke-width="2" stroke-linecap="round" />
                                <path class="dark:stroke-white"
                                    d="M15.8235 1H16.2727C21.6441 1 24.3298 1 26.1949 2.35633C26.7294 2.74493 27.2037 3.20578 27.6038 3.72491C29 5.53674 29 8.14571 29 13.3636V17.6909C29 22.7284 29 25.2469 28.2277 27.2585C26.986 30.4926 24.3601 33.0435 21.0309 34.2496C18.9602 35 16.3674 35 11.1818 35C8.21865 35 6.73707 35 5.55376 34.5713C3.65142 33.8821 2.15088 32.4243 1.44135 30.5764C1 29.4269 1 27.9877 1 25.1091V18"
                                    stroke="#6E8092" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <path class="dark:stroke-white"
                                    d="M29 18C29 21.1295 26.4629 23.6666 23.3334 23.6666C22.2015 23.6666 20.8672 23.4684 19.7666 23.7632C18.7889 24.0251 18.0251 24.7889 17.7632 25.7666C17.4684 26.8672 17.6666 28.2015 17.6666 29.3334C17.6666 32.4629 15.1295 35 12 35"
                                    stroke="#6E8092" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </div>
                        <div class="flex flex-col justify-between gap-5">
                            <p class="text-[#726C6C] dark:text-white/50 text-xs"> قابلیت تبدیل سه بعدی به</p>
                            <p class="text-[#1D364D] dark:text-white/70 font-bold">
                                @php
                                    $convertableTo3dModel = $product->attributes
                                        ->where('slug', 'convertable_to_3d_model')
                                        ->first();
                                    if ($convertableTo3dModel) {
                                        echo $convertableTo3dModel->pivot->value;
                                    } else {
                                        echo 'ندارد';
                                    }
                                @endphp
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap gap-3 mt-6">
                @foreach ($product->tags as $tag)
                    <a href="/products?tag={{ $tag->slug }}"
                        class="py-3 pb-[13px] px-5 rounded-[10px] w-max bg-[#ffffffa8] dark:bg-[#1A1A18] text-[#8E9ABC] dark:text-white/70 text-sm">{{ $tag->name }}</a>
                @endforeach
            </div>
        </section>
        <section class="max-w-[1500px] mx-auto p-4 lg:p-9 mt-20 lg:mt-14">
            <div class="border-b-2 border-gray-200 dark:border-gray-700">
                <nav class=" flex justify-start gap-10 mb-[-2px] " aria-label="Tabs" role="tablist">
                    <button type="button"
                        class=" hs-tab-active:border-blue-600 hs-tab-active:text-blue-600 dark:hs-tab-active:text-[#E59819] dark:hs-tab-active:border-[#E59819] py-4 px-5 inline-flex items-center gap-x-2 border-b-2 border-transparent  whitespace-nowrap text-[#8E9ABC] hover:text-[#164C96]   disabled:pointer-events-none dark:text-gray-400  active"
                        id="horizontal-right-alignment-item-1" data-hs-tab="#horizontal-right-alignment-1"
                        aria-controls="horizontal-right-alignment-1" role="tab">
                        توضیحات
                    </button>
                    <button type="button"
                        class=" hs-tab-active:border-blue-600 hs-tab-active:text-blue-600 dark:hs-tab-active:text-[#E59819] dark:hs-tab-active:border-[#E59819] py-4 px-5 inline-flex items-center gap-x-2 border-b-2 border-transparent  whitespace-nowrap text-[#8E9ABC] hover:text-[#164C96]    disabled:pointer-events-none dark:text-gray-400 "
                        id="horizontal-right-alignment-item-2" data-hs-tab="#horizontal-right-alignment-2"
                        aria-controls="horizontal-right-alignment-2" role="tab">
                        بررسی ها
                    </button>
                </nav>
            </div>
            <div class="mt-7">
                <div id="horizontal-right-alignment-1" role="tabpanel"
                    aria-labelledby="horizontal-right-alignment-item-1" class="flex flex-col gap-4">
                    <div
                        class="bg-[#FFFFFF] dark:bg-[#1A1A18] rounded-[10px] flex flex-col gap-5 justify-between  p-5 px-4 dark:text-white">
                        <p class="text-gray-800 dark:text-white">توضیحات :</p>
                        <p class="text-[#667085] dark:text-white">{!! nl2br(e($product->long_description)) !!}</p>
                    </div>
                    <div class="grid md:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-4">
                        @foreach ($product->attributes->where('slug', '!=', 'convertable_to_3d_model') as $attribute)
                            <div
                                class="bg-[#FFFFFF] dark:bg-[#1A1A18] rounded-[10px] flex justify-between items-center p-5 px-4">
                                <p class="text-gray-800 dark:text-gray-300">{{ $attribute->name }}</p>
                                <p class="text-[#667085] dark:text-gray-300"> {{ $attribute->pivot->value }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div id="horizontal-right-alignment-2" class="hidden" role="tabpanel"
                    aria-labelledby="horizontal-right-alignment-item-2">
                    <div class="w-full lg:w-[60%] space-y-10">
                        <livewire:reviews :product="$product" />
                    </div>
                </div>
            </div>


        </section>
        <section class="mt-10 max-w-[1500px] mx-auto p-4 lg:p-9 mt-20 lg:mt-0">
            <p
                class="text-[#344054] dark:text-gray-200 font-bold text-xl text-center md:text-right md:text-[30px] my-8">
                موارد مشابه‌ که ممکن است دوست داشته باشید.</p>
            <livewire:similar-products :category="$product->category" :product="$product" />
        </section>
    </main>
</div>

@script
    <script>
        let addToCartBtn = document.getElementById('addToCartBtn');
        let customInputNumber = document.getElementById('custom-input-number');

        if (addToCartBtn) {
            addToCartBtn.addEventListener('click', () => {
                $wire.call('addToCart', customInputNumber.value);
            });
        }
    </script>
@endscript
