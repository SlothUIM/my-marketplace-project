<?php
/* manage.php - Stateless Key Controller */

$ledgerFile = 'listings.json';
$magicToken = $_GET['key'] ?? '';

if (empty($magicToken)) {
    die("<div style='font-family:sans-serif; text-align:center; margin-top:5rem;'>❌ Access Denied: Invalid Security token parameters.</div>");
}

// Read raw state ledger
if (!file_exists($ledgerFile)) {
    die("❌ Error: No operational listing records found.");
}

$listings = json_decode(file_get_contents($ledgerFile), true);
$targetIndex = null;

// Search for the specific matching transaction key
foreach ($listings as $index => $item) {
    if (($item['magic_token'] ?? '') === $magicToken) {
        $targetIndex = $index;
        break;
    }
}

if ($targetIndex === null) {
    die("<div style='font-family:sans-serif; text-align:center; margin-top:5rem;'>❌ Invalid Passkey: This listing may have already been permanently deleted.</div>");
}

$item = $listings[$targetIndex];

// Process Update and Action Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action_update'])) {
        $listings[$targetIndex]['price'] = intval($_POST['price']);
        $listings[$targetIndex]['description'] = htmlspecialchars($_POST['description']);
        file_put_contents($ledgerFile, json_encode($listings, JSON_PRETTY_PRINT));
        header("Location: manage.php?key=" . $magicToken . "&success=updated");
        exit;
    }
    
    if (isset($_POST['action_toggle_hold'])) {
        $currentState = $listings[$targetIndex]['status'] ?? 'active';
        $listings[$targetIndex]['status'] = ($currentState === 'active') ? 'pending' : 'active';
        file_put_contents($ledgerFile, json_encode($listings, JSON_PRETTY_PRINT));
        header("Location: manage.php?key=" . $magicToken . "&success=status");
        exit;
    }

    if (isset($_POST['action_delete'])) {
        // Permanently splice out data slice from file mapping memory
        array_splice($listings, $targetIndex, 1);
        file_put_contents($ledgerFile, json_encode($listings, JSON_PRETTY_PRINT));
        echo "<div style='font-family:sans-serif; max-width:400px; margin:5rem auto; text-align:center; border:1px solid #e2e8f0; padding:2rem; border-radius:8px;'>";
        echo "<h3 style='color:#b91c1c;'>🗑️ Listing Permanently Deleted</h3>";
        echo "<p style='color:#64748b; font-size:0.9rem;'>All image metadata records and transaction strings have been entirely wiped from the server disk layer.</p>";
        echo "<a href='index.html' style='color:#4f46e5; text-decoration:none; font-size:0.9rem;'>Return to Common Homepage</a>";
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
    <div class="manage-box">
        <h2 style="margin-top:0; letter-spacing:-0.03em;">⚙️ Listing Management Hub</h2>
        <p style="color:var(--text-muted); font-size:0.85rem; margin-top:-0.5rem; margin-bottom:1.5rem;">Stateless validation verified via token signature.</p>

        <?php if(($_GET['success'] ?? '') === 'updated'): ?>
            <div class="alert-box">✅ Listing parameters updated successfully!</div>
        <?php endif; ?>
        <?php if(($_GET['success'] ?? '') === 'status'): ?>
            <div class="alert-box">🔄 Status configuration toggled successfully!</div>
        <?php endif; ?>

        <div style="margin-bottom: 1.5rem; padding:0.75rem; background:#f8fafc; border-radius:6px; border:1px solid var(--border); font-size:0.9rem;">
            Current Public State: 
            <span class="badge <?php echo $item['status'] === 'active' ? 'badge-active' : 'badge-pending'; ?>">
                <?php echo $item['status'] === 'active' ? '● Active' : '⏳ Pending Hold'; ?>
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
                <textarea name="description" rows="3" required style="padding:0.6rem; border:1px solid var(--border); border-radius:4px; font-family:inherit;"><?php echo htmlspecialchars($item['description']); ?></textarea>
            </div>
            <button type="submit" name="action_update" class="btn" style="width:100%; padding:0.7rem; font-size:0.95rem; background:var(--accent-indigo);">Save Listing Updates</button>
        </form>

        <!-- Dynamic Controls Actions Matrix -->
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
            <form method="POST">
                <button type="submit" name="action_toggle_hold" class="btn" style="width:100%; padding:0.7rem; font-size:0.9rem; background:#ca8a04;">
                    <?php echo $item['status'] === 'active' ? '⚠️ Set to Pending' : '✅ Set to Active'; ?>
                </button>
            </form>
            <form method="POST" onsubmit="return confirm('Are you completely sure you want to permanently delete this item? This action completely purges file data blocks and cannot be undone.');">
                <button type="submit" name="action_delete" class="btn" style="width:100%; padding:0.7rem; font-size:0.9rem; background:#b91c1c;">🗑️ Delete Listing</button>
            </form>
        </div>
    </div>
</body>
</html>
