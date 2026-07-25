<div>
    <style>
        table {
            width: 100%;
        }

        table,
        th,
        td {
            border: 1px solid gray;
            padding: 10px;
            text-align: center
        }
    </style>
    <x-page title="جزئیات سفارش">
        <div class="flex flex-col gap-5">
            <div class="overflow-x-auto w-full">
                <table class="table mb-0 table-borderless border-0">
                    <thead>
                        <tr class="userDatatable-header">
                            <th>
                                <span class="userDatatable-title">شناسه سفارش</span>
                            </th>
                            <th>
                                <span class="userDatatable-title">مبلغ پرداختی</span>
                            </th>
                            <th>
                                <span class="userDatatable-title">وضعیت</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="orderDatatable-title">
                                    {{ $order->tracking_id }}
                                </div>
                            </td>
                            <td>
                                <div class="orderDatatable-title">
                                    {{ $order->amount }}
                                </div>
                            </td>
                            <td>
                                <div class="orderDatatable-title">
                                    {{ $order->isPaid() ? 'پرداخت شده' : 'پرداخت نشده' }}
                                </div>
                            </td>
                        </tr>
                        <!-- End: tr -->
                    </tbody>
                </table><!-- End: table -->
            </div>
        </div>
        <div>
            <div class="overflow-x-auto w-full  mt-7">
                <table class="table mb-0 table-borderless border-0">
                    <thead>
                        <tr class="userDatatable-header">
                            <th>
                                ردیف
                            </th>
                            <th>
                                <span class="userDatatable-title">نام محصول</span>
                            </th>
                            <th>
                                <span class="userDatatable-title">تعداد</span>
                            </th>
                            <th>
                                <span class="userDatatable-title">تعداد دانلود</span>
                            </th>
                            <th>
                                <span class="userDatatable-title">آخرین دانلود</span>
                            </th>
                            <th>
                                <span class="userDatatable-title">عملیات</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->products as $product)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <div class="orderDatatable-title">
                                        {{ $product->name }}
                                    </div>
                                </td>
                                <td>
                                    <div class="orderDatatable-title">
                                        {{ $product->users()->find(Auth::id())->pivot->quantity }}
                                    </div>
                                <td>
                                    <div class="orderDatatable-title">
                                        {{ $product->users()->find(Auth::id())->pivot->download_count }}
                                    </div>
                                </td>
                                <td>
                                    <div class="orderDatatable-title">
                                        {{ jdate($product->users()->find(Auth::id())->pivot->downloaded_at)->format('Y/m/d H:i:s') }}
                                    </div>
                                </td>
                                <td>
                                    <div class="orderDatatable-title">
                                        <x-download-files :product="$product" method="download" :pass-product-id="true" />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        <!-- End: tr -->
                    </tbody>
                </table><!-- End: table -->
            </div>
        </div>
    </x-page>
</div>
