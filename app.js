/* app.js - Shared Interactive Logic */

// Verified safe swap zone entries
const verifiedSafeZones = {
    "54481": [
        "Stevens Point Police Department Lobby & Lot (933 Michigan Ave) - 24/7 Monitored Cameras",
        "Portage County Sheriff's Office Parking Exchange Area (1500 Strongs Ave) - Well-Lit Public Space"
    ],
    "54467": [
        "Plover Police Department Main Exchange Lot (2420 Post Rd) - Designated Safe Trade Zone"
    ],
    "default": [
        "Your Nearest Local Municipal Police Department Parking Lot",
        "Highly Trafficked, Well-Lit Public Station Areas during daytime operational hours"
    ]
};

// 1. Safe Swap Toggle
function toggleSafeZones() {
    const popup = document.getElementById('safeZonePopup');
    const link = document.getElementById('safeZoneToggleLink');
    const listContainer = document.getElementById('safeLocationList');
    if (!popup || !link || !listContainer) return;

    const zipInput = document.querySelector('input[type="number"]');
    const currentZip = zipInput ? zipInput.value.trim() : "54481";

    if (popup.style.display === 'none' || popup.style.display === '') {
        const spots = verifiedSafeZones[currentZip] || verifiedSafeZones["default"];
        listContainer.innerHTML = spots.map(spot => `<li style="margin-bottom:0.4rem; color:#222;">${spot}</li>`).join('');
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
