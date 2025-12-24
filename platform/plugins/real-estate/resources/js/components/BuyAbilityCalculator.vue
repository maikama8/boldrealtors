<template>
    <div class="buyability-calculator">
        <h4>BuyAbility Calculator</h4>
        <p class="subtitle">Find out what you can afford</p>

        <div class="calculator-tabs">
            <button
                class="tab-btn"
                :class="{ active: calculationType === 'monthly_payment' }"
                @click="calculationType = 'monthly_payment'"
            >
                From Monthly Payment
            </button>
            <button
                class="tab-btn"
                :class="{ active: calculationType === 'max_budget' }"
                @click="calculationType = 'max_budget'"
            >
                From Max Budget
            </button>
        </div>

        <form @submit.prevent="calculate" class="calculator-form">
            <div v-if="calculationType === 'monthly_payment'" class="form-group">
                <label>Desired Monthly Payment (₦)</label>
                <input
                    v-model.number="monthlyPayment"
                    type="number"
                    class="form-control"
                    placeholder="e.g., 500000"
                    required
                    min="0"
                />
            </div>

            <div v-else class="form-group">
                <label>Maximum Budget (₦)</label>
                <input
                    v-model.number="maxBudget"
                    type="number"
                    class="form-control"
                    placeholder="e.g., 50000000"
                    required
                    min="0"
                />
            </div>

            <div class="form-group">
                <label>Interest Rate (%)</label>
                <input
                    v-model.number="interestRate"
                    type="number"
                    class="form-control"
                    placeholder="e.g., 15"
                    required
                    min="0"
                    max="100"
                    step="0.1"
                />
            </div>

            <div class="form-group">
                <label>Loan Term (Years)</label>
                <select v-model.number="loanTerm" class="form-control" required>
                    <option value="5">5 years</option>
                    <option value="10">10 years</option>
                    <option value="15">15 years</option>
                    <option value="20">20 years</option>
                    <option value="25">25 years</option>
                    <option value="30">30 years</option>
                </select>
            </div>

            <div class="form-group">
                <label>Down Payment (%)</label>
                <input
                    v-model.number="downPaymentPercent"
                    type="number"
                    class="form-control"
                    placeholder="e.g., 20"
                    required
                    min="0"
                    max="100"
                />
            </div>

            <div class="form-group">
                <label>Annual Property Tax (₦)</label>
                <input
                    v-model.number="propertyTaxRate"
                    type="number"
                    class="form-control"
                    placeholder="e.g., 50000"
                    min="0"
                />
            </div>

            <div class="form-group">
                <label>Annual Home Insurance (₦)</label>
                <input
                    v-model.number="homeInsuranceAnnual"
                    type="number"
                    class="form-control"
                    placeholder="e.g., 100000"
                    min="0"
                />
            </div>

            <div class="form-group">
                <label>Monthly HOA Fees (₦)</label>
                <input
                    v-model.number="hoaFees"
                    type="number"
                    class="form-control"
                    placeholder="e.g., 10000"
                    min="0"
                />
            </div>

            <button
                type="submit"
                class="btn btn-primary btn-block"
                :disabled="loading"
            >
                <span v-if="loading" class="spinner-border spinner-border-sm"></span>
                <span v-else>Calculate</span>
            </button>
        </form>

        <div v-if="results" class="results">
            <h5>Results</h5>
            
            <div v-if="calculationType === 'monthly_payment'" class="result-card primary">
                <div class="result-label">You Can Afford</div>
                <div class="result-value">₦{{ formatPrice(results.affordable_price) }}</div>
            </div>

            <div v-else class="result-card primary">
                <div class="result-label">Monthly Payment</div>
                <div class="result-value">₦{{ formatPrice(results.monthly_payment) }}</div>
            </div>

            <div class="breakdown">
                <div class="breakdown-item">
                    <span>Loan Amount:</span>
                    <strong>₦{{ formatPrice(results.loan_amount) }}</strong>
                </div>
                <div class="breakdown-item">
                    <span>Down Payment:</span>
                    <strong>₦{{ formatPrice(results.down_payment) }}</strong>
                </div>
                <div class="breakdown-item">
                    <span>Principal & Interest:</span>
                    <strong>₦{{ formatPrice(results.principal_interest) }}/month</strong>
                </div>
                <div class="breakdown-item">
                    <span>Property Tax:</span>
                    <strong>₦{{ formatPrice(results.property_tax) }}/month</strong>
                </div>
                <div class="breakdown-item">
                    <span>Home Insurance:</span>
                    <strong>₦{{ formatPrice(results.home_insurance) }}/month</strong>
                </div>
                <div class="breakdown-item">
                    <span>HOA Fees:</span>
                    <strong>₦{{ formatPrice(results.hoa_fees) }}/month</strong>
                </div>
            </div>

            <button
                class="btn btn-secondary btn-block mt-3"
                @click="searchProperties"
            >
                Search Properties in This Range
            </button>
        </div>
    </div>
