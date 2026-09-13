<?php
/* publish.php - Stateless Processing & Image Upload Node */
require_once 'config.php';
include_once 'common_email_helper.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $title       = htmlspecialchars(trim($_POST['title'] ?? 'Untitled Item'));
    $price       = intval($_POST['price'] ?? 0);
    $zip         = intval($_POST['zip'] ?? 54481);
    $description = htmlspecialchars(trim($_POST['description'] ?? ''));
    $endpoint    = htmlspecialchars(trim($_POST['endpoint'] ?? '')); 
    $category    = htmlspecialchars(trim($_POST['category'] ?? 'general'));
    $venmo       = htmlspecialchars(trim($_POST['venmo'] ?? ''));
    $cashapp     = htmlspecialchars(trim($_POST['cashapp'] ?? ''));

    $magicToken  = bin2hex(random_bytes(16)); 
    $listingId   = uniqid('item_');            

    // --- NEW: CORE IMAGE HANDLING ENGINE ---
    $savedImageUrls = [];
    $uploadDir = 'uploads/';

    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $totalFiles = count($_FILES['images']['name']);
        
        // Loop over each uploaded file from the dropbox array
        for ($i = 0; $i < $totalFiles; $i++) {
            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                
                $tmpName  = $_FILES['images']['tmp_name'][$i];
                $origName = basename($_FILES['images']['name'][$i]);
                $fileSize = $_FILES['images']['size'][$i];
                
                // Security Check: Verify it is a real image file type, not masked malicious code
                $fileInfo = getimagesize($tmpName);
                if ($fileInfo === false) continue; // Skip file if it fails validation

                // Parse extension securely
                $extension = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
                if (!in_array($extension, $allowedExtensions)) continue;

                // Generate a unique, un-guessable filename to prevent duplication overwriting
                $newFileName = 'img_' . bin2hex(random_bytes(8)) . '_' . time() . '.' . $extension;
                $targetFilePath = $uploadDir . $newFileName;

                // Move file stream from temporary directory memory straight into uploads folder
                if (move_uploaded_file($tmpName, $targetFilePath)) {
                    $savedImageUrls[] = $targetFilePath;
                }
            }
        }
    }

    // Fallback default placeholder array if the seller attaches zero images
    if (empty($savedImageUrls)) {
        $savedImageUrls[] = 'https://unsplash.com';
    }

    // Construct the complete data record referencing your real upload paths
    $newListing = [
        "id"          => $listingId,
        "magic_token" => $magicToken, 
        "title"       => $title,
        "price"       => $price,
        "zip"         => $zip,
        "description" => $description,
        "endpoint"    => common_encrypt($endpoint),
        "category"    => $category,
        "venmo"       => common_encrypt($venmo),
        "cashapp"     => common_encrypt($cashapp),
        "images"      => $savedImageUrls, // Array storing all real uploaded image file tracks
        "status"      => "active", 
        "timestamp"   => time()     
    ];

    $ledgerFile = 'listings.json';
    if (!file_exists($ledgerFile)) {
        file_put_contents($ledgerFile, json_encode([]));
    }

    $currentData = json_decode(file_get_contents($ledgerFile), true);
    array_unshift($currentData, $newListing);
    file_put_contents($ledgerFile, json_encode($currentData, JSON_PRETTY_PRINT));

    $editUrl = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/manage.php?key=" . $magicToken;

    // --- SLOTHSCAPE SMTP DISPATCH ---
    if (filter_var($endpoint, FILTER_VALIDATE_EMAIL)) {
        include_once 'common_email_helper.php';
        $subject = "🔒 Your Common Management Key: " . $title;
        $body = "
            <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; color: #0f172a; padding: 30px; border: 1px solid #e2e8f0; border-radius: 8px;'>
                <h2 style='color: #4f46e5; margin-top: 0; font-weight: 800; letter-spacing: -0.05em;'>COMMON.</h2>
                <p>Your listing for <strong>" . htmlspecialchars($title) . "</strong> is now live on the local feed.</p>
                <div style='margin: 25px 0; text-align: center;'><a href='$editUrl' style='display: inline-block; background: #4f46e5; color: #ffffff; padding: 12px 28px; text-decoration: none; font-weight: 600; border-radius: 6px; font-size: 0.95rem;'>Manage Your Listing</a></div>
                <p style='font-size: 0.8rem; color: #64748b; border-top: 1px dashed #e2e8f0; padding-top: 15px;'>URL Passkey link:<br>$editUrl</p>
            </div>";
        sendCommonEmail($endpoint, $subject, $body);
    }

    echo "<div style='font-family:sans-serif; max-width:500px; margin:4rem auto; border:1px solid #e2e8f0; padding:2rem; border-radius:8px; background: #fff;'>";
    echo "<h2 style='color:#4f46e5; margin-top:0;'>✨ Listing Published Live!</h2>";
    echo "<p>Your item is now live with your photos stored securely.</p>";
    echo "<hr style='border:none; border-top:1px dashed #e2e8f0; margin:1.5rem 0;'>";
    echo "<strong>⚠️ CRITICAL SECURITY KEY:</strong><br>";
    echo "<a href='$editUrl' style='display:block; background:#0f172a; color:#fff; text-decoration:none; padding:0.75rem; border-radius:5px; text-align:center; font-family:monospace; font-size:0.9rem; margin-top:1rem;'>$editUrl</a>";
    echo "<br><a href='index.html' style='color:#4f46e5; font-size:0.9rem; text-decoration:none;'>← Return to Common Homepage</a>";
    echo "</div>";

} else {
    header("Location: post.html");
    exit;
}
?>
