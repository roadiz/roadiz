<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use GuzzleHttp\Client;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @see https://github.com/thrace-project/form-bundle/blob/master/Validator/Constraint/RecaptchaValidator.php
 */
class RecaptchaValidator extends ConstraintValidator
{
    /**
     *
     * @see \Symfony\Component\Validator\ConstraintValidator::validate()
     * @param mixed $data
     * @param Constraint $constraint
     */
    public function validate($data, Constraint $constraint)
    {
        if ($constraint instanceof Recaptcha) {
            $propertyPath = $this->context->getPropertyPath();
            $responseField = $constraint->request->request->get($constraint->fieldName);

            if (empty($responseField)) {
                $this->context->buildViolation($constraint->emptyMessage)
                    ->atPath($propertyPath)
                    ->addViolation();
            } elseif (true !== $response = $this->check($constraint, $responseField)) {
                $this->context->buildViolation($constraint->invalidMessage)
                    ->atPath($propertyPath)
                    ->addViolation();

                if (is_array($response)) {
                    foreach ($response as $errorCode) {
                        $this->context->buildViolation($errorCode)
                            ->atPath($propertyPath)
                            ->addViolation();
                    }
                } elseif (is_string($response)) {
                    $this->context->buildViolation($response)
                        ->atPath($propertyPath)
                        ->addViolation();
                }
            }
        }
    }

    /**
     * Makes a request to recaptcha service and checks if recaptcha field is valid.
     * Returns Google error-codes if recaptcha fails.
     *
     * @param Recaptcha $constraint
     * @param string $responseField
     *
     * @return bool|string|array
     */
    protected function check(Recaptcha $constraint, $responseField)
    {
        $data = [
            'secret' => $constraint->privateKey,
            'response' => $responseField,
        ];

        $client = new Client();
        $response = $client->post($constraint->verifyUrl, [
            'form_params' => $data,
            'connect_timeout' => 10,
            'timeout' => 10,
            'headers' => [
                'Accept'     => 'application/json',
            ]
        ]);
        $jsonResponse = json_decode($response->getBody()->getContents(), true);

        return (isset($jsonResponse['success']) && $jsonResponse['success'] === true) ?
            ($jsonResponse['success']) :
            ($jsonResponse['error-codes']);
    }
}
