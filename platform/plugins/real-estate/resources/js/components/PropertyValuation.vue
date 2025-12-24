<template>
    <div class="property-valuation">
        <div class="valuation-header">
            <h4>Property Estimate</h4>
            <button
                class="btn btn-sm btn-link"
                @click="recalculate"
                :disabled="loading"
            >
                <i class="ti ti-refresh" :class="{ 'spinning': loading }"></i> Recalculate
            </button>
        </div>

        <div v-if="loading" class="loading-state">
            <div class="spinner-border text-primary"></div>
            <p>Calculating valuation...</p>
        </div>

        <div v-else-if="valuation" class="valuation-content">
            <div class="estimate-value">
                <div class="value-amount">
                    ₦{{ formatPrice(valuation.estimated_value) }}
                </div>
                <div class="value-label">Estimated Value</div>
                <div class="confidence-badge" :class="confidenceClass">
                    {{ confidenceText }} Confidence
                </div>
            </div>

            <div class="valuation-details">
                <div class="detail-item">
                    <span class="label">Price per m²:</span>
                    <span class="value">₦{{ formatPrice(valuation.price_per_square_foot) }}</span>
                </div>
                <div class="detail-item">
                    <span class="label">Rent Estimate:</span>
                    <span class="value">₦{{ formatPrice(valuation.rent_estimate) }}/month</span>
                </div>
                <div class="detail-item" v-if="valuation.valuation_data">
                    <span class="label">Based on:</span>
                    <span class="value">
                        {{ valuation.valuation_data.similar_properties_count || 0 }} similar properties
                    </span>
                </div>
            </div>

            <div class="valuation-footer">
                <small class="text-muted">
                    Last updated: {{ formatDate(valuation.last_calculated_at) }}
                </small>
                <small class="text-muted disclaimer">
                    * This is an estimate based on comparable properties and may not reflect the actual market value.
                </small>
            </div>
        </div>

        <div v-else class="no-valuation">
            <p>Valuation not available</p>
            <button class="btn btn-primary btn-sm" @click="recalculate">
                Calculate Now
            </button>
        </div>
    </div>
</template>

<script>
export default {
    name: 'PropertyValuation',
    props: {
        propertyId: {
            type: [Number, String],
            required: true
        }
    },
    data() {
        return {
            valuation: null,
            loading: false
        }
    },
    computed: {
        confidenceClass() {
            if (!this.valuation?.valuation_data?.confidence_score) return 'low';
            const score = this.valuation.valuation_data.confidence_score;
            if (score >= 0.7) return 'high';
            if (score >= 0.4) return 'medium';
            return 'low';
        },
        confidenceText() {
            if (!this.valuation?.valuation_data?.confidence_score) return 'Low';
            const score = this.valuation.valuation_data.confidence_score;
            if (score >= 0.7) return 'High';
            if (score >= 0.4) return 'Medium';
            return 'Low';
        }
    },
    mounted() {
        this.fetchValuation();
    },
    methods: {
        async fetchValuation(recalculate = false) {
            this.loading = true;
            try {
                const response = await this.$httpClient.make().get(`/property/${this.propertyId}/valuation`, {
                    params: { recalculate }
                });
                this.valuation = response.data.valuation;
            } catch (error) {
                console.error('Failed to fetch valuation:', error);
            } finally {
                this.loading = false;
            }
        },
        recalculate() {
            this.fetchValuation(true);
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
.property-valuation {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.valuation-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.valuation-header h4 {
    margin: 0;
    font-size: 18px;
}

.loading-state {
    text-align: center;
    padding: 40px 20px;
}

.estimate-value {
    text-align: center;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e0e0e0;
}

.value-amount {
    font-size: 32px;
    font-weight: bold;
    color: #4285F4;
    margin-bottom: 5px;
}

.value-label {
    font-size: 14px;
    color: #666;
    margin-bottom: 10px;
}

.confidence-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.confidence-badge.high {
    background: #d4edda;
    color: #155724;
}

.confidence-badge.medium {
    background: #fff3cd;
    color: #856404;
}

.confidence-badge.low {
    background: #f8d7da;
    color: #721c24;
}

.valuation-details {
    margin-bottom: 20px;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-item .label {
    color: #666;
}

.detail-item .value {
    font-weight: 500;
    color: #333;
}

.valuation-footer {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #e0e0e0;
}

.disclaimer {
    display: block;
    margin-top: 10px;
    font-size: 11px;
    line-height: 1.4;
}

.no-valuation {
    text-align: center;
    padding: 40px 20px;
}

.spinning {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>

