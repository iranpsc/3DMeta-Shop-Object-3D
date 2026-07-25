<div>
    <x-page title="نتیجه پرداخت">
        <div class="flex flex-col justify-center items-center">
            <div class="w-full">
                <div class="row justify-content-center">
                    <div class="col-xl-8 col-12">
                        <div>
                            <div class=" flex items-center justify-between gap-3 ">
                                <div class="step current flex flex-col gap-2  items-center mt-5" id="1">
                                    <span
                                        class="bg-[#06CC85] rounded-full text-white flex items-center justify-center w-12 aspect-square p-2">
                                        <svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 512 457.57">
                                            <path fill="white" storke="white"
                                                d="M0,220.57c100.43-1.33,121-5.2,191.79,81.5,54.29-90,114.62-167.9,179.92-235.86C436-.72,436.5-.89,512,.24,383.54,143,278.71,295.74,194.87,457.57,150,361.45,87.33,280.53,0,220.57Z" />
                                        </svg>
                                    </span>
                                    <span>سبد خرید</span>
                                </div>
                                <div class="current hidden lg:block w-full"><img src="{{ asset('img/svg/green.svg') }}" alt="img"
                                        class="w-full"></div>
                                <div class="step flex flex-col gap-2  items-center mt-5" id="2">
                                    <span
                                        class="bg-[#06CC85] rounded-full text-white flex items-center justify-center w-12 aspect-square p-2">
                                        <svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 512 457.57">
                                            <path fill="white" storke="white"
                                                d="M0,220.57c100.43-1.33,121-5.2,191.79,81.5,54.29-90,114.62-167.9,179.92-235.86C436-.72,436.5-.89,512,.24,383.54,143,278.71,295.74,194.87,457.57,150,361.45,87.33,280.53,0,220.57Z" />
                                        </svg>
                                    </span>
                                    <span>ایجاد حساب</span>
                                </div>
                                <div class="current hidden lg:block w-full"><img src="{{ asset('img/svg/green.svg') }}" alt="img"
                                        class="w-full"></div>
                                <div class="step flex flex-col gap-2  items-center mt-5" id="3">
                                    <span
                                        class="bg-[#06CC85] rounded-full text-white flex items-center justify-center w-12 aspect-square p-2">
                                        <svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 512 457.57">
                                            <path fill="white" storke="white"
                                                d="M0,220.57c100.43-1.33,121-5.2,191.79,81.5,54.29-90,114.62-167.9,179.92-235.86C436-.72,436.5-.89,512,.24,383.54,143,278.71,295.74,194.87,457.57,150,361.45,87.33,280.53,0,220.57Z" />
                                        </svg>
                                    </span>
                                    <span>پرداخت</span>
                                </div>
                                <div class="current hidden lg:block w-full"><img src="{{ asset('img/svg/green.svg') }}" alt="img"
                                        class="w-full"></div>
                                <div class="step flex flex-col gap-2  items-center mt-5  text-center {{ $status === 0 ? 'completed' : 'not-completed' }}"
                                    id="4">
                                    <span
                                        class="flex justify-center items-center bg-[#EFEFEF] dark:bg-[#4A4E7C] aspect-square rounded-full text-white text-3xl w-12 las {{ $status === 0 ?  : 'la-times' }}"
                                        style="display: flex">
                                        <svg class="hidden" id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 512 457.57">
                                        <path fill="white" storke="white"
                                            d="M0,220.57c100.43-1.33,121-5.2,191.79,81.5,54.29-90,114.62-167.9,179.92-235.86C436-.72,436.5-.89,512,.24,383.54,143,278.71,295.74,194.87,457.57,150,361.45,87.33,280.53,0,220.57Z" />
                                    </svg>
                                     </span>

                                    <p>جزئیات پرداخت</p>
                                </div>
                            </div>
                        </div><!-- checkout -->
                        <div class="row justify-content-center">
                            <div class="col-12">
                                <div class="card checkout-shipping-form shadow-none border-0 shadow-none">
                                    <div class="card-body">
                                        <div class="edit-profile__body">

                                            @session('error')
                                                <x-alert type="danger" :message="session('error')" />
                                            @endsession

                                            @if ($order && $order->isPaid())

                                                <div class="alert alert-success my-5">
                                                    <p>پرداخت شما با موفقیت انجام شد. کد پیگیری پرداخت:
                                                        {{ $transaction->reference_id }}</p>
                                                </div>

                                                <div
                                                    class="userDatatable orderDatatable shipped-dataTable global-shadow ">
                                                    <div class="w-full  overflow-x-auto">
                                                        <table class="lg:w-full flex flex-col gap-10">
                                                            <thead
                                                                class="w-max lg:w-full bg-[#EFEFEF] dark:bg-[#4A4E7C] rounded-[10px] py-3 px-5">
                                                                <tr
                                                                    class="flex  w-full space-x-10 lg:space-x-0 justify-between">
                                                                    <th>
                                                                        ردیف
                                                                    </th>
                                                                    <th>
                                                                        <span class="userDatatable-title">شناسه
                                                                            سفارش</span>
                                                                    </th>
                                                                    <th>
                                                                        <span class="userDatatable-title">نام
                                                                            محصول</span>
                                                                    </th>
                                                                    <th>
                                                                        <span class="userDatatable-title float-end">دانلود</span>
                                                                    </th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="lg:px-5 flex flex-col gap-5">
                                                                @foreach ($order->products as $product)
                                                                    <tr
                                                                        class=" w-full justify-between flex items-center gap-10 lg:gap-0">
                                                                        <td>{{ $loop->iteration }}</td>
                                                                        <td>
                                                                            <div class="orderDatatable-title">
                                                                                {{ $order->tracking_id }}
                                                                            </div>
                                                                        </td>
                                                                        <td>
                                                                            <div class="orderDatatable-title">
                                                                                {{ $product->name }}
                                                                            </div>
                                                                        </td>
                                                                        <td>
                                                                            <x-download-files :product="$product" method="download" :pass-product-id="true">
                                                                                <i class="uil uil-download-alt"></i>دانلود
                                                                            </x-download-files>
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                                <!-- End: tr -->
                                                            </tbody>
                                                        </table><!-- End: table -->
                                                    </div>
                                                </div><!-- End: .userDatatable -->
                                            @endif
                                        </div>
                                    </div>
                                </div><!-- ends: card -->
                            </div><!-- ends: col -->
                        </div>
                    </div><!-- ends: col -->
                </div>
            </div><!-- End: .global-shadow-->
        </div>
    </x-page>
</div>
