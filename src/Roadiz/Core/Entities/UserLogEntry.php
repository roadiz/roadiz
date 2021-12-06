<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;

/**
 * Add User to Gedmo\Loggable\Entity\LogEntry
 *
 * @ORM\Table(
 *     name="user_log_entries",
 *     options={"row_format":"DYNAMIC"},
 *  indexes={
 *      @ORM\Index(name="log_class_lookup_idx", columns={"object_class"}),
 *      @ORM\Index(name="log_date_lookup_idx", columns={"logged_at"}),
 *      @ORM\Index(name="log_user_lookup_idx", columns={"username"}),
 *      @ORM\Index(name="log_version_lookup_idx", columns={"object_id", "object_class", "version"})
 *  }
 * )
 * @ORM\Entity(repositoryClass="Gedmo\Loggable\Entity\Repository\LogEntryRepository")
 */
class UserLogEntry extends AbstractLogEntry
{
    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", unique=false, onDelete="SET NULL")
     * @var User|null
     */
    protected ?User $user = null;

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User|null $user
     *
     * @return UserLogEntry
     */
    public function setUser(?User $user): UserLogEntry
    {
        $this->user = $user;

        return $this;
    }
}
