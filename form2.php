<?php
// Add PHPMailer classes at the very top
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

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

// Require the Composer autoloader
require 'vendor/autoload.php';

try {
    // Validate request method
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception('Invalid request method');
    }

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

    // Configure PHPMailer
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
    $mail->addAddress('ddecors2022@gmail.com'); // Replace with your email
    $mail->addReplyTo($formData['email']);

    // Content
    $mail->isHTML(true);
    $mail->Subject = "New Contact Form Submission from {$formData['firstName']}";
    $mail->Body = $emailContent;

    // Send email
    if($mail->send()) {
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

        echo json_encode([
            'status' => 'success',
            'message' => 'Request received ✨ Dream decor coming your way... 🎀🎉'
        ]);
    } else {
        throw new Exception("Failed to send email");
    }

} catch (Exception $e) {
    error_log("Form Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}