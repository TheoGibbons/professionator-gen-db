<?php

namespace Professionator\Models;


use Professionator\Utils;

class TaughtBy
{

    const TAUGHT_BY_TYPE_SHORT = 'short';
    const TAUGHT_BY_TYPE_MEDIUM = 'medium';
    const TAUGHT_BY_TYPE_LONG = 'long';

    private Spell $spell;
    private string $side;
    private array $npcs;
    private array $formulas;        // Use an array here even thought there's only ever one

    function __construct(string $spellId, string $side)
    {

        $this->spell = new Spell($spellId);

        $wowHeadHtml = $this->spell->getHtml();

        // npc json EG: view-source:https://www.wowhead.com/classic/spell=13659/enchant-shield-spirit
        $npcJson = [];
        if (preg_match('/\n' . preg_quote("new Listview({template: 'npc', id: 'taught-by-npc'", "/") . '.*?, data: (.*?)}\);\n/', $wowHeadHtml, $matches)) {
            if ($temp = $matches[1]) {
                $npcJson = json_decode($temp, true);

                // filter based on side
                $npcJson = array_filter($npcJson, fn($npc) => ($side === 'alliance' && $npc['react'][0] === 1) || ($side === 'horde' && $npc['react'][1] === 1));
            }
        }

        // item json EG: view-source:https://www.wowhead.com/classic/spell=13655/enchant-weapon-lesser-elemental-slayer
        $itemJson = [];
        if (preg_match('/\n' . preg_quote("new Listview({template: 'item', id: 'taught-by-item'", "/") . '.*?, data: (.*?)}\);\n/', $wowHeadHtml, $matches)) {
            if ($temp = $matches[1]) {
                $itemJson = json_decode($temp, true);
            }
        }

//        if (!$npcJson && !$itemJson) {
//            throw new \Exception('Could not find source of this recipe ' . $this->getId() . '. ' . $url);
//        }

        $this->npcs = $npcJson;
        $this->formulas = $itemJson;
        $this->side = $side;
    }

    public function getSourceString(string $type): string
    {

        if ($this->npcs) {

            if ($type === self::TAUGHT_BY_TYPE_SHORT) {

                if (count($this->npcs) === 1) {
                    return implode("\n", array_map(fn($trainer) => $this->getSourceStringNpc($trainer, $type), $this->npcs));
                } else if (count($this->npcs) > 1) {
                    return 'Trainers';
                }

            } elseif ($type === self::TAUGHT_BY_TYPE_MEDIUM) {

                // 'Annora(U) Kitta Firewind(EF) - Gnomeregon';

                if (count($this->npcs) <= 2) {
                    return implode("\n", array_map(fn($trainer) => $this->getSourceStringNpc($trainer, $type), $this->npcs));
                } else {
                    return 'Trainers';
                }

            } elseif ($type === self::TAUGHT_BY_TYPE_LONG) {

                // 'Annora(Uldaman) Kitta Firewind(Elwynn Forest)';

                if (count($this->npcs) <= 4) {
                    return implode("\n", array_map(fn($trainer) => $this->getSourceStringNpc($trainer, $type), $this->npcs));
                } else {
                    return 'Trainers';
                }

            }

        }

        if ($this->formulas) {

            if (count($this->formulas) > 1) {
                // This is rare, so return 'Multiple Formulas'
                // EG: https://www.wowhead.com/classic/spell=8895/goblin-rocket-boots#taught-by-item
                // This just simplifies all the below logic if we don't have to think about multiple formulas
                return 'Multiple Formulas';
            }

            $singleFormula = $this->formulas[0];
            $formulaItem = new Item($singleFormula['id']);

            if ($type === self::TAUGHT_BY_TYPE_SHORT) {

                return preg_replace('/^Formula: /', '', $formulaItem->getName());

            } elseif ($type === self::TAUGHT_BY_TYPE_MEDIUM) {

                $formulaLocations = $this->getFormulaLocations($formulaItem, $type);

                if (count($formulaLocations) < 3) {
                    return implode("\n", $formulaLocations);
                } else {

                    // EG "https://www.wowhead.com/classic/item=20734/formula-enchant-cloak-stealth"
                    // Drops from many mobs in "Ahn'Qiraj" and "Ruins of Ahn'Qiraj"
                    // Return "Ahn'Qiraj, Ruins of Ahn'Qiraj"
                    if ($zones = $this->getZonesFormulaAquiredIn($formulaItem, $type, 2)) {
                        return $zones;
                    }

                    return 'Formula multiple locations';
                }

            } elseif ($type === self::TAUGHT_BY_TYPE_LONG) {

                $formulaLocations = $this->getFormulaLocations($formulaItem, $type);

                if (count($formulaLocations) < 4) {
                    return implode("\n", $formulaLocations);
                } else {

                    // EG "https://www.wowhead.com/classic/item=20734/formula-enchant-cloak-stealth"
                    // Drops from many mobs in "Ahn'Qiraj" and "Ruins of Ahn'Qiraj"
                    // Return "Ahn'Qiraj, Ruins of Ahn'Qiraj"
                    if ($zones = $this->getZonesFormulaAquiredIn($formulaItem, $type, 4)) {
                        return $zones;
                    }

                    return 'Formula multiple locations';
                }

            }
        }

        // Unknown location/recipe
        return 'Unknown';
    }

