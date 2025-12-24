<?php

namespace Botble\RealEstate\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Illuminate\Http\Request;

class MortgageCalculatorController extends BaseController
{
    public function calculate(Request $request): BaseHttpResponse
    {
        $request->validate([
            'home_price' => 'required|numeric|min:0',
            'down_payment' => 'required|numeric|min:0',
            'interest_rate' => 'required|numeric|min:0|max:100',
            'loan_term' => 'required|integer|min:1|max:50',
            'property_tax' => 'nullable|numeric|min:0',
            'home_insurance' => 'nullable|numeric|min:0',
            'hoa_fees' => 'nullable|numeric|min:0',
        ]);

        $homePrice = (float) $request->input('home_price');
        $downPayment = (float) $request->input('down_payment');
        $interestRate = (float) $request->input('interest_rate') / 100; // Convert to decimal
        $loanTerm = (int) $request->input('loan_term'); // in years
        $propertyTax = (float) ($request->input('property_tax', 0) / 12); // Monthly
        $homeInsurance = (float) ($request->input('home_insurance', 0) / 12); // Monthly
        $hoaFees = (float) $request->input('hoa_fees', 0);

        $loanAmount = $homePrice - $downPayment;
        $monthlyRate = $interestRate / 12;
        $numPayments = $loanTerm * 12;

        // Calculate monthly principal and interest
        if ($monthlyRate > 0) {
            $monthlyPayment = $loanAmount * 
                ($monthlyRate * pow(1 + $monthlyRate, $numPayments)) / 
                (pow(1 + $monthlyRate, $numPayments) - 1);
        } else {
            $monthlyPayment = $loanAmount / $numPayments;
        }

        $totalMonthlyPayment = $monthlyPayment + $propertyTax + $homeInsurance + $hoaFees;
        $totalInterest = ($monthlyPayment * $numPayments) - $loanAmount;
        $totalCost = $totalMonthlyPayment * $numPayments + $downPayment;

        return $this->httpResponse()
            ->setData([
                'monthly_payment' => round($monthlyPayment, 2),
                'total_monthly_payment' => round($totalMonthlyPayment, 2),
                'principal_interest' => round($monthlyPayment, 2),
                'property_tax' => round($propertyTax, 2),
                'home_insurance' => round($homeInsurance, 2),
                'hoa_fees' => round($hoaFees, 2),
                'loan_amount' => round($loanAmount, 2),
                'total_interest' => round($totalInterest, 2),
                'total_cost' => round($totalCost, 2),
                'down_payment_percent' => $homePrice > 0 ? round(($downPayment / $homePrice) * 100, 2) : 0,
            ]);
    }
}

