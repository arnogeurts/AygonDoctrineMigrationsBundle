<?php
/*
 * This file is part of the DoctrineMigrationsBundle package.
 *
 * (c) Arno Geurts
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Aygon\DoctrineMigrationsBundle\Migrations;

use Doctrine\DBAL\Migrations\Version;

/**
 * @author      Arno Geurts
 */
class NamedVersion extends Version
{       
    public function markMigrated()
    {
        $this->getConfiguration()->createMigrationTable();
        $this->getConfiguration()->getConnection()->executeQuery("INSERT INTO " . $this->getConfiguration()->getMigrationsTableName() . " (version, name) VALUES (?, ?)", array($this->getVersion(), $this->getConfiguration()->getName()));
    }

    public function markNotMigrated()
    {
        $this->getConfiguration()->createMigrationTable();
        $this->getConfiguration()->getConnection()->executeQuery("DELETE FROM " . $this->getConfiguration()->getMigrationsTableName() . " WHERE version = ? AND name = ?", array($this->getVersion(), $this->getConfiguration()->getName()));
    }
}
