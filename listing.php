<?php
/* listing.php - Dynamic Individual Item View with Automated Time-Elapsed and Map Geometry Engines */

require_once 'config.php';
include_once 'common_email_helper.php';

$ledgerFile = 'listings.json';
$listingId = $_GET['id'] ?? '';
$item = null;

if (empty($listingId)) {
    die("<div style='font-family:sans-serif; text-align:center; margin-top:5rem;'>❌ Error: No item identification token specified.</div>");
}

if (file_exists($ledgerFile)) {
    $listings = json_decode(file_get_contents($ledgerFile), true) ?? [];
    
    for ($i = 0; $i < count($listings); $i++) {
        if (($listings[$i]['id'] ?? '') === $listingId) {
            $item = $listings[$i];
            break;
        }
    }
}

if (!$item) {
    die("<div style='font-family:sans-serif; text-align:center; margin-top:5rem;'>❌ Item Not Found: This listing may have been removed by the seller.</div>");
}

if ($item) {
    $item['venmo']   = common_decrypt($item['venmo'] ?? '');
    $item['cashapp'] = common_decrypt($item['cashapp'] ?? '');
}

// --- ⏱️ 1. AUTOMATED CRAIGSLIST-STYLE TIME ELAPSED ENGINE ---
function getHumanTimeElapsed($timestamp) {
    if (empty($timestamp)) return "sometime ago";
    
    $currentTime = time();
    $timeDifference = $currentTime - $timestamp;
    
    if ($timeDifference < 60) {
        return "just seconds ago";
    }
    
    $minutes = round($timeDifference / 60);
    if ($minutes < 60) {
        return "about " . $minutes . " " . ($minutes == 1 ? "minute" : "minutes") . " ago";
    }
    
    $hours = round($timeDifference / 3600);
    if ($hours < 24) {
        return "about " . $hours . " " . ($hours == 1 ? "hour" : "hours") . " ago";
    }
    
    $days = round($timeDifference / 86400);
    if ($days < 30) {
        return "about " . $days . " " . ($days == 1 ? "day" : "days") . " ago";
    }
    
    return "on " . date("M j, Y", $timestamp);
}

$postedTimeString = getHumanTimeElapsed($item['timestamp'] ?? time());
$updatedTimeString = isset($item['updated_timestamp']) ? getHumanTimeElapsed($item['updated_timestamp']) : null;


// --- REPAIRED: ERROR-PROOF COORDINATE CONVERSION ENGINE ---
function getMapSectorCoordinates($zipCode) {
    $zipCode = preg_replace('/[^0-9]/', '', $zipCode);
    if (strlen($zipCode) !== 5) return null;

    $apiUrl = "https://zippopotam.us" . $zipCode;
    $ctx = stream_context_create(['http' => ['timeout' => 2]]);
    $response = @file_get_contents($apiUrl, false, $ctx);
    
    if ($response === false) return null;

    $data = json_decode($response, true);
    if (!empty($data['places'])) {
        return [
            'lat'  => floatval($data['places'][0]['latitude'] ?? 44.5236),
            'lon'  => floatval($data['places'][0]['longitude'] ?? -89.5746),
            'name' => htmlspecialchars(($data['places'][0]['place name'] ?? 'Local') . ', ' . ($data['places'][0]['state abbreviation'] ?? 'Area'))
        ];
    }
    return null;
}

// Fetch coordinates dynamically, with a rock-solid default fallback to Stevens Point, WI
$mapData = getMapSectorCoordinates($item['zip'] ?? '');

$mapLat  = !empty($mapData['lat'])  ? floatval($mapData['lat'])  : 44.5236;
$mapLon  = !empty($mapData['lon'])  ? floatval($mapData['lon'])  : -89.5746;
$mapName = !empty($mapData['name']) ? $mapData['name'] : 'Local Area';

?>


