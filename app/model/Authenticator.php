<?php

namespace App\Model;

use Nette;
use Nette\Security;


/**
 * Users authenticator.
 */
class Authenticator extends Nette\Object implements Security\IAuthenticator
{
	private $model;



	public function __construct(AuthorManager $user)
	{
		$this->model = $user;
	}


	public function authenticate(array $credentials)
	{
		list($userId, $password) = $credentials;

		$pswd = AuthorModel::hash($password);
        $row = $this->model->getByName($userId);

        if(!$row){
            throw new Security\AuthenticationException('Login credentials invalid.', self::INVALID_CREDENTIAL);
        }

        if($pswd !== $row->password){
            throw new Security\AuthenticationException('Login credentials invalid.', self::INVALID_CREDENTIAL);
        }

		return new Security\Identity($row->id, $row->role, (array) $row);

	}




}
