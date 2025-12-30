// Public UI Application Logic

const PLATFORM_URL = 'http://localhost:8080/api/invoke';
const UI_ID = 'public';
const ROLE = 'guest';

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
                user_id: 'guest_user'
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

// Car Browsing
async function listCars() {
    try {
        const result = await callPlatform('car.list', {});
        
        const carList = document.getElementById('carList');
        
        if (result.result && result.result.data) {
            const cars = result.result.data;
            
            if (cars.length === 0) {
                carList.innerHTML = '<p>No cars available</p>';
                return;
            }
            
            carList.innerHTML = cars.map(car => `
                <div class="car-card">
                    <h3>${car.brand} ${car.model}</h3>
                    <p><strong>Year:</strong> ${car.year}</p>
                    <p><strong>Price:</strong> $${car.price}</p>
                    <p><strong>ID:</strong> ${car.id}</p>
                </div>
            `).join('');
        } else {
            showResult('carList', result);
        }
    } catch (error) {
        showResult('carList', { error: error.message }, true);
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
