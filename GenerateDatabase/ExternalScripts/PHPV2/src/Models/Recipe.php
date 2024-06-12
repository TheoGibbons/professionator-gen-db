<?php

namespace Professionator\Models;

use Professionator\Utils;

class Recipe
{

    private array $data;

    function __construct($data)
    {
        $this->data = $data;
    }

    public function getId()
    {
        return $this->data['id'];
    }

    public function getName()
    {
        return $this->data['name'];
    }

    public function toLua()
    {

        $taughtByAlliance = $this->getTaughtBy('alliance');
        $taughtByHorde = $this->getTaughtBy('horde');

//        if ($this->getId() == 13659) {
//            dj(
//                $this->getId(),
//                $taughtByAlliance->getSourceString('short'),
//                $taughtByAlliance->getSourceString('medium'),
//                $taughtByAlliance->getSourceString('long'),
//                $taughtByAlliance->getRawNpcs(),
//                $taughtByAlliance->getRawFormulas(),
//            );
//        }

        $html = "[" . l($this->getId()) . "] = {\n";
        $html .= "  id = " . l($this->getId()) . ",\n";
        $html .= "  name = " . l($this->getName()) . ",\n";

        $html .= "  recipe_source_alliance_short = " . l($taughtByAlliance->getSourceString(TaughtBy::TAUGHT_BY_TYPE_SHORT)) . ",\n";
        $html .= "  recipe_source_alliance_medium = " . l($taughtByAlliance->getSourceString(TaughtBy::TAUGHT_BY_TYPE_MEDIUM)) . ",\n";
        $html .= "  recipe_source_alliance_long = " . l($taughtByAlliance->getSourceString(TaughtBy::TAUGHT_BY_TYPE_LONG)) . ",\n";

        $html .= "  recipe_source_horde_short = " . l($taughtByHorde->getSourceString(TaughtBy::TAUGHT_BY_TYPE_SHORT)) . ",\n";
        $html .= "  recipe_source_horde_medium = " . l($taughtByHorde->getSourceString(TaughtBy::TAUGHT_BY_TYPE_MEDIUM)) . ",\n";
        $html .= "  recipe_source_horde_long = " . l($taughtByHorde->getSourceString(TaughtBy::TAUGHT_BY_TYPE_LONG)) . ",\n";

        $html .= "  recipe_item_id_alliance = " . l($taughtByAlliance->getFormulaId()) . ",\n";
        $html .= "  recipe_item_id_horde = " . l($taughtByHorde->getFormulaId()) . ",\n";
        $html .= "  recipe_item_id_bop = " . l($taughtByHorde->isFormulaBOP()) . ",\n";

        $html .= "  training_cost = " . l($this->getTrainingCost()) . ",\n";

        $html .= "  cast_time = " . l($this->getCastTime()) . ",\n";

        $html .= "  learnedat = " . l($this->getLearnedAt()) . ",\n";
        $html .= "  red = " . l($this->getColour('red')) . ",\n";
        $html .= "  yellow = " . l($this->getColour('yellow')) . ",\n";
        $html .= "  green = " . l($this->getColour('green')) . ",\n";
        $html .= "  grey = " . l($this->getColour('grey')) . ",\n";

        $html .= "  reagents = " . $this->transformReagentsToLua($this->reagents()) . "\n";

        $html .= "  tools = " . $this->transformToolsToLua($this->tools()) . "\n";

        $html .= "},\n";

        return $html;
    }

    public function getTrainingCost()
    {
        return $this->data['trainingcost'] ?? null;
    }

    public function getCastTime(): string
    {
        $url = "https://www.wowhead.com/classic/spell={$this->getId()}";

        $contents = Utils::getFileContents($url);

        // view-source:https://www.wowhead.com/classic/spell=13659/enchant-shield-spirit
        if (preg_match('/([0-9]+(\.[0-9]+)?) sec cast/', $contents, $matches)) {
            return $matches[1];
        }

        // view-source:https://www.wowhead.com/classic/spell=19825/master-engineers-goggles
        if (preg_match('/([0-9]+(\.[0-9]+)?) min cast/', $contents, $matches)) {
            return $matches[1] * 60;
        }

        throw new \Exception("Cast time not found for spell {$this->getId()} - " . $url);
    }

    private function transformReagentsToLua(array $reagents): string
    {
        $html = "{\n";
        /** @var Reagent $reagent */
        foreach ($reagents as $reagent) {
            $html .= "    [" . l($reagent->getId()) . "] = {\n";
            $html .= "      name = " . l($reagent->getName()) . ",\n";
            $html .= "      quantity = " . l($reagent->getQuantity()) . ",\n";
            $html .= "    },\n";
        }
        $html .= "  },\n";

        return $html;
    }

    private function getLearnedAt()
    {
        return $this->data['learnedat'] ?? null;
    }

    private function getColour(string $string)
    {
        $colours = $this->data['colors'] ?? null;

        if (!$colours) {
            // EG https://www.wowhead.com/classic/spell=13240/the-mortar-reloaded
            return null;
        }

        return match ($string) {
            'red' => $colours[0],
            'yellow' => $colours[1],
            'green' => $colours[2],
            'grey' => $colours[3],
            default => throw new \Exception("Color not found for spell {$this->getId()}"),
        };

    }

    private function reagents(): array
    {
        return array_map(fn($reagent) => new Reagent($reagent), $this->data['reagents'] ?? []);
    }

    private function getTaughtBy(string $side): TaughtBy
    {
        return new TaughtBy($this->getId(), $side);
    }

    private function tools(): array
    {
        $spell = new Spell($this->getId());

        return $spell->getTools();
    }

    private function transformToolsToLua(array $tools)
    {
        $html = "{\n";
        /** @var Tool $tool */
        foreach ($tools as $tool) {
            $html .= "    [" . l($tool->getItemId()) . "] = {\n";
            $html .= "      name = " . l($tool->getName()) . ",\n";
            $html .= "      quantity = " . l($tool->getQuantity()) . ",\n";
            $html .= "    },\n";
        }
        $html .= "  },\n";

        return $html;
    }

}