<?php

namespace Botble\RealEstate\Services;

use Botble\RealEstate\Models\RentPayment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NigerianCreditBureauService
{
    /**
     * Nigerian credit bureaus
     */
    protected array $bureaus = [
        'crc' => [
            'name' => 'CRC Credit',
            'api_url' => env('CRC_CREDIT_API_URL', 'https://api.crccredit.com'),
            'api_key' => env('CRC_CREDIT_API_KEY'),
        ],
        'firstcentral' => [
            'name' => 'FirstCentral Credit Bureau',
            'api_url' => env('FIRSTCENTRAL_API_URL', 'https://api.firstcentral.com'),
            'api_key' => env('FIRSTCENTRAL_API_KEY'),
        ],
        'creditregistry' => [
            'name' => 'CreditRegistry',
            'api_url' => env('CREDITREGISTRY_API_URL', 'https://api.creditregistry.com'),
            'api_key' => env('CREDITREGISTRY_API_KEY'),
        ],
    ];

    /**
     * Report rent payment to credit bureaus
     */
    public function reportPayment(RentPayment $payment, array $bureaus = ['crc', 'firstcentral']): array
    {
        $results = [];

        foreach ($bureaus as $bureauKey) {
            if (!isset($this->bureaus[$bureauKey])) {
                continue;
            }

            try {
                $result = $this->reportToBureau($payment, $bureauKey);
                $results[$bureauKey] = $result;

                if ($result['success']) {
                    $payment->update([
                        'reported_to_credit_bureau' => true,
                        'reported_at' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Failed to report payment to {$bureauKey}: " . $e->getMessage());
                $results[$bureauKey] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Report payment to a specific bureau
     */
    protected function reportToBureau(RentPayment $payment, string $bureauKey): array
    {
        $bureau = $this->bureaus[$bureauKey];

        if (!$bureau['api_key']) {
            throw new \Exception("API key not configured for {$bureau['name']}");
        }

        // Get account information
        $account = $payment->account;
        $property = $payment->property;

        // Prepare data for credit bureau
        $data = [
            'bvn' => $account->bvn ?? null, // Bank Verification Number
            'nin' => $account->nin ?? null, // National Identification Number
            'first_name' => $account->first_name ?? $payment->account->name,
            'last_name' => $account->last_name ?? '',
            'email' => $account->email,
            'phone' => $account->phone,
            'payment_amount' => $payment->amount,
            'payment_date' => $payment->payment_date->format('Y-m-d'),
            'due_date' => $payment->due_date->format('Y-m-d'),
            'status' => $payment->status === 'paid' ? 'on_time' : 'late',
            'property_address' => $property->location,
            'currency' => $payment->currency ?? 'NGN',
        ];

        // Make API call to credit bureau
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $bureau['api_key'],
            'Content-Type' => 'application/json',
        ])->post($bureau['api_url'] . '/api/rent-payments', $data);

        if ($response->successful()) {
            return [
                'success' => true,
                'bureau' => $bureau['name'],
                'reference' => $response->json('reference_id'),
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('message') ?? 'Unknown error',
            'status' => $response->status(),
        ];
    }

    /**
     * Batch report multiple payments
     */
    public function batchReportPayments(array $paymentIds, array $bureaus = ['crc', 'firstcentral']): array
    {
        $payments = RentPayment::whereIn('id', $paymentIds)
            ->where('status', 'paid')
            ->where('reported_to_credit_bureau', false)
            ->get();

        $results = [];

        foreach ($payments as $payment) {
            $results[$payment->id] = $this->reportPayment($payment, $bureaus);
        }

        return $results;
    }

    /**
     * Get credit report for an account
     */
    public function getCreditReport($account, string $bureauKey = 'crc'): ?array
    {
        $bureau = $this->bureaus[$bureauKey] ?? null;

        if (!$bureau || !$bureau['api_key']) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $bureau['api_key'],
            ])->get($bureau['api_url'] . '/api/credit-report', [
                'bvn' => $account->bvn ?? null,
                'email' => $account->email,
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error("Failed to get credit report: " . $e->getMessage());
        }

        return null;
    }
}

