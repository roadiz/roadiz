<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Serializers;

use RZ\Roadiz\Utils\XlsxExporter;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Define basic serialize operations for XLSX data type.
 */
abstract class AbstractXlsxSerializer implements SerializerInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * AbstractXlsxSerializer constructor.
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Serializes data.
     *
     * @param mixed $obj
     *
     * @return string
     */
    public function serialize($obj)
    {
        $data = $this->toArray($obj);
        $exporter = new XlsxExporter($this->translator);

        return $exporter->exportXlsx($data);
    }
}
