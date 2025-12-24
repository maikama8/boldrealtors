<?php

namespace Botble\RealEstate\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\RealEstate\Models\RentPayment;
use Botble\RealEstate\Services\NigerianPaymentService;
use Botble\RealEstate\Services\NigerianCreditBureauService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RentPaymentController extends BaseController
{
    public function initiatePayment(Request $request): BaseHttpResponse
    {
        $request->validate([
            'payment_id' => 'required|exists:re_rent_payments,id',
            'payment_method' => ['required', 'in:paystack,flutterwave,bank_transfer,ussd'],
            'bank_code' => 'required_if:payment_method,ussd|string',
        ]);

        $account = Auth::guard('account')->user();
        $payment = RentPayment::where('id', $request->input('payment_id'))
            ->where('account_id', $account->id)
            ->firstOrFail();

        if ($payment->status === 'paid') {
            return $this->httpResponse()
                ->setError()
                ->setMessage('This payment has already been completed.');
        }

        $paymentService = app(NigerianPaymentService::class);

        try {
            if ($request->input('payment_method') === 'paystack') {
                $result = $paymentService->processPaystackPayment($payment, $request->all());
            } elseif ($request->input('payment_method') === 'flutterwave') {
                $result = $paymentService->processFlutterwavePayment($payment, $request->all());
            } elseif ($request->input('payment_method') === 'ussd') {
                $result = $paymentService->initiateUSSD($payment, $request->input('bank_code'));
            } else {
                // Bank transfer - generate payment details
                $result = [
                    'success' => true,
                    'type' => 'bank_transfer',
                    'account_number' => setting('payment_bank_account_number'),
                    'account_name' => setting('payment_bank_account_name'),
                    'bank_name' => setting('payment_bank_name'),
                    'reference' => 'RENT_' . $payment->id,
                ];
            }

            return $this->httpResponse()
                ->setData($result);
        } catch (\Exception $e) {
            return $this->httpResponse()
                ->setError()
                ->setMessage($e->getMessage());
        }
    }

    public function callback(Request $request): BaseHttpResponse
    {
        $reference = $request->input('reference') ?? $request->input('tx_ref');
        $gateway = $request->input('gateway', 'paystack');

        if (!$reference) {
            return $this->httpResponse()
                ->setError()
                ->setMessage('Payment reference not provided');
        }

        $paymentService = app(NigerianPaymentService::class);
        $verification = $paymentService->verifyPayment($reference, $gateway);

        if ($verification['success']) {
            // Find payment by reference in metadata or payment reference field
            $payment = RentPayment::where('payment_reference', $reference)
                ->orWhere('payment_reference', 'like', '%' . $reference . '%')
                ->first();

            if ($payment) {
                $payment->update([
                    'status' => 'paid',
                    'payment_date' => now(),
                    'payment_method' => $gateway,
                    'payment_reference' => $reference,
                ]);

                // Report to credit bureau
                $creditBureauService = app(NigerianCreditBureauService::class);
                $creditBureauService->reportPayment($payment);

                return $this->httpResponse()
                    ->setMessage('Payment completed successfully!')
                    ->setData(['payment' => $payment]);
            }
        }

        return $this->httpResponse()
            ->setError()
            ->setMessage('Payment verification failed');
    }

    public function getBanks(): BaseHttpResponse
    {
        $paymentService = app(NigerianPaymentService::class);
        $banks = $paymentService->getNigerianBanks();

        return $this->httpResponse()
            ->setData(['banks' => $banks]);
    }

    public function index(Request $request): BaseHttpResponse
    {
        $account = Auth::guard('account')->user();
        
        $payments = RentPayment::where('account_id', $account->id)
            ->with(['property', 'rentalApplication'])
            ->orderBy('due_date', 'desc')
            ->paginate(20);

        return $this->httpResponse()
            ->setData(['payments' => $payments]);
    }
}

