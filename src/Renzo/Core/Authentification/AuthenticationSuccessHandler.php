<?php
namespace RZ\Renzo\Core\Authentification;

use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use RZ\Renzo\Core\Kernel;
/**
* 		
*/
class AuthenticationSuccessHandler extends DefaultAuthenticationSuccessHandler
{
	
	/**
	 * {@inheritdoc}
	 */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
    	if (null !== $user = $token->getUser()) {
			$user->setLastLogin(new \DateTime('now'));
			Kernel::getInstance()->em()->flush();
		}	
        return parent::onAuthenticationSuccess( $request, $token );
    }	
}