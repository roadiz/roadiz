<?php 
namespace RZ\Renzo\Core\Authentification;

use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;


class AuthenticationManager implements AuthenticationManagerInterface
{
	public function authenticate(TokenInterface $token)
	{
		# code...
	}	
}