</template>

<script>
export default {
    name: 'BuyAbilityCalculator',
    data() {
        return {
            calculationType: 'monthly_payment',
            monthlyPayment: null,
            maxBudget: null,
            interestRate: 15,
            loanTerm: 30,
            downPaymentPercent: 20,
            propertyTaxRate: 0,
            homeInsuranceAnnual: 0,
            hoaFees: 0,
            results: null,
            loading: false
        }
    },
    methods: {
        async calculate() {
            this.loading = true;
            try {
                const response = await this.$httpClient.make().post('/buyability/calculate', {
                    calculation_type: this.calculationType,
                    monthly_payment: this.monthlyPayment,
                    max_budget: this.maxBudget,
                    interest_rate: this.interestRate,
                    loan_term: this.loanTerm,
                    down_payment_percent: this.downPaymentPercent,
                    property_tax_rate: this.propertyTaxRate,
                    home_insurance_annual: this.homeInsuranceAnnual,
                    hoa_fees: this.hoaFees
                });

                this.results = response.data;
                this.$emit('calculated', this.results);
            } catch (error) {
                console.error('Calculation error:', error);
                alert('Failed to calculate. Please check your inputs.');
            } finally {
                this.loading = false;
            }
        },
        searchProperties() {
            if (!this.results) return;

            const minPrice = this.calculationType === 'monthly_payment' 
                ? this.results.affordable_price * 0.9 
                : this.results.max_budget * 0.9;
            
            const maxPrice = this.calculationType === 'monthly_payment'
                ? this.results.affordable_price * 1.1
                : this.results.max_budget * 1.1;

            // Navigate to properties page with filters
            const url = new URL('/properties', window.location.origin);
            url.searchParams.set('min_price', Math.round(minPrice));
            url.searchParams.set('max_price', Math.round(maxPrice));
            window.location.href = url.toString();
        },
        formatPrice(price) {
            if (!price) return '0';
            return new Intl.NumberFormat('en-NG', {
                maximumFractionDigits: 0
            }).format(price);
        }
    }
}
</script>

<style scoped>
.buyability-calculator {
    background: white;
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.subtitle {
    color: #666;
    margin-bottom: 20px;
}

.calculator-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    border-bottom: 2px solid #e0e0e0;
}

.tab-btn {
    padding: 10px 20px;
    border: none;
    background: transparent;
    color: #666;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s;
}

.tab-btn:hover {
    color: #4285F4;
}

.tab-btn.active {
    color: #4285F4;
    border-bottom-color: #4285F4;
    font-weight: 600;
}

.calculator-form {
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
}

.results {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 2px solid #e0e0e0;
}

.result-card {
    background: linear-gradient(135deg, #4285F4 0%, #34a853 100%);
    color: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    margin-bottom: 20px;
}

.result-label {
    font-size: 14px;
    opacity: 0.9;
    margin-bottom: 5px;
}

.result-value {
    font-size: 32px;
    font-weight: bold;
}

.breakdown {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
}

.breakdown-item {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #e0e0e0;
}

.breakdown-item:last-child {
    border-bottom: none;
}

.breakdown-item strong {
    color: #4285F4;
}
</style>

