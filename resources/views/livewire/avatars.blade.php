@section('title', 'ساخت آواتار رایگان')
@section('description', 'ساخت آواتار رایگان به سادگی و با چند کلیک در وب‌سایت ما انجام می‌شود.')
@section('keywords', 'ساخت آواتار رایگان, طراحی آواتار آنلاین, آواتار رایگان')
@section('og:title', 'ساخت آواتار رایگان')
@section('og:description', 'با وب‌سایت ما به راحتی و به صورت رایگان آواتارهای جذاب و حرفه‌ای طراحی کنید.')
@section('og:image', asset('home-page/images/avatar.s.png'))
@section('og:type', 'website')

<div>
    <x-page title="آواتارها">

        @session('success')
            <div class="mb-4 rounded-lg bg-green-100 px-6 py-4 text-green-800 border border-green-300 flex items-center">
                <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endsession

        <div class="row justify-content-center">
            <div class="flex flex-col gap-5">
                <button id="openAvatarModal"
                    class=" bg-primery-blue dark:bg-dark-yellow px-11 py-3 rounded-xl font-semibold text-white dark:text-black hover:bg-primery-blue/90 dark:hover:bg-dark-yellow/90">
                    ایجاد آواتار
                </button>

                <!-- Modal Container -->
                <div id="avatarModal" class="fixed h-screen overflow-y-auto  modal-avatar-smallNav z-50 hidden top-20 lg:top-0" wire:ignore>
                    <!-- Transparent Backdrop (click-through) -->
                    <!-- Modal Panel -->
                    <div
                        class="relative w-full h-full  bg-white dark:bg-gray-800  pointer-events-auto flex flex-col ">
                        <!-- Header -->
                        <div
                            class="flex justify-between items-center p-4 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ایجاد آواتار</h3>
                            <div
                            class="flex justify-end gap-2 flex-shrink-0">
                            <button id="saveAvatarBtn"
                                class="hidden bg-primery-blue dark:bg-dark-yellow text-white dark:text-black rounded-md px-4 py-2 hover:bg-primery-blue/90 dark:hover:bg-dark-yellow/90">
                                ذخیره آواتار
                            </button>
                            <button id="closeAvatarModal"
                                class="bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded-md px-4 py-2 hover:bg-gray-300 dark:hover:bg-gray-600">
                                لغو
                            </button>
                        </div>
                        </div>

                        <!-- Body -->
                        <div class="flex-1 overflow-y-auto ">
                            <!-- Name Section -->
                            <div id="nameSection" class="p-4 flex flex-col gap-5 lg:flex-row">
                                <input type="text" wire:model="name" id="avatarName"
                                    placeholder="نام آواتار را وارد کنید"
                                    class="w-full bg-[#e9f3fd] dark:bg-black placeholder:text-[#90bde9] dark:placeholder:text-white/40 dark:text-white/50 rounded-[10px] p-4 py-3 border border-primery-blue dark:border-dark-yellow placeholder:text-[#00000030] dark:ring-[#E59819]">
                                <button id="confirmNameBtn"
                                    class=" w-full rounded-xl bg-primery-blue dark:bg-dark-yellow px-3 py-4 text-sm font-semibold text-white dark:text-black hover:bg-primery-blue/90 dark:hover:bg-dark-yellow/90">
                                    تایید نام
                                </button>
                            </div>

                            <!-- Avatar Builder Section -->
                            <div id="avatarBuilderSection" class="hidden  overflow-y-auto flex-1 min-h-0 w-full">
                                <iframe id="frame"
                                    class="frame w-full h-[60vh] xl:h-[70vh] 2xl:h-[80vh] 3xl:h-[92vh] border border-gray-300 dark:border-gray-600"
                                    allow="camera *; microphone *; clipboard-write"></iframe>
                            </div>
                        </div>

                    </div>
                </div>

                <input type="text" wire:model.live="search" name="search" placeholder="جستجو آواتار "
                    class="bg-[#e9f3fd] dark:bg-black placeholder:text-[#90bde9] dark:placeholder:text-white/40 dark:text-white/50 rounded-[10px] p-4 border-0 placeholder:text-[#00000030] dark:ring-[#E59819]">

                @if ($products->count() > 0)
                    <x-table>
                        <x-slot name="header">
                            <th>ردیف</th>
                            <th>شناسه</th>
                            <th>نام</th>
                            <th>تصویر</th>
                            <th>فایل</th>
                            <th>تاریخ ایجاد</th>
                        </x-slot>
                        @foreach ($products as $product)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $product->sku }}</td>
                                <td>{{ $product->name }}</td>
                                <td class="flex justify-center items-center">
                                    <a href="{{ $product->latestImage->url }}" target="_blank">
                                        <img class="w-[60px] rounded-lg" src="{{ $product->latestImage->url }}"
                                            alt="تصویر آواتار">
                                    </a>
                                </td>
                                <td>
                                    @foreach ($product->files as $file)
                                        <a href="{{ $file->url }}" class="block">دانلود {{ $file->name ?? $loop->iteration }}</a>
                                    @endforeach
                                </td>
                                <td>{{ jdate($product->created_at)->format('Y/m/d') }}</td>
                            </tr>
                        @endforeach
                    </x-table>

                    {{ $products->links() }}
                @else
                    <div class="text-center text-gray-500 dark:text-gray-400 mt-4">
                        <p>آواتاری یافت نشد.</p>
                    </div>
                @endif
            </div>
        </div>
    </x-page>
