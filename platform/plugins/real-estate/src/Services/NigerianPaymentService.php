<?php

namespace Botble\RealEstate\Services;

use Botble\RealEstate\Models\RentPayment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NigerianPaymentService
{
    /**
     * Process rent payment via Paystack
     */
    public function processPaystackPayment(RentPayment $payment, array $data): array
    {
        $paystackSecretKey = setting('payment_paystack_secret');
        
        if (!$paystackSecretKey) {
            throw new \Exception('Paystack secret key not configured');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $paystackSecretKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.paystack.co/transaction/initialize', [
            'email' => $data['email'] ?? $payment->account->email,
            'amount' => $payment->amount * 100, // Convert to kobo
            'currency' => 'NGN',
            'reference' => 'RENT_' . $payment->id . '_' . time(),
            'callback_url' => route('public.rent-payments.callback'),
            'metadata' => [
                'payment_id' => $payment->id,
                'property_id' => $payment->property_id,
                'account_id' => $payment->account_id,
                'type' => 'rent_payment',
            ],
        ]);

        if ($response->successful()) {
            $data = $response->json('data');
            
            return [
                'success' => true,
                'authorization_url' => $data['authorization_url'],
                'access_code' => $data['access_code'],
                'reference' => $data['reference'],
            ];
        }

        throw new \Exception('Paystack payment initialization failed: ' . $response->json('message'));
    }

    /**
     * Process rent payment via Flutterwave
     */
    public function processFlutterwavePayment(RentPayment $payment, array $data): array
    {
        $flutterwaveSecretKey = setting('payment_flutterwave_secret');
        
        if (!$flutterwaveSecretKey) {
            throw new \Exception('Flutterwave secret key not configured');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $flutterwaveSecretKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.flutterwave.com/v3/payments', [
            'tx_ref' => 'RENT_' . $payment->id . '_' . time(),
            'amount' => $payment->amount,
            'currency' => 'NGN',
            'redirect_url' => route('public.rent-payments.callback'),
            'payment_options' => 'card,banktransfer,ussd,mobilemoney',
            'customer' => [
                'email' => $data['email'] ?? $payment->account->email,
                'phonenumber' => $data['phone'] ?? $payment->account->phone,
                'name' => $payment->account->name,
            ],
            'customizations' => [
                'title' => 'Rent Payment',
                'description' => 'Payment for ' . $payment->property->name,
            ],
            'meta' => [
                'payment_id' => $payment->id,
                'property_id' => $payment->property_id,
            ],
        ]);

        if ($response->successful()) {
            $data = $response->json('data');
            
            return [
                'success' => true,
                'link' => $data['link'],
                'tx_ref' => $data['tx_ref'],
            ];
        }

        throw new \Exception('Flutterwave payment initialization failed: ' . $response->json('message'));
    }

    /**
     * Verify payment status
     */
    public function verifyPayment(string $reference, string $gateway = 'paystack'): array
    {
        if ($gateway === 'paystack') {
            return $this->verifyPaystackPayment($reference);
        } elseif ($gateway === 'flutterwave') {
            return $this->verifyFlutterwavePayment($reference);
        }

        throw new \Exception('Unknown payment gateway');
    }

    protected function verifyPaystackPayment(string $reference): array
    {
        $paystackSecretKey = setting('payment_paystack_secret');
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $paystackSecretKey,
        ])->get('https://api.paystack.co/transaction/verify/' . $reference);

        if ($response->successful()) {
            $data = $response->json('data');
            
            return [
                'success' => $data['status'] === 'success',
                'status' => $data['status'],
                'amount' => $data['amount'] / 100, // Convert from kobo
                'reference' => $data['reference'],
                'gateway_response' => $data['gateway_response'],
                'paid_at' => $data['paid_at'],
            ];
        }

        return [
            'success' => false,
            'message' => $response->json('message'),
        ];
    }

    protected function verifyFlutterwavePayment(string $txRef): array
    {
        $flutterwaveSecretKey = setting('payment_flutterwave_secret');
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $flutterwaveSecretKey,
        ])->get('https://api.flutterwave.com/v3/transactions/' . $txRef . '/verify');

        if ($response->successful()) {
            $data = $response->json('data');
            
            return [
                'success' => $data['status'] === 'successful',
                'status' => $data['status'],
                'amount' => $data['amount'],
                'tx_ref' => $data['tx_ref'],
                'currency' => $data['currency'],
                'created_at' => $data['created_at'],
            ];
        }

        return [
            'success' => false,
            'message' => $response->json('message'),
        ];
    }

    /**
     * Process USSD payment (Nigerian banks)
     */
    public function initiateUSSD(RentPayment $payment, string $bankCode): array
    {
        // USSD payment flow for Nigerian banks
        // This would integrate with bank USSD APIs
        $banks = [
            '044' => 'Access Bank',
            '050' => 'Ecobank',
            '070' => 'Fidelity Bank',
            '011' => 'First Bank',
            '214' => 'First City Monument Bank',
            '058' => 'Guaranty Trust Bank',
            '030' => 'Heritage Bank',
            '301' => 'Jaiz Bank',
            '082' => 'Keystone Bank',
            '526' => 'Parallex Bank',
            '076' => 'Polaris Bank',
            '101' => 'Providus Bank',
            '221' => 'Stanbic IBTC Bank',
            '068' => 'Standard Chartered Bank',
            '232' => 'Sterling Bank',
            '100' => 'Suntrust Bank',
            '032' => 'Union Bank',
            '033' => 'United Bank For Africa',
            '215' => 'Unity Bank',
            '035' => 'Wema Bank',
            '057' => 'Zenith Bank',
        ];

        if (!isset($banks[$bankCode])) {
            throw new \Exception('Invalid bank code');
        }

        // Generate USSD code or payment link
        // This would be bank-specific implementation
        return [
            'success' => true,
            'bank_name' => $banks[$bankCode],
            'ussd_code' => '*901*' . $payment->id . '#', // Example format
            'instructions' => "Dial the USSD code on your phone to complete payment",
        ];
    }

    /**
     * Get Nigerian banks list
     */
    public function getNigerianBanks(): array
    {
        return [
            ['code' => '044', 'name' => 'Access Bank'],
            ['code' => '050', 'name' => 'Ecobank'],
            ['code' => '070', 'name' => 'Fidelity Bank'],
            ['code' => '011', 'name' => 'First Bank'],
            ['code' => '214', 'name' => 'First City Monument Bank'],
            ['code' => '058', 'name' => 'Guaranty Trust Bank'],
            ['code' => '030', 'name' => 'Heritage Bank'],
            ['code' => '301', 'name' => 'Jaiz Bank'],
            ['code' => '082', 'name' => 'Keystone Bank'],
            ['code' => '526', 'name' => 'Parallex Bank'],
            ['code' => '076', 'name' => 'Polaris Bank'],
            ['code' => '101', 'name' => 'Providus Bank'],
            ['code' => '221', 'name' => 'Stanbic IBTC Bank'],
            ['code' => '068', 'name' => 'Standard Chartered Bank'],
            ['code' => '232', 'name' => 'Sterling Bank'],
            ['code' => '100', 'name' => 'Suntrust Bank'],
            ['code' => '032', 'name' => 'Union Bank'],
            ['code' => '033', 'name' => 'United Bank For Africa'],
            ['code' => '215', 'name' => 'Unity Bank'],
            ['code' => '035', 'name' => 'Wema Bank'],
            ['code' => '057', 'name' => 'Zenith Bank'],
        ];
    }
}

