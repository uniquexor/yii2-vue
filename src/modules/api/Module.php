<?php
    namespace unique\yii2vue\modules\api;
    
    use yii\base\Application;
    use yii\base\BootstrapInterface;
    use yii\helpers\Inflector;
    use yii\helpers\Url;
    use yii\web\Response;
    use yii\web\UrlRule;

    class Module extends \yii\base\Module implements BootstrapInterface {

        public string $rest_config_path = __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

        public function init() {

            parent::init();

            if ( file_exists( $this->rest_config_path ) ) {

                $config = require $this->rest_config_path;
                \Yii::configure( \Yii::$app, $config );
            }
        }

        public function bootstrap( $app ) {

            // Let's discover all api controllers, to apply UrlRule to:
            $d = dir( $this->getBasePath() . DIRECTORY_SEPARATOR . 'controllers' );
            $controllers = [];
            while ( false !== ( $entry = $d->read() ) ) {

                if ( strpos( $entry, 'Controller.php' ) !== false ) {

                    $controller = str_replace( '.php', '', $entry );
                    $reflection = new \ReflectionClass( $this->controllerNamespace . '\\' . $controller );
                    if ( $parent = $reflection->getParentClass() ) {

                        if ( $parent->getShortName() !== 'ActiveController' ) {

                            // We only need controller's that inherit from ActiveController class.
                            // Doesn't matter if \yii\rest\ActiveController or \unique\yii2vue\modules\api\components

                            continue;
                        }
                    }

                    $short_controller_name = Inflector::camel2id( substr( $controller, 0, -1 * strlen( 'Controller' ) ) );
                    $controllers[ $short_controller_name ] = 'api/' . $short_controller_name;
                }
            }

            $app->getUrlManager()->addRules( [
                [ 'class' => \yii\rest\UrlRule::class, 'controller' => array_values( $controllers ) ]
            ], false);
        }
    }