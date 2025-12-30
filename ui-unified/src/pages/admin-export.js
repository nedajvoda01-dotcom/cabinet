// Admin Export/Import Page

import { createLayout } from '../components/Layout.js';
import { invokeSafe, can } from '../api/guards.js';

export async function render() {
    const content = `
        <div class="section">
            <h2>Export & Import</h2>
            <p>Import car listings from CSV files</p>
            
            ${can('import.run') ? `
                <div class="card mt-md">
                    <div class="card-title">CSV Import</div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Filename:</label>
                            <input type="text" id="importFilename" placeholder="cars.csv" value="import_${Date.now()}.csv">
                        </div>
                        
                        <div class="form-group">
                            <label>CSV Data:</label>
                            <textarea id="importCsvData" rows="10" placeholder="external_id,brand,model,year,price
EXT001,Toyota,Camry,2020,25000
EXT002,Honda,Accord,2021,28000"></textarea>
                        </div>
                        
                        <button class="btn btn-success" id="importBtn">Import CSV</button>
                        
                        <div id="importResult" class="mt-md"></div>
                    </div>
                </div>
            ` : `
                <div class="error">You don't have permission to import data</div>
            `}
            
            <div class="card mt-md">
                <div class="card-title">Import History</div>
                <div class="card-body">
                    <p class="info">Import history feature coming soon...</p>
                </div>
            </div>
        </div>
    `;
    
    createLayout(content);
    
    if (can('import.run')) {
        attachEvents();
    }
}

function attachEvents() {
    document.getElementById('importBtn')?.addEventListener('click', runImport);
}

async function runImport() {
    const btn = document.getElementById('importBtn');
    const resultDiv = document.getElementById('importResult');
    const filename = document.getElementById('importFilename').value;
    const csvData = document.getElementById('importCsvData').value;
    
    if (!filename || !csvData) {
        resultDiv.innerHTML = '<div class="error">Please provide filename and CSV data</div>';
        return;
    }
    
    if (!can('import.run')) {
        resultDiv.innerHTML = '<div class="error">You don\'t have permission to import data</div>';
        return;
    }
    
    try {
        btn.disabled = true;
        btn.textContent = 'Importing...';
        resultDiv.innerHTML = '<div class="loading"></div> <p>Processing import...</p>';
        
        const result = await invokeSafe('import.run', {
            filename: filename,
            csv_data: csvData
        });
        
        displayImportResult(result);
        
    } catch (error) {
        resultDiv.innerHTML = `<div class="error">Import failed: ${error.message}</div>`;
    } finally {
        btn.disabled = false;
        btn.textContent = 'Import CSV';
    }
}

function displayImportResult(result) {
    const resultDiv = document.getElementById('importResult');
    const data = result.result?.data || {};
    
    let html = '<div class="success">Import completed successfully!</div>';
    
    html += '<div class="mt-md">';
    html += `<h4>Import Summary</h4>`;
    html += `<p><strong>Import ID:</strong> ${data.import_id || 'N/A'}</p>`;
    html += `<p><strong>Status:</strong> ${data.status || 'N/A'}</p>`;
    html += `<p><strong>Filename:</strong> ${data.filename || 'N/A'}</p>`;
    
    if (data.records_created !== undefined) {
        html += `<p><strong>Records Created:</strong> ${data.records_created}</p>`;
    }
    
    if (data.records_updated !== undefined) {
        html += `<p><strong>Records Updated:</strong> ${data.records_updated}</p>`;
    }
    
    if (data.records_failed !== undefined) {
        html += `<p><strong>Records Failed:</strong> ${data.records_failed}</p>`;
    }
    
    if (data.message) {
        html += `<p><strong>Message:</strong> ${data.message}</p>`;
    }
    
    html += '</div>';
    
    // Show full result details
    html += `
        <details class="mt-md">
            <summary style="cursor: pointer; font-weight: bold;">View Full Response</summary>
            <pre class="result mt-md">${JSON.stringify(result, null, 2)}</pre>
        </details>
    `;
    
    resultDiv.innerHTML = html;
}
