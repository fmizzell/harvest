<?php

namespace Harvest\ETL\Load;

use Harvest\Harvester;
use Harvest\Log\MakeItLog;
use Harvest\Storage\Storage;
use Harvest\Util;

abstract class Load {

  protected $harvestPlan;
  protected $hashStorage;
  protected $itemStorage;

  abstract protected function saveItem($item);

  function __construct($harvest_plan, Storage $hash_storage, Storage $item_storage) {
    $this->harvestPlan = $harvest_plan;
    $this->hashStorage = $hash_storage;
    $this->itemStorage = $item_storage;
  }

  public function run($item) {

    $state = $this->itemState($item);

    if ($state == Harvester::HARVEST_LOAD_NEW_ITEM || $state == Harvester::HARVEST_LOAD_UPDATED_ITEM) {

      $this->saveItem($item);

      $identifier = $item->identifier;

      $hash = Util::generateHash($item);
      $object = (object) ['harvest_plan_id' => $this->harvestPlan->identifier, "hash" => $hash];
      $this->hashStorage->store(json_encode($object), $identifier);
    }

    return $state;
  }

  private function itemState($item) {
    if (isset($item->identifier)) {
      $identifier = $item->identifier;

      $json = $this->hashStorage->retrieve($identifier);

      $hash = NULL;
      if (isset($json)) {
        $data = json_decode($json);
        $hash = $data->hash;
      }

      if (isset($hash)) {
        $new_hash = Util::generateHash($item);
        if ($hash == $new_hash) {
          return Harvester::HARVEST_LOAD_UNCHANGED;
        }
        else {
          return Harvester::HARVEST_LOAD_UPDATED_ITEM;
        }
      }
      else {
        return Harvester::HARVEST_LOAD_NEW_ITEM;
      }
    }
    else {
      throw new \Exception("Item does not have an identifier " . json_encode($item));
    }

  }

}