<?php

namespace App\Services\Store;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class OrderService
{
    /**
     * Create a new order from PayPal payment and session cart.
     *
     * @param  array  $paypalResult  The PayPal API capture result
     */
    public function createOrderFromPaypal(array $paypalResult): Order
    {
        // Extract PayPal payer & order data
        $payer = $paypalResult['payer'] ?? [];
        $purchaseUnit = $paypalResult['purchase_units'][0] ?? [];
        $amount = $purchaseUnit['payments']['captures'][0]['amount']['value'] ?? 0;

        // Create Order
        $order = Order::create([
            'customer_id' => Auth::check() ? Auth::id() : null,
            'guest_email' => $payer['email_address'] ?? null,
            'total_amount' => $amount,
            'status' => 'completed',
        ]);

        // Create Order Details from cart session
        $cart = session('cart', []);
        foreach ($cart as $productId => $item) {
            OrderDetail::create([
                'order_id' => $order->id,
                'product_id' => $productId,
                'quantity' => $item['quantity'],
                'price' => $item['price'],
            ]);
        }

        // Clear cart session
        session()->forget(['cart', 'checkout.shipping']);

        return $order;
    }

    public function createOrder(Request $request, array $cart): Order
    {
        $name = trim($request->first_name . ' ' . $request->last_name);
        $customer = Customer::firstOrCreate(
            ['email' => $request->email], // search by email
            [
                'name' => $name,
                'password' => bcrypt('admin123'),
                'phone' => $request->phone,
                'address' => $request->address,
            ]
        );
       
        $order = null;
        $total_amount=0;
        foreach ($cart as $key => $item) {
            $total_amount += $item['price'] * $item['quantity'];
        }
        
        DB::transaction(function () use ($request, &$order,&$customer,&$cart,&$total_amount) {

            // Create Order
            $order = Order::create([
                'total_amount' => $total_amount,
                'status' => 'ordered',
                'customer_id'=>$customer->id,
                'guest_email'=>$customer->email,
                'created_at'=>now(),
                'updated_at'=>now()
            ]);
             
            // Create Order Details 
            foreach ($cart as $key => $item) {
                OrderDetail::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }
        });

        return $order ?? new Order();
    }
}
