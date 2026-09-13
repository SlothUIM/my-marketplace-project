<?php
/* browse - Dynamic Chronological Feed Grid */

$ledgerFile = 'listings.json';
$listings = [];

// Read raw data layer from server disk storage
if (file_exists($ledgerFile)) {
    $listings = json_decode(file_get_contents($ledgerFile), true) ?? [];
}

// Gather query parameter criteria from your sidebar filtering tools
$searchQuery = isset($_GET['q']) ? strtolower(trim($_GET['q'])) : '';
$categoryFilter = isset($_GET['cat']) ? strtolower(trim($_GET['cat'])) : '';
$zipFilter = isset($_GET['zip']) ? trim($_GET['zip']) : '';

// Process local filters array mapping matching criteria
$filteredListings = [];
foreach ($listings as $item) {
    // 1. Text Query Filter
    if (!empty($searchQuery)) {
        $titleMatch = strpos(strtolower($item['title'] ?? ''), $searchQuery) !== false;
        $descMatch  = strpos(strtolower($item['description'] ?? ''), $searchQuery) !== false;
        if (!$titleMatch && !$descMatch) continue;
    }
    
    // 2. Category Dropdown Filter
    if (!empty($categoryFilter) && ($item['category'] ?? '') !== $categoryFilter) {
        continue;
    }
    
    // 3. Simple Local ZIP Filter
    if (!empty($zipFilter) && ($item['zip'] ?? '') !== $zipFilter) {
        continue;
    }
    
    $filteredListings[] = $item;
}
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
                    <input type="text" name="q" placeholder="Keywords..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                </div>
                <div style="margin-bottom: 1rem;">
                    <input type="number" name="zip" placeholder="ZIP Code" value="<?php echo htmlspecialchars($zipFilter); ?>">
                </div>
                <div style="margin-bottom: 1rem;">
                    <select name="cat" onchange="this.form.submit()" style="padding: 0.65rem; border: 1px solid var(--border); border-radius: 6px; font-size: 0.95rem; background: var(--surface); color: var(--text); width: 100%; box-sizing: border-box;">
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
                <button type="submit" class="btn" style="width: 100%; font-size: 0.9rem; padding: 0.5rem;">Apply Filters</button>
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
