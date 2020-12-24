<?php


use Doctrine\Common\Collections\ArrayCollection;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Tests\DefaultThemeDependentCase;

class UniqueNodeGeneratorTest extends DefaultThemeDependentCase
{
    public function testUniqueNodeGenerator()
    {
        $generator = $this->get('utils.uniqueNodeGenerator');
        $translation = static::getManager()
            ->getRepository(Translation::class)
            ->findDefault();
        $nodeType = static::getManager()
            ->getRepository(NodeType::class)
            ->findOneByName('Page');

        $nodeSourceRoot = $generator->generate($nodeType, $translation);

        $this->assertEquals(1, $nodeSourceRoot->getNode()->getPosition());

        static::getManager()->remove($nodeSourceRoot);
        static::getManager()->flush();
    }

    public function testNodeGenerator()
    {
        $generator = $this->get('utils.uniqueNodeGenerator');
        $translation = static::getManager()
            ->getRepository(Translation::class)
            ->findDefault();
        $nodeType = static::getManager()
            ->getRepository(NodeType::class)
            ->findOneByName('Page');
        $collection = new ArrayCollection();

        $nodeSourceRoot = $generator->generate($nodeType, $translation);
        $collection->add($nodeSourceRoot);
        $nodeSource1 = $generator->generate($nodeType, $translation, $nodeSourceRoot->getNode());
        $collection->add($nodeSource1);
        $nodeSource2 = $generator->generate($nodeType, $translation, $nodeSourceRoot->getNode());
        $collection->add($nodeSource2);
        $nodeSource3 = $generator->generate($nodeType, $translation, $nodeSourceRoot->getNode());
        $collection->add($nodeSource3);

        static::getManager()->flush();

        $this->assertEquals(3, $nodeSourceRoot->getNode()->getChildren()->count());
        $this->assertEquals(1.0, $nodeSourceRoot->getNode()->getPosition());
        $this->assertEquals(1.0, $nodeSource1->getNode()->getPosition());
        $this->assertEquals(2.0, $nodeSource2->getNode()->getPosition());
        $this->assertEquals(3.0, $nodeSource3->getNode()->getPosition());

        foreach ($collection as $source) {
            static::getManager()->remove($source);
        }
        static::getManager()->flush();
    }

    public function testInversedNodeGenerator()
    {
        $generator = $this->get('utils.uniqueNodeGenerator');
        $translation = static::getManager()
            ->getRepository(Translation::class)
            ->findDefault();
        $nodeType = static::getManager()
            ->getRepository(NodeType::class)
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
        $this->assertEquals(1.0, $nodeSourceRoot->getNode()->getPosition());
        $this->assertEquals(3.0, $nodeSource1->getNode()->getPosition());
        $this->assertEquals(2.0, $nodeSource2->getNode()->getPosition());
        $this->assertEquals(1.0, $nodeSource3->getNode()->getPosition());

        foreach ($collection as $source) {
            static::getManager()->remove($source);
        }
        static::getManager()->flush();
    }
}
