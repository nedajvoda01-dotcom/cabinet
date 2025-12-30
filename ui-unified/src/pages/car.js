// Car Details Page
// Shows detailed information about a specific car

import { createLayout } from '../components/Layout.js';
import { invokeSafe, can } from '../api/guards.js';

export async function render(path, params) {
    const carId = params.id;
    
    const content = `
        <div class="section">
            <a href="#/catalog" class="btn btn-secondary mb-md">← Back to Catalog</a>
            <h2>Car Details</h2>
            <div id="carDetails">
                <div class="loading"></div>
            </div>
        </div>
    `;
    
    createLayout(content);
    
    // Load car details
    await loadCarDetails(carId);
}

async function loadCarDetails(carId) {
    const detailsDiv = document.getElementById('carDetails');
    
    if (!can('catalog.listing.get')) {
        detailsDiv.innerHTML = '<div class="error">You don\'t have permission to view car details</div>';
        return;
    }
    
    try {
        const result = await invokeSafe('catalog.listing.get', {
            listing_id: carId
        });
        
        const data = result.result?.data || {};
        
        displayCarDetails(data);
        
        // Load photos if capability available
        if (can('catalog.photos.list')) {
            loadPhotos(carId);
        }
        
    } catch (error) {
        detailsDiv.innerHTML = `<div class="error">Error loading car details: ${error.message}</div>`;
    }
}

function displayCarDetails(car) {
    const detailsDiv = document.getElementById('carDetails');
    
    let html = `
        <div class="card">
            <div class="card-title">${car.brand} ${car.model} (${car.year})</div>
            <div class="card-body">
                <div class="grid grid-2">
                    <div>
                        <p><strong>ID:</strong> ${car.id}</p>
                        ${car.external_id ? `<p><strong>External ID:</strong> ${car.external_id}</p>` : ''}
                        <p><strong>Brand:</strong> ${car.brand}</p>
                        <p><strong>Model:</strong> ${car.model}</p>
                        <p><strong>Year:</strong> ${car.year}</p>
                    </div>
                    <div>
                        <p><strong>Price:</strong> $${car.price?.toLocaleString() || 'N/A'}</p>
                        <p><strong>Status:</strong> <span class="badge">${car.status || 'unknown'}</span></p>
                        ${car.vin ? `<p><strong>VIN:</strong> ${car.vin}</p>` : ''}
                        ${car.created_at ? `<p><strong>Listed:</strong> ${new Date(car.created_at).toLocaleDateString()}</p>` : ''}
                    </div>
                </div>
                
                ${car.description ? `
                    <div class="mt-md">
                        <strong>Description:</strong>
                        <p>${car.description}</p>
                    </div>
                ` : ''}
                
                ${can('catalog.listing.use') ? `
                    <div class="mt-md">
                        <button class="btn btn-success" id="useListingBtn">Mark as Used</button>
                    </div>
                ` : ''}
            </div>
        </div>
        
        <div id="photosContainer" class="mt-lg"></div>
    `;
    
    detailsDiv.innerHTML = html;
    
    // Attach use listing button event
    if (can('catalog.listing.use')) {
        document.getElementById('useListingBtn')?.addEventListener('click', () => useListing(car.id));
    }
}

async function useListing(carId) {
    const btn = document.getElementById('useListingBtn');
    
    if (!confirm('Are you sure you want to mark this listing as used?')) {
        return;
    }
    
    try {
        btn.disabled = true;
        btn.textContent = 'Processing...';
        
        const result = await invokeSafe('catalog.listing.use', {
            listing_id: carId
        });
        
        alert('Listing marked as used successfully!');
        
        // Reload details
        await loadCarDetails(carId);
        
    } catch (error) {
        alert(`Error: ${error.message}`);
        btn.disabled = false;
        btn.textContent = 'Mark as Used';
    }
}

async function loadPhotos(carId) {
    const photosDiv = document.getElementById('photosContainer');
    
    try {
        const result = await invokeSafe('catalog.photos.list', {
            listing_id: carId
        });
        
        const data = result.result?.data || {};
        const photos = data.photos || [];
        
        if (photos.length === 0) {
            photosDiv.innerHTML = '<div class="info">No photos available</div>';
            return;
        }
        
        let html = '<h3>Photos</h3><div class="grid grid-3">';
        
        photos.forEach(photo => {
            html += `
                <div class="card">
                    ${photo.url ? `<img src="${photo.url}" alt="Car photo" style="width: 100%; border-radius: 4px;">` : ''}
                    <p class="mt-sm">${photo.type || 'Photo'}</p>
                </div>
            `;
        });
        
        html += '</div>';
        photosDiv.innerHTML = html;
        
    } catch (error) {
        photosDiv.innerHTML = `<div class="error">Error loading photos: ${error.message}</div>`;
    }
}
