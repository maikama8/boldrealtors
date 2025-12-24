<template>
    <div class="map-search-container">
        <div class="map-wrapper">
            <div id="property-map" ref="mapContainer" class="map"></div>
            
            <div class="map-controls">
                <button
                    class="btn btn-sm btn-primary"
                    @click="toggleDrawMode"
                    :class="{ active: drawMode }"
                >
                    <i class="ti ti-pencil"></i> Draw Area
                </button>
                <button
                    class="btn btn-sm btn-secondary"
                    @click="clearDrawnArea"
                    v-if="drawnArea"
                >
                    <i class="ti ti-x"></i> Clear
                </button>
            </div>
        </div>

        <div class="search-filters" v-if="showFilters">
            <h5>Filters</h5>
            <div class="filter-group">
                <label>Property Type</label>
                <select v-model="filters.type" class="form-control">
                    <option value="">All Types</option>
                    <option value="selling">For Sale</option>
                    <option value="renting">For Rent</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Price Range</label>
                <div class="row">
                    <div class="col-6">
                        <input
                            v-model.number="filters.min_price"
                            type="number"
                            class="form-control"
                            placeholder="Min (₦)"
                        />
                    </div>
                    <div class="col-6">
                        <input
                            v-model.number="filters.max_price"
                            type="number"
                            class="form-control"
                            placeholder="Max (₦)"
                        />
                    </div>
                </div>
            </div>
            <div class="filter-group">
                <label>Bedrooms</label>
                <input
                    v-model.number="filters.bedrooms"
                    type="number"
                    class="form-control"
                    min="0"
                    placeholder="Min bedrooms"
                />
            </div>
            <button class="btn btn-primary btn-block" @click="applyFilters">
                Apply Filters
            </button>
        </div>

        <div class="results-panel" v-if="properties.length > 0">
            <h5>Found {{ properties.length }} properties</h5>
            <div class="property-list">
                <div
                    v-for="property in properties"
                    :key="property.id"
                    class="property-item"
                    @click="focusProperty(property)"
                >
                    <img
                        :src="property.image || '/vendor/core/core/base/images/placeholder.png'"
                        :alt="property.name"
                        class="property-thumb"
                    />
                    <div class="property-info">
                        <h6>{{ property.name }}</h6>
                        <p class="price">{{ property.price_formatted }}</p>
                        <p class="location">{{ property.location }}</p>
                        <p class="details">
                            {{ property.bedrooms }} bed • {{ property.bathrooms }} bath
                            <span v-if="property.square"> • {{ property.square }} m²</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'MapSearch',
    props: {
        apiKey: {
            type: String,
            required: true
        },
        initialCenter: {
            type: Object,
            default: () => ({ lat: 6.5244, lng: 3.3792 }) // Lagos, Nigeria
        },
        initialZoom: {
            type: Number,
            default: 12
        },
        showFilters: {
            type: Boolean,
            default: true
        }
    },
    data() {
        return {
            map: null,
            markers: [],
            drawMode: false,
            drawnArea: null,
            properties: [],
            filters: {
                type: '',
                min_price: null,
                max_price: null,
                bedrooms: null
            },
            drawingManager: null
        }
    },
    mounted() {
        this.initMap();
    },
    methods: {
        initMap() {
            // Initialize Google Maps or Mapbox
            if (typeof google !== 'undefined') {
                this.initGoogleMap();
            } else if (typeof mapboxgl !== 'undefined') {
                this.initMapbox();
            } else {
                console.error('Map library not loaded');
            }
        },
        initGoogleMap() {
            this.map = new google.maps.Map(this.$refs.mapContainer, {
                center: this.initialCenter,
                zoom: this.initialZoom,
                mapTypeControl: true,
                streetViewControl: false
            });

            // Listen to map bounds changes
            this.map.addListener('bounds_changed', () => {
                this.searchByBounds();
            });

            // Initialize drawing manager for custom areas
            this.drawingManager = new google.maps.drawing.DrawingManager({
                drawingMode: null,
                drawingControl: false,
                polygonOptions: {
                    fillColor: '#4285F4',
                    fillOpacity: 0.2,
                    strokeWeight: 2,
                    clickable: false,
                    editable: true,
                    zIndex: 1
                }
            });

            this.drawingManager.setMap(this.map);

            this.drawingManager.addListener('polygoncomplete', (polygon) => {
                this.handlePolygonComplete(polygon);
            });
        },
        initMapbox() {
            mapboxgl.accessToken = this.apiKey;
            
            this.map = new mapboxgl.Map({
                container: this.$refs.mapContainer,
                style: 'mapbox://styles/mapbox/streets-v11',
                center: [this.initialCenter.lng, this.initialCenter.lat],
                zoom: this.initialZoom
            });

            // Add drawing control
            const draw = new MapboxDraw({
                displayControlsDefault: false,
                controls: {
                    polygon: true,
                    trash: true
                }
            });

            this.map.addControl(draw);

            this.map.on('draw.create', (e) => {
                this.handleMapboxPolygonComplete(e.features[0]);
            });

            this.map.on('moveend', () => {
                this.searchByBounds();
            });
        },
        async searchByBounds() {
            if (!this.map) return;

            let bounds;
            if (this.map.getBounds) {
                // Google Maps
                bounds = this.map.getBounds();
                const north = bounds.getNorthEast().lat();
                const south = bounds.getSouthWest().lat();
                const east = bounds.getNorthEast().lng();
                const west = bounds.getSouthWest().lng();

                await this.fetchPropertiesByBounds(north, south, east, west);
            } else if (this.map.getBounds) {
                // Mapbox
                const bounds = this.map.getBounds();
                await this.fetchPropertiesByBounds(
                    bounds.getNorth(),
                    bounds.getSouth(),
                    bounds.getEast(),
                    bounds.getWest()
                );
            }
        },
        async fetchPropertiesByBounds(north, south, east, west) {
            try {
                const response = await this.$httpClient.make().post('/map-search/bounds', {
                    north,
                    south,
                    east,
                    west,
                    filters: this.filters
                });

                this.properties = response.data.properties || [];
                this.updateMarkers();
            } catch (error) {
                console.error('Map search error:', error);
            }
        },
        updateMarkers() {
            // Clear existing markers
            this.markers.forEach(marker => marker.setMap ? marker.setMap(null) : marker.remove());
            this.markers = [];

            // Add new markers
            this.properties.forEach(property => {
                const marker = this.createMarker(property);
                this.markers.push(marker);
            });
        },
        createMarker(property) {
            if (typeof google !== 'undefined') {
                const marker = new google.maps.Marker({
                    position: { lat: property.latitude, lng: property.longitude },
                    map: this.map,
                    title: property.name
                });

                const infoWindow = new google.maps.InfoWindow({
                    content: this.getMarkerContent(property)
                });

                marker.addListener('click', () => {
                    infoWindow.open(this.map, marker);
                });

                return marker;
            } else if (typeof mapboxgl !== 'undefined') {
                const el = document.createElement('div');
                el.className = 'property-marker';
                el.innerHTML = `<div class="marker-price">${property.price_formatted}</div>`;

                const marker = new mapboxgl.Marker(el)
                    .setLngLat([property.longitude, property.latitude])
                    .setPopup(new mapboxgl.Popup().setHTML(this.getMarkerContent(property)))
                    .addTo(this.map);

                return marker;
            }
        },
        getMarkerContent(property) {
            return `
                <div class="marker-info">
                    <img src="${property.image}" alt="${property.name}" style="width: 100px; height: 60px; object-fit: cover;">
                    <h6>${property.name}</h6>
                    <p><strong>${property.price_formatted}</strong></p>
                    <p>${property.bedrooms} bed • ${property.bathrooms} bath</p>
                    <a href="${property.url}" target="_blank">View Details</a>
                </div>
            `;
        },
        toggleDrawMode() {
            this.drawMode = !this.drawMode;
            
            if (this.drawingManager) {
                this.drawingManager.setDrawingMode(
                    this.drawMode ? google.maps.drawing.OverlayType.POLYGON : null
                );
            }
        },
        handlePolygonComplete(polygon) {
            this.drawnArea = polygon;
            const path = polygon.getPath();
            const coordinates = [];

            path.forEach((latLng) => {
                coordinates.push([latLng.lat(), latLng.lng()]);
            });

            this.searchByPolygon(coordinates);
        },
        async searchByPolygon(coordinates) {
            try {
                const response = await this.$httpClient.make().post('/map-search/polygon', {
                    coordinates,
                    filters: this.filters
                });

                this.properties = response.data.properties || [];
                this.updateMarkers();
            } catch (error) {
                console.error('Polygon search error:', error);
            }
        },
        clearDrawnArea() {
            if (this.drawnArea) {
                this.drawnArea.setMap(null);
                this.drawnArea = null;
            }
            this.searchByBounds();
        },
        applyFilters() {
            this.searchByBounds();
        },
        focusProperty(property) {
            if (this.map.setCenter) {
                this.map.setCenter({ lat: property.latitude, lng: property.longitude });
                this.map.setZoom(15);
            }
        }
    }
}
</script>

