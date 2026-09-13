<?php
/* browse.php - Complete Sorting Matrix & Dynamic Ingest API Core */

$ledgerFile = 'listings.json';
$listings = [];

if (file_exists($ledgerFile)) {
    $listings = json_decode(file_get_contents($ledgerFile), true) ?? [];
}

// 1. Ingest all standardized interface parameters
$searchQuery    = isset($_GET['q']) ? strtolower(trim($_GET['q'])) : '';
$categoryFilter = isset($_GET['cat']) ? strtolower(trim($_GET['cat'])) : '';
$zipFilter      = isset($_GET['zip']) ? trim($_GET['zip']) : '';
$rangeFilter    = isset($_GET['range']) ? trim($_GET['range']) : ''; 
$sortFilter     = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest'; // Options: newest, oldest, price_low, price_high

// 2. HELPER FUNCTIONS: Lat/Lon API Geocoding Lookups & Haversine Distance Mechanics
function getLiveCoordinates($zipCode) {
    $zipCode = preg_replace('/[^0-9]/', '', $zipCode);
    if (strlen($zipCode) !== 5) return null;

    $apiUrl = "https://zippopotam.us" . $zipCode;
    $ctx = stream_context_create(['http' => ['timeout' => 2]]);
    $response = @file_get_contents($apiUrl, false, $ctx);
    
    if ($response === false) return null;

    $data = json_decode($response, true);
    if (!empty($data['places'][0])) {
        return [
            'lat' => floatval($data['places'][0]['latitude']),
            'lon' => floatval($data['places'][0]['longitude'])
        ];
    }
    return null;
}

function calculateDynamicDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadiusMiles = 3958.8;
    $deltaLat = deg2rad($lat2 - $lat1);
    $deltaLon = deg2rad($lon2 - $lon1);

    $a = sin($deltaLat / 2) * sin($deltaLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($deltaLon / 2) * sin($deltaLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return ($earthRadiusMiles * $c) * 1.25; // Returns road-mile estimates
}

// Resolve buyer anchor layer coordinates
$buyerCoords = (!empty($zipFilter) && !empty($rangeFilter)) ? getLiveCoordinates($zipFilter) : null;

// 3. Process item array validation filters
$filteredListings = [];
foreach ($listings as $item) {
    
    if (($item['status'] ?? 'active') !== 'active') continue;

    if (!empty($searchQuery)) {
        $titleMatch = strpos(strtolower($item['title'] ?? ''), $searchQuery) !== false;
        $descMatch  = strpos(strtolower($item['description'] ?? ''), $searchQuery) !== false;
        if (!$titleMatch && !$descMatch) continue;
    }
    
    if (!empty($categoryFilter) && ($item['category'] ?? '') !== $categoryFilter) continue;
    
    if (!empty($zipFilter)) {
        $itemZip = trim($item['zip'] ?? '');
        
        if (!empty($rangeFilter) && is_numeric($rangeFilter)) {
            if ($buyerCoords) {
                $itemCoords = getLiveCoordinates($itemZip);
                if ($itemCoords) {
                    $computedMiles = calculateDynamicDistance($buyerCoords['lat'], $buyerCoords['lon'], $itemCoords['lat'], $itemCoords['lon']);
                    if ($computedMiles > intval($rangeFilter)) continue;
                } else {
                    continue; 
                }
            }
        } else if ($itemZip !== $zipFilter) {
            continue;
        }
    }
    
    $filteredListings[] = $item;
}

// 4. CORE ENGINE SORTING SELECTION MATRIX - Re-arranges listings natively in temporary memory
usort($filteredListings, function($a, $b) use ($sortFilter) {
    if ($sortFilter === 'oldest') {
        return ($a['timestamp'] ?? 0) <=> ($b['timestamp'] ?? 0);
    }
    if ($sortFilter === 'price_low') {
        return intval($a['price'] ?? 0) <=> intval($b['price'] ?? 0);
    }
    if ($sortFilter === 'price_high') {
        return intval($b['price'] ?? 0) <=> intval($a['price'] ?? 0);
    }
    // Default Fallback: 'newest' (Newest Listings First Chronological)
    return ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0);
});
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Browse - COMMON.</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header>
        <a href="index" class="logo-btn"><h1>COMMON.</h1></a>
        <a href="post" class="btn">+ Sell Something</a>
    </header>

    <main class="split">
        <!-- LEFT COLUMN: SIDEBAR CONTROLS FILTER BAR -->
       <aside class="sidebar">
    <form method="GET" action="browse">
        <div style="margin-bottom: 1rem;">
            <label style="font-size:0.85rem; font-weight:600; margin-bottom:0.25rem; display:block; color:var(--text-muted);">Keywords</label>
            <input type="text" name="q" placeholder="Search keywords..." value="<?php echo htmlspecialchars($searchQuery); ?>">
        </div>
        
        <div style="margin-bottom: 1rem;">
            <label style="font-size:0.85rem; font-weight:600; margin-bottom:0.25rem; display:block; color:var(--text-muted);">ZIP Code</label>
            <input type="number" name="zip" placeholder="54481" value="<?php echo htmlspecialchars($zipFilter); ?>">
        </div>
        
        <div style="margin-bottom: 1rem;">
            <label style="font-size:0.85rem; font-weight:600; margin-bottom:0.25rem; display:block; color:var(--text-muted);">Distance Radius</label>
            <select name="range" onchange="this.form.submit()">
                <option value="" <?php echo $rangeFilter === '' ? 'selected' : ''; ?>>Exact location</option>
                <option value="5" <?php echo $rangeFilter === '5' ? 'selected' : ''; ?>>Within 5 miles</option>
                <option value="10" <?php echo $rangeFilter === '10' ? 'selected' : ''; ?>>Within 10 miles</option>
                <option value="15" <?php echo $rangeFilter === '15' ? 'selected' : ''; ?>>Within 15 miles</option>
                <option value="25" <?php echo $rangeFilter === '25' ? 'selected' : ''; ?>>Within 25 miles</option>
                <option value="50" <?php echo $rangeFilter === '50' ? 'selected' : ''; ?>>Within 50 miles</option>
            </select>
        </div>
        
        <div style="margin-bottom: 1rem;">
            <label style="font-size:0.85rem; font-weight:600; margin-bottom:0.25rem; display:block; color:var(--text-muted);">Sort Listings</label>
            <select name="sort" onchange="this.form.submit()">
                <option value="newest" <?php echo $sortFilter === 'newest' ? 'selected' : ''; ?>>🕒 Newest First</option>
                <option value="oldest" <?php echo $sortFilter === 'oldest' ? 'selected' : ''; ?>>⏳ Oldest First</option>
                <option value="price_low" <?php echo $sortFilter === 'price_low' ? 'selected' : ''; ?>>📈 Price: Low to High</option>
                <option value="price_high" <?php echo $sortFilter === 'price_high' ? 'selected' : ''; ?>>📉 Price: High to Low</option>
            </select>
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label style="font-size:0.85rem; font-weight:600; margin-bottom:0.25rem; display:block; color:var(--text-muted);">Category Filter</label>
            <select name="cat" onchange="this.form.submit()">
                <option value="">All Categories</option>
                <option value="vehicles" <?php echo $categoryFilter === 'vehicles' ? 'selected' : ''; ?>>🚗 Vehicles</option>
                <option value="parts" <?php echo $categoryFilter === 'parts' ? 'selected' : ''; ?>>⚙️ Auto Parts</option>
                <option value="tools" <?php echo $categoryFilter === 'tools' ? 'selected' : ''; ?>>🔧 Tools</option>
                <option value="garden" <?php echo $categoryFilter === 'garden' ? 'selected' : ''; ?>>🌱 Garden & Outdoor</option>
                <option value="furniture" <?php echo $categoryFilter === 'furniture' ? 'selected' : ''; ?>>🛋️ Furniture</option>
                <option value="appliances" <?php echo $categoryFilter === 'appliances' ? 'selected' : ''; ?>>🔌 Appliances</option>
                <option value="electronics" <?php echo $categoryFilter === 'electronics' ? 'selected' : ''; ?>>💻 Tech & Computing</option>
                <option value="phones" <?php echo $categoryFilter === 'phones' ? 'selected' : ''; ?>>📱 Mobile Phones</option>
                <option value="sporting" <?php echo $categoryFilter === 'sporting' ? 'selected' : ''; ?>>🚲 Sports & Bikes</option>
                <option value="hobbies" <?php echo $categoryFilter === 'hobbies' ? 'selected' : ''; ?>>🎨 Hobbies & Art</option>
                <option value="books" <?php echo $categoryFilter === 'books' ? 'selected' : ''; ?>>📚 Books & Media</option>
                <option value="free" <?php echo $categoryFilter === 'free' ? 'selected' : ''; ?>>🎁 Free Curb Alerts</option>
            </select>
        </div>
        <button type="submit" class="btn" style="width: 100%; font-size: 0.95rem; padding: 0.7rem;">Apply Filters</button>
    </form>
