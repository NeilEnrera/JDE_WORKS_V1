const PH_LOCATIONS_API_PROXY = '../backend/location_proxy.php?endpoint=';

class PhLocationPicker {
    constructor(config) {
        this.provinceSelect = document.getElementById(config.provinceId);
        this.citySelect = document.getElementById(config.cityId);
        this.barangaySelect = document.getElementById(config.barangayId);
        
        this.init();
    }

    async init() {
        if (!this.provinceSelect) return;
        this.cache = {}; // Memory cache for API responses

        this.provinceSelect.addEventListener('change', () => this.loadCities());
        this.citySelect.addEventListener('change', () => this.loadBarangays());
        
        await this.loadProvinces();
    }

    async fetchApi(endpoint) {
        if (this.cache[endpoint]) return this.cache[endpoint];
        
        try {
            const response = await fetch(`${PH_LOCATIONS_API_PROXY}${encodeURIComponent(endpoint)}`);
            if (!response.ok) throw new Error('Network response was not ok');
            const data = await response.json();
            if (data.error) throw new Error(data.error);
            const result = Array.isArray(data) ? data : [];
            this.cache[endpoint] = result;
            return result;
        } catch (error) {
            console.error('Error fetching PH locations:', error);
            return [];
        }
    }

    clearSelect(select, placeholder) {
        if (!select) return;
        select.innerHTML = '<option value=""> </option>';
        select.disabled = true;
    }

    populateSelect(select, data, placeholder, labelField = 'name') {
        if (!select) return;
        select.innerHTML = '<option value=""> </option>';
        
        if (!data || data.length === 0) {
            select.disabled = true;
            return;
        }

        // Sort alphabetically
        data.sort((a, b) => a[labelField].localeCompare(b[labelField]));

        // Group by first letter
        let currentLetter = '';
        let currentGroup = null;

        data.forEach(item => {
            const firstLetter = item[labelField].charAt(0).toUpperCase();
            
            if (firstLetter !== currentLetter) {
                currentLetter = firstLetter;
                currentGroup = document.createElement('optgroup');
                currentGroup.label = currentLetter;
                select.appendChild(currentGroup);
            }

            const option = document.createElement('option');
            option.value = item[labelField];
            option.text = item[labelField].toUpperCase().replace(/-/g, ' ');
            option.dataset.code = item.code;
            currentGroup.appendChild(option);
        });
        
        select.disabled = false;
    }

    async loadProvinces() {
        this.clearSelect(this.provinceSelect, 'LOADING PROVINCES...');
        this.clearSelect(this.citySelect, 'SELECT CITY');
        this.clearSelect(this.barangaySelect, 'SELECT BARANGAY');

        const provinces = await this.fetchApi('/provinces/');
        const districts = await this.fetchApi('/regions/130000000/districts/'); // NCR
        
        // Manual insertion for Metro Manila as it is a region, not a province
        const metroManila = { 
            code: '130000000', 
            name: 'METRO MANILA (NCR)' 
        };
        
        const combined = [metroManila, ...provinces, ...districts];
        
        if (combined.length === 1) { // Only our manual entry
            alert('Warning: No provinces fetched! Proxy path: ' + PH_LOCATIONS_API_PROXY);
        }

        this.populateSelect(this.provinceSelect, combined, 'SELECT PROVINCE');
        return combined;
    }

    async loadCities() {
        const provinceOption = this.provinceSelect.options[this.provinceSelect.selectedIndex];
        const provinceCode = provinceOption ? provinceOption.dataset.code : null;

        this.clearSelect(this.citySelect, 'LOADING CITIES...');
        this.clearSelect(this.barangaySelect, 'SELECT BARANGAY');

        if (!provinceCode) {
            this.clearSelect(this.citySelect, 'SELECT CITY');
            return;
        }

        let endpoint = `/provinces/${provinceCode}/cities-municipalities/`;
        
        if (provinceCode === '130000000') {
            // Metro Manila as a whole
            endpoint = `/regions/${provinceCode}/cities-municipalities/`;
        } else if (provinceCode.startsWith('13')) {
            // NCR Districts (e.g., First District, etc.)
            endpoint = `/districts/${provinceCode}/cities-municipalities/`;
        }

        const cities = await this.fetchApi(endpoint);
        this.populateSelect(this.citySelect, cities, 'SELECT CITY');
        return cities;
    }

    async loadBarangays() {
        const cityOption = this.citySelect.options[this.citySelect.selectedIndex];
        const cityCode = cityOption ? cityOption.dataset.code : null;

        this.clearSelect(this.barangaySelect, 'LOADING BARANGAYS...');

        if (!cityCode) {
            this.clearSelect(this.barangaySelect, 'SELECT BARANGAY');
            return;
        }

        const barangays = await this.fetchApi(`/cities-municipalities/${cityCode}/barangays/`);
        this.populateSelect(this.barangaySelect, barangays, 'SELECT BARANGAY');
        return barangays;
    }
}
