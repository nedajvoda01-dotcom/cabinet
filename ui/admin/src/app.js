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