</aside>

        <!-- RIGHT COLUMN: DYNAMIC GRID OUTPUT -->
        <section>
            <div class="feed-grid">
                <?php if (empty($filteredListings)): ?>
                    <p style="color: var(--text-muted); font-style: italic; grid-column: 1 / -1;">No active listings found matching those parameters. Check back soon!</p>
                <?php else: ?>
                    <?php foreach ($filteredListings as $listing): ?>
                        <!-- Link points dynamically to individual listing passing its record database id key -->
                        <a href="listing?id=<?php echo urlencode($listing['id']); ?>" class="card">
                            <!-- Inside browse.php -> Replace the image block with this array-aware check -->
						<div class="img-wrap">
							<?php 
								// FIX: Adding the [0] array check ensures it pulls the single string path tracking characters cleanly
								if (!empty($listing['images']) && is_array($listing['images']) && isset($listing['images'][0])) {
									$displayImg = $listing['images'][0];
								} else {
									$displayImg = 'https://unsplash.com';
								}
							?>
							<img src="<?php echo htmlspecialchars($displayImg); ?>" alt="Item Image">
							<div class="badge <?php echo ($listing['status'] ?? 'active') === 'active' ? 'badge-active' : 'badge-pending'; ?>" style="position:absolute; top:8px; left:8px;">
								<?php echo ($listing['status'] ?? 'active') === 'active' ? '● Active' : '⏳ Pending Hold'; ?>
							</div>
						</div>
                            <div class="card-details">
                                <span class="price">$<?php echo intval($listing['price']); ?></span>
                                <h4 style="margin:0; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo htmlspecialchars($listing['title']); ?></h4>
                                <span style="font-size:0.75rem; color:var(--text-muted);"><?php echo htmlspecialchars($listing['zip']); ?> • <?php echo date("M j, g:i a", $listing['timestamp']); ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer>
        <div class="footer-brand">
            <h4>COMMON.</h4>
            <p>An open-source, decentralized local classifieds prototype. This software does not run background tracking algorithms, store user cookies, or collect biometric network data.</p>
        </div>
        
        <div class="footer-links">
            <h5>The Infrastructure</h5>
            <ul>
                <li>
                    <a href="javascript:void(0);" onclick="toggleHowItRuns()" id="howItRunsToggleLink">How it Runs (Serverless Routing) ↓</a>
                    <div id="howItRunsPopup" style="display: none; margin-top: 0.75rem; padding: 1rem; background: #f4f4f5; border: 1px solid var(--border); border-radius: 6px; font-size: 0.85rem; color: #333; line-height: 1.4;">
                        <strong>Stateless Architecture Protocol:</strong><br><br>
                        This platform functions without a traditional central user database. When listings are created, data fields are stored as single-use transactional records indexed strictly by geographical location filters.
                    </div>
                </li>
                <li>
                    <a href="javascript:void(0);" onclick="togglePrivacyBlueprint()" id="privacyBlueprintToggleLink">Data Privacy Blueprint ↓</a>
                    <div id="privacyBlueprintPopup" style="display: none; margin-top: 0.75rem; padding: 1rem; background: #f4f4f5; border: 1px solid var(--border); border-radius: 6px; font-size: 0.85rem; color: #333; line-height: 1.4;">
                        <strong>Uncompromising Anonymity Principles:</strong><br><br>
                        • 0 KB Tracking compiled into this application code.<br>
                        • Sensitive endpoints remain masked from public scrapers natively.
                    </div>
                </li>
                <li>
                    <a href="javascript:void(0);" onclick="toggleSafeZones()" id="safeZoneToggleLink">Safe Swap Exchange Locations ↓</a>
                    <div id="safeZonePopup" style="display: none; margin-top: 0.75rem; padding: 1rem; background: #edf7ed; border: 1px solid #c3e6cb; border-radius: 6px; font-size: 0.85rem; color: #1e4620; line-height: 1.4;">
                        <ul id="safeLocationList" style="margin: 0; padding-left: 1.2rem;"></ul>
                    </div>
                </li>
            </ul>
        </div>

               <div class="footer-links">
            <h5>The Project</h5>
            <ul>
                <li><a href="https://github.com" target="_blank">View GitHub Repository</a></li>
                <li>
                    <a href="javascript:void(0);" onclick="toggleOwnershipInfo()" id="ownerToggleLink">Who Owns & Runs This? ↓</a>
                    <div id="ownershipPopup" style="display: none; margin-top: 0.75rem; padding: 1rem; background: #f4f4f5; border: 1px solid var(--border); border-radius: 6px; font-size: 0.85rem; color: #333; line-height: 1.4;">
                        <strong>Nobody owns it. It is an open-source utility.</strong><br><br>
                        This platform is a transparent public prototype born from real exhaustion with modern big-tech data tracking and web slop.
                    </div>
                </li>
            </ul>
        </div>
    </footer>

    <script src="app.js"></script>
</body>
</html>
