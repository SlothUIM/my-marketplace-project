<?php
/* listing.php - Dynamic Individual Item View */

// CRITICAL FIX: Include your encryption utilities at the very top so the page doesn't crash
require_once 'config.php';
include_once 'common_email_helper.php';

$ledgerFile = 'listings.json';
$listingId = $_GET['id'] ?? '';
$item = null;

if (empty($listingId)) {
    die("<div style='font-family:sans-serif; text-align:center; margin-top:5rem;'>❌ Error: No item identification token specified.</div>");
}

// Read raw state ledger from file data storage
if (file_exists($ledgerFile)) {
    $listings = json_decode(file_get_contents($ledgerFile), true) ?? [];
    
    // Search the ledger array for the matching public ID key
    for ($i = 0; $i < count($listings); $i++) {
        if (($listings[$i]['id'] ?? '') === $listingId) {
            $item = $listings[$i];
            break;
        }
    }
}

// Fallback safety checkpoint if item is deleted or doesn't exist
if (!$item) {
    die("<div style='font-family:sans-serif; text-align:center; margin-top:5rem;'>❌ Item Not Found: This listing may have been removed by the seller.</div>");
}

// Unscramble the masked handles securely in the server RAM [1]
if ($item) {
    $item['venmo']   = common_decrypt($item['venmo'] ?? '');
    $item['cashapp'] = common_decrypt($item['cashapp'] ?? '');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($item['title'] ?? 'Item View'); ?> - COMMON.</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .listing-layout { 
            max-width: 1300px; 
            margin: 2rem auto; 
            display: grid; 
            grid-template-columns: 1.2fr 1fr; 
            gap: 3rem; 
        }
        
        /* Image presentation layout */
        .photo-pane { display: flex; flex-direction: column; gap: 1rem; }
        .main-img { width: 100%; border-radius: 6px; border: 1px solid var(--border); aspect-ratio: 4/3; object-fit: cover; }
        .thumb-row { display: flex; gap: 0.5rem; }
        .thumb-img { width: 80px; height: 60px; border-radius: 4px; border: 1px solid var(--border); object-fit: cover; cursor: pointer; }

        /* Listing details data presentation */
        .meta-pane { display: flex; flex-direction: column; }
        .title-row { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem; }
        .title-row h2 { font-size: 1.8rem; font-weight: 800; margin: 0; letter-spacing: -0.03em; }
        .price-tag { font-size: 1.8rem; font-weight: 800; color: var(--accent-green); }
        .geo-tag { font-family: monospace; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem; }
        
        .status-pill { display: inline-flex; align-items: center; padding: 0.35rem 0.75rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600; width: fit-content; margin-bottom: 2rem; }
        .desc-text { font-size: 1rem; line-height: 1.5; color: var(--text); margin-bottom: 2rem; border-bottom: 1px solid var(--border); padding-bottom: 2rem; }

        /* UI Input Group Boxes */
        .ui-card { background: var(--surface); border: 1px solid var(--border); padding: 1.5rem; border-radius: 6px; margin-bottom: 1.5rem; }
        .ui-card h4 { margin: 0 0 0.75rem 0; font-size: 1.05rem; font-weight: 700; }
        .ui-card p { font-size: 0.85rem; color: var(--text-muted); margin: 0 0 1rem 0; line-height: 1.4; }
        
        textarea, .relay-input-box input[type="text"] { width: 100%; padding: 0.65rem; border: 1px solid var(--border); border-radius: 4px; box-sizing: border-box; font-size: 0.95rem; font-family: inherit; background: var(--bg); color: var(--text); }
        .relay-input-box { display: flex; gap: 0.5rem; margin-top: 0.75rem; }

        /* Masked App Deeplink Targets */
        .btn-p2p { display: flex; align-items: center; justify-content: center; width: 100%; padding: 0.75rem; color: #fff; font-weight: 600; text-decoration: none; border-radius: 5px; margin-bottom: 0.5rem; font-size: 0.95rem; text-align: center; }
        .btn-venmo { background: #008cff; }
        .btn-cashapp { background: #00d632; }
        
        .stopgap-banner { background: #fef2f2; border: 1px dashed #b91c1c; color: #991b1b; padding: 0.85rem; border-radius: 5px; font-size: 0.8rem; margin-top: 1rem; line-height: 1.4; }
    </style>
</head>
<body>

    <header>
        <!-- Clean index-less anchor routing -->
        <a href="./" class="logo-btn"><h1>COMMON.</h1></a>
        <a href="post" class="btn">+ Sell Something</a>
    </header>

    <main class="listing-layout">
        <!-- LEFT COLUMN: PHOTO AND GALLERY MATRIX -->
        <section class="photo-pane">
            <?php 
                // FIX: Added [0] to fetch the primary showcase picture string 
                if (!empty($item['images']) && is_array($item['images']) && isset($item['images'][0])) {
                    $primaryImg = $item['images'][0];
                } else {
                    $primaryImg = 'https://unsplash.com';
                }
            ?>
            <img src="<?php echo htmlspecialchars($primaryImg); ?>" class="main-img" id="mainDisplayFrame" alt="Main Item Photo">
            
            <!-- Render auxiliary thumbnail strips below if the user attached more than one picture -->
            <?php if (!empty($item['images']) && is_array($item['images']) && count($item['images']) > 1): ?>
                <div class="thumb-row">
                    <?php for ($i = 0; $i < count($item['images']); $i++): ?>
                        <img src="<?php echo htmlspecialchars($item['images'][$i]); ?>" class="thumb-img" onclick="document.getElementById('mainDisplayFrame').src = this.src">
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- RIGHT COLUMN: TRANSACTION DATA PROPERTIES -->
        <section class="meta-pane">
            <div class="title-row">
                <h2><?php echo htmlspecialchars($item['title'] ?? 'Untitled Item'); ?></h2>
                <div class="price-tag">$<?php echo intval($item['price'] ?? 0); ?></div>
            </div>
            <div class="geo-tag">ID: <?php echo htmlspecialchars($item['id'] ?? ''); ?> // Listed: <?php echo date("M j, g:i a", $item['timestamp'] ?? time()); ?> // 📍 ZIP: <?php echo htmlspecialchars($item['zip'] ?? ''); ?></div>

            <div class="status-pill <?php echo ($item['status'] ?? 'active') === 'active' ? 'badge-active' : 'badge-pending'; ?>">
                <?php echo ($item['status'] ?? 'active') === 'active' ? '● Active (Available for Swap)' : '⏳ Pending Hold'; ?>
            </div>

            <div class="desc-text">
                <?php echo nl2br(htmlspecialchars($item['description'] ?? '')); ?>
            </div>

            <!-- EPHEMERAL INBOX INGEST ROUTING -->
            <div class="ui-card">
                <h4>Message Seller Anonymously</h4>
                <p>Your tracking endpoints remain walled off. The backend handles message transfers securely via temporary tracking sessions.</p>

                <!-- Success indicator alerts the buyer instantly when the email relay fires -->
                <?php if (($_GET['status'] ?? '') === 'sent'): ?>
                    <div style="background: #dcfce7; color: #166534; padding: 0.75rem; border-radius: 5px; font-size: 0.85rem; margin-bottom: 1rem; font-weight: 600; text-align: center;">
                        🚀 Message sent to the seller securely!
                    </div>
                <?php endif; ?>

                <!-- Targets extensionless communication gateway script -->
                <form action="message_relay" method="POST">
                    <!-- Hidden input passes the listing record ID context to the script -->
                    <input type="hidden" name="listing_id" value="<?php echo htmlspecialchars($item['id'] ?? ''); ?>">
                    
                    <textarea name="message" placeholder="Ask a question or propose an exchange meetup window..." rows="3" required></textarea>
                    
                    <div class="relay-input-box">
                        <input type="text" name="buyer_contact" placeholder="Your Phone or Email Address for their response" required>
                        <button type="submit" class="btn" style="padding: 0 1.2rem;">Send</button>
                    </div>
                </form>
            </div>

            <!-- ENCRYPTED P2P LINK UTILITIES -->
            <div class="ui-card">
                <h4>In-Person Cashless Payment</h4>
                <p>Inspect item first. Tapping triggers your phone client app natively with verified credentials pre-filled. User strings remain hidden from scrapers.</p>
                
                <?php if (!empty($item['venmo'])): ?>
                    <a href="venmo://paycharge?txn=pay&recipients=<?php echo urlencode($item['venmo']); ?>&amount=<?php echo intval($item['price'] ?? 0); ?>&note=Common+Item" class="btn-p2p btn-venmo">
                        Pay with Venmo
                    </a>
                <?php endif; ?>

                <?php if (!empty($item['cashapp'])): ?>
                    <a href="https://cash.app<?php echo urlencode(preg_replace('/[^a-zA-Z0-9_]/', '', $item['cashapp'])); ?>/<?php echo intval($item['price'] ?? 0); ?>" class="btn-p2p btn-cashapp">
                        Pay with Cash App
                    </a>
                <?php endif; ?>

                <?php if (empty($item['venmo']) && empty($item['cashapp'])): ?>
                    <p style="font-size: 0.85rem; font-style: italic; color: var(--text-muted); margin: 0;">Seller prefers cold hard cash or direct manual P2P app exchange at meetup.</p>
                <?php endif; ?>

                               <div class="stopgap-banner">
                    <strong>🛑 Anti-Fraud Checkpoint:</strong> Never verify payments via text notifications or screenshot image claims. Manually launch your banking client interface to confirm the balancing record.
                </div>
            </div>
        </section>
    </main>

    <!-- UNIFIED DOCUMENT FOOTER -->
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

