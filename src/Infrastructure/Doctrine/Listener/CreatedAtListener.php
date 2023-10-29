<?php

declare(strict_types=1);

namespace Devscast\Bundle\HexaBundle\Infrastructure\Doctrine\Listener;

use Doctrine\Persistence\Event\LifecycleEventArgs;

/**
 * Class CreatedAtListener.
 *
 * @author bernard-ng <bernard@devscast.tech>
 */
final readonly class CreatedAtListener
{
    public function prePersist(LifecycleEventArgs $args): void
    {
        $object = $args->getObject();
        if (method_exists($object, 'setCreatedAt') && method_exists($object, 'getCreatedAt')) {
            if ($object->getCreatedAt() === null) {
                $object->setCreatedAt(new \DateTimeImmutable());
            }
        }
    }
}
