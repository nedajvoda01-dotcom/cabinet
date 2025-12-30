// Catalog Page
// Public catalog with filters and search

import { createLayout } from '../components/Layout.js';
import { invokeSafe, can } from '../api/guards.js';

let currentFilters = {};
let currentPage = 1;

export async function render() {
    const content = `
        <div class="section">
            <h2>Car Catalog</h2>
            
            <button class="btn btn-primary" id="loadFiltersBtn">Load Filters</button>
            
            <div id="filtersContainer" class="hidden mt-md">
                <div class="grid grid-2">
                    <div class="form-group">
                        <label>Brand:</label>
                        <select id="filterBrand">
                            <option value="">All Brands</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Model:</label>
                        <select id="filterModel">
                            <option value="">All Models</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Year:</label>
                        <select id="filterYear">
                            <option value="">All Years</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Status:</label>
                        <select id="filterStatus">
                            <option value="">All Statuses</option>
                            <option value="available">Available</option>
                            <option value="sold">Sold</option>
                            <option value="reserved">Reserved</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Min Price:</label>
                        <input type="number" id="filterMinPrice" placeholder="0">
                    </div>
                    
                    <div class="form-group">
                        <label>Max Price:</label>
                        <input type="number" id="filterMaxPrice" placeholder="100000">
                    </div>
                </div>
                
                <button class="btn btn-success" id="searchBtn">Search</button>
                <button class="btn btn-secondary ml-md" id="clearBtn">Clear Filters</button>
            </div>
            
            <div id="searchResult" class="mt-lg"></div>
        </div>
    `;
    
    createLayout(content);
    attachEvents();
}

function attachEvents() {
    document.getElementById('loadFiltersBtn').addEventListener('click', loadFilters);
    document.getElementById('searchBtn')?.addEventListener('click', searchListings);
    document.getElementById('clearBtn')?.addEventListener('click', clearFilters);
}

async function loadFilters() {
    const btn = document.getElementById('loadFiltersBtn');
    const resultDiv = document.getElementById('searchResult');
    
    if (!can('catalog.filters.get')) {
        resultDiv.innerHTML = '<div class="error">You don\'t have permission to load filters</div>';
        return;
    }
    
    try {
        btn.disabled = true;
        btn.textContent = 'Loading...';
        
        const result = await invokeSafe('catalog.filters.get', {});
        
        // Populate filters
        const data = result.result?.data || {};
        
        populateSelect('filterBrand', data.brands || []);
        populateSelect('filterModel', data.models || []);
        populateSelect('filterYear', data.years || []);
        
        document.getElementById('filtersContainer').classList.remove('hidden');
        resultDiv.innerHTML = '<div class="success">Filters loaded successfully</div>';
        
    } catch (error) {
        resultDiv.innerHTML = `<div class="error">Error: ${error.message}</div>`;
    } finally {
        btn.disabled = false;
        btn.textContent = 'Load Filters';
    }
}

function populateSelect(id, options) {
    const select = document.getElementById(id);
    const firstOptionText = select.options[0].text;  // Save before clearing
    select.innerHTML = `<option value="">${firstOptionText}</option>`;
    
    options.forEach(opt => {
        const option = document.createElement('option');
        option.value = opt;
        option.textContent = opt;
        select.appendChild(option);
    });
}

async function searchListings() {
    const resultDiv = document.getElementById('searchResult');
    
    if (!can('catalog.listings.search')) {
        resultDiv.innerHTML = '<div class="error">You don\'t have permission to search listings</div>';
        return;
    }
    
    try {
        // Collect filters
        const filters = {};
        
        const brand = document.getElementById('filterBrand').value;
        if (brand) filters.brand = brand;
        
        const model = document.getElementById('filterModel').value;
        if (model) filters.model = model;
        
        const year = document.getElementById('filterYear').value;
        if (year) filters.year = parseInt(year);
        
        const status = document.getElementById('filterStatus').value;
        if (status) filters.status = status;
        
        const minPrice = document.getElementById('filterMinPrice').value;
        if (minPrice) filters.min_price = parseFloat(minPrice);
        
        const maxPrice = document.getElementById('filterMaxPrice').value;
        if (maxPrice) filters.max_price = parseFloat(maxPrice);
        
        currentFilters = filters;
        
        resultDiv.innerHTML = '<div class="loading"></div>';
        
        const result = await invokeSafe('catalog.listings.search', {
            filters: filters,
            page: currentPage,
            per_page: 20
        });
        
        displayResults(result);
        
    } catch (error) {
        resultDiv.innerHTML = `<div class="error">Error: ${error.message}</div>`;
    }
}

function displayResults(result) {
    const resultDiv = document.getElementById('searchResult');
    const data = result.result?.data || {};
    const listings = data.listings || [];
    
    if (listings.length === 0) {
        resultDiv.innerHTML = '<div class="info">No listings found</div>';
        return;
    }
    
    let html = `<h3>Search Results (${listings.length} items)</h3>`;
    html += '<div class="grid grid-3">';
    
    listings.forEach(listing => {
        html += `
            <div class="card">
                <div class="card-title">${listing.brand} ${listing.model}</div>
                <div class="card-body">
                    <p><strong>Year:</strong> ${listing.year}</p>
                    <p><strong>Price:</strong> $${listing.price?.toLocaleString() || 'N/A'}</p>
                    <p><strong>Status:</strong> ${listing.status || 'unknown'}</p>
                    ${can('catalog.listing.get') ? `
                        <a href="#/car/${listing.id}" class="btn btn-primary btn-sm mt-md">View Details</a>
                    ` : ''}
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    resultDiv.innerHTML = html;
}

function clearFilters() {
    document.getElementById('filterBrand').value = '';
    document.getElementById('filterModel').value = '';
    document.getElementById('filterYear').value = '';
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterMinPrice').value = '';
    document.getElementById('filterMaxPrice').value = '';
    currentFilters = {};
    document.getElementById('searchResult').innerHTML = '';
}
