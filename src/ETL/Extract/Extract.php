<?php

namespace Harvest\ETL\Extract;

use Harvest\Log\MakeItLog;
use Harvest\Storage\Storage;

abstract class Extract implements IExtract {

  /**
   * {@inheritDoc}
   */
  public function run(): array
  {
    $items = $this->getItems();

    if (empty($items)) {
      throw new \Exception("No Items were extracted.");
    }

    $copy = array_values($items);
    if (!is_object($copy[0])) {
      throw new \Exception("The items extracted are not php objects: {json_encode($copy[0])}");
    }

    return $items;
  }

  abstract protected function getItems();
}
