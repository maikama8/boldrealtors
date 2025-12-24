<template>
    <div class="rent-payment-form">
        <h4>Pay Rent</h4>

        <div v-if="payment" class="payment-details">
            <div class="detail-card">
                <div class="detail-row">
                    <span>Property:</span>
                    <strong>{{ payment.property?.name }}</strong>
                </div>
                <div class="detail-row">
                    <span>Amount Due:</span>
                    <strong class="amount">₦{{ formatPrice(payment.amount) }}</strong>
                </div>
                <div class="detail-row">
                    <span>Due Date:</span>
                    <strong>{{ formatDate(payment.due_date) }}</strong>
                </div>
            </div>
        </div>

        <form @submit.prevent="processPayment" class="payment-form">
            <div class="form-group">
                <label>Payment Method</label>
                <select v-model="paymentMethod" class="form-control" required>
                    <option value="">Select payment method</option>
                    <option value="paystack">Paystack (Card, Bank Transfer)</option>
                    <option value="flutterwave">Flutterwave (Card, Bank, USSD, Mobile Money)</option>
                    <option value="bank_transfer">Direct Bank Transfer</option>
                    <option value="ussd">USSD Payment</option>
                </select>
            </div>

            <div v-if="paymentMethod === 'ussd'" class="form-group">
                <label>Select Bank</label>
                <select v-model="selectedBank" class="form-control" required>
                    <option value="">Select your bank</option>
                    <option
                        v-for="bank in banks"
                        :key="bank.code"
                        :value="bank.code"
                    >
                        {{ bank.name }}
                    </option>
                </select>
            </div>

            <button
                type="submit"
                class="btn btn-primary btn-block"
                :disabled="loading || !paymentMethod"
            >
                <span v-if="loading" class="spinner-border spinner-border-sm"></span>
                <span v-else>Proceed to Payment</span>
            </button>
        </form>

        <div v-if="paymentResult" class="payment-result">
            <div v-if="paymentResult.success" class="alert alert-success">
                <h5>Payment Initiated</h5>
                <div v-if="paymentResult.authorization_url">
                    <p>Redirecting to payment page...</p>
                    <a
                        :href="paymentResult.authorization_url"
                        class="btn btn-primary"
                        target="_blank"
                    >
                        Complete Payment
                    </a>
                </div>
                <div v-else-if="paymentResult.ussd_code">
                    <p><strong>USSD Code:</strong> {{ paymentResult.ussd_code }}</p>
                    <p class="instructions">{{ paymentResult.instructions }}</p>
                </div>
                <div v-else-if="paymentResult.account_number">
                    <p><strong>Bank Account Details:</strong></p>
                    <p>Account Number: {{ paymentResult.account_number }}</p>
                    <p>Account Name: {{ paymentResult.account_name }}</p>
                    <p>Bank: {{ paymentResult.bank_name }}</p>
                    <p>Reference: {{ paymentResult.reference }}</p>
                </div>
            </div>
            <div v-else class="alert alert-danger">
                <p>{{ paymentResult.message || 'Payment failed. Please try again.' }}</p>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'RentPaymentForm',
    props: {
        paymentId: {
            type: [Number, String],
            required: true
        }
    },
    data() {
        return {
            payment: null,
            paymentMethod: '',
            selectedBank: '',
            banks: [],
            paymentResult: null,
            loading: false
        }
    },
    mounted() {
        this.loadBanks();
        // Load payment details if needed
    },
    methods: {
        async loadBanks() {
            try {
                const response = await this.$httpClient.make().get('/rent-payments/banks');
                this.banks = response.data.banks || [];
            } catch (error) {
                console.error('Failed to load banks:', error);
            }
        },
        async processPayment() {
            this.loading = true;
            this.paymentResult = null;

            try {
                const response = await this.$httpClient.make().post('/rent-payments/initiate', {
                    payment_id: this.paymentId,
                    payment_method: this.paymentMethod,
                    bank_code: this.selectedBank
                });

                this.paymentResult = response.data;

                // Auto-redirect for Paystack/Flutterwave
                if (this.paymentResult.authorization_url) {
                    window.location.href = this.paymentResult.authorization_url;
                } else if (this.paymentResult.link) {
                    window.location.href = this.paymentResult.link;
                }
            } catch (error) {
                this.paymentResult = {
                    success: false,
                    message: error.response?.data?.message || 'Payment initiation failed'
                };
            } finally {
                this.loading = false;
            }
        },
        formatPrice(price) {
            if (!price) return '0';
            return new Intl.NumberFormat('en-NG', {
                maximumFractionDigits: 0
            }).format(price);
        },
        formatDate(dateString) {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleDateString('en-NG', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
    }
}
</script>

<style scoped>
.rent-payment-form {
    background: white;
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.payment-details {
    margin-bottom: 24px;
}

.detail-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #4285F4;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #e0e0e0;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-row .amount {
    color: #4285F4;
    font-size: 18px;
}

.payment-form {
    margin-top: 20px;
}

.payment-result {
    margin-top: 20px;
}

.instructions {
    background: #fff3cd;
    padding: 10px;
    border-radius: 4px;
    margin-top: 10px;
    font-size: 14px;
}
</style>

