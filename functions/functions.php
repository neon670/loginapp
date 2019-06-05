<?php

require'./vendor/autoload.php';

function get_user($id){

	$user_name = "SELECT * FROM users";

	return $user_name;
}


function clean($string){

	return htmlentities($string);
}	

function redirect($location){

	return header("Location: {$location}");
}

function set_message($message){

	if(!empty($message)){

		$_SESSION['message'] = $message;
	}else{
		$message ="";
	}
}

function display_message(){

	if(isset($_SESSION['message'])){

		echo $_SESSION['message'];

		unset($_SESSION['message']);
	}
}

function token_generator(){

	$token = $_SESSION['token'] = md5(uniqid(mt_rand(), true));

	return $token;
}
function validation_errors($error_message){

	$error_message = <<<DELIMITER

	<div class='alert alert-danger' role='alert'>
  	
  <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
    <span aria-hidden='true'>&times;</span> 
  </button> $error_message
</div>
DELIMITER;
	return $error_message;

}

function email_exists($email){

	$sql = "SELECT id FROM users WHERE email = '$email'";

	$result = query($sql);

	if(row_count($result) == 1){
		return true;
	}else{
		return false;
	}

}
function username_exists($username){

	$sql = "SELECT id FROM users WHERE username = '$username'";

	$result = query($sql);

	if(row_count($result) == 1){
		return true;
	}else{
		return false;
	}

}
function getInputValue($name){
	if (isset($_POST[$name])){
		echo $_POST[$name];
	}
}

/******Validate*********/

function validate_user_registration(){

	$errors = [];
	$min 	= 3;
	$max 	= 20;


		if($_SERVER['REQUEST_METHOD'] == "POST"){

			$first_name			= clean($_POST['first_name']);
			$last_name			= clean($_POST['last_name']);
			$username			= clean($_POST['username']);
			$password			= clean($_POST['password']);
			$email				= clean($_POST['email']);
			$confirm_password	= clean($_POST['confirm_password']);

			if(strlen($first_name) < $min ){

				$errors[] = "Your first name can not be less than {$min} characters";

			}
			if(strlen($first_name) > $max ){

				$errors[] = "Your first name can not be more than {$max} characters";

			}
			if(strlen($last_name) < $min ){

				$errors[] = "Your last name can not be less than {$min} characters";

			}
			if(strlen($last_name) > $max ){

				$errors[] = "Your last name can not be more than {$max} characters";

			}
			if(strlen($username) < $min ){

				$errors[] = "Your username can not be less than {$min} characters";

			}
			if(strlen($username) > $max ){

				$errors[] = "Your username can not be more than {$max} characters";

			}

			if(username_exists($username)){

				$errors[] = "Sorry that username is already taken";
			}

			if(email_exists($email)){

				$errors[] = "Sorry that email is already registered";
			}

			if($password != $confirm_password ){

				$errors[] = "Your passwords do not match";

			}


			if(!empty($errors)){
				foreach ($errors as $error) {

					echo validation_errors($error);
					
					}
				}else{
					if(register_user($first_name, $last_name, $username, $email, $password)){
						set_message("<p class='bg-success text-center'>Please check your email or spam folder for activation link</p>");
						redirect("index.php");
						
					}else{

						set_message("<p class='bg-danger text-center'>Sorry could not register the user</p>");
						redirect("index.php");
					}
				}
			
		}

		
} // function validate registration

function send_email($email, $subject, $msg, $headers){

	$mail = new PHPMailer(true);                              

	try{
    //Server settings
    $mail->SMTPDebug = 2;                                 
    $mail->isSMTP();                                      
    $mail->Host = 'smtp1.example.com;smtp2.example.com';  
    $mail->SMTPAuth = true;                               
    $mail->Username = 'user@example.com';                 
    $mail->Password = 'secret';                             
    $mail->SMTPSecure = 'tls';                            
    $mail->Port = 587;      

    $mail->setFrom('closner_nevarez@hotmail.com', 'Noe');
    $mail->addAddress($email);

    //Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = 'Here is the subject';
    $mail->Body    = 'This is the HTML message body <b>in bold!</b>';
    $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

    $mail->send();
    echo 'Message has been sent';
	} catch (Exception $e) {
	    echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
	}                              


			return mail($email, $subject, $msg, $headers);
		

	}

/******Register User Fucntion*********/

function register_user($first_name, $last_name, $username, $email, $password){

	$first_name		= escape($first_name);
	$last_name		= escape($last_name);
	$username		= escape($username);
	$email			= escape($email);
	$password		= escape($password);

	if(email_exists($email)){

		return false;

	}else if(username_exists($username)){
		return false;
	}else{
		$password = password_hash($password, PASSWORD_BCRYPT);
		$validation_code =md5($username . microtime());	

		$sql =	"INSERT INTO users(first_name, last_name, username, email, password, validation_code, active) ";
		$sql.=	" VALUES('$first_name','$last_name','$username','$email','$password','$validation_code',0)";
		$result = query($sql);
		confirm($result);

		$subject = "Activate Account";
		$msg 	 = " Please click link below to activate your account 
					 http://localhost/login/activate.php?email=$email&code=$validation_code	
		";
		$headers =	"From: noreply@mywebsite.com";

		send_email($email, $subject, $msg, $headers);

		return true;
	}

}	

