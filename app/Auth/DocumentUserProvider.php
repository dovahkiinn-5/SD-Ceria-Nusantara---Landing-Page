<?php

namespace App\Auth;

use App\Contracts\DocumentStore;
use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Hash;

class DocumentUserProvider implements UserProvider
{
    public function __construct(private DocumentStore $store) {}

    public function retrieveById($identifier)
    {
        $record = $this->store->get('admins', $identifier);
        return $record && ($record['active'] ?? false) ? new GenericUser($record + ['remember_token'=>'']) : null;
    }
    public function retrieveByToken($identifier, $token) { return null; }
    public function updateRememberToken(Authenticatable $user, $token) {}
    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials['email'])) return null;
        return $this->retrieveById(hash('sha256', mb_strtolower(trim($credentials['email']))));
    }
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        return Hash::check($credentials['password'], $user->getAuthPassword());
    }
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false)
    {
        if ($force || Hash::needsRehash($user->getAuthPassword())) {
            $record = $this->store->get('admins', $user->getAuthIdentifier());
            $record['password'] = Hash::make($credentials['password']);
            $this->store->put('admins', $user->getAuthIdentifier(), $record);
        }
    }
}
