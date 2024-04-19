<?php

namespace Professionator\Models;


class Tool
{

    private int $itemId;
    private string $name;
    private int $quantity;

    function __construct(int $itemId, string $name, int $quantity)
    {
        $this->itemId = $itemId;
        $this->name = $name;
        $this->quantity = $quantity;
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

}