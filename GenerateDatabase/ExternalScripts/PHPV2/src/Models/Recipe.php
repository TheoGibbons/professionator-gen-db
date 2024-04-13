<?php

namespace Professionator\Models;

use Professionator\RecipeTaughtByTrait;
use Professionator\Utils;

class Recipe
{
    use RecipeTaughtByTrait;

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

        $html = "[" . l($this->getId()) . "] = {\n";
        $html .= "  name = " . l($this->getName()) . ",\n";

        $html .= "  recipe_source_alliance_short = " . l($this->getSourceString('alliance', 'short')) . ",\n";
        $html .= "  recipe_source_alliance_medium = " . l($this->getSourceString('alliance', 'medium')) . ",\n";
        $html .= "  recipe_source_alliance_long = " . l($this->getSourceString('alliance', 'long')) . ",\n";

        $html .= "  recipe_source_horde_short = " . l($this->getSourceString('horde', 'short')) . ",\n";
        $html .= "  recipe_source_horde_medium = " . l($this->getSourceString('horde', 'medium')) . ",\n";
        $html .= "  recipe_source_horde_long = " . l($this->getSourceString('horde', 'long')) . ",\n";

        $html .= "  recipe_item_id_horde = " . l($this->getFormulaItemId('horde')) . ",\n";
        $html .= "  recipe_item_id_alliance = " . l($this->getFormulaItemId('alliance')) . ",\n";
        $html .= "  training_cost = " . l($this->getTrainingCost()) . ",\n";

        $html .= "  cast_time = " . l($this->getCastTime()) . ",\n";
        $html .= "  grey = " . l($this->getColour('grey')) . ",\n";
        $html .= "  yellow = " . l($this->getColour('yellow')) . ",\n";

        $html .= "  reagents = " . $this->transformReagentsToHtml($this->reagents()) . "\n";

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

    private function transformReagentsToHtml(array $reagents): string
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
        // TODO
        return [];
    }

}