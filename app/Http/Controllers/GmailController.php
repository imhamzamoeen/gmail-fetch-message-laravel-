<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Client;
use Google\Service\Gmail;

class GmailController extends Controller
{

    // for this to work i created a dummy app at google console , setted the redirect uri and got client credentials and used in this project
    protected $client;

    public function __construct()
    {
        // initializing the wrapper client google/apiclient
        $this->client = new Client();
        $this->client->setClientId(env('GOOGLE_CLIENT_ID'));   // set the client id you got from the google developer app console
        $this->client->setClientSecret(env('GOOGLE_SECRET_KEY'));  // set the secret key you got from the google developer app console
        $this->client->setRedirectUri(env('GOOGLE_REDIRECT_URI')); // set the redirect uri you have setted for the call back where google will redirect our application
        $this->client->addScope(Gmail::MAIL_GOOGLE_COM);   // setting the gmail scope that user will grant access for
        $this->client->setAccessType('offline');  // setting access type to offline as we require refresh token
    }


    public function redirectToProvider()
    {
        /* this route is used to redirect a user to google oauth page where user authenticate our app and redirect to our redirect url */
        $authUrl = $this->client->createAuthUrl();
        return redirect()->away($authUrl);
    }


    public function callback(Request $request)
    {

        /* after user authorize our app the google send the response to this route */
        $this->client->authenticate($request->code);   // getting the authorization code form the reponse
        $accessToken = $this->client->getAccessToken();  // getting the access token form the from the authorization code

        // here access token contian the response like an array with acecess token  , refresh token , epxiry time and other stuff

        /*
         array:6 [
            "access_token" => "
            ya29.a0AXooCgs5QblEbuem9EqbDUZBoKdreyENIaF_X9nTmweL3O4Ayb9wOBrlW1X37MxvZ8rVD_2zouGHEVBeLZhwox8lNC0fju-"
            "expires_in" => 3599
            "refresh_token" => "1//03b_XrwwvfotnCgYIARAAGAMSNwF-UmEbrLVoHUvr6TcS4kB7mS14bbBQbD1Lw4"
            "scope" => "https://mail.google.com/"
            "token_type" => "Bearer"
            "created" => 1719593217
            ]
        */

        /*  --------------    LOGIC    ---------
         here either we can cache this response or better way is to store this in the database against the user  then we can get the access token from the database and pass it to our client
        that checks whether access token is expired or not if expired use the refresh token as never expires and we get new access token


        */
        session(['access_token' => $accessToken]);

        return redirect('/gmail');
    }

    public function listMessages()
    {
        // befere making a request to gmail api set access token from the session or database wherever stored
        $this->client->setAccessToken(session('access_token'));
        // check first expiry
        if ($this->client->isAccessTokenExpired()) {
            // this makes call to refresh token api
            $refreshToken = $this->client->getRefreshToken();
            // get the new access token by the refresh token we provide
            $this->client->fetchAccessTokenWithRefreshToken($refreshToken);

            // store in the db or session according to business logic
            session(['access_token' => $this->client->getAccessToken()]);
        }
        // use the service GMAIL provided by the package
        $service = new Gmail($this->client);
        $user = 'me';  // me as for authenicated user
        $query = 'subject:upwork';  // query
        // using package method to get message of the user with query defiened
        $results = $service->users_messages->listUsersMessages($user, ['q' => $query]);

        // here we can check the next page token and handle that accordingly
        $messages = [];
        foreach ($results->getMessages() as $message) {
            // get the message details using message id and user id
            $msg = $service->users_messages->get($user, $message->getId());
            $messages[] = $msg;
        }

        return view('gmail.index', ['messages' => $messages]);
    }
}
