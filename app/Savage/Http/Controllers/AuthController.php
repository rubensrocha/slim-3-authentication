<?php

namespace Savage\Http\Controllers;

use Savage\Http\Auth\Models\User;
use Savage\Utils\Helper;

/**
 * AuthController handles all of our authentication system.
 */

class AuthController extends Controller
{
    public function getLogin()
    {
        return $this->render('auth/login');
    }

    public function postLogin()
    {
        if(!$this->param('identifier') || !$this->param('password')) {
            $this->flash('error', 'Please enter your e-mail/username and password to login.');
            return $this->redirect('auth.login');
        }

        $auth = $this->auth->attemptLogin($this->param('identifier'), $this->param('password'));

        if(!$auth) {
            $this->flash('warn', 'The credentials you entered were invalid.');
            return $this->redirect('auth.login');
        }

        return $this->redirect();
    }

    public function getRegister()
    {
        return $this->render('auth/register');
    }

    public function postRegister()
    {
        $validator = $this->validator();

        $username = $this->param('username');
        $email = $this->param('email');
        $password = $this->param('password');
        $confirm_password = $this->param('confirm_password');

        $validator->validate([
            'username|Username' => [$username, 'required|min(3)|max(25)|alnumDash|uniqueUsername'],
            'email|E-Mail' => [$email, 'required|email|uniqueEmail'],
            'password|Password' => [$password, 'required|min(6)|max(255)'],
            'confirm_password|Confirm Password' => [$confirm_password, 'required|matches(password)'],
        ]);

        if($validator->passes()) {
            $user = User::create([
                'username' => $username,
                'email' => $email,
                'password' => Helper::hashPassword($password),
            ]);

            $this->flash('success', 'You have been registered!');

            $this->auth->attemptLogin($user->email, $this->param('password'));

            return $this->redirect();
        }

        $errorMessages = "";

        foreach($validator->errors()->all() as $error) {
            $errorMessages .= "<li>$error</li>";
        }

        $errorCountMessage = count($validator->errors()->all()) > 1 ? "were some errors" : "was an error";
        $errorCountMessage2 = count($validator->errors()->all()) > 1 ? "them" : "it";

        $message = "There " . $errorCountMessage . " with your registration: <ul><strong>" . $errorMessages . "</strong></ul>" . "Fix " . $errorCountMessage2 . " and try again.";

        $this->flashNow('raw_error', $message);

        return $this->render('auth/register', [
            'errors' => $validator->errors(),
            'data' => $this->request->getParsedBody()
        ]);
    }


    public function getLogout()
    {
        $this->auth->logout();

        return $this->redirect();
    }

    public function getAccount() {
        return $this->render('auth/account');
    }

    public function postProfile() {
        $validator = $this->validator();

        $email = $this->param('new_email');

        $validator->validate([
            'new_email|E-Mail' => [$email, 'required|email|uniqueEmail']
        ]);

        if($validator->passes()) {
            $this->auth->user()->update([
                'email' => $email
            ]);

            $this->flash('success', 'Your profile has been updated!');
            return $this->redirect('auth.account');
        }

        $errorMessages = "";

        foreach($validator->errors()->all() as $error) {
            $errorMessages .= "<li>$error</li>";
        }

        $errorCountMessage = count($validator->errors()->all()) > 1 ? "were some errors" : "was an error";
        $errorCountMessage2 = count($validator->errors()->all()) > 1 ? "them" : "it";

        $message = "There " . $errorCountMessage . " while trying to update your profile: <ul><strong>" . $errorMessages . "</strong></ul>" . "Fix " . $errorCountMessage2 . " and try again.";

        $this->flashNow('raw_error', $message);

        return $this->render('auth/account', [
            'errors' => $validator->errors(),
        ]);
    }

    public function postChangePassword() {
        $validator = $this->validator();

        $current_password = $this->param('current_password');
        $new_password = $this->param('new_password');
        $confirm_new_password = $this->param('confirm_new_password');

        $validator->validate([
            'current_password|Current Password' => [$current_password, 'required|matchesCurrentPassword'],
            'new_password|New Password' => [$new_password, 'required|min(6)|max(255)'],
            'confirm_new_password|Confirm New Password' => [$confirm_new_password, 'required|matches(new_password)'],
        ]);

        if($validator->passes()) {
            $this->auth->user()->update([
                'password' => Helper::hashPassword($new_password)
            ]);

            $this->flash('success', 'Your password has been updated!');
            return $this->redirect('auth.account');
        }

        $errorMessages = "";

        foreach($validator->errors()->all() as $error) {
            $errorMessages .= "<li>$error</li>";
        }

        $errorCountMessage = count($validator->errors()->all()) > 1 ? "were some errors" : "was an error";
        $errorCountMessage2 = count($validator->errors()->all()) > 1 ? "them" : "it";

        $message = "There " . $errorCountMessage . " while trying to change your password: <ul><strong>" . $errorMessages . "</strong></ul>" . "Fix " . $errorCountMessage2 . " and try again.";

        $this->flashNow('raw_error', $message);

        return $this->render('auth/account', [
            'errors' => $validator->errors(),
        ]);
    }
}