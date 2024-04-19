<?php

namespace Professionator\Models;


use Professionator\Utils;

class Item
{

    private string $wowheadHtml;
    private string $itemId;

    function __construct(string $itemId)
    {
        $this->itemId = $itemId;

        $this->wowheadHtml = Utils::getFileContents($this->getUrl());

    }

    public function getUrl(): string
    {
        return "https://www.wowhead.com/classic/item=$this->itemId";
    }

    public function getNpcsThatSellThisItem()
    {
        // EG: view-source:https://www.wowhead.com/classic/item=16217/formula-enchant-shield-greater-stamina

        return $this->getListViewData("id: 'sold-by',");
    }

    public function getMobsThatDropThisItem(): array
    {
        // EG: view-source:https://www.wowhead.com/classic/item=16220/formula-enchant-boots-spirit

        return $this->getListViewData("id: 'dropped-by',");

    }

    public function getContainedInItem(): array
    {
        // EG view-source:https://www.wowhead.com/classic/item=16220/formula-enchant-boots-spirit
        return $this->getListViewData("id: 'contained-in-item',");
    }

    public function getContainedInObject(): array
    {
        // EG view-source:https://www.wowhead.com/classic/item=16220/formula-enchant-boots-spirit
        return $this->getListViewData("id: 'contained-in-object',");
    }

    public function getPickPocketedFrom(): array
    {
        // EG view-source:https://www.wowhead.com/classic/item=16220/formula-enchant-boots-spirit
        return $this->getListViewData("id: 'pick-pocketed-from'");
    }

    private function getListViewData(string $needle): array
    {

        // Explode on "new Listview({"
        $temp = explode("new Listview({", $this->wowheadHtml);

        // Filter parts that don't contain "id: 'dropped-by',"
        $temp = array_filter($temp, fn($part) => str_contains($part, $needle));

        if (count($temp) === 0) {
            return [];
        } else if (count($temp) > 1) {
            throw new \Exception("Unexpected number of parts found: " . $this->getUrl() . ' ' . $needle);
        }

        // Split based on newlines
        $temp = explode("\n", array_values($temp)[0]);

        // only keep this line that starts with "    data: "
        $temp = array_filter($temp, fn($line) => str_starts_with(trim($line), "data: "));

        if (count($temp) !== 1) {
            throw new \Exception("Unexpected number of lines found: " . $this->getUrl() . ' ' . $needle);
        }

        $temp = trim(array_values($temp)[0]);

        // Strip the "data: " from the start of the line
        $temp = substr($temp, 6);

        // right trim ","
        $temp = rtrim($temp, ",");

        if (is_null(json_decode($temp, true))) {
            throw new \Exception("Could not decode json: " . $this->getUrl() . ' ' . $needle);
        }

        // decode the json
        return json_decode($temp, true);
    }

    public function getName(): string
    {
        if (preg_match('/<title>(.*?) - Item - .*World of Warcraft<\/title>/', $this->wowheadHtml, $matches)) {
            return $matches[1];
        }

        throw new \Exception("Name not found for item: " . $this->getUrl());
    }

    public function getId(): string
    {
        return $this->itemId;
    }

    public function isBOP(): bool
    {

        // Split based on newlines
        $temp = explode("\n", $this->wowheadHtml);

        // only keep this line that starts with "g_items[16217].tooltip_enus = "
        $temp = array_filter($temp, fn($line) => preg_match('/^g_items\\[[0-9]+]\\.tooltip_enus = /', trim($line)));

        if (count($temp) !== 1) {
            throw new \Exception("Unexpected number of lines found: " . $this->getUrl());
        }

        $temp = trim(array_values($temp)[0]);

        return str_contains($temp, 'Binds when picked up');
    }

}