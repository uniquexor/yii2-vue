<?php
    namespace unique\yii2vue\commands;

    use yii\console\Controller;
    use yii\db\ActiveRecord;
    use yii\helpers\Console;
    use yii\helpers\Inflector;

    class VueAssetsController extends Controller {

        /**
         * @var string|null Path to where VUE asset's models are stored.
         */
        public ?string $vue_asset_models_path = null;

        /**
         * @var string Path to Model's class's template file.
         */
        public string $js_model_template_path = __DIR__ . DIRECTORY_SEPARATOR . '__vue_model_template.js';

        public function actionGenerateModel( ?string $class = null ) {

            if ( !$this->vue_asset_models_path || !file_exists( $this->vue_asset_models_path ) || !is_dir( $this->vue_asset_models_path ) ) {

                throw new \Exception( 'vue_asset_models_path is not a directory or does not exist.' );
            }

            if ( $class === null ) {

                $this->stdout( 'Enter a Class name (with namespace) to generate the JS model from: ' );
                $class = Console::stdin();
            }

            /**
             * @var ActiveRecord $obj
             */
            $obj = new $class;
            if ( !( $obj instanceof ActiveRecord ) ) {

                throw new \Exception( 'Can only be used on ActiveRecord models.' );
            }

            $pos = strrpos( $class, '\\' );
            if ( $pos !== false ) {

                $class = substr( $class, $pos + 1 );
            }

            $file_name = Inflector::camel2id( $class ) . '.js';
            if ( file_exists( $this->vue_asset_models_path . $file_name ) ) {

                if ( !$this->confirm( 'File `' . $file_name . '` already exists in Vue models path. Overwrite it?' ) ) {

                    return;
                }
            }

            $this->stdout( 'Generating model... ' );

            $model_template = file_get_contents( $this->js_model_template_path );
            $model_template = str_replace( '__CLASS__', $class, $model_template );

            $jsdoc = [];
            $jsdoc_template = null;
            if ( preg_match( '/^([\s*]*)__JSDOC_BLOCK__/m', $model_template, $matches ) ) {

                $jsdoc_template = $matches[1];
            }

            $to_body = [];
            $to_body_template = null;
            if ( preg_match( '/^([\s*]*)__TO_BODY__/m', $model_template, $matches ) ) {

                $to_body_template = $matches[1];
            }

            $relations = [];
            $relations_template = null;
            if ( preg_match( '/^([\s*]*)__RELATIONS__/m', $model_template, $matches ) ) {

                $relations_template = $matches[1];
            }

            $reflection = new \ReflectionClass( $obj::class );
            $doc = $reflection->getDocComment();
            preg_match_all( '/@property *([\w|_0-9[\]]+) *\$([^ ]*)/m', $doc, $matches, PREG_SET_ORDER );
            foreach ( $matches as $match ) {

                $types = explode( '|', $match[1] );
                $relation = null;
                $is_primitives = true;

                foreach ( $types as $type ) {

                    $type = trim( $type );
                    $is_array = false;
                    if ( str_contains( $type, '[]' ) ) {

                        $is_array = true;
                        $type = str_replace( '[]', '', $type );
                    }

                    if ( !in_array( strtolower( $type ), [ 'int', 'integer', 'float', 'double', 'array', 'string', 'bool', 'boolean', 'null' ] ) ) {

                        $is_primitives = false;
                        if ( $relation === null ) {

                            $relation = $type . ( $is_array ? '[]' : '' );
                        } else {

                            $relation = false;
                        }
                    }
                }

                if ( $is_primitives || $relation ) {

                    $jsdoc[] = $jsdoc_template . '@property {' . $match[1] . '} ' . trim( $match[2] );

                    if ( $relation ) {

                        $relations[] = $relations_template . trim( $match[2] ) . ': new Relation( Relation.' . ( $is_array ? 'TYPE_HAS_MANY' : 'TYPE_HAS_ONE' ) .
                            ', ' . $relation . ' ),';
                    } else {

                        $to_body[] = $to_body_template . trim( $match[2] ) . ': this.' . trim( $match[2] );
                    }
                }
            }

            if ( $jsdoc_template ) {

                $model_template = str_replace( $jsdoc_template . '__JSDOC_BLOCK__', implode( "\r\n", $jsdoc ), $model_template );
            }

            if ( $to_body_template ) {

                $model_template = str_replace( $to_body_template . '__TO_BODY__', implode( ",\r\n", $to_body ), $model_template );
            }

            if ( $relations_template ) {

                $model_template = str_replace( $relations_template . '__RELATIONS__', implode( "\r\n", $relations ), $model_template );
            }

            if ( preg_match( '/^([\s]*)__SET_PRIMARY_KEYS__/m', $model_template, $matches ) ) {

                $primary_keys_template = ltrim( $matches[1], "\r\n" );
                $primary_keys = [];

                foreach ( $obj->getPrimaryKey( true ) as $key => $val ) {

                    $primary_keys[] = $primary_keys_template . 'data[\'' . $key . '\'] = this.' . $key . ';';
                }

                $model_template = str_replace( $matches[0], implode( "\r\n", $primary_keys ), $model_template );
            }

            file_put_contents( $this->vue_asset_models_path . $file_name, $model_template );
            $this->stdout( 'Done.' );
        }
    }