<?php

namespace App\Services\PaymentGateway;

use App\Models\PaymentGateway;
use InvalidArgumentException;

class PaymentManager
{
    /**
     * Create a payment service instance for the given gateway code.
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    // public static function make(string $gatewayCode, string $environment = 'sandbox')
    // {
    //     // Check if gateway exists & active
    //     $gateway = PaymentGateway::where('code', $gatewayCode)
    //         ->where('is_active', true)
    //         ->first();

    //     if (! $gateway) {
    //         throw new InvalidArgumentException("Payment gateway [{$gatewayCode}] not found or inactive.");
    //     }

    //     // Map gateway codes to their service classes
    //     $services = [
    //         'paypal' => PayPalService::class,
    //         'stripe' => StripeService::class,
    //         // 'razorpay' => RazorpayService::class,
    //     ];
        
    //     // Map DB codes to service keys
    // $gatewayMap = [
    //     'P-1' => 'paypal',
    //     'S-1' => 'stripe',
    // ];

    // $serviceKey = $gatewayMap[$gatewayCode] ?? null;

    //     //dd($gatewayCode, $services);
    //     if (! array_key_exists($gatewayCode, $services)) {
    //         throw new InvalidArgumentException("No service class found for [{$gatewayCode}].");
    //     }

    //     $serviceClass = $services[$serviceKey];

    //     return new $serviceClass($environment);
    // }

    public static function make(string $gatewayCode, string $environment = 'sandbox')
{
    // Check if gateway exists & active
    $gateway = PaymentGateway::where('code', $gatewayCode)
        ->where('is_active', true)
        ->first();

    if (! $gateway) {
        throw new InvalidArgumentException("Payment gateway [{$gatewayCode}] not found or inactive.");
    }

    // Map DB codes to service keys
    $gatewayMap = [
        'P-1' => 'paypal',
        'S-1' => 'stripe',
    ];

    $serviceKey = $gatewayMap[$gatewayCode] ?? null;

    if (! $serviceKey) {
        throw new InvalidArgumentException("No service mapping found for [{$gatewayCode}].");
    }

    // Map service keys to classes
    $services = [
        'paypal' => PayPalService::class,
        'stripe' => StripeService::class,
    ];

    if (! array_key_exists($serviceKey, $services)) {
        throw new InvalidArgumentException("No service class found for [{$gatewayCode}].");
    }

    $serviceClass = $services[$serviceKey];

    return new $serviceClass($environment);
}

}
