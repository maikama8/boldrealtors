<?php

namespace Botble\RealEstate\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\RealEstate\Services\BuyAbilityService;
use Illuminate\Http\Request;

class BuyAbilityController extends BaseController
{
    public function calculate(Request $request): BaseHttpResponse
    {
        $request->validate([
            'calculation_type' => ['required', 'in:monthly_payment,max_budget'],
            'monthly_payment' => 'required_if:calculation_type,monthly_payment|numeric|min:0',
            'max_budget' => 'required_if:calculation_type,max_budget|numeric|min:0',
            'interest_rate' => 'required|numeric|min:0|max:100',
            'loan_term' => 'required|integer|min:1|max:50',
            'down_payment_percent' => 'nullable|numeric|min:0|max:100',
            'property_tax_rate' => 'nullable|numeric|min:0',
            'home_insurance_annual' => 'nullable|numeric|min:0',
            'hoa_fees' => 'nullable|numeric|min:0',
        ]);

        $service = app(BuyAbilityService::class);

        $interestRate = (float) $request->input('interest_rate');
        $loanTerm = (int) $request->input('loan_term');
        $downPaymentPercent = (float) ($request->input('down_payment_percent', 20));
        $propertyTaxRate = (float) ($request->input('property_tax_rate', 0));
        $homeInsuranceAnnual = (float) ($request->input('home_insurance_annual', 0));
        $hoaFees = (float) ($request->input('hoa_fees', 0));

        if ($request->input('calculation_type') === 'monthly_payment') {
            $monthlyPayment = (float) $request->input('monthly_payment');
            $result = $service->calculateFromMonthlyPayment(
                $monthlyPayment,
                $interestRate,
                $loanTerm,
                $downPaymentPercent,
                $propertyTaxRate,
                $homeInsuranceAnnual,
                $hoaFees
            );
        } else {
            $maxBudget = (float) $request->input('max_budget');
            $result = $service->calculateFromMaxBudget(
                $maxBudget,
                $interestRate,
                $loanTerm,
                $downPaymentPercent,
                $propertyTaxRate,
                $homeInsuranceAnnual,
                $hoaFees
            );
        }

        return $this->httpResponse()
            ->setData($result);
    }
}

