<?php
/* header.php - Unified Side-By-Side Navigation Engine */
$currentScript = basename($_SERVER['PHP_SELF']);
$isPostPage = ($currentScript === 'post.php' || $currentScript === 'post');
?>
<header style="width: 100%; background: var(--surface); border-bottom: 1px solid var(--border);">
    <div class="header-container">
        <!-- Logo Block -->
        <a href="./" class="logo-btn"><h1>COMMON.</h1></a>
        
        <!-- Right Navigation Matrix Row -->
        <div style="display: flex; align-items: center; gap: 1.5rem;">
            
            <!-- HORIZONTAL INLINE PASSKEY FIELD (Folds out sideways flawlessly) -->
            <div id="headerKeyGateDrawer" style="display: none; transition: all 0.2s ease;">
                <div style="display: flex; align-items: center; gap: 0.5rem; background: var(--bg); padding: 0.35rem; border: 1px solid var(--border); border-radius: 6px;">
                    <input type="text" id="manualHeaderPasskeyInput" placeholder="Paste your 32-ch token..." style="height: 34px; padding: 0 0.75rem; border: none; font-family: monospace; font-size: 0.85rem; background: transparent; width: 220px; box-sizing: border-box;">
                    <button type="button" class="btn" onclick="executeManualKeyRedirect()" style="height: 34px; padding: 0 1rem; font-size: 0.85rem; display: inline-flex; align-items: center; justify-content: center;">Verify</button>
                </div>
            </div>

            <!-- Management Trigger Text Action link -->
            <a href="javascript:void(0);" onclick="toggleKeyGateDrawer()" id="navGateToggleLink" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem; font-weight: 600;">Manage Ad ↓</a>
            
            <!-- Safe Ingest Filter: Prevents redundant posting button overlays -->
            <?php if (!$isPostPage): ?>
                <a href="post" class="btn">+ Sell Something</a>
            <?php endif; ?>
        </div>
    </div>
</header>
