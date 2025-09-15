1. in config/databse.php locate the lines 

    private $host = 'localhost';
    private $db_name = 'sql';
    private $username = 'root';
    private $password = '';
    private $conn;

$host is the server or which database you are using, 'localhost' as it's area or by itself it can be networked by replacing the localhost
to the ip address of the device where the will be fetch or sent to

$db_name is the name of the database

$password will be added only when the database is sercured by a password, and will be needing to get a privilege 
from the server to fetch data

2. go to the terminal using bash to encode "composer require phpmailer/phpmailer"

3. locate the send_reset.php and look for this lines:

a. link config
$resetLink = "http://alex.local/reset_password.php?token=" . urlencode($token);

change the 'alex.local' to localhost

b. email configuration
$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host       = 'smtp.gmail.com';
$mail->SMTPAuth   = true;
$mail->Username   = 'the-gmail-you-want-to-use-as-the-sender'; 
$mail->Password   = 'your-app-password';     
$mail->SMTPSecure = 'tls';
$mail->Port       = 587;

$mail->setFrom('the-gmail-you-want-to-use-as-the-sender', 'App-Name'); // must match Username
$mail->addAddress($email);  // recipient email
$mail->isHTML(true);
$mail->Subject = 'Password Reset';
$mail->Body    = "Click the link to reset your password: $resetLink";

$mail->send();


the-gmail-you-want-to-use-as-the-sender = any gmail will do as long it's registered in a device, prefrerably a cellphone 
that have a sim card due to that there will be a need to verify the gmail that will be used

your-app-password = this can be aquire after following the instructions below

1️. Enable 2-Step Verification
Go to Google Account Security
Under “Signing in to Google”, find 2-Step Verification and enable it.
Complete the verification setup (phone, Google prompt, etc.).

2️. Generate an App Password
After 2-Step Verification is enabled, stay on the Security page.
Click App passwords (under “Signing in to Google”). if can be located just search

You’ll see a dropdown:

Select app: choose “Mail”
Select device: choose “Other (Custom name)” and type something like "DMS"

3. when coming up with the custom name you will see the 16-character-password that will be use in your-app-password

app-name = it is the custom name that you made 