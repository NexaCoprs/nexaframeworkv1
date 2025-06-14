<?php

namespace Nexa\Queue\Jobs;

use Nexa\Queue\Job;
// use Nexa\Logging\Logger; // Logger class doesn't exist yet

class SendEmailJob extends Job
{
    protected $queue = 'emails';
    protected $maxAttempts = 3;
    protected $timeout = 30;

    public function __construct($emailData)
    {
        parent::__construct($emailData);
    }

    /**
     * Execute the job
     */
    public function handle()
    {
        $to = $this->get('to');
        $subject = $this->get('subject');
        $message = $this->get('message');
        $from = $this->get('from', $_ENV['MAIL_FROM'] ?? 'noreply@example.com');
        
        // Validate required fields
        if (!$to || !$subject || !$message) {
            throw new \InvalidArgumentException('Missing required email fields: to, subject, message');
        }

        // Send email using real implementation
        $this->sendEmail($to, $subject, $message, $from);

        // Log successful email
        // $logger = new Logger(); // Logger class doesn't exist yet
        // $logger->info('Email sent successfully', [
        //     'to' => $to,
        //     'subject' => $subject,
        //     'job_id' => $this->getId()
        // ]);
        
        // Temporary logging until Logger class is implemented
        error_log("Email sent successfully to: {$to}, subject: {$subject}, job_id: {$this->getId()}");
    }

    /**
     * Send email using real implementation
     */
    private function sendEmail($to, $subject, $message, $from)
    {
        // Get email configuration
        $config = $this->getEmailConfig();
        
        try {
            if ($config['driver'] === 'smtp') {
                $this->sendViaSMTP($to, $subject, $message, $from, $config);
            } else {
                $this->sendViaPHPMail($to, $subject, $message, $from);
            }
            
            error_log("Email sent successfully to: {$to}, from: {$from}, subject: {$subject}");
        } catch (\Exception $e) {
            error_log("Failed to send email to: {$to}, error: " . $e->getMessage());
            throw new \Exception('Failed to send email: ' . $e->getMessage());
        }
    }
    
    /**
     * Send email via SMTP
     */
    private function sendViaSMTP($to, $subject, $message, $from, $config)
    {
        // Create SMTP connection
        $smtp = fsockopen($config['host'], $config['port'], $errno, $errstr, 30);
        if (!$smtp) {
            throw new \Exception("SMTP connection failed: {$errstr} ({$errno})");
        }
        
        // SMTP conversation
        $this->smtpCommand($smtp, null, '220'); // Wait for greeting
        $this->smtpCommand($smtp, "EHLO {$config['host']}", '250');
        
        if ($config['encryption'] === 'tls') {
            $this->smtpCommand($smtp, 'STARTTLS', '220');
            stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->smtpCommand($smtp, "EHLO {$config['host']}", '250');
        }
        
        if (!empty($config['username'])) {
            $this->smtpCommand($smtp, 'AUTH LOGIN', '334');
            $this->smtpCommand($smtp, base64_encode($config['username']), '334');
            $this->smtpCommand($smtp, base64_encode($config['password']), '235');
        }
        
        $this->smtpCommand($smtp, "MAIL FROM: <{$from}>", '250');
        $this->smtpCommand($smtp, "RCPT TO: <{$to}>", '250');
        $this->smtpCommand($smtp, 'DATA', '354');
        
        $headers = "From: {$from}\r\n";
        $headers .= "To: {$to}\r\n";
        $headers .= "Subject: {$subject}\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "MIME-Version: 1.0\r\n\r\n";
        
        fwrite($smtp, $headers . $message . "\r\n.");
        $this->smtpCommand($smtp, null, '250');
        $this->smtpCommand($smtp, 'QUIT', '221');
        
        fclose($smtp);
    }
    
    /**
     * Send email via PHP mail() function
     */
    private function sendViaPHPMail($to, $subject, $message, $from)
    {
        $headers = [
            'From: ' . $from,
            'Reply-To: ' . $from,
            'Content-Type: text/html; charset=UTF-8',
            'MIME-Version: 1.0'
        ];
        
        $success = mail($to, $subject, $message, implode("\r\n", $headers));
        if (!$success) {
            throw new \Exception('PHP mail() function failed');
        }
    }
    
    /**
     * Execute SMTP command
     */
    private function smtpCommand($smtp, $command, $expectedCode)
    {
        if ($command !== null) {
            fwrite($smtp, $command . "\r\n");
        }
        
        $response = fgets($smtp, 512);
        $code = substr($response, 0, 3);
        
        if ($code !== $expectedCode) {
            throw new \Exception("SMTP error: {$response}");
        }
        
        return $response;
    }
    
    /**
     * Get email configuration
     */
    private function getEmailConfig()
    {
        // Default configuration - should be loaded from config file
        return [
            'driver' => 'mail', // 'mail' or 'smtp'
            'host' => 'localhost',
            'port' => 587,
            'encryption' => 'tls',
            'username' => '',
            'password' => ''
        ];
    }

    /**
     * Handle job failure
     */
    public function failed(\Exception $exception)
    {
        // $logger = new Logger(); // Logger class doesn't exist yet
        // $logger->error('Email job failed', [
        //     'to' => $this->get('to'),
        //     'subject' => $this->get('subject'),
        //     'error' => $exception->getMessage(),
        //     'attempts' => $this->getAttempts(),
        //     'job_id' => $this->getId()
        // ]);
        
        // Temporary logging until Logger class is implemented
        $to = $this->get('to');
        $subject = $this->get('subject');
        $error = $exception->getMessage();
        $attempts = $this->getAttempts();
        $jobId = $this->getId();
        error_log("Email job failed - to: {$to}, subject: {$subject}, error: {$error}, attempts: {$attempts}, job_id: {$jobId}");

        // You could send a notification to administrators here
        // or add the email to a dead letter queue for manual review
    }

    /**
     * Determine if the job should be retried
     */
    public function shouldRetry(\Exception $exception)
    {
        // Don't retry for validation errors
        if ($exception instanceof \InvalidArgumentException) {
            return false;
        }

        // Retry for other exceptions if under max attempts
        return parent::shouldRetry($exception);
    }

    /**
     * Retry a failed job
     *
     * @param string $jobId
     * @return bool
     */
    public function retry($jobId)
    {
        // For email jobs, we can simply return false as retry logic
        // is typically handled by the queue system itself
        // This method is mainly for custom retry logic if needed
        return false;
    }
}