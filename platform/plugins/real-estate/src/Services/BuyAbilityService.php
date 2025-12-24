<?php

namespace Botble\RealEstate\Services;

use Botble\RealEstate\Models\Property;

class BuyAbilityService
{
    /**
     * Calculate what a user can afford based on their desired monthly payment
     */
    public function calculateFromMonthlyPayment(
        float $monthlyPayment,
        float $interestRate,
        int $loanTermYears = 30,
        float $downPaymentPercent = 20,
        float $propertyTaxRate = 0,
        float $homeInsuranceAnnual = 0,
        float $hoaFees = 0
    ): array {
        $monthlyRate = $interestRate / 100 / 12;
        $numPayments = $loanTermYears * 12;
        
        // Monthly costs excluding principal & interest
        $monthlyOtherCosts = ($propertyTaxRate / 12) + ($homeInsuranceAnnual / 12) + $hoaFees;
        
        // Available for principal & interest
        $availableForPI = $monthlyPayment - $monthlyOtherCosts;
        
        if ($availableForPI <= 0) {
            return [
                'affordable_price' => 0,
                'loan_amount' => 0,
                'down_payment' => 0,
            ];
        }
        
        // Calculate loan amount from monthly payment
        if ($monthlyRate > 0) {
            $loanAmount = $availableForPI * 
                (pow(1 + $monthlyRate, $numPayments) - 1) / 
                ($monthlyRate * pow(1 + $monthlyRate, $numPayments));
        } else {
            $loanAmount = $availableForPI * $numPayments;
        }
        
        // Calculate home price from loan amount and down payment
        $downPaymentPercentDecimal = $downPaymentPercent / 100;
        $affordablePrice = $loanAmount / (1 - $downPaymentPercentDecimal);
        $downPayment = $affordablePrice * $downPaymentPercentDecimal;
        
        return [
            'affordable_price' => round($affordablePrice, 2),
            'loan_amount' => round($loanAmount, 2),
            'down_payment' => round($downPayment, 2),
            'monthly_payment' => round($monthlyPayment, 2),
            'principal_interest' => round($availableForPI, 2),
            'property_tax' => round($propertyTaxRate / 12, 2),
            'home_insurance' => round($homeInsuranceAnnual / 12, 2),
            'hoa_fees' => round($hoaFees, 2),
        ];
    }

    /**
     * Calculate monthly payment from maximum budget
     */
    public function calculateFromMaxBudget(
        float $maxBudget,
        float $interestRate,
        int $loanTermYears = 30,
        float $downPaymentPercent = 20,
        float $propertyTaxRate = 0,
        float $homeInsuranceAnnual = 0,
        float $hoaFees = 0
    ): array {
        $downPayment = $maxBudget * ($downPaymentPercent / 100);
        $loanAmount = $maxBudget - $downPayment;
        
        $monthlyRate = $interestRate / 100 / 12;
        $numPayments = $loanTermYears * 12;
        
        // Calculate monthly principal & interest
        if ($monthlyRate > 0) {
            $monthlyPI = $loanAmount * 
                ($monthlyRate * pow(1 + $monthlyRate, $numPayments)) / 
                (pow(1 + $monthlyRate, $numPayments) - 1);
        } else {
            $monthlyPI = $loanAmount / $numPayments;
        }
        
        $monthlyOtherCosts = ($propertyTaxRate / 12) + ($homeInsuranceAnnual / 12) + $hoaFees;
        $totalMonthlyPayment = $monthlyPI + $monthlyOtherCosts;
        
        return [
            'max_budget' => round($maxBudget, 2),
            'loan_amount' => round($loanAmount, 2),
            'down_payment' => round($downPayment, 2),
            'monthly_payment' => round($totalMonthlyPayment, 2),
            'principal_interest' => round($monthlyPI, 2),
            'property_tax' => round($propertyTaxRate / 12, 2),
            'home_insurance' => round($homeInsuranceAnnual / 12, 2),
            'hoa_fees' => round($hoaFees, 2),
        ];
    }

    /**
     * Filter properties within BuyAbility range
     */
    public function filterPropertiesByBuyAbility(
        $properties,
        float $affordablePriceMin,
        float $affordablePriceMax
    ) {
        return $properties->filter(function ($property) use ($affordablePriceMin, $affordablePriceMax) {
            $price = (float) $property->price;
            return $price >= $affordablePriceMin && $price <= $affordablePriceMax;
        });
    }
}

