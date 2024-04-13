<?php

namespace Professionator;

trait RecipeTaughtByTrait
{

    public function getSourceString(string $side, string $type)
    {
        $learnedFrom = $this->getLearnedFrom($side);

        if ($learnedFrom['npc']) {

            if ($type === 'short') {

                if (count($learnedFrom['npc']) === 1) {
                    return $learnedFrom['npc'][0]['name'];
                } else if (count($learnedFrom['npc']) > 1) {
                    return 'Trainers';
                }

            } elseif ($type === 'medium') {

                // 'Annora(U) Kitta Firewind(EF) - Gnomeregon';

                if (count($learnedFrom['npc']) > 2) {
                    return 'Trainers';
                } else {
                    $trainersArray = array_map(fn($trainer) => $trainer['name'] . '(' . $trainer['location_short'] . ')', $learnedFrom['npc']);
                    return implode(', ', $trainersArray);
                }

            } elseif ($type === 'long') {

                // 'Annora(Uldaman) Kitta Firewind(Elwynn Forest)';

                if (count($learnedFrom['npc']) > 4) {
                    return 'Trainers';
                } else {
                    return implode(', ', array_map(fn($trainer) => $trainer['name'] . '(' . $trainer['location_long'] . ')', $learnedFrom['npc']));
                }

            }

        }


        if ($learnedFrom['formula']) {

            if ($type === 'short') {

                return "Formula";

            } elseif ($type === 'medium') {

                $formulaLocations = array_map(fn($trainer) => $trainer['name'] . '(' . $trainer['location_short'] . ')', $learnedFrom['formula']->getFormulaLocations());

                if (count($formulaLocations) < 3) {
                    return implode(', ', $formulaLocations);
                } else {
                    return 'Formula multiple locations';
                }

            } elseif ($type === 'long') {

                $formulaLocations = array_map(fn($trainer) => $trainer['name'] . '(' . $trainer['location_short'] . ')', $learnedFrom['formula']->getFormulaLocations());

                if (count($learnedFrom['formula']) < 4) {
                    return implode(', ', $formulaLocations);
                } else {
                    return 'Formula multiple locations';
                }

            }
        }

        // Unknown location/recipe
        return 'Unknown';

    }

    public function getFormulaItemId($side)
    {
        $learnedFrom = $this->getLearnedFrom($side);

        if ($learnedFrom['formula']) {
            if (count($learnedFrom['formula']) === 1) {
                return $learnedFrom['formula'][0]['id'];
            }
        }
        return null;
    }

    private function getLearnedFrom(string $side): array
    {

        $taughtBy = $this->getTaughtByFromWowhead();

        if ($taughtBy['npc']) {
            // filter based on side
            $taughtBy['npc'] = array_filter($taughtBy['npc'], fn($npc) => ($side === 'alliance' && $npc['react'][0] === 1) || ($side === 'horde' && $npc['react'][1] === 1));
        }

        if ($taughtBy['formula']) {
            // filter based on side
            $taughtBy['formula'] = array_filter($taughtBy['formula'], fn($npc) => ($side === 'alliance' && $npc['react'][0] === 1) || ($side === 'horde' && $npc['react'][1] === 1));
        }

        return $taughtBy;

    }

    private function getTaughtByFromWowhead(): array
    {
        $url = "https://www.wowhead.com/classic/spell={$this->getId()}";

        $contents = Utils::getFileContents($url);

        // npc json EG: view-source:https://www.wowhead.com/classic/spell=13659/enchant-shield-spirit
        $npcJson = [];
        if (preg_match('/\n' . preg_quote("new Listview({template: 'npc', id: 'taught-by-npc'", "/") . '.*?, data: (.*?)}\);\n/', $contents, $matches)) {
            $npcJson = $matches[1];
            if ($npcJson) {
                $npcJson = json_decode($npcJson, true);
            }
        }

        // item json EG: view-source:https://www.wowhead.com/classic/spell=13655/enchant-weapon-lesser-elemental-slayer
        $itemJson = [];
        if (preg_match('/\n' . preg_quote("new Listview({template: 'npc', id: 'taught-by-item'", "/") . '.*?, data: (.*?)]}\);\n/', $contents, $matches)) {
            $itemJson = $matches[1];
            if ($itemJson) {
                $itemJson = json_decode($itemJson, true);
            }
        }

//        if (!$npcJson && !$itemJson) {
//            throw new \Exception('Could not find source of this recipe ' . $this->getId() . '. ' . $url);
//        }

        return [
            'npc'     => $npcJson,
            'formula' => $itemJson,
        ];
    }

}