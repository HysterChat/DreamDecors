<?php
// Add PHPMailer classes at the very top
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;

// Require the Composer autoloader
require 'vendor/autoload.php';

// Add error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'error.log');

// Set JSON header
header('Content-Type: application/json');

// Add CORS headers for development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start output buffering
ob_start();

// Add error handling function
function returnError($message) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $message
    ]);
    exit;
}

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

// Add Google Sheets functions before the try-catch block
function initializeGoogleClient() {
    $client = new Client();
    $client->setApplicationName('Dream Decors Form');
    $client->setScopes([Sheets::SPREADSHEETS]);
    $client->setAuthConfig('credentials.json');
    return $client;
}

function saveToGoogleSheets($formData) {
    try {
        $client = initializeGoogleClient();
        $service = new Sheets($client);
        
        $spreadsheetId = '1cqwI7q8rVpPbNjO-Rec1Tb6Vt5nGHi3oxVUZfnt2Ujk';
        $range = 'Sheet1!A:H';
        
        // Prepare row data
        $values = [
            [
                date('Y-m-d H:i:s'),
                $formData['firstName'],
                $formData['email'],
                $formData['contactNumber'],
                $formData['city'],
                $formData['occasion'],
                $formData['occasionDate'],
                $formData['message']
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
        error_log("Google Sheets Error: " . $e->getMessage());
        return false;
    } catch (Exception $e) {
        error_log("Unexpected Google Sheets Error: " . $e->getMessage());
        return false;
    }
}

try {
    // Verify PHPMailer is installed
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        returnError('PHPMailer not installed. Please run: composer require phpmailer/phpmailer');
    }

    // Validate request method
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception('Invalid request method');
    }

    // Debug: Log received data
    error_log('POST data received: ' . print_r($_POST, true));
    error_log('Uploaded Files: ' . print_r($_FILES, true));

    // Get form data
    $formData = [
        'firstName' => $_POST['firstName'] ?? '',
        'email' => $_POST['email'] ?? '',
        'contactNumber' => $_POST['contactNumber'] ?? '',
        'city' => $_POST['city'] ?? '',
        'occasion' => $_POST['occasion'] ?? '',
        'occasionDate' => $_POST['occasionDate'] ?? '',
        'message' => $_POST['message'] ?? ''
    ];

    // Validate required fields
    $requiredFields = ['firstName', 'email', 'contactNumber', 'city', 'occasion', 'message'];
    foreach ($requiredFields as $field) {
        if (empty($formData[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Save booking data first
    if (!saveBookingData($formData)) {
        throw new Exception("Failed to save booking data");
    }

    // Save to Google Sheets
    if (!saveToGoogleSheets($formData)) {
        error_log("Failed to save to Google Sheets - continuing with form submission");
    }

    // Email recipients array
    $to_emails = [
        "hello@desidreamdecors.com",
        "ddecors2022@gmail.com",
        "sarathbabuc@duck.com"
    ];

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
        <h2>New Contact Form Submission</h2>
        <div class='details'>
            <p><span class='label'>Name:</span> {$formData['firstName']}</p>
            <p><span class='label'>Email:</span> {$formData['email']}</p>
            <p><span class='label'>Contact:</span> {$formData['contactNumber']}</p>
            <p><span class='label'>City:</span> {$formData['city']}</p>
            <p><span class='label'>Occasion:</span> {$formData['occasion']}</p>
            <p><span class='label'>Occasion Date:</span> {$formData['occasionDate']}</p>
            <p><span class='label'>Message:</span> {$formData['message']}</p>
        </div>
    </body>
    </html>";

    // Create and send email to all recipients
    foreach ($to_emails as $to) {
        $mail = new PHPMailer(true);
        
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
        $mail->addReplyTo($formData['email']);

        // Content
        $mail->isHTML(true);
        $mail->Subject = "New Contact Form Submission from {$formData['firstName']}";
        $mail->Body = $emailContent;

        if (!$mail->send()) {
            error_log("Failed to send email to: $to");
        }
    }

    // Send confirmation email to customer
    $customerMail = new PHPMailer(true);
    $customerMail->isSMTP();
    $customerMail->Host = 'mail.smtp2go.com';
    $customerMail->SMTPAuth = true;
    $customerMail->Username = 'imcourageous.com';
    $customerMail->Password = 'ep1XvG9sVMo00jdz';
    $customerMail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $customerMail->Port = 2525;

    $customerMail->setFrom('serenevaughan6@imcourageous.com', 'Dream Decors');
    $customerMail->addAddress($formData['email']);

    $customerMail->isHTML(true);
    $customerMail->Subject = "Thank you for contacting Dream Decors";
    $customerMail->Body = "
    <html>
    <body>
        <h2>Thank you for contacting us!</h2>
        <p>Dear {$formData['firstName']},</p>
        <p>We have received your message and will get back to you shortly.</p>
        <p>Best regards,<br>Dream Decors Team</p>
    </body>
    </html>";

    $customerMail->send();

    // Clean output buffer before success response
    ob_clean();

    echo json_encode([
        'status' => 'success',
        'message' => 'Request received ✨ Dream decor coming your way... 🎀🎉'
    ]);

} catch (Exception $e) {
    error_log("Form Error: " . $e->getMessage());
    returnError($e->getMessage());
}

// Ensure we end the script properly
exit;