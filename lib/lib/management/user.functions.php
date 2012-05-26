<?php
PackageManager::requireFunctionOnce('ml.form');
function user_reset_password($user=null){
	if($user==null){
		if(form_get('action',null)===null){
			include 'view/user.reset.password.html';
			return;
		}
		if(form_get('uname',null)===null){
			if(form_get('email',null)===null){
				echo '<div class="error">Please enter your username or email address.</div>';
				include 'view/user.reset.password.html';
				return;
			}else{
				$user=new user();
				$user->set('email', trim(form_get('email')));
				if($user->find('email')){
					if(form_get('stage')=='1')
						user_resend_verification_code($user);
					else
						user_reset_password($user);
				}else{
					echo '<div class="error">No account exists with that email.</div>';
					include 'view/user.reset.password.html';
					return;
				}
			}
		}else{
			$user=new user();
			$user->set('uname', trim(form_get('uname')));
			if($user->find('uname')){
				if(form_get('stage')=='1')
					user_resend_verification_code($user);
				else
					user_reset_password($user);
			}else{
				echo '<div class="error">No account exists with that username.</div>';
				include 'view/user.reset.password.html';
				return;
			}
		}
	}else{
		if(form_get('verification_code',null)!=null){
			if($user->get('verification')==trim(form_get('verification_code'))){
				if(form_get('password',null)!=null&&form_get('password')===form_get('password2')){
					$user->set('upass',md5(form_get('password')));
					$user->update();
				}else{
					echo '<div class="error">Passwords do not match.</div>';
					include 'view/user.reset.password2.html';
					return;
				}
			}else{
				echo '<div class="error">Incorrect verification code.</div>';
				include 'view/user.reset.password2.html';
				return;
			}
		}else{
			include 'view/user.reset.password2.html';
			return;
		}
	}
}
function user_resend_verification_code($user=null){

}
function user_login(){

}
