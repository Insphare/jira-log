<?php

/**
 * Class Auth
 *
 * @author Manuel Will <insphare@gmail.com>
 */
class Auth {

	/**
	 * @var null|string
	 */
	private $user = null;

	/**
	 * @var null|string
	 */
	private $pass = null;

    private $token = null;

	/**
	 * @param string $user
	 * @param string $pass
     * @param string $token
	 */
	public function __construct($user, $pass, $token) {
		$this->user = (string)$user;
		$this->pass = (string)$pass;
        $this->token = (string)$token;
	}

	/**
	 * @author Manuel Will <insphare@gmail.com>
	 * @return null|string
	 */
	public function getPass() {
		return $this->pass;
	}

	/**
	 * @author Manuel Will <insphare@gmail.com>
	 * @return null|string
	 */
	public function getUser() {
		return $this->user;
	}

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }
}
