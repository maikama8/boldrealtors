<template>
    <div class="price-history-chart">
        <div class="chart-header">
            <h4>Price History</h4>
            <select v-model="selectedMonths" @change="fetchHistory" class="form-control form-control-sm">
                <option value="6">Last 6 Months</option>
                <option value="12">Last 12 Months</option>
                <option value="24">Last 24 Months</option>
            </select>
        </div>

        <div v-if="loading" class="loading-state">
            <div class="spinner-border text-primary"></div>
            <p>Loading price history...</p>
        </div>

        <div v-else-if="chartData" class="chart-content">
            <!-- Price Change Summary -->
            <div class="price-summary" v-if="priceChange">
                <div class="summary-item">
                    <span class="label">Price Change:</span>
                    <span 
                        class="value"
                        :class="{
                            'text-success': priceChange.direction === 'up',
                            'text-danger': priceChange.direction === 'down',
                            'text-muted': priceChange.direction === 'stable'
                        }"
                    >
                        {{ formatPriceChange(priceChange) }}
                    </span>
                </div>
            </div>

            <!-- Chart Container -->
            <div class="chart-container">
                <canvas ref="chartCanvas"></canvas>
            </div>

            <!-- Price per Sqft Trend -->
            <div v-if="pricePerSqftTrend && pricePerSqftTrend.length > 0" class="sqft-trend">
                <h5>Price per Square Foot Trend</h5>
                <div class="trend-list">
                    <div 
                        v-for="(item, index) in pricePerSqftTrend" 
                        :key="index"
                        class="trend-item"
                    >
                        <span class="date">{{ formatDate(item.date) }}</span>
                        <span class="price">₦{{ formatPrice(item.price_per_sqft) }}/sqft</span>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="no-data">
            <p>No price history available for this property.</p>
        </div>
    </div>
</template>

<script>
export default {
    name: 'PriceHistoryChart',
    props: {
        propertyId: {
            type: [Number, String],
            required: true
        }
    },
    data() {
        return {
            chartData: null,
            priceChange: null,
            pricePerSqftTrend: null,
            selectedMonths: 12,
            loading: false,
            chart: null
        }
    },
    mounted() {
        this.fetchHistory();
    },
    methods: {
        async fetchHistory() {
            this.loading = true;
            try {
                const response = await this.$httpClient.make().get(
                    `/property/${this.propertyId}/price-history?months=${this.selectedMonths}`
                );
                this.chartData = response.data.chart_data;
                this.priceChange = response.data.price_change;
                this.pricePerSqftTrend = response.data.price_per_sqft_trend;
                
                this.$nextTick(() => {
                    this.renderChart();
                });
            } catch (error) {
                console.error('Failed to fetch price history:', error);
            } finally {
                this.loading = false;
            }
        },
        renderChart() {
            if (!this.chartData || !this.chartData.prices.length) {
                return;
            }

            // Destroy existing chart
            if (this.chart) {
                this.chart.destroy();
            }

            // Simple chart using Canvas API (you can replace with Chart.js if available)
            const canvas = this.$refs.chartCanvas;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            const width = canvas.width = canvas.offsetWidth;
            const height = canvas.height = 300;

            // Clear canvas
            ctx.clearRect(0, 0, width, height);

            // Chart settings
            const padding = 40;
            const chartWidth = width - (padding * 2);
            const chartHeight = height - (padding * 2);

            // Find min/max prices
            const prices = this.chartData.prices;
            const minPrice = Math.min(...prices);
            const maxPrice = Math.max(...prices);
            const priceRange = maxPrice - minPrice || 1;

            // Draw axes
            ctx.strokeStyle = '#ddd';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(padding, padding);
            ctx.lineTo(padding, height - padding);
            ctx.lineTo(width - padding, height - padding);
            ctx.stroke();

            // Draw price line
            ctx.strokeStyle = '#4285F4';
            ctx.lineWidth = 2;
            ctx.beginPath();

            prices.forEach((price, index) => {
                const x = padding + (index / (prices.length - 1 || 1)) * chartWidth;
                const y = height - padding - ((price - minPrice) / priceRange) * chartHeight;
                
                if (index === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            });

            ctx.stroke();

            // Draw points
            ctx.fillStyle = '#4285F4';
            prices.forEach((price, index) => {
                const x = padding + (index / (prices.length - 1 || 1)) * chartWidth;
                const y = height - padding - ((price - minPrice) / priceRange) * chartHeight;
                
                ctx.beginPath();
                ctx.arc(x, y, 4, 0, Math.PI * 2);
                ctx.fill();
            });

            // Draw labels
            ctx.fillStyle = '#666';
            ctx.font = '12px Arial';
            ctx.textAlign = 'center';
            
            this.chartData.labels.forEach((label, index) => {
                const x = padding + (index / (prices.length - 1 || 1)) * chartWidth;
                ctx.fillText(label, x, height - padding + 20);
            });

            // Draw price labels
            ctx.textAlign = 'right';
            const priceSteps = 5;
            for (let i = 0; i <= priceSteps; i++) {
                const price = minPrice + (maxPrice - minPrice) * (i / priceSteps);
                const y = height - padding - (i / priceSteps) * chartHeight;
                ctx.fillText('₦' + this.formatPrice(price), padding - 10, y + 4);
            }
        },
        formatPrice(price) {
            return new Intl.NumberFormat('en-NG', {
                maximumFractionDigits: 0
            }).format(price);
        },
        formatPriceChange(change) {
            const sign = change.direction === 'up' ? '+' : '';
            return `${sign}${change.percentage}% (₦${this.formatPrice(Math.abs(change.amount))})`;
        },
        formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('en-NG', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }
    },
    beforeUnmount() {
        if (this.chart) {
            this.chart.destroy();
        }
    }
}
</script>

<style scoped>
.price-history-chart {
    background: white;
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.chart-header h4 {
    margin: 0;
}

.chart-content {
    margin-top: 20px;
}

.price-summary {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.summary-item .label {
    font-weight: 500;
    color: #666;
}

.summary-item .value {
    font-size: 18px;
    font-weight: bold;
}

.chart-container {
    margin: 20px 0;
    position: relative;
}

.chart-container canvas {
    width: 100%;
    height: 300px;
}

.sqft-trend {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e0e0e0;
}

.sqft-trend h5 {
    margin-bottom: 15px;
    font-size: 16px;
}

.trend-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.trend-item {
    display: flex;
    justify-content: space-between;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
}

.trend-item .date {
    color: #666;
}

.trend-item .price {
    font-weight: 600;
    color: #4285F4;
}

.loading-state,
.no-data {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}
</style>

