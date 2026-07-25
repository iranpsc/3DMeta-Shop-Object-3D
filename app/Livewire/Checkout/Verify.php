<?php

namespace App\Livewire\Checkout;

use App\Models\Product;
use Livewire\Component;
use App\Models\Transaction;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\Auth;

class Verify extends Component
{
    #[Url]
    public $Token;

    #[Url]
    public $status;

    #[Url]
    public $OrderId;

    #[Url]
    public $TerminalNo;

    #[Url]
    public $RRN;

    #[Url]
    public $HashCardNumber;

    #[URL]
    public $Amount;

    #[Locked]
    public $transaction = null;

    #[Locked]
    public $order = null;

    /**
     * Mount method is called when the component is being mounted.
     * It retrieves the transaction details and performs the verification process.
     */
    public function mount()
    {
        $this->transaction = Transaction::where('token', $this->Token)->with('order.orderItems')->first();

        if (!$this->transaction || $this->transaction->status != -1) {
            session()->flash('error', 'تراکنش مورد نظر یافت نشد.');
            return;
        }

        $this->handleTransaction();
    }

    /**
     * Handles the transaction by sending a verification request to ZarinPal payment gateway.
     *
     * @return void
     */
    private function handleTransaction()
    {
        $response = parsian()
            ->token($this->Token)
            ->verification()
            ->send();

        if (!$response->success()) {
            $this->handleFailedTransaction($response);
        } else {
            $this->handleSuccessfulTransaction($response);
        }
    }

    /**
     * Handles a failed transaction.
     * Updates the transaction and order status, and displays an error message.
     * @param $response The response object from the payment gateway.
     */
    private function handleFailedTransaction($response)
    {
        $this->transaction->update([
            'status' => $this->status,
        ]);

        $this->transaction->order->update([
            'status' => $this->status,
        ]);

        session()->flash('error', $response->error()->message());
    }

    /**
     * Handles a successful transaction.
     * Updates the transaction and order details, and updates the user's product quantity.
     * @param $response The response object from the payment gateway.
     */
    private function handleSuccessfulTransaction($response)
    {
        $this->transaction->update([
            'reference_id' => $response->referenceId(),
            'card_hash' => $response->cardHash(),
            'status' => $this->status,
        ]);

        $this->order = $this->transaction->order;

        $this->order->load('products.files');

        $this->order->update([
            'status' => $this->status,
        ]);

        $user = $this->order->user;

        foreach ($this->getOrderItems() as $product_id => $data) {
            $product = $user->products()->where('product_id', $product_id)->first();
            if ($product) {
                $product->pivot->increment('quantity', $data['quantity']);
            } else {
                $user->products()->attach($product_id, ['quantity' => $data['quantity']]);
            }
        }
    }

    /**
     * Retrieves the order items and their quantities.
     * @return array An array of order items with their quantities.
     */
    private function getOrderItems()
    {
        $orderItems = [];

        foreach ($this->order->orderItems as $item) {
            $orderItems[$item->product_id] = ['quantity' => $item->quantity];
        }

        return $orderItems;
    }

    /**
     * Downloads a product file.
     * @param Product $product The product to download.
     * @return \Illuminate\Http\Response The file download response.
     */
    public function download(Product $product, ?int $fileId = null)
    {
        $this->authorize('download', $product);

        $file = $fileId
            ? $product->files()->findOrFail($fileId)
            : $product->files()->firstOrFail();

        $product->users()->updateExistingPivot(Auth::id(), [
            'download_count' => $product->users()->find(auth()->id())->pivot->download_count + 1,
            'downloaded_at' => now(),
        ]);

        return response()->download(storage_path('app/' . $file->path), $file->name);
    }

    #[Title('تایید پرداخت')]
    public function render()
    {
        return view('livewire.checkout.verify');
    }
}
