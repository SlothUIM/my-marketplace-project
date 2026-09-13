<?php
/* message_relay.php - Stateless Anonymized Communication Node with Decryption */

// Include system configurations and encryption utilities
require_once 'config.php';
include_once 'common_email_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $listingId   = htmlspecialchars(trim($_POST['listing_id'] ?? ''));
    $buyerMessage = htmlspecialchars(trim($_POST['message'] ?? ''));
    $buyerContact = htmlspecialchars(trim($_POST['buyer_contact'] ?? '')); // The buyer's input return route

    if (empty($listingId) || empty($buyerMessage) || empty($buyerContact)) {
        die("❌ Error: Missing required message routing parameters.");
    }

    $ledgerFile = 'listings.json';
    $item = null;

    // 1. Fetch the listing record to extract the seller's encrypted delivery address
    if (file_exists($ledgerFile)) {
        $listings = json_decode(file_get_contents($ledgerFile), true) ?? [];
        foreach ($listings as $entry) {
            if (($entry['id'] ?? '') === $listingId) {
                $item = $entry;
                break;
            }
        }
    }

    if (!$item) {
        die("❌ Error: The listing you are trying to contact no longer exists.");
    }

    // 2. DECRYPT THE SELLER'S ADDRESS IN MEMORY
    // Reverses the AES-128-CTR scramble on the fly so the script knows where to send the email
    $sellerEndpoint = common_decrypt($item['endpoint'] ?? '');
    $itemTitle      = $item['title'] ?? 'Your Item';

    if (empty($sellerEndpoint)) {
        die("❌ Error: The seller has not configured a valid message delivery endpoint.");
    }

    // 3. Map the transmission payload out over the verified Slothscape pipeline
    if (filter_var($sellerEndpoint, FILTER_VALIDATE_EMAIL)) {
        
        $subject = "💬 New Anonymous Inquiry: " . $itemTitle;
        
        // Build a highly structured message card layout
        $body = "
            <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; color: #0f172a; padding: 30px; border: 1px solid #e2e8f0; border-radius: 8px;'>
                <h2 style='color: #4f46e5; margin-top: 0; font-weight: 800; letter-spacing: -0.05em;'>COMMON.</h2>
                <p>A local buyer is interested in your item: <strong>" . htmlspecialchars($itemTitle) . "</strong></p>
                
                <div style='background: #f8fafc; border: 1px solid #e2e8f0; padding: 1.25rem; border-radius: 6px; margin: 20px 0; font-style: italic; color: #334155; line-height: 1.5;'>
                    \"" . nl2br(htmlspecialchars($buyerMessage)) . "\"
                </div>
                
                <p style='font-size: 0.9rem; color: #334155;'>
                    👉 <strong>How to respond:</strong> To keep this platform 100% account-free, do not reply directly to this automated email. Instead, reach out to the buyer directly using the temporary return contact route they provided below:
                </p>
                <div style='background: #eeebff; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 0.95rem; color: #4f46e5; font-weight: bold; text-align: center; border: 1px dashed #4f46e5;'>
                    " . htmlspecialchars($buyerContact) . "
                </div>
            </div>
        ";

        sendCommonEmail($sellerEndpoint, $subject, $body);
    } 
    // Handle phone number carrier gates if the decrypted string resolves as numeric data
    else {
        $cleanPhone = preg_replace('/[^0-9]/', '', $sellerEndpoint);
        if (strlen($cleanPhone) === 10) {
            $smsGateways = [
                $cleanPhone . "@vtext.com",
                $cleanPhone . "@txt.att.net",
                $cleanPhone . "@tmomail.net"
            ];
            $smsSubject = "Common Inquiry";
            $smsMessage = "New inquiry for '" . $itemTitle . "'. Reply to: " . $buyerContact . " | Message: " . $buyerMessage;
            
            foreach ($smsGateways as $gatewayEmail) {
                sendCommonEmail($gatewayEmail, $smsSubject, $smsMessage);
            }
        }
    }

    // 4. Kick back to the listing detail view with a clear visual success indicator
    // Updated path mapping targets your extensionless IIS web.config rule layout
    header("Location: listing?id=" . $listingId . "&status=sent");
    exit;

} else {
    // Falls back gracefully to directory base index
    header("Location: ./");
    exit;
}
?>
