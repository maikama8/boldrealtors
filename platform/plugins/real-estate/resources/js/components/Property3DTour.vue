<template>
    <div class="property-3d-tour">
        <div class="tour-header">
            <h4>3D Virtual Tour</h4>
            <div class="tour-providers" v-if="tours.length > 1">
                <button
                    v-for="(tour, index) in tours"
                    :key="tour.id"
                    class="btn btn-sm"
                    :class="{ 'btn-primary': activeTourIndex === index, 'btn-secondary': activeTourIndex !== index }"
                    @click="activeTourIndex = index"
                >
                    {{ getProviderName(tour.metadata?.provider) }}
                </button>
            </div>
        </div>

        <div v-if="loading" class="loading-state">
            <div class="spinner-border text-primary"></div>
            <p>Loading tour...</p>
        </div>

        <div v-else-if="activeTour" class="tour-container">
            <div class="tour-embed" v-html="getTourEmbed(activeTour)"></div>
            
            <div class="tour-info">
                <p class="tour-description">{{ activeTour.description }}</p>
                <a
                    :href="activeTour.file_path"
                    target="_blank"
                    class="btn btn-primary btn-sm"
                >
                    <i class="ti ti-external-link"></i> Open Full Screen
                </a>
            </div>
        </div>

        <div v-else class="no-tour">
            <p>No 3D tour available for this property.</p>
        </div>
    </div>
</template>

<script>
export default {
    name: 'Property3DTour',
    props: {
        propertyId: {
            type: [Number, String],
            required: true
        },
        tours: {
            type: Array,
            default: () => []
        }
    },
    data() {
        return {
            loading: false,
            activeTourIndex: 0
        }
    },
    computed: {
        activeTour() {
            return this.tours[this.activeTourIndex] || null;
        }
    },
    methods: {
        getProviderName(provider) {
            const names = {
                'matterport': 'Matterport',
                'iguide': 'iGuide',
                'custom': 'Virtual Tour'
            };
            return names[provider] || 'Virtual Tour';
        },
        getTourEmbed(tour) {
            if (!tour || !tour.file_path) return '';

            const provider = tour.metadata?.provider || 'custom';
            const embedUrl = tour.file_path;

            if (provider === 'matterport') {
                return `
                    <iframe
                        width="100%"
                        height="500"
                        src="${embedUrl}"
                        frameborder="0"
                        allowfullscreen
                        allow="xr-spatial-tracking"
                    ></iframe>
                `;
            } else if (provider === 'iguide') {
                return `
                    <iframe
                        width="100%"
                        height="500"
                        src="${embedUrl}"
                        frameborder="0"
                        allowfullscreen
                    ></iframe>
                `;
            } else {
                // Custom/other - try iframe first
                return `
                    <iframe
                        width="100%"
                        height="500"
                        src="${embedUrl}"
                        frameborder="0"
                        allowfullscreen
                    ></iframe>
                `;
            }
        }
    }
}
</script>

<style scoped>
.property-3d-tour {
    background: white;
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.tour-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.tour-header h4 {
    margin: 0;
}

.tour-providers {
    display: flex;
    gap: 10px;
}

.tour-container {
    margin-top: 20px;
}

.tour-embed {
    width: 100%;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 15px;
}

.tour-embed iframe {
    width: 100%;
    height: 500px;
    border: none;
    border-radius: 8px;
}

.tour-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.tour-description {
    margin: 0;
    color: #666;
}

.loading-state,
.no-tour {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}
</style>