<style scoped>
.map-search-container {
    display: flex;
    gap: 20px;
    height: 600px;
}

.map-wrapper {
    flex: 1;
    position: relative;
}

.map {
    width: 100%;
    height: 100%;
    border-radius: 8px;
}

.map-controls {
    position: absolute;
    top: 10px;
    left: 10px;
    z-index: 1000;
    display: flex;
    gap: 10px;
}

.search-filters {
    width: 300px;
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow-y: auto;
}

.filter-group {
    margin-bottom: 15px;
}

.filter-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.results-panel {
    width: 350px;
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow-y: auto;
}

.property-list {
    margin-top: 15px;
}

.property-item {
    display: flex;
    gap: 12px;
    padding: 12px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    margin-bottom: 12px;
    cursor: pointer;
    transition: all 0.2s;
}

.property-item:hover {
    border-color: #4285F4;
    box-shadow: 0 2px 8px rgba(66, 133, 244, 0.2);
}

.property-thumb {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 4px;
}

.property-info {
    flex: 1;
}

.property-info h6 {
    margin: 0 0 5px 0;
    font-size: 14px;
}

.property-info .price {
    font-weight: bold;
    color: #4285F4;
    margin: 0 0 5px 0;
}

.property-info .location {
    font-size: 12px;
    color: #666;
    margin: 0 0 5px 0;
}

.property-info .details {
    font-size: 12px;
    color: #999;
    margin: 0;
}

.marker-price {
    background: #4285F4;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    white-space: nowrap;
}
</style>