    public function getFormulaId(): ?string
    {
        if ($this->formulas) {

            if (count($this->formulas) > 1) {
                // This is rare, so return null
                // EG: https://www.wowhead.com/classic/spell=8895/goblin-rocket-boots#taught-by-item
                return null;
            }

            $singleFormula = $this->formulas[0];
            $formulaItem = new Item($singleFormula['id']);

            return $formulaItem->getId();
        }

        return null;
    }

    public function isFormulaBOP(): ?bool
    {
        if ($this->formulas) {

            if (count($this->formulas) > 1) {
                // This is rare, so return null
                // EG: https://www.wowhead.com/classic/spell=8895/goblin-rocket-boots#taught-by-item
                return null;
            }

            $singleFormula = $this->formulas[0];
            $formulaItem = new Item($singleFormula['id']);

            return $formulaItem->isBOP();
        }

        return null;
    }

    public function getRawNpcs()
    {
        return $this->npcs;
    }

    public function getRawFormulas()
    {
        return $this->formulas;
    }

    private function getSourceStringNpc(array $trainer, string $type): string
    {

        // EG "Annora"
        $return = $trainer['name'];

        if ($type === self::TAUGHT_BY_TYPE_MEDIUM || $type === self::TAUGHT_BY_TYPE_LONG) {
            // Add tag if it exists
            // EG "Master Enchanter"
            if ($trainer['tag'] ?? null) {
                $return .= ' <' . $trainer['tag'] . '>';
            }
        }

        // Add location names if they exist (we need to get the names as we only have the ids)
        // EG "Uldaman"
        if (
            !is_null($trainer['location'] ?? null) &&   // EG: https://www.wowhead.com/classic/npc=625/undead-dynamiter#drops;50
            count($trainer['location'])
        ) {
            if ($locationNames = array_filter(array_map(fn($location) => Utils::getLocationNameFromId($location), $trainer['location']))) {

                if ($type === self::TAUGHT_BY_TYPE_SHORT) {
                    $locationNames = count($locationNames) > 2 ? ['multiple locations'] : array_map('\Professionator\Utils::abbreviate', $locationNames);
                    $return .= ' (' . implode(', ', $locationNames) . ')';
                } else if ($type === self::TAUGHT_BY_TYPE_MEDIUM) {
                    $locationNames = count($locationNames) > 2 ? ['multiple locations'] : array_map('\Professionator\Utils::abbreviate', $locationNames);
                    $return .= ' (' . implode(', ', $locationNames) . ')';
                } else if ($type === self::TAUGHT_BY_TYPE_LONG) {
                    $locationNames = count($locationNames) > 3 ? ['multiple locations'] : $locationNames;
                    $return .= ' (' . implode(', ', $locationNames) . ')';
                }

            }
        }

        return $return;
    }

    private function getTrainerZones(array $trainer, string $type): array
    {

        if (
            !is_null($trainer['location'] ?? null) &&   // EG: https://www.wowhead.com/classic/npc=625/undead-dynamiter#drops;50
            count($trainer['location'])
        ) {
            return array_unique(array_filter(array_map(fn($location) => Utils::getLocationNameFromId($location), $trainer['location'])));
        }

        return [];
    }

