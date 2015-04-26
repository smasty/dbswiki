<?php

namespace App\Model;

use Nette;
use Nette\Security;
use Nette\Utils\Strings;


/**
 * Users authenticator.
 */
class Authenticator extends Nette\Object implements Security\IAuthenticator
{
	private $model;



	public function __construct(AuthorManager $user){
		$this->model = $user;
	}


	public function authenticate(array $credentials){
		list($userId, $password) = $credentials;

		$pswd = static::hash($password);
        $row = $this->model->getByName($userId);

        if(!$row){
            throw new Security\AuthenticationException('Login credentials invalid.', self::INVALID_CREDENTIAL);
        }

        if($pswd !== $row->password){
            throw new Security\AuthenticationException('Login credentials invalid.', self::INVALID_CREDENTIAL);
        }

        $array = [];
        foreach((array) $row as $k=>$v){
            $array[str_replace("\x00*\x00", "", $k)] = $v;
        }
        unset($array['password']);
        unset($array['revisions']);
		return new Security\Identity($row->id, $row->role, $array);

	}


    public static function hash($password){
        if ($password === Strings::upper($password)){
            $password = Strings::lower($password);
        }
        return md5($password);
    }

}
