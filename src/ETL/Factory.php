<?php

namespace Harvest\ETL;

use Harvest\Storage\Storage;
use Opis\JsonSchema\{
  Validator, ValidationResult, ValidationError, Schema
};

class Factory {

  private $harvestPlan;
  private $itemStorage;
  private $hashStorage;

  public function __construct($harvest_plan, Storage $item_storage, Storage $hash_storage) {
    if (self::validateHarvestPlan($harvest_plan)) {
      $this->harvestPlan = $harvest_plan;
    }
    $this->itemStorage = $item_storage;
    $this->hashStorage = $hash_storage;
  }

  public function get($type) {
    if ($type == "extract") {
      $class = $this->harvestPlan->source->type;
      return new $class($this->harvestPlan);
    }
    elseif ($type == "load") {
      $class = $this->harvestPlan->load->type;
      return  new $class($this->harvestPlan, $this->hashStorage, $this->itemStorage);
    }
    elseif($type == "transforms") {
      $transforms = [];
      if ($this->harvestPlan->transforms) {
        foreach ($this->harvestPlan->transforms as $info) {
          $config = NULL;

          if (is_object($info)) {
            $info = (array) $info;
            $class = array_keys($info)[0];
          }
          else {
            $class = $info;
          }
          $transforms[] = $this->getOne($class, $this->harvestPlan);
        }
      }

      return $transforms;
    }
  }

  private function getOne($class, $config = NULL) {
    if (!$config) {
      $config = $this->harvestPlan;
    }
    return new $class($config);
  }

  public static function validateHarvestPlan(object $harvest_plan) {
    $path_to_schema = __DIR__ . "/../../schema/schema.json";
    $json_schema = file_get_contents($path_to_schema);
    $schema = json_decode($json_schema);

    if ($schema == null) {
      throw new \Exception("the json-schema is invalid json.");
    }

    $data = $harvest_plan;
    $schema = Schema::fromJsonString($json_schema);
    $validator = new Validator();

    /** @var $result ValidationResult */
    $result = $validator->schemaValidation($data, $schema);

    if (!$result->isValid()) {
      /** @var $error ValidationError */
      $error = $result->getFirstError();
      throw new \Exception("Invalid harvest plan. " . implode("->", $error->dataPointer()) . " " . json_encode($error->keywordArgs()));
    }

    return TRUE;
  }

}
