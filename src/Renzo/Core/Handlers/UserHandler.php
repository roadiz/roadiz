<?php 

namespace RZ\Renzo\Core\Handlers;

use RZ\Renzo\Core\Entities\User;
use RZ\Renzo\Core\Log\Logger;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\Util\SecureRandom;


/**
* 
*/
class UserHandler 
{
	private static $encoderFactory = null;
	private $user;

	/**
	 * @return RZ\Renzo\Core\Entities\User
	 */
	public function getUser()
	{
		return $this->user;
	}

	function __construct( User $user )
	{
		$this->user = $user;
	}

	/**
	 * Encode current User password
	 * @return void
	 */
	public function encodePassword()
	{
		if ($this->getUser()->getPlainPassword() != '') {
			$encoder = static::getEncoderFactory()->getEncoder($this->getUser());
			$encodedPassword = $encoder->encodePassword($this->getUser()->getPlainPassword(), $this->getUser()->getSalt());
			$this->getUser()->setPassword($encodedPassword);
		}
		else {
			throw new Exception("User plain password is empty", 1);
		}
	}

	/**
	 * 
	 * @param  string  $plainPassword Submitted password to validate
	 * @return boolean 
	 */
	public function isPasswordValid( $plainPassword )
	{
		$encoder = static::getEncoderFactory()->getEncoder($this->getUser());

		return $encoder->isPasswordValid(
			$this->getUser()->getPassword(), // the encoded password
			$plainPassword,       // the submitted password
			$this->getUser()->getSalt()
		);
	}

	/**
	 * Get default encoder factory for Renzo Entities
	 * @return Symfony\Component\Security\Core\Encoder\EncoderFactory
	 */
	public static function getEncoderFactory()
	{
		if (static::$encoderFactory === null) {
			$defaultEncoder = new MessageDigestPasswordEncoder('sha512', true, 5000);

			$encoders = array(
			    'Symfony\\Component\\Security\\Core\\User\\User' => $defaultEncoder,
			    'RZ\\Renzo\\Core\\Entities\\User' => $defaultEncoder,
			);

			static::$encoderFactory =  new EncoderFactory($encoders);
		}

		return static::$encoderFactory;
	}

	/**
	 * @param  integer $length Password length
	 * @return string New password
	 */
	public static function generatePassword($length = 9) {

        $lowercase = "qwertyuiopasdfghjklzxcvbnm";
        $uppercase = "ASDFGHJKLZXCVBNMQWERTYUIOP";
        $numbers = "1234567890";
        $specialcharacters = "{}[]:.<>?_+!@#";
        $randomCode = "";
        mt_srand(crc32(microtime()));
        $max = strlen($lowercase) - 1;
        for ($x = 0; $x < abs($length/3); $x++) {
            $randomCode .= $lowercase{mt_rand(0, $max)};
        }
        $max = strlen($uppercase) - 1;
        for ($x = 0; $x < abs($length/3); $x++) {
            $randomCode .= $uppercase{mt_rand(0, $max)};
        }
        $max = strlen($specialcharacters) - 1;
        for ($x = 0; $x < abs($length/3); $x++) {
            $randomCode .= $specialcharacters{mt_rand(0, $max)};
        }
        $max = strlen($numbers) - 1;
        for ($x = 0; $x < abs($length/3); $x++) {
            $randomCode .= $numbers{mt_rand(0, $max)};
        }
        return str_shuffle($randomCode);
    }
}