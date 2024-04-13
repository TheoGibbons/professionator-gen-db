<?php

namespace Professionator;

trait ItemWowHeadTrait
{

    protected ?\SimpleXMLElement $whData;

    public function initWowheadTrait(string $version, int $id): void
    {
        $this->whData = $this->getWhData($version, $id);
    }

    protected function getWhData(string $version, int $id): ?\SimpleXMLElement
    {
        if ($whData = $this->getDataFromCache($version, $id)) {
            return $whData;
        }

        return $this->refreshCache($version, $id);
    }

    private function getDataFromCache(string $version, int $id): ?\SimpleXMLElement
    {
        $filePath = self::getCacheFilePath($version, $id);

        if (is_file($filePath)) {
            if ($contents = file_get_contents($filePath)) {
                if ($xml = simplexml_load_string($contents)) {
                    return $xml;
                }
            }
        }

        return null;
    }

    private function getCacheFilePath(string $version, string $itemId): string
    {
        $dir = Files::cache("/scraps/wh/$version/item/");
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        return $dir . $itemId . '.xml';
    }

    private function refreshCache(string $version, int $id): ?\SimpleXMLElement
    {
        $url = Env::get('VERSIONS')[$version]['wowhead'] . "item=$id&xml";

        $xml = file_get_contents($url);
        GlobalRequestCounter::incrementWowheadCounter();

        if ($this->wowheadItemDoesNotExist($xml)) {

            echo "Item ($version:$id) doesn't exist ";

            return null;
        }

//        echo $version . "|  url={$url} |   " . ($isError ? 'null' : $this->getIdentifierFromXml($xml)) . "\n";

        echo "" . $this->getIdentifierFromXml($xml) . " ";

        // save to file cache
        file_put_contents($this->getCacheFilePath($version, $id), $xml);

        return simplexml_load_string($xml);
    }

    /**
     * XML <wowhead><error>Item not found!</error></wowhead> is returned when a item doesn't exist
     */
    private function wowheadItemDoesNotExist(string $xml): bool
    {
        $xml = simplexml_load_string($xml);
        return (string)$xml->error === 'Item not found!';
    }

    public function getWhJson(): ?array
    {
        return isset($this->whData->item[0]->json) ? @json_decode("{{$this->whData->item[0]->json}}", true) : null;
    }

    protected function getWhJsonEquip(): ?array
    {
        return isset($this->whData->item[0]->jsonEquip) ? @json_decode("{{$this->whData->item[0]->jsonEquip}}", true) : null;
    }

    /**
     * XML Looks like <wowhead><item id="211423"><name><![CDATA[ Void-Touched Leather Gloves ]]></name><level>30</level><quality id="4">Epic</quality>...
     * This function should return "Void-Touched Leather Gloves (211423)"
     */
    private function getIdentifierFromXml(string $xml): string
    {
        $xml = simplexml_load_string($xml);
        return trim($xml->item->name . ' (' . $xml->item['id'] . ')');

    }


}