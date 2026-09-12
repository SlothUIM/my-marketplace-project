/* app.js - Shared Interactive Logic */

// Automatically trigger the location scan on page load only if the user hasn't explicitly chosen to skip it
document.addEventListener("DOMContentLoaded", () => {
    // Check if the user previously blocked or allowed location to prevent constant, jarring prompt loops
    if (localStorage.getItem("common_location_scanned") !== "true") {
        autoDetectLocation();
    }
});

function autoDetectLocation() {
    if (!navigator.geolocation) {
        showManualLocationMessage();
        return;
    }

    // Set a flag immediately so the browser does not spam the user on every page click or refresh
    localStorage.setItem("common_location_scanned", "true");

    navigator.geolocation.getCurrentPosition(async (position) => {
        const lat = position.coords.latitude;
        const lon = position.coords.longitude;

        try {
            const response = await fetch(`https://bigdatacloud.com{lat}&longitude=${lon}&localityLanguage=en`);
            const data = await response.json();
            
            if (data.postcode) {
                const detectedZip = data.postcode.trim();
                
                // Update layout inputs instantly if coordinates are resolved securely
                const zipInputs = document.querySelectorAll('input[type="number"]');
                zipInputs.forEach(input => {
                    input.value = detectedZip;
                });
                
                console.log(`[Common Geolocation] Auto-populated sector: ${detectedZip}`);
            } else {
                showManualLocationMessage();
            }
        } catch (error) {
            showManualLocationMessage();
        }
    }, (error) => {
        // Triggers if browser access permissions are explicitly blocked
        showManualLocationMessage();
    });
}

// Clean text delivery if permission is blocked or API drops context
function showManualLocationMessage() {
    const listContainer = document.getElementById('safeLocationList');
    if (listContainer) {
        listContainer.innerHTML = `<li style="list-style: none; margin-left: -1.2rem; color: var(--text-muted); font-style: italic;">
            Location permissions disabled or unavailable. Type your local ZIP code into the search parameters box above to populate regional safe swap points automatically.
        </li>`;
    }
    console.log("[Common Geolocation] Standing by for manual input filters.");
}

// 1. Safe Swap Toggle
function toggleSafeZones() {
    const popup = document.getElementById('safeZonePopup');
    const link = document.getElementById('safeZoneToggleLink');
    const listContainer = document.getElementById('safeLocationList');
    if (!popup || !link || !listContainer) return;

    const zipInput = document.querySelector('input[type="number"]');
    const currentZip = zipInput ? zipInput.value.trim() : "";

    if (popup.style.display === 'none' || popup.style.display === '') {
        // Since we dropped the static const mapping, we dynamically direct the user to their local municipal office using the current text field context
        const locationText = currentZip ? `ZIP Code ${currentZip}` : "your current area";
        
        listContainer.innerHTML = `
            <li style="margin-bottom:0.4rem; color:#222;">The main municipal police department precinct closest to ${locationText}.</li>
            <li style="margin-bottom:0.4rem; color:#222;">Highly trafficked public municipal station lobbies or designated, camera-monitored "Safe Trade" public lots.</li>
        `;
        
        popup.style.display = 'block';
        link.innerHTML = 'Safe Swap Exchange Locations ↑';
        link.style.fontWeight = '700';
    } else {
        popup.style.display = 'none';
        link.innerHTML = 'Safe Swap Exchange Locations ↓';
        link.style.fontWeight = 'normal';
    }
}

// 2. Who Owns This Toggle
function toggleOwnershipInfo() {
    const popup = document.getElementById('ownershipPopup');
    const link = document.getElementById('ownerToggleLink');
    if (!popup || !link) return;
    
    if (popup.style.display === 'none' || popup.style.display === '') {
        popup.style.display = 'block';
        link.innerHTML = 'Who Owns & Runs This? ↑';
        link.style.fontWeight = '700';
    } else {
        popup.style.display = 'none';
        link.innerHTML = 'Who Owns & Runs This? ↓';
        link.style.fontWeight = 'normal';
    }
}

// 3. How It Runs (Serverless Routing) Toggle
function toggleHowItRuns() {
    const popup = document.getElementById('howItRunsPopup');
    const link = document.getElementById('howItRunsToggleLink');
    if (!popup || !link) return;
    
    if (popup.style.display === 'none' || popup.style.display === '') {
        popup.style.display = 'block';
        link.innerHTML = 'How it Runs (Serverless Routing) ↑';
        link.style.fontWeight = '700';
    } else {
        popup.style.display = 'none';
        link.innerHTML = 'How it Runs (Serverless Routing) ↓';
        link.style.fontWeight = 'normal';
    }
}

// 4. Data Privacy Blueprint Toggle
function togglePrivacyBlueprint() {
    const popup = document.getElementById('privacyBlueprintPopup');
    const link = document.getElementById('privacyBlueprintToggleLink');
    if (!popup || !link) return;
    
    if (popup.style.display === 'none' || popup.style.display === '') {
        popup.style.display = 'block';
        link.innerHTML = 'Data Privacy Blueprint ↑';
        link.style.fontWeight = '700';
    } else {
        popup.style.display = 'none';
        link.innerHTML = 'Data Privacy Blueprint ↓';
        link.style.fontWeight = 'normal';
    }
}
