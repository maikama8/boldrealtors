/**
 * Zillow-like Features Vue Components
 * Auto-register all components for easy import
 */

import NaturalLanguageSearch from './NaturalLanguageSearch.vue';
import MapSearch from './MapSearch.vue';
import PropertyValuation from './PropertyValuation.vue';
import PropertyComparison from './PropertyComparison.vue';
import BuyAbilityCalculator from './BuyAbilityCalculator.vue';
import NeighborhoodInsights from './NeighborhoodInsights.vue';
import Property3DTour from './Property3DTour.vue';
import RentPaymentForm from './RentPaymentForm.vue';

export {
    NaturalLanguageSearch,
    MapSearch,
    PropertyValuation,
    PropertyComparison,
    BuyAbilityCalculator,
    NeighborhoodInsights,
    Property3DTour,
    RentPaymentForm,
};

// Auto-register if Vue is available globally
if (typeof window !== 'undefined' && window.Vue) {
    const Vue = window.Vue;
    
    Vue.component('natural-language-search', NaturalLanguageSearch);
    Vue.component('map-search', MapSearch);
    Vue.component('property-valuation', PropertyValuation);
    Vue.component('property-comparison', PropertyComparison);
    Vue.component('buyability-calculator', BuyAbilityCalculator);
    Vue.component('neighborhood-insights', NeighborhoodInsights);
    Vue.component('property-3d-tour', Property3DTour);
    Vue.component('rent-payment-form', RentPaymentForm);
}

