<template>
    <div class="natural-language-search">
        <div class="search-input-wrapper">
            <input
                v-model="query"
                type="text"
                class="form-control search-input"
                :placeholder="placeholder"
                @keyup.enter="performSearch"
                @input="handleInput"
            />
            <button
                class="btn btn-primary search-button"
                @click="performSearch"
                :disabled="loading || !query.trim()"
            >
                <span v-if="loading" class="spinner-border spinner-border-sm"></span>
                <i v-else class="ti ti-search"></i>
            </button>
        </div>

        <div v-if="suggestions.length > 0" class="suggestions-dropdown">
            <div
                v-for="(suggestion, index) in suggestions"
                :key="index"
                class="suggestion-item"
                @click="selectSuggestion(suggestion)"
            >
                {{ suggestion }}
            </div>
        </div>

        <div v-if="parsedCriteria && showCriteria" class="parsed-criteria">
            <small class="text-muted">
                <strong>Searching for:</strong>
                <span v-if="parsedCriteria.bedrooms">{{ parsedCriteria.bedrooms }} bedrooms</span>
                <span v-if="parsedCriteria.bathrooms">{{ parsedCriteria.bathrooms }} bathrooms</span>
                <span v-if="parsedCriteria.location">in {{ parsedCriteria.location }}</span>
                <span v-if="parsedCriteria.price_max">under ₦{{ formatPrice(parsedCriteria.price_max) }}</span>
            </small>
        </div>
    </div>
</template>

<script>
export default {
    name: 'NaturalLanguageSearch',
    props: {
        placeholder: {
            type: String,
            default: 'Search for properties... e.g., "3-bedroom house in Lagos with pool"'
        },
        showCriteria: {
            type: Boolean,
            default: true
        }
    },
    data() {
        return {
            query: '',
            loading: false,
            suggestions: [],
            parsedCriteria: null,
            debounceTimer: null
        }
    },
    methods: {
        async performSearch() {
            if (!this.query.trim() || this.loading) return;

            this.loading = true;
            this.suggestions = [];

            try {
                const response = await this.$httpClient.make().get('/search/natural', {
                    params: {
                        query: this.query,
                        per_page: 20
                    }
                });

                this.parsedCriteria = response.data.parsed_criteria;
                this.$emit('search-complete', response.data);
            } catch (error) {
                console.error('Search error:', error);
                this.$emit('search-error', error);
            } finally {
                this.loading = false;
            }
        },
        handleInput() {
            clearTimeout(this.debounceTimer);
            
            // Generate suggestions as user types
            this.debounceTimer = setTimeout(() => {
                this.generateSuggestions();
            }, 300);
        },
        generateSuggestions() {
            // Simple suggestion generation based on common patterns
            const commonSearches = [
                '3-bedroom house in Lagos',
                'Apartment for rent in Abuja',
                'House with pool in Port Harcourt',
                'Furnished flat in Ikeja',
                'Duplex for sale in Lekki'
            ];

            if (this.query.length < 2) {
                this.suggestions = [];
                return;
            }

            this.suggestions = commonSearches
                .filter(s => s.toLowerCase().includes(this.query.toLowerCase()))
                .slice(0, 5);
        },
        selectSuggestion(suggestion) {
            this.query = suggestion;
            this.suggestions = [];
            this.performSearch();
        },
        formatPrice(price) {
            return new Intl.NumberFormat('en-NG', {
                style: 'currency',
                currency: 'NGN',
                maximumFractionDigits: 0
            }).format(price);
        }
    }
}
</script>

<style scoped>
.natural-language-search {
    position: relative;
}

.search-input-wrapper {
    display: flex;
    gap: 10px;
}

.search-input {
    flex: 1;
    padding: 12px 16px;
    font-size: 16px;
    border-radius: 8px;
}

.search-button {
    padding: 12px 24px;
    border-radius: 8px;
}

.suggestions-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-top: 4px;
    max-height: 300px;
    overflow-y: auto;
    z-index: 1000;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.suggestion-item {
    padding: 12px 16px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.2s;
}

.suggestion-item:hover {
    background-color: #f8f9fa;
}

.suggestion-item:last-child {
    border-bottom: none;
}

.parsed-criteria {
    margin-top: 8px;
    padding: 8px;
    background-color: #f8f9fa;
    border-radius: 4px;
}

.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}
</style>

