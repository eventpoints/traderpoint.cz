<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Partner;
use App\Entity\Store;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Seeds a couple of partners and stores with unique store codes for testing.
 *
 * Partners (slug):
 *  - baumax
 *  - obi
 *
 * Stores (code):
 *  - PRG-BMX1, BRN-BMX1
 *  - PRG-OBI1, OVA-OBI1
 */
final class PartnerStoreFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $em): void
    {
        // Partner 1: BauMax
        $baumax = new Partner('BauMax', 'baumax', 15);
        $em->persist($baumax);

        $em->persist(new Store($baumax, 'BauMax Praha — Černý Most', 'baumax-prague-cerny-most', 'PRG-BMX1'));
        $em->persist(new Store($baumax, 'BauMax Brno', 'baumax-brno', 'BRN-BMX1'));

        // Partner 2: OBI
        $obi = new Partner('OBI', 'obi', 15);
        $em->persist($obi);

        $em->persist(new Store($obi, 'OBI Praha — Zličín', 'obi-prague-zlicin', 'PRG-OBI1'));
        $em->persist(new Store($obi, 'OBI Ostrava — Avion', 'obi-ostrava-avion', 'OVA-OBI1'));

        $em->flush();
    }

    /**
     * Optional: run with --group=dev-data so you don’t nuke other fixtures
     */
    public static function getGroups(): array
    {
        return ['dev-data'];
    }
}
