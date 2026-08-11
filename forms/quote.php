<?php
require __DIR__.'/_helpers.php'; guard();
$required=["service_type","full_name","email","phone"]; foreach($required as $r){ if(empty($_POST[$r])) fail('Please complete all required fields.'); }
if(!valid_email($_POST['email'])) fail('Please enter a valid email address.');
$fields=[]; foreach($_POST as $k=>$v){ if(in_array($k,['website','form_started_at'],true)) continue; $fields[$k]=$v; }

$sent=send_basic_mail('New quote enquiry - Trinetra Fleet Solutions',$fields,$_POST['email']); if(!$sent) fail('Your enquiry could not be emailed right now. Please call or WhatsApp Trinetra Fleet Solutions.'); ack($_POST['email'],clean($_POST['full_name'],120)); ok();
