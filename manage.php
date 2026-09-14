<?php
/* manage.php - Secure Dashboard Controller */

// Include encryption helper variables
require_once 'config.php';
include_once 'common_email_helper.php';

$ledgerFile = 'listings.json';
$magicToken = $_GET['key'] ?? '';

if (empty($magicToken)) {
    die("<div style='font-family:sans-serif; text-align:center; margin-top:5rem;'>❌ Access Denied: Invalid parameters.</div>");
}

if (!file_exists($ledgerFile)) {
    die("❌ Error: No operational listing records found.");
}

$listings = json_decode(file_get_contents($ledgerFile), true);
$targetIndex = null;

// Search for the specific matching transaction passkey entry
for ($i = 0; $i < count($listings); $i++) {
    if (($listings[$i]['magic_token'] ?? '') === $magicToken) {
        $targetIndex = $i;
        break;
    }
}

if ($targetIndex === null) {
    die("<div style='font-family:sans-serif; text-align:center; margin-top:5rem;'>❌ Invalid Passkey: This listing may have already been removed.</div>");
}

$item = $listings[$targetIndex];

// Process Update and Action Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Form Action 1: Save Input Parameter Changes
    if (isset($_POST['action_update'])) {
        $listings[$targetIndex]['price'] = intval($_POST['price']);
        $listings[$targetIndex]['description'] = htmlspecialchars($_POST['description']);
        
        file_put_contents($ledgerFile, json_encode($listings, JSON_PRETTY_PRINT));
        header("Location: manage?key=" . $magicToken . "&success=updated");
        exit;
    }
    
    // Form Action 2: Toggle Hold/Active States
    if (isset($_POST['action_toggle_hold'])) {
        $currentState = $listings[$targetIndex]['status'] ?? 'active';
        $listings[$targetIndex]['status'] = ($currentState === 'active') ? 'pending' : 'active';
        
        file_put_contents($ledgerFile, json_encode($listings, JSON_PRETTY_PRINT));
        header("Location: manage?key=" . $magicToken . "&success=status");
        exit;
    }

    // Form Action 3: Permanently Wipe Listing and Associated Assets from Disk
    if (isset($_POST['action_delete'])) {
        // Core Cleanup: Physically delete the stored image files off the Windows Server disk
        if (!empty($item['images']) && is_array($item['images'])) {
            foreach ($item['images'] as $imagePath) {
                if (file_exists($imagePath) && strpos($imagePath, 'uploads/') === 0) {
                    @unlink($imagePath); // Erases the image file permanently
                }
            }
        }

        // Splice the entry data entirely out of your listings ledger
        array_splice($listings, $targetIndex, 1);
        file_put_contents($ledgerFile, json_encode($listings, JSON_PRETTY_PRINT));
        
        echo "<div style='font-family:sans-serif; max-width:400px; margin:5rem auto; text-align:center; border:1px solid #e2e8f0; padding:2rem; border-radius:8px;'>";
        echo "<h3 style='color:#b91c1c;'>🗑️ Listing Permanently Deleted</h3>";
        echo "<p style='color:#64748b; font-size:0.9rem;'>All encrypted database records and uploaded image assets have been entirely purged from the server drive.</p>";
        echo "<a href='./' style='color:#4f46e5; text-decoration:none; font-size:0.9rem;'>Return to Common Homepage</a>";
        echo "</div>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Manage Listing - COMMON</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .manage-box { max-width: 500px; margin: 3rem auto; background: var(--surface); border: 1px solid var(--border); padding: 2rem; border-radius: 8px; }
        .control-group { margin-bottom: 1.25rem; display: flex; flex-direction: column; gap: 0.4rem; }
        .alert-box { background: #dcfce7; color: #166534; padding: 0.75rem; border-radius: 5px; font-size: 0.85rem; margin-bottom: 1.5rem; font-weight: 600; text-align:center; }
    </style>
</head>
<body>
    <?php include_once 'header.php'; ?>


    <div class="manage-box">
        <h2 style="margin-top:0; letter-spacing:-0.03em;">⚙️ Listing Management Hub</h2>
        <p style="color:var(--text-muted); font-size:0.85rem; margin-top:-0.5rem; margin-bottom:1.5rem;">Stateless token authorization verified securely.</p>

        <?php if(($_GET['success'] ?? '') === 'updated'): ?>
            <div class="alert-box">✅ Listing parameters updated successfully!</div>
        <?php endif; ?>
        <?php if(($_GET['success'] ?? '') === 'status'): ?>
            <div class="alert-box">🔄 Status configuration toggled successfully!</div>
        <?php endif; ?>

        <div style="margin-bottom: 1.5rem; padding:0.75rem; background:#f8fafc; border-radius:6px; border:1px solid var(--border); font-size:0.9rem;">
            Current Public State: 
            <span class="badge <?php echo ($item['status'] ?? 'active') === 'active' ? 'badge-active' : 'badge-pending'; ?>">
                <?php echo ($item['status'] ?? 'active') === 'active' ? '● Active' : '⏳ Pending Hold'; ?>
            </span>
        </div>

        <!-- Form 1: Modify Context Details -->
        <form method="POST" style="border-bottom:1px dashed var(--border); padding-bottom:1.5rem; margin-bottom:1.5rem;">
            <div class="control-group">
                <label style="font-weight:600; font-size:0.85rem;">Modify Price ($)</label>
                <input type="number" name="price" value="<?php echo intval($item['price']); ?>" required style="padding:0.6rem; border:1px solid var(--border); border-radius:4px;">
            </div>
            <div class="control-group">
                <label style="font-weight:600; font-size:0.85rem;">Modify Description</label>
                <!-- Decodes HTML formatting characters cleanly for editing -->
                <textarea name="description" rows="5" required style="padding:0.6rem; border:1px solid var(--border); border-radius:4px; font-family:inherit; width:100%; box-sizing:border-box;"><?php echo htmlspecialchars($item['description']); ?></textarea>
            </div>
            <button type="submit" name="action_update" class="btn" style="width:100%; padding:0.7rem; font-size:0.95rem; background:var(--accent-indigo); color:#fff;">Save Listing Updates</button>
        </form>

        <!-- Dynamic Controls Actions Matrix -->
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
            <form method="POST">
                <button type="submit" name="action_toggle_hold" class="btn" style="width:100%; padding:0.7rem; font-size:0.9rem; background:#ca8a04; color:#fff;">
                    <?php echo ($item['status'] ?? 'active') === 'active' ? '⏳ Set to Pending' : '✅ Set to Active'; ?>
                </button>
            </form>
            <form method="POST" onsubmit="return confirm('Are you completely sure you want to permanently delete this item? This action completely purges file data blocks and images off the disk.');">
                <button type="submit" name="action_delete" class="btn" style="width:100%; padding:0.7rem; font-size:0.9rem; background:#b91c1c; color:#fff;">🗑️ Delete Listing</button>
            </form>
        </div>
    </div>
</body>
</html>
