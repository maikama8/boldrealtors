<template>
    <div class="property-comparison">
        <div class="comparison-header">
            <h4>Compare Properties</h4>
            <div class="comparison-actions">
                <button
                    class="btn btn-sm btn-primary"
                    @click="addToComparison"
                    :disabled="comparisonCount >= 4"
                >
                    <i class="ti ti-plus"></i> Add Property
                </button>
                <button
                    class="btn btn-sm btn-secondary"
                    @click="clearComparison"
                    v-if="comparisonCount > 0"
                >
                    <i class="ti ti-trash"></i> Clear All
                </button>
            </div>
        </div>

        <div v-if="properties.length === 0" class="empty-state">
            <p>No properties added to comparison</p>
            <p class="text-muted">Add up to 4 properties to compare them side by side</p>
        </div>

        <div v-else class="comparison-table-wrapper">
            <div class="comparison-table">
                <div class="comparison-row header">
                    <div class="comparison-cell property-header">Property</div>
                    <div
                        v-for="property in properties"
                        :key="property.id"
                        class="comparison-cell property-card"
                    >
                        <button
                            class="remove-btn"
                            @click="removeProperty(property.id)"
                            title="Remove"
                        >
                            <i class="ti ti-x"></i>
                        </button>
                        <img
                            :src="property.image || '/vendor/core/core/base/images/placeholder.png'"
                            :alt="property.name"
                            class="property-image"
                        />
                        <h6>{{ property.name }}</h6>
                        <a :href="property.url" target="_blank" class="view-link">
                            View Details <i class="ti ti-external-link"></i>
                        </a>
                    </div>
                </div>

                <div class="comparison-row">
                    <div class="comparison-cell label">Price</div>
                    <div
                        v-for="property in properties"
                        :key="property.id"
                        class="comparison-cell value"
                    >
                        <strong>{{ property.price_formatted }}</strong>
                    </div>
                </div>

                <div class="comparison-row">
                    <div class="comparison-cell label">Location</div>
                    <div
                        v-for="property in properties"
                        :key="property.id"
                        class="comparison-cell value"
                    >
                        {{ property.location }}
                    </div>
                </div>

                <div class="comparison-row">
                    <div class="comparison-cell label">Bedrooms</div>
                    <div
                        v-for="property in properties"
                        :key="property.id"
                        class="comparison-cell value"
                    >
                        {{ property.number_bedroom }}
                    </div>
                </div>

                <div class="comparison-row">
                    <div class="comparison-cell label">Bathrooms</div>
                    <div
                        v-for="property in properties"
                        :key="property.id"
                        class="comparison-cell value"
                    >
                        {{ property.number_bathroom }}
                    </div>
                </div>

                <div class="comparison-row">
                    <div class="comparison-cell label">Square Meters</div>
                    <div
                        v-for="property in properties"
                        :key="property.id"
                        class="comparison-cell value"
                    >
                        {{ property.square }} m²
                    </div>
                </div>

                <div class="comparison-row">
                    <div class="comparison-cell label">Price per m²</div>
                    <div
                        v-for="property in properties"
                        :key="property.id"
                        class="comparison-cell value"
                    >
                        ₦{{ formatPricePerSqm(property.price, property.square) }}
                    </div>
                </div>

                <div class="comparison-row" v-if="hasFeatures">
                    <div class="comparison-cell label">Features</div>
                    <div
                        v-for="property in properties"
                        :key="property.id"
                        class="comparison-cell value"
                    >
                        <div class="feature-tags">
                            <span
                                v-for="feature in property.features"
                                :key="feature.id"
                                class="feature-tag"
                            >
                                {{ feature.name }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'PropertyComparison',
    props: {
        propertyId: {
            type: [Number, String],
            default: null
        }
    },
    data() {
        return {
            properties: [],
            loading: false
        }
    },
    computed: {
        comparisonCount() {
            return this.properties.length;
        },
        hasFeatures() {
            return this.properties.some(p => p.features && p.features.length > 0);
        }
    },
    mounted() {
        this.loadComparison();
    },
    methods: {
        async loadComparison() {
            this.loading = true;
            try {
                const response = await this.$httpClient.make().get('/property-comparison');
                this.properties = response.data.properties || [];
            } catch (error) {
                console.error('Failed to load comparison:', error);
            } finally {
                this.loading = false;
            }
        },
        async addToComparison() {
            if (!this.propertyId) {
                this.$emit('select-property');
                return;
            }

            if (this.comparisonCount >= 4) {
                alert('You can compare up to 4 properties at a time.');
                return;
            }

            try {
                const response = await this.$httpClient.make().post('/property-comparison/add', {
                    property_id: this.propertyId
                });
                
                if (response.data) {
                    this.loadComparison();
                    this.$emit('added');
                }
            } catch (error) {
                console.error('Failed to add property:', error);
                if (error.response?.data?.message) {
                    alert(error.response.data.message);
                }
            }
        },
        async removeProperty(propertyId) {
            try {
                await this.$httpClient.make().post('/property-comparison/remove', {
                    property_id: propertyId
                });
                this.loadComparison();
            } catch (error) {
                console.error('Failed to remove property:', error);
            }
        },
        async clearComparison() {
            if (!confirm('Are you sure you want to clear all properties from comparison?')) {
                return;
            }

            try {
                await this.$httpClient.make().post('/property-comparison/clear');
                this.properties = [];
                this.$emit('cleared');
            } catch (error) {
                console.error('Failed to clear comparison:', error);
            }
        },
        formatPricePerSqm(price, square) {
            if (!price || !square || square === 0) return '0';
            const pricePerSqm = price / square;
            return new Intl.NumberFormat('en-NG', {
                maximumFractionDigits: 0
            }).format(pricePerSqm);
        }
    }
}
</script>

<style scoped>
.property-comparison {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.comparison-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.comparison-header h4 {
    margin: 0;
}

.comparison-actions {
    display: flex;
    gap: 10px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.comparison-table-wrapper {
    overflow-x: auto;
}

.comparison-table {
    display: table;
    width: 100%;
    min-width: 800px;
}

.comparison-row {
    display: table-row;
}

.comparison-row.header {
    background: #f8f9fa;
    font-weight: 600;
}

.comparison-cell {
    display: table-cell;
    padding: 15px;
    vertical-align: top;
    border-bottom: 1px solid #e0e0e0;
}

.comparison-cell.label {
    font-weight: 600;
    background: #f8f9fa;
    width: 150px;
}

.comparison-cell.property-header {
    font-weight: 600;
    background: #f8f9fa;
}

.comparison-cell.property-card {
    position: relative;
    text-align: center;
    min-width: 200px;
}

.remove-btn {
    position: absolute;
    top: 5px;
    right: 5px;
    background: rgba(0,0,0,0.6);
    color: white;
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.2s;
}

.remove-btn:hover {
    background: rgba(0,0,0,0.8);
}

.property-image {
    width: 100%;
    height: 120px;
    object-fit: cover;
    border-radius: 4px;
    margin-bottom: 10px;
}

.property-card h6 {
    margin: 0 0 10px 0;
    font-size: 14px;
}

.view-link {
    font-size: 12px;
    color: #4285F4;
    text-decoration: none;
}

.view-link:hover {
    text-decoration: underline;
}

.comparison-cell.value {
    text-align: center;
}

.feature-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    justify-content: center;
}

.feature-tag {
    background: #e3f2fd;
    color: #1976d2;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
}
</style>

