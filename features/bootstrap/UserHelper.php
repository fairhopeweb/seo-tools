<?php

require_once __DIR__ . '/../../vendor/phpunit/phpunit/PHPUnit/Framework/Assert/Functions.php';

class UserHelper
{
    private $user_repo;
    private $response;
    private $email;

    public function __construct()
    {
        $users = array(
            new \DomainFinder\Entity\User( 'existing@email.com', 'correct_password' ),
            new \DomainFinder\Entity\User( 'jack@email.com', 'correct_password' )
        );

        $this->user_repo    = new \DomainFinder\Entity\UserArrayRepository( $users );
        $config             = require __DIR__ . '/../../config/config.php';
        $this->container    = require __DIR__ . '/../../src/DomainFinderSilex/app.php';        
        $this->session      = $this->container['session'];
        $this->db           = $this->container['orm.em']->getConnection();
    }

    public function getUserRepository()
    {
        return $this->user_repo;
    }

    public function getSession()
    {
        return $this->session;
    }

    public function registerUser( array $user_data )
    {
        $use_case = new DomainFinder\UseCase\RegisterUser( $this->user_repo );

        try {
            $this->response = $use_case->execute( $user_data );
            $this->email    = $user_data['email'];
            $this->password = sha1( $user_data['password'] );
        } catch ( \Exception $e ) {
            $this->response = $e;
        }

        return $this->response;
    }

    public function login( array $user_data )
    {
        $use_case = new DomainFinder\UseCase\Login( $this->user_repo, $this->session );

        try {
            $this->response = $use_case->execute( $user_data );
            $this->user     = $this->response['user'];
        } catch ( \Exception $e ) {
            $this->response = $e;
        }

        return $this->response;
    }

    public function logout()
    {
        $use_case = new DomainFinder\UseCase\Logout( $this->session );

        try {
            $use_case->execute();
        } catch ( \Exception $e ) {
            $this->response = $e;
        }

        return $this->response;
    }

    public function assertUserCreated( $user_email )
    {
        assertInstanceOf( 'DomainFinder\Entity\User', $this->response['user'] );
        assertSame( $this->email, $this->response['user']->getEmail() );
        assertSame( $this->password, $this->response['user']->getPassword() );
        assertNotNull( $this->user_repo->findOneByEmail( $user_email ) );
    }

    public function assertErrorInRegistration($exception)
    {
        assertInstanceOf( $exception, $this->response );
    }

    public function assertUserIsLoggedIn()
    {
        assertSame( $this->session->get( 'current_user' ), $this->response['user'] );
    }

    public function assertUserHasLogout()
    {
        assertNull( $this->session->get( 'current_user' ) );
    }

    public function install( $request )
    {
        $use_case = new DomainFinder\UseCase\Install( $this->user_repo, $this->session, $this->db );

        try {
            $this->response = $use_case->execute( $request );
            $this->user     = $this->response['user'];
            $this->email    = $request['email'];
            $this->password = sha1( $request['password'] );
        } catch ( \Exception $e ) {
            var_dump( $e->getMessage() );die;
            $this->response = $e;
        }
    }
}