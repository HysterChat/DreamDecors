<?php
// Add PHPMailer classes at the very top
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

try {
    // Verify PHPMailer is installed
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        throw new Exception('PHPMailer not installed. Please run: composer require phpmailer/phpmailer');
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
        'additionalDetails' => $_POST['additionalDetails'] ?? ''
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
        </ul>
        <p>Best regards,<br>Dream Decors Team</p>
    </body>
    </html>
    ";

    sendEmail($formData['email'], $customerSubject, $customerContent, 'info@dreamdecors.com');

    // Ensure clean output before JSON response
    ob_clean();

    echo json_encode([
        'status' => 'success',
        'message' => 'Thank you for your booking request! We will contact you soon.'
    ]);

} catch (Exception $e) {
    // Log the error
    error_log("Form Error: " . $e->getMessage());

    // Clean output buffer
    ob_clean();

    // Send error response
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

// Flush and end output
ob_end_flush();
exit;
