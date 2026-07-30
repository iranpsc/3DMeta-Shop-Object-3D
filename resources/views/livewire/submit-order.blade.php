<div>
    <main >
        <section>
            <div class="bg-[#000BEEF7] dark:bg-[#E59819] w-full py-[10px] text-white text-sm hidden lg:block px-5" style="font-family: rokh">
                <div class="flex items-center justify-between max-w-[1500px] mx-auto">
                    <div>
                        <a href="#" class="px-4">قوانین و مجوزات</a>
                        <a href="#" class="px-4 border-x"> سوالات متداول </a>
                        <a href="#" class="px-4"> سیاست حفظ حریم خصوصی </a>
                    </div>
                    <div class="flex gap-4">
                        <div><a href="#"><img src="https://3d.irpsc.com/home-page/images/Union (1).png"
                                    alt="telegram"></a></div>
                        <div><a href="https://www.instagram.com/modelify3d_com/" ><img
                                    src="https://3d.irpsc.com/home-page/images/Union (2).png" alt="instagram"></a></div>
                        <div><a href="whatsapp://send?text=http://+989127855049"><img
                                    src="https://3d.irpsc.com/home-page/images/Union (3).png" alt="whatsapp"></a></div>
                        <div><a href="mailto:dmeta.irpsc@gmail.com"><img
                                    src="https://3d.irpsc.com/home-page/images/Union (4).png" alt="email"></a></div>
                    </div>
                </div>
            </div>
        </section>
        <section class="max-w-[1500px] mx-auto p-4 lg:p-9 mt-24 lg:mt-14">
            <div class="w-full lg:w-[70%] 2xl:w-[55%] bg-white mx-auto dark:bg-[#1A1A18] rounded-xl dark:text-white p-5 lg:p-7">
                <h1 class="text-lg md:text-2xl dark:text-white font-bold py-5 text-center "style="font-family:rokh ;" > جهت ثبت سفارش طراحی، فرم زیرا پر کنید.</h1>

                <div class="flex-col flex gap-7 justify-center w-full">
                    <div class="flex flex-col gap-2 md:gap-7 w-full  justify-center ">


                        @session('message')
                            <div class="bg-green-500 text-white p-3 rounded-lg text-center">
                                {{ session('message') }}
                            </div>
                        @endsession

                        <form wire:submit="submit">
                            <div class="grid lg:grid-cols-2 gap-4 md:gap-7 w-full">
                                @guest
                                    <x-form.text name="name" placeholder=" نام و نام خانوادگی" label="" wire:model="name" />

                                    <x-form.text name="phone" type="number" placeholder="شماره تلفن" label="" wire:model="phone" />

                                    <x-form.text name="email" placeholder="پست الکترونیک" label="" wire:model="email" />
                                @endguest

                                <x-form.text name="subject" placeholder="موضوع پیام" label="" wire:model="subject" />


                            </div>


                            <div class="flex flex-col gap-7 w-full mt-2 md:mt-5">
                                <x-form.textarea name="message" placeholder="پیام خود را اینجا بنویسید..." label=""
                                    wire:model="message" />
                                    <x-form.file name="attachment" label="" wire:model="attachment"  style="width: 100% ; margin-top: 10px"/>

                                <x-button type="submit">ارسال پیام</x-button>
                            </div>
                        </form>
                    </div>
                    <div class="flex flex-col gap-7 mx-auto text-center">
                        <p class="text-3xl " style="font-family:rokh">تماس با تیم مشاوره</p>
                        <div class="flex flex-col  gap-3  text-xl text-black/50 dark:text-gray-300">
                            <a href="tel:09337850424" style="font-family:rokh" dir="ltr">۰۹۳۳- ۷۸۵۰۴۲۴</a>
                            <a href="maileto:3dmeta.irpsc@gmail.com" style="font-family:rokh">3dmeta.irpsc@gmail.com</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>
