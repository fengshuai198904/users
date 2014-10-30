<?php

require_once(dirname(dirname(__DIR__)) . '/global.php');

require_once(dirname(dirname(__DIR__)) . '/classes/User.php');

if (array_key_exists('recover', $_POST)) {
	$users = User::getUsersByEmailOrUsername(mb_convert_encoding($_POST['emailorusername'], 'UTF-8'));

	$subject = UserConfig::$passwordRecoveryEmailSubject;

	$headers = 'From: ' . UserConfig::$supportEmailFrom . "\r\n" .
			'Reply-To: ' . UserConfig::$supportEmailReplyTo . "\r\n" .
			'X-Mailer: ' . UserConfig::$supportEmailXMailer;

	if (!is_null(UserConfig::$onRenderTemporaryPasswordEmail)) {
		$baseurl = UserConfig::$USERSROOTFULLURL . '/login.php';

		foreach ($users as $user) {
			$temppass = $user->generateTemporaryPassword();
			$tempass_enc = urlencode($temppass);

			$username = $user->getUsername();
			$name_enc = urlencode($username);

			$email = $user->getEmail();

			$message = call_user_func_array(UserConfig::$onRenderTemporaryPasswordEmail, array($baseurl, $username, $temppass));

			mail($email, $subject, $message, $headers);
		}

		// We always report "sent" to avoid information disclosure
		// e.g. letting hackers know which usernames and emails are available
		header('Location: ' . UserConfig::$USERSROOTURL . '/modules/usernamepass/forgotpassword.php?status=sent');

		exit;
	} else {
		throw new StartupAPIException('Can\'t render temporary password email, check if UserConfig::$onRenderTemporaryPasswordEmail is set');
	}
}

$template_info = StartupAPI::getTemplateInfo();

if (array_key_exists('status', $_GET) && $_GET['status'] == 'sent') {
	$template_info['sent'] = TRUE;
}
StartupAPI::$template->display('modules/usernamepass/forgotpassword.html.twig', $template_info);