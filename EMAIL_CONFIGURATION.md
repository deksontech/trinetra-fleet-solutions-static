# Email Configuration

The form handlers use authenticated SMTP when SMTP_HOST, SMTP_PORT, SMTP_USERNAME, SMTP_PASSWORD, SMTP_ENCRYPTION, MAIL_TO and MAIL_FROM are configured in /config/mail-config.php. The config file is protected by /config/.htaccess and ignored by git. Use sales@trinetrafleet.com as SMTP_USERNAME, MAIL_TO and MAIL_FROM for all website form submissions. Never place SMTP passwords in HTML or JavaScript.
