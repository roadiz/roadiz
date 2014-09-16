<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file Newsletter.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\AbstractEntity;
/**
 * Newsletters entities wrap a Node and are linked to
 * Subscribers in order to render a HTML Email and send it over
 * MailTransportAgent.
 *
 * @Entity(repositoryClass="RZ\Renzo\Core\Utils\EntityRepository")
 * @Table(name="newsletters")
 */
class Newsletter extends AbstractEntity
{

}
