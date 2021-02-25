<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\CustomForm;

use RZ\Roadiz\Core\Entities\CustomFormAnswer;
use RZ\Roadiz\Core\Entities\CustomFormFieldAttribute;
use RZ\Roadiz\Core\Entities\Document;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CustormFormAnswerSerializer
{
    /**
     * @var UrlGeneratorInterface
     */
    protected UrlGeneratorInterface $urlGenerator;

    /**
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param CustomFormAnswer $answer
     *
     * @return array
     */
    public function toSimpleArray(CustomFormAnswer $answer): array
    {
        $answers = [];
        /** @var CustomFormFieldAttribute $answerAttr */
        foreach ($answer->getAnswers() as $answerAttr) {
            $field = $answerAttr->getCustomFormField();
            if ($field->isDocuments()) {
                $answers[$field->getName()] = implode(PHP_EOL, $answerAttr->getDocuments()->map(function (Document $document) {
                    return $this->urlGenerator->generate('documentsDownloadPage', [
                        'documentId' => $document->getId()
                    ], UrlGeneratorInterface::ABSOLUTE_URL);
                })->toArray());
            } else {
                $answers[$field->getName()] = $answerAttr->getValue();
            }
        }
        return $answers;
    }
}
