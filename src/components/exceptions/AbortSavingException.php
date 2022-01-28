<?php
    namespace unique\yii2vue\exceptions;

    class AbortSavingException extends \Exception {

        public $relation_model_data;

        public static function fromRelationSave( $relation_model_data ) {

            $exception = new self();
            $exception->relation_model_data = $relation_model_data;

            throw $exception;
        }
    }