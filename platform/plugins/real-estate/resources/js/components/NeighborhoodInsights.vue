<template>
    <div class="neighborhood-insights">
        <h4>Neighborhood Insights</h4>

        <div v-if="loading" class="loading-state">
            <div class="spinner-border text-primary"></div>
            <p>Loading insights...</p>
        </div>

        <div v-else-if="insights" class="insights-content">
            <!-- Walkability Score -->
            <div class="insight-card" v-if="insights.walkability">
                <div class="insight-header">
                    <i class="ti ti-walk"></i>
                    <h5>Walkability Score</h5>
                </div>
                <div class="score-display">
                    <div class="score-value">{{ insights.walkability.score }}</div>
                    <div class="score-label">/ 100</div>
                </div>
                <p class="score-description">{{ insights.walkability.description }}</p>
            </div>

            <!-- Transit Score -->
            <div class="insight-card" v-if="insights.transit_score">
                <div class="insight-header">
                    <i class="ti ti-bus"></i>
                    <h5>Transit Score</h5>
                </div>
                <div class="score-display">
                    <div class="score-value">{{ insights.transit_score.score }}</div>
                    <div class="score-label">/ 100</div>
                </div>
                <p class="score-description">{{ insights.transit_score.description }}</p>
            </div>

            <!-- Schools -->
            <div class="insight-card" v-if="insights.schools">
                <div class="insight-header">
                    <i class="ti ti-school"></i>
                    <h5>Schools</h5>
                </div>
                <div class="insight-details">
                    <div class="detail-item">
                        <span>Primary Schools:</span>
                        <strong>{{ insights.schools.primary_schools?.count || 0 }}</strong>
                    </div>
                    <div class="detail-item">
                        <span>Secondary Schools:</span>
                        <strong>{{ insights.schools.secondary_schools?.count || 0 }}</strong>
                    </div>
                    <div class="detail-item">
                        <span>Universities:</span>
                        <strong>{{ insights.schools.universities?.count || 0 }}</strong>
                    </div>
                </div>
            </div>

            <!-- Crime Stats -->
            <div class="insight-card" v-if="insights.crime_stats?.safety_score !== null">
                <div class="insight-header">
                    <i class="ti ti-shield"></i>
                    <h5>Safety</h5>
                </div>
                <div class="score-display">
                    <div class="score-value" :class="getSafetyClass(insights.crime_stats.safety_score)">
                        {{ insights.crime_stats.safety_score }}
                    </div>
                    <div class="score-label">/ 100</div>
                </div>
                <p v-if="insights.crime_stats.crime_trend" class="trend">
                    Trend: {{ insights.crime_stats.crime_trend }}
                </p>
            </div>

            <!-- Nearby Amenities -->
            <div class="insight-card" v-if="insights.amenities">
                <div class="insight-header">
                    <i class="ti ti-map-pin"></i>
                    <h5>Nearby Amenities</h5>
                </div>
                <div class="amenities-grid">
                    <div class="amenity-item">
                        <i class="ti ti-tools-kitchen"></i>
                        <span>{{ insights.amenities.restaurants?.length || 0 }} Restaurants</span>
                    </div>
                    <div class="amenity-item">
                        <i class="ti ti-shopping-cart"></i>
                        <span>{{ insights.amenities.shopping?.length || 0 }} Shopping</span>
                    </div>
                    <div class="amenity-item">
                        <i class="ti ti-trees"></i>
                        <span>{{ insights.amenities.parks?.length || 0 }} Parks</span>
                    </div>
                    <div class="amenity-item">
                        <i class="ti ti-building-hospital"></i>
                        <span>{{ insights.amenities.hospitals?.length || 0 }} Hospitals</span>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="no-insights">
            <p>Neighborhood insights not available for this location.</p>
        </div>
    </div>
</template>

<script>
export default {
    name: 'NeighborhoodInsights',
    props: {
        propertyId: {
            type: [Number, String],
            required: true
        }
    },
    data() {
        return {
            insights: null,
            loading: false
        }
    },
    mounted() {
        this.fetchInsights();
    },
    methods: {
        async fetchInsights() {
            this.loading = true;
            try {
                const response = await this.$httpClient.make().get(`/property/${this.propertyId}/neighborhood-insights`);
                this.insights = response.data.insights;
            } catch (error) {
                console.error('Failed to fetch insights:', error);
            } finally {
                this.loading = false;
            }
        },
        getSafetyClass(score) {
            if (score >= 70) return 'safe';
            if (score >= 50) return 'moderate';
            return 'low';
        }
    }
}
</script>

<style scoped>
.neighborhood-insights {
    background: white;
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.neighborhood-insights h4 {
    margin-bottom: 20px;
    font-size: 20px;
}

.loading-state {
    text-align: center;
    padding: 40px 20px;
}

.insights-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.insight-card {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    border-left: 4px solid #4285F4;
}

.insight-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
}

.insight-header i {
    font-size: 24px;
    color: #4285F4;
}

.insight-header h5 {
    margin: 0;
    font-size: 16px;
}

.score-display {
    display: flex;
    align-items: baseline;
    gap: 5px;
    margin-bottom: 10px;
}

.score-value {
    font-size: 36px;
    font-weight: bold;
    color: #4285F4;
}

.score-value.safe {
    color: #34a853;
}

.score-value.moderate {
    color: #fbbc04;
}

.score-value.low {
    color: #ea4335;
}

.score-label {
    font-size: 18px;
    color: #666;
}

.score-description {
    color: #666;
    font-size: 14px;
    margin: 0;
}

.insight-details {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #e0e0e0;
}

.detail-item:last-child {
    border-bottom: none;
}

.amenities-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.amenity-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: white;
    border-radius: 6px;
}

.amenity-item i {
    color: #4285F4;
    font-size: 20px;
}

.trend {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
    text-transform: capitalize;
}

.no-insights {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}
</style>

