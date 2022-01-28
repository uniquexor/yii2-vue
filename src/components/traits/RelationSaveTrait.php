<?php
    namespace unique\yii2vue\traits;

    use unique\yii2vue\exceptions\AbortSavingException;
    use yii\base\Model;
    use yii\db\ActiveRecord;

    trait RelationSaveTrait {

        /**
         * Saves a model's relational data. Does the following:
         * 1. Iterate through $relation_model_data
         * 2. Check if data primary ID is found in $existing_models, if so use it, else create a new model.
         * 3. Call setAttributes() on a new/existing model with the data taken from $relation_model_data
         * 4. Assign $foreign_key for a new/existing model
         * 5. Try saving, if it fails add a new attribute to $relation_model_data[]['errors'] with all save errors.
         * 6. If all models saved correctly, delete expired $existing_models models and return
         *    Else, throw AbortSavingException with the modified $relation_model_data.
         *
         * Usage Example:
         * Imagine You have a model Part and PartAttribute. PartAttribute has a foreign key called `part_id` to the Part model.
         * Then we could have something similar in Part class:
         * ```php
         * class PartForm extends Part {
         *
         *     use TransactionalSaveTrait;
         *
         *     public $part_attributes_data;
         *
         *     public function afterSave( $insert, $changedAttributes ) {
         *
         *         // Load existing part's attributes and group them by ID:
         *         $existing_models = PartAttributes::find()->where( [ 'part_id' => $this->id ] )->indexBy( 'id' )->all();
         *
         *         try {
         *
         *             $this->saveRelation( $existing_models, $this->part_attributes_data, PartAttribute::class, [ 'part_id' => 'id' ], 'partAttributes' );
         *         } catch ( AbortSavingException $exception ) {
         *
         *             $this->part_attributes_data = $exception->relation_model_data;
         *             throw $exception;
         *         }
         *
         *         parent::afterSave( $insert, $changedAttributes );
         *     }
         * }
         * ```
         *
         * When you set $error_field and any of the relational model fails to save, because of validation errors, then $this->addError() would be called
         * setting errors for attributes in the form: `[$error_field].[relation's id/key].[relation's field]`
         *
         * @param ActiveRecord[] $existing_models - An associated array of Models, using primary keys of related data that is currently in DB.
         * @param array $relation_model_data - An array of data, to be assigned to models.
         * @param string $relation_class - A class of relational model
         * @param string|array $foreign_key - Foreign keys in the relation model, i.e.: 'part_id' or [ 'part_id' => 'id' ]
         * @param string|null $error_field - If not null, this field will be used as a base to set errors on the model.
         * @throws AbortSavingException
         * @throws \Throwable
         * @throws \yii\db\StaleObjectException
         */
        public function saveRelation( $existing_models, $relation_model_data, $relation_class, $foreign_key, ?string $error_field ) {

            $has_errors = false;
            if ( !is_array( $foreign_key ) ) {

                $foreign_key = [ $foreign_key => 'id' ];
            }

            foreach ( $relation_model_data ?? [] as $key => $model_data ) {

                /**
                 * @var ActiveRecord $model
                 */
                $model = new $relation_class();
                $primary_key = implode( ',',  $model->primaryKey() );

                if ( isset( $model_data[ $primary_key ] ) && isset( $existing_models[ $model_data[ $primary_key ] ] ) ) {

                    $model = $existing_models[ $model_data[ $primary_key ] ];
                }

                if ( $model->$primary_key ) {

                    unset( $existing_models[ $model_data[ $primary_key ] ] );
                } else {

                    $primary_keys = $this->getPrimaryKey( true );
                    foreach ( $foreign_key as $foreign_k => $primary_k ) {

                        $model->$foreign_k = $primary_keys[ $primary_k ];
                        unset( $model_data[ $foreign_k ] );
                    }
                }

                $model->setAttributes( $model_data );

                if ( !$model->save() ) {

                    if ( !( $relation_model_data[ $key ] instanceof Model ) ) {

                        $relation_model_data[ $key ][ 'errors' ] = $model->getErrors();
                    }

                    if ( $error_field !== null ) {

                        foreach ( $model->getErrors() as $attribute => $errors ) {

                            foreach ( $errors as $error ) {

                                $this->addError( $error_field . '.' . $key . '.' . $attribute, $error );
                            }
                        }
                    }
                    $has_errors = true;
                }
            }

            if ( $has_errors ) {

                AbortSavingException::fromRelationSave( $relation_model_data );
            }

            foreach ( $existing_models as $model ) {

                $model->delete();
            }
        }
    }