</div>

@script
    <script>
        const subdomain = 'metargb';
        const frame = document.getElementById('frame');
        const saveButton = document.getElementById('saveAvatarBtn');
        const nameInput = document.getElementById('avatarName');
        const confirmNameBtn = document.getElementById('confirmNameBtn');
        const avatarBuilderSection = document.getElementById('avatarBuilderSection');
        const nameSection = document.getElementById('nameSection');

        const openModalBtn = document.getElementById('openAvatarModal');
        const modal = document.getElementById('avatarModal');
        const closeModalBtns = [document.getElementById('closeAvatarModal'), document.getElementById('cancelAvatarBtn')];

        let avatarUrl = '';
        let avatarImageURL = '';

        frame.src = `https://${subdomain}.readyplayer.me/avatar?frameApi`;

        window.addEventListener('message', subscribe);
        document.addEventListener('message', subscribe);

        function subscribe(event) {
            const json = parse(event);
            if (json?.source !== 'readyplayerme') return;

            if (json.eventName === 'v1.frame.ready') {
                saveButton.classList.add('hidden');
                frame.contentWindow.postMessage(
                    JSON.stringify({
                        target: 'readyplayerme',
                        type: 'subscribe',
                        eventName: 'v1.**'
                    }),
                    '*'
                );
            }

            if (json.eventName === 'v1.avatar.exported') {
                avatarUrl = json.data.url;
                avatarImageURL = 'https://models.readyplayer.me/' + json.data.avatarId + '.png';
                @this.set('avatarUrl', avatarUrl);
                @this.set('avatarImageURL', avatarImageURL);
                saveButton.classList.remove('hidden');
            }
        }

        confirmNameBtn.addEventListener('click', () => {
            const name = document.getElementById('avatarName').value.trim();
            if (!name) {
                alert('لطفاً نام آواتار را وارد کنید.');
                return;
            }

            // فقط بخش نام را مخفی و بخش ساخت آواتار را نمایش بده
            document.getElementById('nameSection').classList.add('hidden');
            document.getElementById('avatarBuilderSection').classList.remove('hidden');

            // ست کردن نام در Livewire بدون رفرش مودال
            @this.set('name', name);
        });


        saveButton.addEventListener('click', function() {
            const name = nameInput.value.trim();
            if (!name) {
                alert('لطفاً نام آواتار را وارد کنید.');
                return;
            }
            modal.classList.add('hidden');
            @this.call('save');
        });

        // Open & Close Modal
        openModalBtn.addEventListener('click', () => modal.classList.remove('hidden'));
        closeModalBtns.forEach(btn => btn.addEventListener('click', () => modal.classList.add('hidden')));

        function parse(event) {
            try {
                return JSON.parse(event.data);
            } catch {
                return null;
            }
        }
    </script>
@endscript