/******ACtivat User Function*********/


function activate_user(){


	if($_SERVER['REQUEST_METHOD'] = "GET"){
		if(isset($_GET['email'])){
			$email = clean($_GET['email']);
			$validation_code = clean($_GET['code']);

			$sql = "SELECT id FROM users WHERE email = '".escape($_GET['email'])."' AND validation_code = '".($_GET['code'])."'";
			$result = query($sql);
			confirm($result);

			if(row_count($result) == 1){

			$sql2 = "UPDATE users SET active = 1, validation_code = 0 WHERE email = '".escape($email)."' AND validation_code = '".escape($validation_code)."' ";	

			$result2 = query($sql2);
			confirm($result2);

			set_message ("<p class='bg-success'>your account has been activated, please login</p>");

			redirect("login.php");
			}
		}

	}
}

/****Validate User Login***/


	function validate_user_login(){

		$errors = [];
		$min    = 3;
		$max    =20;

		if($_SERVER['REQUEST_METHOD'] == "POST"){

			$email			= clean($_POST['email']);
			$password		= clean($_POST['password']);
			$remember 		= isset($_POST['remember']);


			if(!empty($email)){

				$errors[] = "Email field can not be empty";

			}
			if(!empty($password)){
				
				$errors[] = "Password field can not be empty";

			}

			if(empty($errors)){
				foreach ($errors as $error) {

					echo validation_errors($error);
					
					}
				}else{
					if(login_user($email, $password, $remember)){
						redirect("admin.php");
					}else{
						echo validation_errors("your credntials are not correct");
					}
				}

		}
	}


function login_user($email, $password, $remember){

	$sql = "SELECT password, id FROM users WHERE email = '".escape($email)."' AND active = 1";
	$result = query($sql);

	if(row_count($result) == 1){

		$row = fetch_array($result);

		$db_password = $row['password'];

			if(password_verify($password, $db_password)){
				if($remember == "on"){
					setcookie('email', $email, time() + 86400);
				}

				$_SESSION['email'] = $email;

				return true;

			}else{
				return false;
			}

		return true;

	}else{

		return false;
	}

}

/**** Logged in User ****/

function logged_in(){

	if(isset($_SESSION['email']) || isset($_COOKIE['email'])){

		return true;

	}else{

		return false;
	}

}


/*** Recover password ***/
function recover_password(){

	if($_SERVER['REQUEST_METHOD'] == "POST"){

		if(isset($_SESSION['token']) && $_POST['token'] === $_SESSION['token']){

			$email = clean($_POST['email']);

			if(email_exists($_POST)){

				$validation_code = md5($email . microtime());

				setcookie('temp_access_code', $validation_code, time()+3600);

				$sql = "UPDATE users SET validation_code = '".escape($validation_code)."' WHERE email = '".escape($email)."'";
				$result = query($sql);
				confirm($result);

				$subject = "Please reset your password";
				$message = "Here is your passowrd reset code {$validation_code}
				Cick here to reset your password http://localhost/code.php?email=$email&code=$validation_code
				";
				$headers = "From noreply@website.com";

				send_email($email, $subject, $message, $headers);

					set_message("<p class='bg-success'>Please check your inbox or spam folder for your password reset code</p>");
					redirect("index.php");


			}else{
				echo validation_errors("This email does not exist");
			}
		}else{

			redirect("index.php");

		}

		if(isset($_POST['cancel-submit'])){

			redirect("login.php");

		}

	}


} 


/******* Code Validation ******/

function validate_code(){

	if(isset($_COOKIE['temp_access_code'])){


			if(!isset($_GET['email']) && !isset($_GET['code'])){

				redirect("index.php");

			}elseif(empty($_GET['email']) || empty($_GET['code'])){

				redirect("index.php");

			}else{

				if(isset($_POST['code'])){

					$email = clean($_GET['email']);
					$validation_code = clean($_POST['code']);

					$sql = "SELECT id FROM user WHERE validation_code = '".escape($validation_code)."' AND email = '".escape($email)."'";
					$result = query($sql);
					confirm($result);

					if(row_count($result) == 1){

						setcookie('temp_access_code', $validation_code, time()+300);

						redirect("reset.php?email=$email&code=$validation_code");

					}else{

						echo validation_errors("Sorry wrong validation code");
					}

				}
			}
		

	}else{

		set_message("<p class='bg-danger'>Sorry you validation code has expired</p>");

		redirect("recover.php");
	}

}


/*** Password Reset  ***/

function password_reset(){

	if(isset($_COOKIE['temp_access_code'])){

			if(isset($_GET['email']) && isset($_GET['code'])){

			if(isset($_SESSION['token']) && isset($_POST['token'])){

			 if($_POST['token'] === $_SESSION['token']){

			 			if($_POST['password'] == $_POST['confirm_password']){

			 				$updated_password = password_hash($password, PASSWORD_BCRYPT);

						$sql = "UPDATE users SET password = '".escape($updated_password)."', validation_code = 0 WHERE email = '".escape($_GET['email'])."'";
						$result = query($sql);
						confirm($result);

						set_message("<p class='bg-success'>Your password has been update, please login</p>");
						redirect("login.php");
						}

					}

				}

			}

		}else{

				set_message("<p class='bg-danger'>Sorry, your time has expired</p>");
				redirect("recover.php");

			}
	}


	
	
?>