    private function getFormulaLocations(Item $formulaItem, string $type): array
    {

        // Formula sold by npc/s (may be different for horde and alliance)
        // EG: https://www.wowhead.com/classic/item=16217/formula-enchant-shield-greater-stamina
        $soldByNpcs = $formulaItem->getNpcsThatSellThisItem();

        // Formula dropped by mob/s (may be different for horde and alliance)
        // EG: https://www.wowhead.com/classic/item=16220/formula-enchant-boots-spirit
        $mobDrops = $formulaItem->getMobsThatDropThisItem();

        $containedIn = $formulaItem->getContainedInItem();
        $containedInObject = $formulaItem->getContainedInObject();
        $pickPocketedFrom = $formulaItem->getPickPocketedFrom();

        $formulaLocations = [];

        if (count($soldByNpcs)) {
            $formulaLocations = array_merge($formulaLocations, array_map(fn($trainer) => 'Sold by: ' . $this->getSourceStringNpc($trainer, $type), $soldByNpcs));
        }

        if (count($mobDrops)) {
            $formulaLocations = array_merge($formulaLocations, array_map(fn($trainer) => 'Dropped by: ' . $this->getSourceStringNpc($trainer, $type), $mobDrops));
        }

        if (count($containedIn)) {
            $formulaLocations = array_merge($formulaLocations, array_map(fn($trainer) => 'Contained in: ' . $this->getSourceStringNpc($trainer, $type), $containedIn));
        }

        if (count($containedInObject)) {
            $formulaLocations = array_merge($formulaLocations, array_map(fn($trainer) => 'Contained in object: ' . $this->getSourceStringNpc($trainer, $type), $containedInObject));
        }

        if (count($pickPocketedFrom)) {
            $formulaLocations = array_merge($formulaLocations, array_map(fn($trainer) => 'Pick pocketed from: ' . $this->getSourceStringNpc($trainer, $type), $pickPocketedFrom));
        }

        return $formulaLocations;
    }

    /**
     * Returns an array of zones that the formula is acquired in
     * EG: https://www.wowhead.com/classic/item=20734/formula-enchant-cloak-stealth
     * is acquired from many different mobs all located in "Ahn'Qiraj" and "Ruins of Ahn'Qiraj"
     */
    private function getZonesFormulaAquiredIn(Item $formulaItem, string $type, int $maxCount)
    {
        // Formula sold by npc/s (may be different for horde and alliance)
        // EG: https://www.wowhead.com/classic/item=16217/formula-enchant-shield-greater-stamina
        $soldByNpcs = $formulaItem->getNpcsThatSellThisItem();

        // Formula dropped by mob/s (may be different for horde and alliance)
        // EG: https://www.wowhead.com/classic/item=16220/formula-enchant-boots-spirit
        $mobDrops = $formulaItem->getMobsThatDropThisItem();

        $containedIn = $formulaItem->getContainedInItem();
        $containedInObject = $formulaItem->getContainedInObject();
        $pickPocketedFrom = $formulaItem->getPickPocketedFrom();

        $formulaLocations = [];
        $prefixes = [];

        if (count($soldByNpcs)) {
            $formulaLocations = array_merge($formulaLocations, $this->getZoneNames($mobDrops, $type));
            $prefixes[] = "Sold in: ";
        }

        if (count($mobDrops)) {
            $formulaLocations = array_merge($formulaLocations, $this->getZoneNames($mobDrops, $type));
            $prefixes[] = "Dropped in: ";
        }

        if (count($containedIn)) {
            $formulaLocations = array_merge($formulaLocations, $this->getZoneNames($mobDrops, $type));
            $prefixes[] = "Found in: ";
        }

        if (count($containedInObject)) {
            $formulaLocations = array_merge($formulaLocations, $this->getZoneNames($mobDrops, $type));
            $prefixes[] = "Found in: ";
        }

        if (count($pickPocketedFrom)) {
            $formulaLocations = array_merge($formulaLocations, $this->getZoneNames($mobDrops, $type));
            $prefixes[] = "Pick pocketed in: ";
        }

        // If it can only be in one way then we can prefix it with that
        if (count($prefixes) === 1) {
            $prefix = $prefixes[0];
        } else {
            $prefix = '';
        }

        // array unique
        $formulaLocations = array_unique($formulaLocations);

        // If we have less than the max count then we can return the locations
        if (count($formulaLocations) <= $maxCount) {
            return $prefix . implode(', ', Utils::surroundValuesWith($formulaLocations, '"'));
        }

        return null;
    }

    private function getZoneNames(array $mobDrops, string $type): array
    {
        $zoneNames = [];

        foreach ($mobDrops as $trainer) {
            foreach ($this->getTrainerZones($trainer, $type) as $zone) {
                $zoneNames[] = $zone;
            }
        }

        return $zoneNames;
    }

}