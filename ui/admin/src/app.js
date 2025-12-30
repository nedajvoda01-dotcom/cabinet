// Admin UI Application Logic

const PLATFORM_URL = 'http://localhost:8080/api/invoke';
const UI_ID = 'admin';
const ROLE = 'admin';

async function callPlatform(capability, payload) {
    try {
        const response = await fetch(PLATFORM_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                capability: capability,
                payload: payload,
                ui: UI_ID,
                role: ROLE,
                user_id: 'admin_user'
            })
        });

        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Request failed');
        }

        return data;
    } catch (error) {
        throw error;
    }
}

function showResult(elementId, data, isError = false) {
    const element = document.getElementById(elementId);
    element.className = isError ? 'error' : 'result';
    element.textContent = JSON.stringify(data, null, 2);
}

// Car Management
async function createCar() {
    try {
        const brand = document.getElementById('carBrand').value;
        const model = document.getElementById('carModel').value;
        const year = parseInt(document.getElementById('carYear').value);
        const price = parseFloat(document.getElementById('carPrice').value);

        const result = await callPlatform('car.create', {
            brand, model, year, price
        });

        showResult('carResult', result);
    } catch (error) {
        showResult('carResult', { error: error.message }, true);
    }
}

async function listCars() {
    try {
        const result = await callPlatform('car.list', {});
        showResult('carResult', result);
    } catch (error) {
        showResult('carResult', { error: error.message }, true);
    }
}

// Pricing
async function calculatePrice() {
    try {
        const brand = document.getElementById('priceBrand').value;
        const year = parseInt(document.getElementById('priceYear').value);
        const base_price = parseFloat(document.getElementById('priceBase').value);

        const result = await callPlatform('price.calculate', {
            brand, year, base_price
        });

        showResult('priceResult', result);
    } catch (error) {
        showResult('priceResult', { error: error.message }, true);
    }
}

async function listRules() {
    try {
        const result = await callPlatform('price.rule.list', {});
        showResult('priceResult', result);
    } catch (error) {
        showResult('priceResult', { error: error.message }, true);
    }
}

// Automation
async function executeWorkflow() {
    try {
        const workflow_id = document.getElementById('workflowId').value;

        const result = await callPlatform('workflow.execute', {
            workflow_id
        });

        showResult('workflowResult', result);
    } catch (error) {
        showResult('workflowResult', { error: error.message }, true);
    }
}

async function listWorkflows() {
    try {
        const result = await callPlatform('workflow.list', {});
        showResult('workflowResult', result);
    } catch (error) {
        showResult('workflowResult', { error: error.message }, true);
    }
}

// Catalog Search
async function loadFilters() {
    try {
        const result = await callPlatform('catalog.filters.get', {});
        
        // Populate filter dropdowns
        const brandSelect = document.getElementById('filterBrand');
        const modelSelect = document.getElementById('filterModel');
        const yearSelect = document.getElementById('filterYear');
        
        // Clear existing options (except "All")
        brandSelect.innerHTML = '<option value="">All Brands</option>';
        modelSelect.innerHTML = '<option value="">All Models</option>';
        yearSelect.innerHTML = '<option value="">All Years</option>';
        
        // Add brands
        if (result.result && result.result.data && result.result.data.brands) {
            result.result.data.brands.forEach(brand => {
                const option = document.createElement('option');
                option.value = brand;
                option.textContent = brand;
                brandSelect.appendChild(option);
            });
        }
        
        // Add models
        if (result.result && result.result.data && result.result.data.models) {
            result.result.data.models.forEach(model => {
                const option = document.createElement('option');
                option.value = model;
                option.textContent = model;
                modelSelect.appendChild(option);
            });
        }
        
        // Add years
        if (result.result && result.result.data && result.result.data.years) {
            result.result.data.years.forEach(year => {
                const option = document.createElement('option');
                option.value = year;
                option.textContent = year;
                yearSelect.appendChild(option);
            });
        }
        
        // Show filters container
        document.getElementById('filtersContainer').style.display = 'block';
        showResult('searchResult', result);
    } catch (error) {
        showResult('searchResult', { error: error.message }, true);
    }
}

async function searchListings() {
    try {
        const filters = {};
        
        const brand = document.getElementById('filterBrand').value;
        if (brand) filters.brand = brand;
        
        const model = document.getElementById('filterModel').value;
        if (model) filters.model = model;
        
        const year = document.getElementById('filterYear').value;
        if (year) filters.year = parseInt(year);
        
        const minPrice = document.getElementById('filterMinPrice').value;
        if (minPrice) filters.min_price = parseFloat(minPrice);
        
        const maxPrice = document.getElementById('filterMaxPrice').value;
        if (maxPrice) filters.max_price = parseFloat(maxPrice);
        
        const result = await callPlatform('catalog.listings.search', {
            filters: filters,
            page: 1,
            per_page: 20
        });
        
        showResult('searchResult', result);
    } catch (error) {
        showResult('searchResult', { error: error.message }, true);
    }
}

// CSV Import
async function runImport() {
    try {
        const filename = document.getElementById('importFilename').value;
        const csvData = document.getElementById('importCsvData').value;
        
        const result = await callPlatform('import.run', {
            filename: filename,
            csv_data: csvData
        });
        
        showResult('importResult', result);
    } catch (error) {
        showResult('importResult', { error: error.message }, true);
    }
}
