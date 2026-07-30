<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class OrderSummaryResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tracking_id' => $this->tracking_id,
            'amount' => $this->amount,
            'total_price' => $this->total_price ?? null,
            'status' => $this->status,
            'is_paid' => $this->isPaid(),
            'status_label' => $this->isPaid() ? 'پرداخت شده' : 'پرداخت نشده',
            'product_names' => $this->whenLoaded('products', fn () => $this->products->pluck('name')->values()),
            'product_skus' => $this->whenLoaded('products', fn () => $this->products->pluck('sku')->values()),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
