<?php

namespace explosivebit\arangodb;

use Yii;
use yii\db\ActiveQueryInterface;
use yii\db\ActiveQueryTrait;
use yii\db\ActiveRelationTrait;

use triagens\ArangoDb\Document;

class ActiveQuery extends Query implements ActiveQueryInterface
{
    use ActiveQueryTrait;
    use ActiveRelationTrait;

    public function __construct($modelClass, $config = [])
    {
        $this->modelClass = $modelClass;
        parent::__construct($config);
    }

    protected function buildQuery($query = null, $params = [])
    {
        if ($this->primaryModel !== null) {
            // lazy loading
            if ($this->via instanceof self) {
                // via pivot collection
                $viaModels = $this->via->findPivotRows([$this->primaryModel]);
                $this->filterByModels($viaModels);
            } elseif (is_array($this->via)) {
                // via relation
                /* @var $viaQuery ActiveQuery */
                list($viaName, $viaQuery) = $this->via;
                if ($viaQuery->multiple) {
                    $viaModels = $viaQuery->all();
                    $this->primaryModel->populateRelation($viaName, $viaModels);
                } else {
                    $model = $viaQuery->one();
                    $this->primaryModel->populateRelation($viaName, $model);
                    $viaModels = $model === null ? [] : [$model];
                }
                $this->filterByModels($viaModels);
            } else {
                $this->filterByModels([$this->primaryModel]);
            }
        }

        return parent::buildQuery($query, $params);
    }

    private function createModels($rows)
    {
        $models = [];
        if ($this->asArray) {
            array_walk(
                $rows,
                function (&$doc) {
                    if ($doc instanceof Document) {
                        $doc = $doc->getAll();
                    }
                }
            );
            if ($this->indexBy === null) {
                return $rows;
            }
            foreach ($rows as $row) {
                if (is_string($this->indexBy)) {
                    $key = $row[$this->indexBy];
                } else {
                    $key = call_user_func($this->indexBy, $row);
                }
                $models[$key] = $row;
            }
        } else {
            /* @var $class ActiveRecord */
            $class = $this->modelClass;
            if ($this->indexBy === null) {
                foreach ($rows as $row) {
                    $model = $class::instantiate($row);
                    $class::populateRecord($model, $row);
                    $model->setIsNewRecord(false);
                    $models[] = $model;
                }
            } else {
                foreach ($rows as $row) {
                    $model = $class::instantiate($row);
                    $class::populateRecord($model, $row);
                    $model->setIsNewRecord(false);
                    if (is_string($this->indexBy)) {
                        $key = $model->{$this->indexBy};
                    } else {
                        $key = call_user_func($this->indexBy, $model);
                    }
                    $models[$key] = $model;
                }
            }
        }

        return $models;
    }

    public function all($db = null)
    {
        $statement = $this->createCommand($db);
        $token = $this->getRawAql($statement);
        Yii::info($token, 'explosivebit\arangodb\Query::query');
        try {
            Yii::beginProfile($token, 'explosivebit\arangodb\Query::query');
            $cursor = $statement->execute();
            $rows = $cursor->getAll();
            Yii::endProfile($token, 'explosivebit\arangodb\Query::query');
        } catch (\Exception $ex) {
            Yii::endProfile($token, 'explosivebit\arangodb\Query::query');
            throw new \Exception($ex->getMessage(), (int) $ex->getCode(), $ex);
        }
        if (!empty($rows)) {
            $models = $this->createModels($rows);
            if (!empty($this->with)) {
                $this->findWith($this->with, $models);
            }
            if (!$this->asArray) {
                foreach ($models as $model) {
                    $model->afterFind();
                }
            }

            return $models;
        } else {
            return [];
        }
    }

    public function one($db = null)
    {
        $row = parent::one($db);
        if ($row !== false) {
            if ($this->asArray) {
                $model = $row;
            } else {
                /* @var $class ActiveRecord */
                $class = $this->modelClass;
                $model = $class::instantiate($row);
                $class::populateRecord($model, $row);
                $model->setIsNewRecord(false);
            }
            if (!empty($this->with)) {
                $models = [$model];
                $this->findWith($this->with, $models);
                $model = $models[0];
            }
            if (!$this->asArray) {
                $model->afterFind();
            }

            return $model;
        } else {
            return null;
        }
    }
}
