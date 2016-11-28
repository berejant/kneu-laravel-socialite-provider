<?php


namespace SocialiteProviders\Kneu;

use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'KNEU';
    /**
     * {@inheritdoc}.
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://auth.kneu.edu.ua/oauth', $state);
    }
    /**
     * {@inheritdoc}.
     */
    protected function getTokenUrl()
    {
        return 'https://auth.kneu.edu.ua/oauth/token';
    }
    /**
     * {@inheritdoc}.
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://auth.kneu.edu.ua/api/user/me', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        $this->credentialsResponseBody = $response->getBody()->getContents();
        $user = json_decode($this->credentialsResponseBody, true);

        return $user;
    }

    /**
     * {@inheritdoc}.
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['id'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'middle_name' => $user['middle_name'],
            'last_name' => $user['last_name'],
            'name' => $user['last_name'] . ' ' . $user['first_name'] . ' ' . $user['middle_name'],
            'type' => $user['type'],
            'teacher_id' => array_get($user, 'teacher_id'), // optional field
            'department_id' => array_get($user, 'department_id'), // optional field
            'student_id' => array_get($user, 'student_id'), // optional field
            'group_id' => array_get($user, 'group_id'), // optional field
            'sex' => array_get($user, 'sex'), // optional field
        ]);
    }
    /**
     * {@inheritdoc}.
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }
    /**
     * {@inheritdoc}.
     */
    public function getAccessToken($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'form_params' => $this->getTokenFields($code),
        ]);
        $this->credentialsResponseBody = json_decode($response->getBody(), true);
        return $this->parseAccessToken($response->getBody());
    }

    public function logoutRedirect ($redirectUri)
    {
        $url = 'https://auth.kneu.edu.ua/oauth/logout?' . http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => url($redirectUri)
        ], '', '&', $this->encodingType);

        return new RedirectResponse($url);
    }

}