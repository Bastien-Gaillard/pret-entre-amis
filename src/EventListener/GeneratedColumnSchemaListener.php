<?php

namespace App\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\ToolEvents;

/**
 * La colonne générée loans.active_item_id (et son index unique) est créée par la
 * migration, en SQL natif : Doctrine ne sait pas comparer une colonne générée.
 *
 * On la déclare ici dans le schéma attendu, telle que Doctrine la lit en base,
 * pour que doctrine:schema:validate et doctrine:migrations:diff restent propres
 * au lieu de proposer de la supprimer.
 */
#[AsDoctrineListener(event: ToolEvents::postGenerateSchema)]
final class GeneratedColumnSchemaListener
{
    public function postGenerateSchema(GenerateSchemaEventArgs $args): void
    {
        $schema = $args->getSchema();
        if (!$schema->hasTable('loans')) {
            return;
        }

        $loans = $schema->getTable('loans');
        $loans->addColumn('active_item_id', Types::INTEGER, ['unsigned' => true, 'notnull' => false]);
        $loans->addUniqueIndex(['active_item_id'], 'uq_loans_one_active_per_item');
    }
}
