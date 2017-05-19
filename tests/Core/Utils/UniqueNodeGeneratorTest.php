<?php


use Doctrine\Common\Collections\ArrayCollection;
use RZ\Roadiz\Tests\DefaultThemeDependentCase;
use RZ\Roadiz\Utils\Node\UniqueNodeGenerator;

class UniqueNodeGeneratorTest extends DefaultThemeDependentCase
{
    public function testUniqueNodeGenerator()
    {
        $generator = new UniqueNodeGenerator(static::getManager());
        $translation = static::getManager()
            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
            ->findDefault();
        $nodeType = static::getManager()
            ->getRepository('RZ\Roadiz\Core\Entities\NodeType')
            ->findOneByName('Page');

        $nodeSourceRoot = $generator->generate($nodeType, $translation);

        $this->assertEquals(1, $nodeSourceRoot->getNode()->getPosition());

        static::getManager()->remove($nodeSourceRoot);
        static::getManager()->flush();
    }

    public function testNodeGenerator()
    {
        $generator = new UniqueNodeGenerator(static::getManager());
        $translation = static::getManager()
            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
            ->findDefault();
        $nodeType = static::getManager()
            ->getRepository('RZ\Roadiz\Core\Entities\NodeType')
            ->findOneByName('Page');
        $collection = new ArrayCollection();

        $nodeSourceRoot = $generator->generate($nodeType, $translation); $collection->add($nodeSourceRoot);
        $nodeSource1 = $generator->generate($nodeType, $translation, $nodeSourceRoot->getNode()); $collection->add($nodeSource1);
        $nodeSource2 = $generator->generate($nodeType, $translation, $nodeSourceRoot->getNode()); $collection->add($nodeSource2);
        $nodeSource3 = $generator->generate($nodeType, $translation, $nodeSourceRoot->getNode()); $collection->add($nodeSource3);

        static::getManager()->flush();

        $this->assertEquals(3, $nodeSourceRoot->getNode()->getChildren()->count());
        $this->assertEquals(1, $nodeSourceRoot->getNode()->getPosition());
        $this->assertEquals(1, $nodeSource1->getNode()->getPosition());
        $this->assertEquals(2, $nodeSource2->getNode()->getPosition());
        $this->assertEquals(3, $nodeSource3->getNode()->getPosition());

        foreach ($collection as $source) {
            static::getManager()->remove($source);
        }
        static::getManager()->flush();
    }

    public function testInversedNodeGenerator()
    {
        $generator = new UniqueNodeGenerator(static::getManager());
        $translation = static::getManager()
            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
            ->findDefault();
        $nodeType = static::getManager()
            ->getRepository('RZ\Roadiz\Core\Entities\NodeType')
            ->findOneByName('Page');
        $collection = new ArrayCollection();

        $nodeSourceRoot = $generator->generate($nodeType, $translation);
        $collection->add($nodeSourceRoot);

        $nodeSource1 = $generator->generate($nodeType, $translation, $nodeSourceRoot->getNode(), null, true);
        $collection->add($nodeSource1);

        $nodeSource2 = $generator->generate($nodeType, $translation, $nodeSourceRoot->getNode(), null, true);
        $collection->add($nodeSource2);

        $nodeSource3 = $generator->generate($nodeType, $translation, $nodeSourceRoot->getNode(), null, true);
        $collection->add($nodeSource3);

        static::getManager()->flush();

        $this->assertEquals(3, $nodeSourceRoot->getNode()->getChildren()->count());
        $this->assertEquals(1, $nodeSourceRoot->getNode()->getPosition());
        $this->assertEquals(3, $nodeSource1->getNode()->getPosition());
        $this->assertEquals(2, $nodeSource2->getNode()->getPosition());
        $this->assertEquals(1, $nodeSource3->getNode()->getPosition());

        foreach ($collection as $source) {
            static::getManager()->remove($source);
        }
        static::getManager()->flush();
    }
}
