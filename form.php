<?php
// Add PHPMailer classes at the very top
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;

// Require the Composer autoloader
require 'vendor/autoload.php';

// Add error logging at the very top
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'error.log');

// Start output buffering
ob_start();

// Set JSON header
header('Content-Type: application/json');

// Check for file upload errors
if (isset($_FILES['referenceDesign']) && $_FILES['referenceDesign']['error'] !== UPLOAD_ERR_OK) {
    error_log('File upload error: ' . $_FILES['referenceDesign']['error']);
}

// Add this function before the try-catch block
function initializeGoogleClient() {
    $client = new Client();
    $client->setApplicationName('Dream Decors Form');
    $client->setScopes([Sheets::SPREADSHEETS]);
    $client->setAuthConfig('credentials.json'); // You'll need to add this file
    return $client;
}

// Add this function to handle Google Sheets submission
function saveToGoogleSheets($formData) {
    try {
        $client = initializeGoogleClient();
        $service = new Sheets($client);
        
        $spreadsheetId = '1cqwI7q8rVpPbNjO-Rec1Tb6Vt5nGHi3oxVUZfnt2Ujk';
        $range = 'Sheet1!A:O';
        
        // Prepare row data
        $values = [
            [
                date('Y-m-d H:i:s'),
                $formData['firstName'],
                $formData['lastName'],
                $formData['contactNumber'],
                $formData['altContactNumber'],
                $formData['email'],
                $formData['eventDate'],
                $formData['eventVenue'],
                $formData['eventType'],
                $formData['location'],
                $formData['eventLocation'],
                $formData['decorType'],
                $formData['numberOfGuests'],
                $formData['budget'],
                $formData['additionalDetails'],
                $formData['hearAboutUs'] === 'Other' ? 
                    $formData['hearAboutUs'] . ': ' . $formData['otherSource'] : 
                    $formData['hearAboutUs']
            ]
        ];

        $body = new ValueRange([
            'values' => $values
        ]);

        $params = [
            'valueInputOption' => 'RAW'
        ];

        $result = $service->spreadsheets_values->append(
            $spreadsheetId,
            $range,
            $body,
            $params
        );
        return true;

    } catch (Google\Service\Exception $e) {
        $error = json_decode($e->getMessage(), true);
        $errorMessage = $error['error']['message'] ?? 'Unknown Google Sheets error';
        error_log("Google Sheets Error: " . $errorMessage);
        
        // Log detailed error for debugging
        error_log("Full Google Sheets Error: " . print_r($error, true));
        return false;

    } catch (Exception $e) {
        error_log("Unexpected Google Sheets Error: " . $e->getMessage());
        return false;
    }
}

// Add error handling function at the top
function returnError($message) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $message
    ]);
    exit;
}

