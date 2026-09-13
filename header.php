<?php
/* header.php - Dynamic Global Navigation Component */

// Detect if we are currently loading the posting interface to hide the duplicate button path
$currentScript = basename($_SERVER['PHP_SELF']);
$isPostPage = ($currentScript === 'post.php' || $currentScript === 'post');
?>
<!-- Unified Global Header Container with Top Accent Border -->
<header style="border-bottom: 1px solid var(--border); width: 100%; position: relative; background: var(--surface);">
    <div class="header-container" style="max-width: 1300px; margin: 0 auto; padding: 1.5rem 2rem; display: flex; justify-content: space-between; align-items: center; box-sizing: border-box;">
        <a href="./" class="logo-btn" style="text-decoration: none;"><h1>COMMON.</h1></a>
        <div style="display: flex; align-items: center; gap: 1.5rem;">
            <!-- Trigger button toggle action inside your global app.js module tracker -->
            <a href="javascript:void(0);" onclick="toggleKeyGateDrawer()" id="navGateToggleLink" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem; font-weight: 600; transition: color 0.15s ease;">Manage Ad ↓</a>
            
            <?php if (!$isPostPage): ?>
                <a href="post" class="btn">+ Sell Something</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Hidden Token Input Drawer Panel Panel Component -->
    <div id="headerKeyGateDrawer" style="display: none; background: var(--bg); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); width: 100%;">
        <div style="max-width: 500px; margin: 0 auto; padding: 1.5rem 2rem; text-align: center;">
            <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.05em;">Enter Private Listing Passkey</label>
            <div class="relay-input-box" style="margin-top: 0.5rem;">
                <input type="text" id="manualHeaderPasskeyInput" placeholder="Paste your 32-character hex token..." style="text-align: center; font-family: monospace;">
                <button type="button" class="btn" onclick="executeManualKeyRedirect()" style="background: var(--accent-indigo); color: #fff;">Verify</button>
            </div>
        </div>
    </div>
</header>
