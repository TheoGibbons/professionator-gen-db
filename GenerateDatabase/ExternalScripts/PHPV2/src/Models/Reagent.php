<?php

namespace Professionator\Models;

class Reagent
{

    private int $id;
    private int $quantity;

    /**
     * @param array $data EG: [4361,1] where 4361 is the reagent id and 1 is the quantity
     */
    function __construct(array $data)
    {
        $this->id = $data[0];
        $this->quantity = $data[1];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getName(): string
    {
        $item = new Item($this->id);
        return $item->getName();
    }

}