try {
    // Verify PHPMailer is installed
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        returnError('PHPMailer not installed. Please run: composer require phpmailer/phpmailer');
    }

    // Validate request method
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        returnError('Invalid request method');
    }

    // Debug: Log received data
    error_log('POST data received: ' . print_r($_POST, true));
    error_log('Uploaded Files: ' . print_r($_FILES, true));

    // Get form data
    $formData = [
        'firstName' => $_POST['firstName'] ?? '',
        'lastName' => $_POST['lastName'] ?? '',
        'contactNumber' => $_POST['contactNumber'] ?? '',
        'altContactNumber' => $_POST['altContactNumber'] ?? '',
        'email' => $_POST['email'] ?? '',
        'eventDate' => $_POST['eventDate'] ?? '',
        'eventVenue' => $_POST['eventVenue'] ?? '',
        'eventType' => $_POST['eventType'] ?? '',
        'location' => $_POST['location'] ?? '',
        'eventLocation' => $_POST['eventLocation'] ?? '',
        'decorType' => $_POST['decorType'] ?? '',
        'numberOfGuests' => $_POST['numberOfGuests'] ?? '',
        'budget' => $_POST['budget'] ?? '',
        'additionalDetails' => $_POST['additionalDetails'] ?? '',
        'hearAboutUs' => $_POST['hearAboutUs'] ?? '',
        'otherSource' => $_POST['otherSource'] ?? ''
    ];

    // Validate required fields
    $requiredFields = ['firstName', 'lastName', 'email', 'contactNumber'];
    foreach ($requiredFields as $field) {
        if (empty($formData[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Email subject
    $subject = "New Event Booking Request";

    // Email recipients
    $to_emails = [
        "hello@desidreamdecors.com",
        "ddecors2022@gmail.com",
        "sarathbabuc@duck.com"
    ];

    // Add function to save booking data to file
    function saveBookingData($formData) {
        $bookingsDir = 'bookings/';
        if (!file_exists($bookingsDir)) {
            mkdir($bookingsDir, 0777, true);
        }

        $timestamp = date('Y-m-d_H-i-s');
        $filename = $bookingsDir . "booking_{$timestamp}.txt";

        $content = "Booking Details:\n";
        foreach ($formData as $key => $value) {
            $content .= "{$key}: {$value}\n";
        }

        return file_put_contents($filename, $content);
    }

    // Create email content
    $emailContent = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .details { margin: 20px 0; }
            .label { font-weight: bold; }
        </style>
    </head>
    <body>
        <h2>New Event Booking Request</h2>

        <div class='details'>
            <p><span class='label'>Name:</span> {$formData['firstName']} {$formData['lastName']}</p>
            <p><span class='label'>Contact:</span> {$formData['contactNumber']}</p>
            <p><span class='label'>Alternative Contact:</span> {$formData['altContactNumber']}</p>
            <p><span class='label'>Email:</span> {$formData['email']}</p>
            <p><span class='label'>Event Date:</span> {$formData['eventDate']}</p>
            <p><span class='label'>Event Venue:</span> {$formData['eventVenue']}</p>
            <p><span class='label'>Event Type:</span> {$formData['eventType']}</p>
            <p><span class='label'>Location:</span> {$formData['location']}</p>
            <p><span class='label'>Indoor/Outdoor:</span> {$formData['eventLocation']}</p>
            <p><span class='label'>Decor Type:</span> {$formData['decorType']}</p>
            <p><span class='label'>Number of Guests:</span> {$formData['numberOfGuests']}</p>
            <p><span class='label'>Budget:</span> {$formData['budget']}</p>
            <p><span class='label'>Additional Details:</span> {$formData['additionalDetails']}</p>
            <p><span class='label'>How Did You Hear About Us:</span> " . 
            ($formData['hearAboutUs'] === 'Other' ? 
                $formData['hearAboutUs'] . ': ' . $formData['otherSource'] : 
                $formData['hearAboutUs']) . "</p>
        </div>
    </body>
    </html>
    ";

    // Email headers
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$formData['email']}\r\n";
    $headers .= "Reply-To: {$formData['email']}\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    // Replace the email sending code with PHPMailer
    function sendEmail($to, $subject, $content, $from) {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'mail.smtp2go.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'imcourageous.com';
            $mail->Password = 'ep1XvG9sVMo00jdz';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 2525;

            // Recipients
            $mail->setFrom('serenevaughan6@imcourageous.com', 'Dream Decors');
            $mail->addAddress($to);
            $mail->addReplyTo($from);

            // Handle file upload and attachment
            if (isset($_FILES['referenceDesign']) && $_FILES['referenceDesign']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $fileName = time() . '_' . basename($_FILES['referenceDesign']['name']);
                $targetPath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['referenceDesign']['tmp_name'], $targetPath)) {
                    $mail->addAttachment($targetPath, basename($_FILES['referenceDesign']['name']));
                    // Add file information to email content
                    $content .= "<p><span class='label'>Reference Design:</span> Attached</p>";
                }
            }

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $content;

            return $mail->send();
        } catch (Exception $e) {
            throw new Exception("Mail error: {$mail->ErrorInfo}");
        }
    }

    // Save booking data first
    if (!saveBookingData($formData)) {
        throw new Exception("Failed to save booking data");
    }

    // Add this after saving booking data and before sending emails
    if (!saveToGoogleSheets($formData)) {
        error_log("Failed to save to Google Sheets - continuing with form submission");
        // Don't throw an exception, just continue with the rest of the process
    }

    // Send email to all recipients
    foreach ($to_emails as $to) {
        if (!sendEmail($to, $subject, $emailContent, $formData['email'])) {
            error_log("Failed to send email to: $to");
        }
    }

    // Send confirmation email to customer
    $customerSubject = "Thank you for your booking request - Dream Decors";
    $customerContent = "
    <html>
    <body>
        <h2>Thank you for your booking request!</h2>
        <p>Dear {$formData['firstName']},</p>
        <p>We have received your event booking request and will get back to you shortly.</p>
        <p>Your booking details:</p>
        <ul>
            <li>Event Type: {$formData['eventType']}</li>
            <li>Event Date: {$formData['eventDate']}</li>
            <li>Location: {$formData['location']}</li>
            <li>Decor Type: {$formData['decorType']}</li>
        </ul>
        <p>Best regards,<br>Dream Decors Team</p>
    </body>
    </html>
    ";

    sendEmail($formData['email'], $customerSubject, $customerContent, 'info@dreamdecors.com');

    // Clean output buffer before success response
    ob_clean();

    // Add gtag tracking code to the success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Request received ✨ Dream decor coming your way... 🎀',
        'tracking' => true  // Add this flag to trigger tracking on frontend
    ]);

} catch (Exception $e) {
    // Log the error
    error_log("Form Error: " . $e->getMessage());
    returnError($e->getMessage());
}

// Ensure we end the script properly
exit;