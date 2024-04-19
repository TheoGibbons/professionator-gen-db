<?php

namespace Professionator\Models;


use Professionator\Utils;

class Spell
{

    private string $spellId;
    private string $html;

    function __construct(string $spellId)
    {
        $this->spellId = $spellId;

        $this->html = Utils::getFileContents($this->getUrl());
    }

    public function getUrl(): string
    {
        return "https://www.wowhead.com/classic/spell=$this->spellId";
    }

    public function getSpellId(): string
    {
        return $this->spellId;
    }

    public function getHtml(): string
    {
        return $this->html;
    }

    public function getTools(): array
    {
        // EG: view-source:https://www.wowhead.com/classic/spell=3926/copper-modulator

        // Split the html on '<h2 class="heading-size-3">Tools</h2><table class="icon-list">'
        $tools = explode('<h2 class="heading-size-3">Tools</h2><table class="icon-list">', $this->html);

        // Check that there are two parts
        if (count($tools) !== 2) {
//            throw new \Exception('Could not find tools for spell ' . $this->getUrl());
            return [];
        }

        // Split the second part on '</table>'
        $tools = explode('</table>', $tools[1]);

        // Get the first element
        $tools = $tools[0];

        // Surround the tools with a table tag
        $tools = '<table>' . $tools . '</table>';

        // Now we can use the DOMDocument to parse the table
        $dom = new \DOMDocument();
        $dom->loadHTML($tools);

        // Foreach <tr> tag
        $tools = [];
        foreach ($dom->getElementsByTagName('tr') as $tr) {

            // Get the <a> tag
            /** @var \DOMElement $a */
            $a = $tr->getElementsByTagName('a')[0];

            $href = $a->getAttribute('href');       // EG "/classic/item=5956/blacksmith-hammer"
            $id = explode('=', $href)[1];           // EG "5956/blacksmith-hammer"
            $id = explode('/', $id)[0];             // EG "5956"

            // Get quantity from the textContent of this element <span class="icon-list-quantity">1</span>
            $quantity = 0;
            foreach ($tr->getElementsByTagName('span') as $span) {
                if ($span->getAttribute('class') == 'icon-list-quantity') {
                    $quantity = $span->textContent;
                }
            }

            if ($quantity != 1) {
                throw new \Exception("How isn't there quantity=1 for this tool (This never happens): " . $this->getUrl());
            }

            $tools[] = new Tool((int)$id, $a->textContent, (int)$quantity);
        }

        return $tools;

    }

}