<?php
    namespace unique\yii2vue\assets;

    use RecursiveDirectoryIterator;
    use RecursiveIteratorIterator;
    use yii\web\AssetBundle;

    class Yii2VueBaseAssets extends AssetBundle {

        public string $applications_path = 'js/apps/';
        public string $mixins_path = 'js/mixins/';
        public string $components_path = 'js/components/';
        public string $models_path = 'js/models/';

        protected function loadPath( string $path, bool $recursively = true ) {

            $path = realpath( $path );
            $assets_path = $this->sourcePath ? realpath( $this->sourcePath ) : realpath( $this->basePath );

            if ( $this->sourcePath && strpos( realpath( $path ), realpath( $this->sourcePath ) ) !== 0 ) {

                throw new \Exception( 'A given path must be within the sourcePath or sourcePath must be NULL to use basePath.' );
            } elseif ( $this->basePath && strpos( realpath( $path ), realpath( $this->basePath ) ) !== 0 ) {

                throw new \Exception( 'A given path must be within either sourcePath or basePath.' );
            }

            $relative_path = str_replace( '\\', '/', substr( $path, strlen( $assets_path ) ) );
            if ( $relative_path && $relative_path[0] === '/' ) {

                $relative_path = substr( $relative_path, 1 );
            }

            if ( $recursively ) {

                $iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $path ) );
            } else {

                $iterator = new \IteratorIterator( new \DirectoryIterator( $path ) );
            }

            foreach ( $iterator as $file ) {

                /**
                 * @var \DirectoryIterator $file
                 */

                if ( $file->isDir() ) {

                    continue;
                }

                $file = $file->getFilename();

                if ( str_ends_with( $file, '.js' ) ) {

                    $this->js[ $relative_path . $file ] = $relative_path . $file;
                } elseif ( str_ends_with( $file, '.css' ) ) {

                    $this->css[ $relative_path . $file ] = $relative_path . $file;
                }
            }
        }

        public function addApplication( string $name ) {

            $this->js[ $this->applications_path . $name . '.js' ] = $this->applications_path . $name . '.js';
            return $this;
        }

        public function addComponent( string $name ) {

            $this->js[ $this->components_path . $name . '.js' ] = $this->components_path . $name . '.js';
            return $this;
        }

        public function addMixin( string $name ) {

            $this->js[ $this->mixins_path . $name . '.js' ] = $this->mixins_path . $name . '.js';
            return $this;
        }

        public function addModel( string $name ) {

            $this->js[ $this->models_path . $name . '.js' ] = $this->models_path . $name . '.js';
            return $this;
        }
    }