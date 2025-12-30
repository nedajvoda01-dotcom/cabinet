// Admin Content Management Page

import { createLayout } from '../components/Layout.js';
import { invokeSafe, can } from '../api/guards.js';

export async function render() {
    const content = `
        <div class="section">
            <h2>Content Management</h2>
            <p>Manage catalog listings and content</p>
            
            <div class="mt-md">
                <button class="btn btn-primary" id="loadListingsBtn">Load Listings</button>
                <button class="btn btn-success ml-md" id="refreshBtn">Refresh</button>
            </div>
            
            <div id="listingsResult" class="mt-lg"></div>
        </div>
    `;
    
    createLayout(content);
    attachEvents();
}

function attachEvents() {
    document.getElementById('loadListingsBtn').addEventListener('click', loadListings);
    document.getElementById('refreshBtn').addEventListener('click', loadListings);
}

async function loadListings() {
    const btn = document.getElementById('loadListingsBtn');
    const resultDiv = document.getElementById('listingsResult');
    
    if (!can('catalog.listings.search')) {
        resultDiv.innerHTML = '<div class="error">You don\'t have permission to view listings</div>';
        return;
    }
    
    try {
        btn.disabled = true;
        resultDiv.innerHTML = '<div class="loading"></div>';
        
        const result = await invokeSafe('catalog.listings.search', {
            filters: {},
            page: 1,
            per_page: 50
        });
        
        const data = result.result?.data || {};
        const listings = data.listings || [];
        
        displayListings(listings);
        
    } catch (error) {
        resultDiv.innerHTML = `<div class="error">Error: ${error.message}</div>`;
    } finally {
        btn.disabled = false;
    }
}

function displayListings(listings) {
    const resultDiv = document.getElementById('listingsResult');
    
    if (listings.length === 0) {
        resultDiv.innerHTML = '<div class="info">No listings found</div>';
        return;
    }
    
    let html = `
        <h3>Listings (${listings.length} items)</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Brand</th>
                    <th>Model</th>
                    <th>Year</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    listings.forEach(listing => {
        html += `
            <tr>
                <td>${listing.id}</td>
                <td>${listing.brand}</td>
                <td>${listing.model}</td>
                <td>${listing.year}</td>
                <td>$${listing.price?.toLocaleString() || 'N/A'}</td>
                <td><span class="badge badge-${getStatusClass(listing.status)}">${listing.status || 'unknown'}</span></td>
                <td>
                    <a href="#/car/${listing.id}" class="btn btn-primary btn-sm">View</a>
                    ${can('catalog.listing.use') && listing.status === 'available' ? `
                        <button class="btn btn-success btn-sm ml-sm" onclick="window.useListing('${listing.id}')">Use</button>
                    ` : ''}
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table>';
    
    // Add CSS for table
    html = `
        <style>
            .data-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 1rem;
            }
            .data-table th, .data-table td {
                padding: 12px;
                text-align: left;
                border-bottom: 1px solid var(--color-border);
            }
            .data-table th {
                background: var(--color-bg);
                font-weight: bold;
            }
            .data-table tr:hover {
                background: var(--color-bg);
            }
            .badge {
                padding: 4px 8px;
                border-radius: 4px;
                font-size: 12px;
                font-weight: 500;
            }
            .badge-available { background: #d5f4e6; color: #27ae60; }
            .badge-sold { background: #fadbd8; color: #e74c3c; }
            .badge-reserved { background: #fef5e7; color: #f39c12; }
        </style>
    ` + html;
    
    resultDiv.innerHTML = html;
}

function getStatusClass(status) {
    const statusMap = {
        'available': 'available',
        'sold': 'sold',
        'reserved': 'reserved',
        'used': 'sold'
    };
    return statusMap[status] || 'available';
}

// Global function for use listing button
window.useListing = async function(listingId) {
    if (!can('catalog.listing.use')) {
        alert('You don\'t have permission to use listings');
        return;
    }
    
    if (!confirm('Are you sure you want to mark this listing as used?')) {
        return;
    }
    
    try {
        await invokeSafe('catalog.listing.use', {
            listing_id: listingId
        });
        
        alert('Listing marked as used successfully!');
        
        // Reload listings
        loadListings();
        
    } catch (error) {
        alert(`Error: ${error.message}`);
    }
};
