php-user-managment-class
========================

One class to control registration, logging in and authorization

Register file example :
========================

    <?php
    include 'user.class.php';
    
    if (!empty($_POST)){
    	$user = new user();
    	$user->register();
    	if($user->error("register") == ""){
    		echo "win";
    	}else{
    		echo $user->error("register");
    	}
    }
    echo "<form name='testform' method='post'>
    <input type='text' name='username'>
    <input type='text' name ='password'>
    <input type='text' name ='age'>
    <input type='submit'>
    </form>";
    ?>

Login file example :
========================

	require_once 'user.class.php';
	$user = new user();
	
	if($_GET["logout"] != ""){
		$user->logout();
	}
	
	if (!empty($_POST)) {
		$user->login($_POST["username"],$_POST["password"]);
		if ($user->error("login") != "") {
			echo $user->error("login");
		}
	}
	
	$user->auth();
	
	if($user->error("auth") == ""){
		echo "logged in as ";
		echo $user->username;
	}else{
		echo "<form name='testform' method='post'>
		<input type='text' name='username' placeholder='username'>
		<input type='text' name ='password' placeholder='password'>
		<input type='submit'>
		</form>";
	}
####