<!DOCTYPE html>
<html lang="en">
<head>
<!-- Open-Source Privacy-First Leaflet Mapping Assets -->
    <!-- FIXED: PINNED FULL SPECIFIC ASSET PATHS FOR LEAFLET LIBRARY -->
    <link rel="stylesheet" href="https://unpkg.com" crossorigin="" />
    <script src="https://unpkg.com" crossorigin=""></script>

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
    <?php include_once 'header.php'; ?>
    
    <!-- 2. NATURAL BACK-BUTTON NAVIGATION ANCHOR -->
    <div style="max-width: 1300px; margin: 1.5rem auto 0 auto; padding: 0 2rem; box-sizing: border-box;">
        <a href="javascript:history.back();" style="color: var(--accent-indigo); text-decoration: none; font-weight: 600; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 0.4rem;">
            ← Back to search results
        </a>
    </div>

        <main class="listing-layout" style="margin-top: 1rem; display: grid; grid-template-columns: 1fr 400px; gap: 2.5rem; max-width: 1300px; margin: 1rem auto 0 auto; padding: 0 2rem; box-sizing: border-box;">
        
        <!-- LEFT COLUMN: PRIMARY VISUAL MEDIA, DESCRIPTION, & LOCALITY MAP -->
        <section class="photo-pane" style="display: flex; flex-direction: column; gap: 2rem; width: 100%;">
            <?php 
                if (!empty($item['images']) && is_array($item['images']) && isset($item['images'][0])) {
                    $primaryImg = $item['images'][0];
                } else {
                    $primaryImg = 'https://unsplash.com';
                }
            ?>
            
            <!-- Gallery Interface Display Frame Wrapper -->
            <div style="width: 100%;">
                <div style="position: relative; width: 100%; aspect-ratio: 4/3; overflow: hidden; border-radius: 6px; border: 1px solid var(--border);">
                    <img src="<?php echo htmlspecialchars($primaryImg); ?>" class="main-img" id="mainDisplayFrame" alt="Main Item Photo" style="width: 100%; height: 100%; object-fit: cover; cursor: zoom-in;" onclick="openImageModal(this.src)">
                    
                    <?php if (!empty($item['images']) && is_array($item['images']) && count($item['images']) > 1): ?>
                        <button type="button" onclick="navigateShowcaseImage(-1); event.stopPropagation();" style="position: absolute; top: 50%; left: 10px; transform: translateY(-50%); background: rgba(15, 23, 42, 0.7); color: #fff; border: none; width: 36px; height: 36px; border-radius: 50%; font-size: 1.1rem; font-weight: bold; cursor: pointer; display: flex; align-items: center; justify-content: center; z-index: 10;">‹</button>
                        <button type="button" onclick="navigateShowcaseImage(1); event.stopPropagation();" style="position: absolute; top: 50%; right: 10px; transform: translateY(-50%); background: rgba(15, 23, 42, 0.7); color: #fff; border: none; width: 36px; height: 36px; border-radius: 50%; font-size: 1.1rem; font-weight: bold; cursor: pointer; display: flex; align-items: center; justify-content: center; z-index: 10;">›</button>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($item['images']) && is_array($item['images']) && count($item['images']) > 1): ?>
                    <div class="thumb-row" style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                        <?php for ($i = 0; $i < count($item['images']); $i++): ?>
                            <img src="<?php echo htmlspecialchars($item['images'][$i]); ?>" class="thumb-img" onclick="updateActiveShowcaseIndex(<?php echo $i; ?>)" style="cursor: pointer; width: 80px; height: 60px; object-fit: cover; border-radius: 4px; border: 2px solid <?php echo $i === 0 ? 'var(--accent-indigo)' : 'var(--border)'; ?>;" data-index="<?php echo $i; ?>">
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- MOVED: ITEM TEXT DESCRIPTION BLOCK -->
            <div style="border-top: 1px solid var(--border); padding-top: 1.5rem;">
                <h3 style="margin: 0 0 0.75rem 0; font-size: 1.2rem; font-weight: 700;">Item Description</h3>
                <div class="desc-text" style="line-height: 1.6; font-size: 1rem; color: var(--text);">
                    <?php echo nl2br(htmlspecialchars($item['description'] ?? '')); ?>
                </div>
            </div>

            <!-- MOVED & REPAIRED: SYMMETRICAL SQUARE LOCALITY MAP CANVAS -->
            <div style="border-top: 1px solid var(--border); padding-top: 1.5rem; margin-bottom: 2rem;">
                <h4 style="margin: 0 0 0.5rem 0; font-size: 1.05rem; font-weight: 700;">Approximate Location Area</h4>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0 0 1.25rem 0; line-height: 1.4;">This map displays the general area based on the seller's ZIP code to protect their precise street address details.</p>
                <div id="listingSectorMapCanvas" style="width: 100%; aspect-ratio: 1/1; max-height: 400px; border-radius: 6px; border: 1px solid var(--border); background: #f1f5f9; z-index: 5;"></div>
            </div>
        </section>

        <!-- RIGHT COLUMN: MINIMALIST TRANSACTION OVERVIEW PANEL -->
        <section class="meta-pane" style="display: flex; flex-direction: column; gap: 1.5rem; position: sticky; top: 1.5rem; height: fit-content;">
            
            <div>
                <div class="title-row" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 0.5rem;">
                    <h2 style="margin: 0; font-size: 1.6rem; font-weight: 800; letter-spacing: -0.02em;"><?php echo htmlspecialchars($item['title'] ?? 'Untitled Item'); ?></h2>
                    <div class="price-tag" style="font-size: 1.6rem; font-weight: 800; color: var(--accent-green);">$<?php echo intval($item['price'] ?? 0); ?></div>
                </div>
                
                <div class="geo-tag" style="color: var(--text-muted); font-size: 0.85rem; display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem;">
                    <span title="<?php echo date('Y-m-d H:i:s', $item['timestamp']); ?>" style="border-bottom: 1px dashed var(--text-muted); cursor: help;">Posted: <?php echo $postedTimeString; ?></span>
                    <?php if ($updatedTimeString): ?>
                        <span>•</span>
                        <span title="<?php echo date('Y-m-d H:i:s', $item['updated_timestamp']); ?>" style="border-bottom: 1px dashed var(--accent-gold); color: var(--accent-gold); font-weight: 600; cursor: help;">Updated: <?php echo $updatedTimeString; ?></span>
                    <?php endif; ?>
                    <span>•</span>
                    <span>ZIP: <?php echo htmlspecialchars($item['zip'] ?? ''); ?></span>
                </div>

                <div class="status-pill <?php echo ($item['status'] ?? 'active') === 'active' ? 'badge-active' : 'badge-pending'; ?>" style="display: inline-block;">
                    <?php echo ($item['status'] ?? 'active') === 'active' ? '● Available' : '⏳ Pending Sale'; ?>
                </div>
            </div>

            <!-- COMPACT TABBED INTERFACE SYSTEM CARD -->
            <div class="ui-card" style="border: 1px solid #d8b4fe; background: #faf5ff; padding: 1.25rem; border-radius: 8px; margin: 0;">
                <h4 style="margin: 0 0 0.5rem 0; color: #6b21a8; font-size: 1.05rem; font-weight: 700;">✉️ Contact & Payment Hub</h4>
                
                <?php 
                    $maskedAlias = "reply-" . substr($item['id'], 5) . "@://slothscape.com";
                    $mailSubject = rawurlencode("Inquiry: " . ($item['title'] ?? 'Item'));
                ?>

                <div style="background: var(--surface); border: 1px solid var(--border); padding: 0.75rem; border-radius: 6px; display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 1rem;">
                    <span id="targetAliasTextString" style="font-family: monospace; font-size: 0.85rem; font-weight: bold; color: var(--accent-indigo); word-break: break-all;"><?php echo $maskedAlias; ?></span>
                    <button type="button" class="btn" onclick="copyAliasToClipboard()" id="copyAliasBtnTrack" style="padding: 0.3rem 0.6rem; font-size: 0.75rem; height: 28px; background: #6b21a8; white-space: nowrap;">Copy</button>
                </div>

                <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem; border-bottom: 2px solid var(--border); padding-bottom: 0.5rem;">
					<button type="button" id="tabBtnMessage" onclick="switchListingTab('message')" style="background: var(--accent-indigo); color: white; border: none; padding: 0.4rem 0.8rem; border-radius: 4px; font-weight: 600; cursor: pointer; font-size: 0.85rem;">✉️ Send Message</button>
					<button type="button" id="tabBtnPayment" onclick="switchListingTab('payment')" style="background: transparent; color: var(--text-muted); border: none; padding: 0.4rem 0.8rem; border-radius: 4px; font-weight: 600; cursor: pointer; font-size: 0.85rem;">📱 Cashless Pay</button>
				</div>

                <div id="listingTabMessage" style="display: block;">
					<div style="display: flex; gap: 0.8rem; font-size: 0.85rem; margin-bottom: 1rem; flex-wrap: wrap;">
						<a href="https://google.com<?php echo $maskedAlias; ?>&su=<?php echo $mailSubject; ?>" target="_blank" style="color: #c2410c; text-decoration: none; font-weight: 600;">🔴 Open Gmail</a>
						<a href="https://yahoo.com<?php echo $maskedAlias; ?>&subj=<?php echo $mailSubject; ?>" target="_blank" style="color: #6b21a8; text-decoration: none; font-weight: 600;">🟣 Open Yahoo</a>
					</div>

					<form action="message_relay" method="POST">
						<input type="hidden" name="listing_id" value="<?php echo htmlspecialchars($item['id'] ?? ''); ?>">
						<textarea name="message" placeholder="Type your message here..." rows="2" required style="width: 100%; box-sizing: border-box; background: var(--surface); margin-bottom: 0.5rem; font-size: 0.9rem; padding: 0.5rem;"></textarea>
						<div class="relay-input-box" style="margin-top: 0; gap: 0.4rem;">
							<input type="text" name="buyer_contact" placeholder="Your Phone or Email address" required style="background: var(--surface); height: 34px; padding: 0.5rem; font-size: 0.9rem;">
							<button type="submit" class="btn" style="height: 34px; font-size: 0.85rem; padding: 0 1rem; background: var(--accent-indigo);">Send Note</button>
						</div>
					</form>
				</div>
            </div>

            <!-- TAB CONTAINER B: COMPACT CASHLESS LINKS -->
            <div id="listingTabPayment" style="display: none;">
                <div class="ui-card" style="padding: 1.25rem;">
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0 0 1rem 0;">Inspect item first. Tapping pre-fills the price directly inside your phone's native app.</p>
                    
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <?php if (!empty($item['venmo'])): ?>
                            <a href="venmo://paycharge?txn=pay&recipients=<?php echo urlencode($item['venmo']); ?>&amount=<?php echo intval($item['price'] ?? 0); ?>&note=Common+Item" class="btn-p2p btn-venmo" style="padding: 0.6rem; font-size: 0.9rem;">Venmo ($<?php echo intval($item['price'] ?? 0); ?>)</a>
                        <?php endif; ?>

                        <?php if (!empty($item['cashapp'])): ?>
                            <a href="https://cash.app<?php echo urlencode(preg_replace('/[^a-zA-Z0-9_]/', '', $item['cashapp'])); ?>/<?php echo intval($item['price'] ?? 0); ?>" class="btn-p2p btn-cashapp" target="_blank" style="padding: 0.6rem; font-size: 0.9rem;">Cash App ($<?php echo intval($item['price'] ?? 0); ?>)</a>
                        <?php endif; ?>

                        <?php if (empty($item['venmo']) && empty($item['cashapp'])): ?>
                            <p style="font-size: 0.85rem; font-style: italic; color: var(--text-muted); margin: 0;">Seller prefers traditional cash at meetup.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div style="background: #fff5f5; border: 1px solid #fecaca; color: #991b1b; padding: 0.75rem; margin-top: 1rem; border-radius: 6px; font-size: 0.8rem; line-height: 1.4;">
                <strong>🛡️ Safety:</strong> Never trust text screenshots. Always manually check your own banking app to confirm funds before handing over items.
            </div>
        </section>
    </main>


    <!-- 1. LIGHTBOX LIGHT MODAL WINDOW GRID LAYER -->
    <div id="imageLightboxModal" style="display: none; position: fixed; z-index: 10000; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.9); align-items: center; justify-content: center; cursor: zoom-out;" onclick="closeImageModal()">
        <img id="lightboxModalImageTarget" src="" style="max-width: 90%; max-height: 90%; border-radius: 6px; object-fit: contain; box-shadow: 0 12px 32px rgba(0,0,0,0.5);">
    </div>

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
<!-- LIGHTBOX MODAL WITH FULL KEYBOARD ESCAPE INTERCEPT SYSTEM -->
    <div id="imageLightboxModal" style="display: none; position: fixed; z-index: 10000; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.9); align-items: center; justify-content: center; cursor: zoom-out;" onclick="closeImageModal()">
        <img id="lightboxModalImageTarget" src="" style="max-width: 90%; max-height: 90%; border-radius: 6px; object-fit: contain; box-shadow: 0 12px 32px rgba(0,0,0,0.5);">
    </div>



    <script>
        const listingImagesArray = <?php echo json_encode($item['images'] ?? []); ?>;
        let activeShowcaseIndex = 0;

        function updateActiveShowcaseIndex(newIndex) {
            if (!listingImagesArray.length || newIndex < 0 || newIndex >= listingImagesArray.length) return;
            activeShowcaseIndex = newIndex;
            const targetUrl = listingImagesArray[activeShowcaseIndex];
            document.getElementById('mainDisplayFrame').src = targetUrl;
            
            const thumbnails = document.querySelectorAll('.thumb-pane img, .thumb-row img');
            thumbnails.forEach((thumb, idx) => {
                thumb.style.borderColor = (idx === activeShowcaseIndex) ? 'var(--accent-indigo)' : 'var(--border)';
            });
        }

        function navigateShowcaseImage(directionOffset) {
            if (!listingImagesArray.length) return;
            let targetIndex = activeShowcaseIndex + directionOffset;
            if (targetIndex >= listingImagesArray.length) targetIndex = 0;
            if (targetIndex < 0) targetIndex = listingImagesArray.length - 1;
            updateActiveShowcaseIndex(targetIndex);
        }

        function openImageModal(imgSrc) {
            const modal = document.getElementById('imageLightboxModal');
            const targetImg = document.getElementById('lightboxModalImageTarget');
            if(modal && targetImg) {
                targetImg.src = imgSrc;
                modal.style.display = 'flex';
            }
        }
        
        function closeImageModal() {
            const modal = document.getElementById('imageLightboxModal');
            if(modal) modal.style.display = 'none';
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeImageModal();
            if (e.key === 'ArrowRight') navigateShowcaseImage(1);
            if (e.key === 'ArrowLeft') navigateShowcaseImage(-1);
        });

        function switchListingTab(targetTab) {
            const msgTab = document.getElementById('listingTabMessage');
            const payTab = document.getElementById('listingTabPayment');
            const msgBtn = document.getElementById('tabBtnMessage');
            const payBtn = document.getElementById('tabBtnPayment');
            if (!msgTab || !payTab || !msgBtn || !payBtn) return;

            if (targetTab === 'message') {
                msgTab.style.display = 'block';
                payTab.style.display = 'none';
                msgBtn.style.background = 'var(--accent-indigo)';
                msgBtn.style.color = 'white';
                payBtn.style.background = 'transparent';
                payBtn.style.color = 'var(--text-muted)';
            } else {
                msgTab.style.display = 'none';
                payTab.style.display = 'block';
                msgBtn.style.background = 'transparent';
                msgBtn.style.color = 'var(--text-muted)';
                payBtn.style.background = 'var(--accent-indigo)';
                payBtn.style.color = 'white';
            }
        }

        function copyAliasToClipboard() {
            const textTarget = document.getElementById('targetAliasTextString').innerText;
            const btn = document.getElementById('copyAliasBtnTrack');
            navigator.clipboard.writeText(textTarget).then(() => {
                if(btn) {
                    btn.innerText = "Copied!";
                    btn.style.background = "#16a34a";
                    setTimeout(() => {
                        btn.innerText = "📋 Copy";
                        btn.style.background = "#6b21a8";
                    }, 2000);
                }
            });
        }

        // --- COMPLETE FAIL-SAFE RUNNER SYSTEM ---
               function initializeLeafletMapEngine() {
            // Extract raw data and force numeric values
            const rawLat = "<?php echo $mapLat; ?>";
            const rawLon = "<?php echo $mapLon; ?>";
            
            // Clean values and ensure defaults if strings break parsing parameters
            let latTarget = parseFloat(rawLat);
            let lonTarget = parseFloat(rawLon);
            if (isNaN(latTarget)) latTarget = 44.5236;
            if (isNaN(lonTarget)) lonTarget = -89.5746;

            const mapContainer = document.getElementById('listingSectorMapCanvas');
            if (!mapContainer) return;
            mapContainer.innerHTML = "";

            try {
                // Construct map configuration matrix parameters
                const map = L.map('listingSectorMapCanvas', {
                    center: [latTarget, lonTarget],
                    zoom: 13,
                    zoomControl: true,
                    dragging: true,
                    scrollWheelZoom: false
                });

                // Pull standard open-source map tile assets safely
                L.tileLayer('https://openstreetmap.org{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                // Draw your soft local neighborhood zone security ring circle over coordinates
                L.circle([latTarget, lonTarget], {
                    color: '#4f46e5',
                    fillColor: '#4f46e5',
                    fillOpacity: 0.15,
                    radius: 800, 
                    weight: 2
                }).addTo(map);

                // Run a deep layout recalculation sequence to burst image tiles into visibility instantly
                map.invalidateSize();
                
                // Backup bursts to catch slower layout renders cleanly
                setTimeout(() => { map.invalidateSize(); }, 150);
                setTimeout(() => { map.invalidateSize(); }, 400);
                
            } catch (err) {
                console.error("Leaflet core error caught: ", err);
                mapContainer.innerHTML = "<div style='padding:2rem; text-align:center; color:var(--text-muted); font-size:0.9rem;'>⚠️ Map loading error. Please refresh.</div>";
            }
        }


        document.addEventListener("DOMContentLoaded", () => {
            // Check if Leaflet loaded correctly, if not, dynamically inject it right now
            if (typeof L === 'undefined') {
                console.log("Leaflet missing from head tag. Attempting direct file injection...");
                
                const leafletStyle = document.createElement('link');
                leafletStyle.rel = 'stylesheet';
                leafletStyle.href = 'https://unpkg.com';
                document.head.appendChild(leafletStyle);

                const leafletScript = document.createElement('script');
                leafletScript.src = 'https://unpkg.com';
                leafletScript.onload = () => {
                    setTimeout(initializeLeafletMapEngine, 200);
                };
                document.head.appendChild(leafletScript);
            } else {
                initializeLeafletMapEngine();
            }
        });

        // Small numeric type validation helper
        function floatval(val) {
            const parsed = parseFloat(val);
            return isNaN(parsed) ? 0 : parsed;
        }
    </script>
    <script src="app.js?v=<?php echo time(); ?>"></script>
</body>
</html>
