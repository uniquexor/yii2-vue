# yii2-vue

This package provides a very basic models management system for Vuejs v3 on yii2 framework. 
In addition, allows to easily control your vue app assets using yii2 AssetBundle.

## Installation
This component requires php >= 8.0. To install it, add the following to your composer.json:
```
    "require": {
        ...
        "unique/yii2-vue": "@dev"
    },
```

For now this package depends on JQuery to do ajax calls and other stuff. Should probably
decouple it in the future...

## Usage

To use this package, you need to create your own AssetBundle file and define your VUE
application in it. You can use the included \unique\yii2vue\assets\VueAssetBundle,
to help with some asset loading, for example:

```php
    class VueAppAssets extends VueAssetBundle {

        public $sourcePath = __DIR__ . '/vue-app';

        public $jsOptions = [
            'position' => View::POS_HEAD
        ];

        public $depends = [
            Yii2VueComponentsAssets::class
        ];

        public function init() {

            parent::init();
            $this->loadPath( __DIR__ . DIRECTORY_SEPARATOR . 'vue-app' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'models' );
        }
    }
```

The VueAssetBundle provides a way to easily add all js and css files in the given path.
This is useful to load all models. If you want to only add specific models, components, apps,
you can use the corresponding `addModel()`, `addComponents()` or `addApplication()` methods.

For example, in your view file, you could register your created bundle and add a specific
application:

```php
    ( \app\assets\VueAppAssets::register( $this ) )
        ->addApplication( 'accounts-index' )
        ->addComponent( 'bootstrap-input' );
```

You can customize where to find each file, by overwriting the corresponding
`VueAssetBundle` properties. For example:

```php
    class VueAppAssets extends VueAssetBundle {

        public string $sourcePath = __DIR__ . DIRECTORY_SEPARATOR . 'myassets';

        public string $applications_path = 'myjs/apps/';
        public string $mixins_path = 'myjs/mixins/';
        public string $components_path = 'myjs/components/';
        public string $models_path = 'myjs/models/';
    }
```

Paths are relative to where the asset files lie. In this particular example, when
calling ```( \app\assets\VueAppAssets::register( $this ) )->addApplication( 'accounts-index' )```
this would translate to a script tag being generated:
```html
    <script type="/assets/.../myjs/apps/accounts-index.js"
```

If you depend your asset bundle on the provided ```Yii2VueComponentsAssets``` class,
this gives a very basic models manager system that kind of mimicks the native Yii2 models system.

By default this also loads `VueAssets` bundle which loads vuejs. If you want to use your
own vuejs, you can disable this behavior in your `config/params.php` file:

```php
    return [
        'use_vue_dependancy' => false,
        ...
    ];
```

## Usage of Models and Models Managers

A basic model file could look like this:
```js
/**
 * @property {int} id
 * @property {string} name
 * ... other attribute definition
 * 
 * @property {Type} type
 * ... other relations definition
 */
class MyModel extends Model {

    /**
     * The data to be passed to the API to create or update the model. (like a serialization of the Model)
     * @returns {Object}
     */
    toBody() {

        return {
            id: this.id,
            name: this.name,
            // ... other properties
        }
    }

    /**
     * Model Relationship definition.
     * @returns {Relation[]}
     */
    relations() {
        return {
            type: new Relation( Relation.TYPE_HAS_ONE, Type ),
        }
    }

    /**
     * Sets the primary keys to the object, to be used when updating the model.
     * @param {Object} data
     * @returns {Object}
     */
    setPrimaryKeys( data ) {
        data['id'] = this.id;
        return data;
    }
}
```

To create this model, you can use the provided constructor:
```js
    let my_model = new MyModel( { id: 1, name: 'ABC', 'type': { type_name: 'Name', ... } } );
```

This propagates all the given properties and creates all the relations. It is specifically
designed to be used with Yii2 serialized models.

To control more than one model, you can use a provided ModelsManager (or create a new one
by extending it):

```js
    let my_models_manager = new ModelsManager( {
        access_token: '(string) An access token, that, if provided, will be sent with API requests' + 
            'as a basic Auth header. The string will be sent as a username.',
    
        url_create: '(string) A URL that will be used to create new Models',
        url_update: '(string) A URL that will be used to update Models',
        url_delete: '(string) A URL that will be used to delete Models',
        url_list: '(string) A URL that will be used to reload data',

        endpoint_model_class: '(string) A php class name of the model used in the backend. (can be' +
            'used to update many managers data at once)',
        model_class: '(string) A JS model\'s class name, that will be created by this Manager',
        model_id: '(string, default="id") a property, which will be used to index models by'
    } );

    my_models_manager.setData( 
        serialized_php_models,  // serialized data, which to use to create the models
        false   // (bool) Are we setting models that are new (not in DB yet)?
    )
```

### ModelsManager methods:

- `reloadData()`
Used to reload data in the manager. url_list must be specified.

- `updateData( new_data: Object, delete_missing: boolean = true )`
This will iterate all the models managed by this manager and update their properties to 
reflect given new data. If models need to be created - they will be.
However, objects will only be deleted from the store if delete_missing === true
`new_data` and it's relations must be indexed using the same keys as in
`ModelsManager.data`.

- `setData( models: Array, new_records: bool )`
Wipes old data from the Manager and sets new data, by creating new models.
`new_records` parameter is used to set if the Model needs to be created (when true),
or updated (when false).

- `addItem( item: Model, new_record: bool )`
Adds a model to the data store (without calling the API).
`new_records` parameter is used to set if the Model needs to be created (when true),
or updated (when false).

- `static handleUnsuccessfulRequest( model: Model, data: Object, default_key: string )`
Sets field errors on {@see Model.errors} according to the `data` response.
`data` - has the following structure:
```json
{
    success: boolean, // was an operation successful. If it's given here, then usually this should be set to false.,
    data: object, // either a serialized Yii2 Exception, or a serialized Model with validation errors.
}
```

**When `data.data` is a seriliazed Yii2 Exception, it will have the following fields:**
```json
{
    name: string,   // Exception class name,
    message: string,    // Exception message
}
```
The error will be set to the provided `default_key` property of the model.

**When `data.data` is a serialized Yii2 Model with validation errors, it will have the following structure:**
```json
[
    { 
        field: string,      // Field name that violated a validation rule,
        message: string,    // Validation message
    },
    ...
]
```

An error for Model's relation can also be set, by providing a full path in the `field`.
For example if model Brand had a HAS_MANY relation named cars with Car class and a HAS_ONE relation manufacturer with Manufacturer class,
after making an unsuccessful request to the API, we could receive the following response:

```json
{
    success: false,
    data: [
        { field: "name", message: "Bad name" },          // This is an error for Brand.name property
        { field: "cars.3.engine", message: "..." },      // This is an error for Brand.cars[3].engine property and will accordingly be set on a Car class
        { field: "manufacturer.year", message: "..." }   // This is an error for Brand.manufacturer.year property and will be set on a Manufacturer class
    ]
}
```

Keep in mind, Yii2 does not have relational save capabilities natively, thus, you need to implement the back end logic yourself.

- `deleteItem( item_id: string|int, remove_from_data: bool )`
Calls the API to remove the given ID. If `remove_from_data` is true, then after
a successful call, data is also removed from the Manager.

- `saveItem( item: Model, request_data: Object )`
Calls the API to create or update the given model.
Additional data can be passed, by providing a `request_data` object.
It will be merged with the Model's data to be saved. (Model's data takes precedence).


### ModelsManagerRegister

All created `ModelsManager` instances are automatically registered with a static instance
of `ModelsManagerRegister`. `ModelsManagerRegister` can be used to update many 
`ModelsManger`s at the same time. This is convenient, when you are performing an API call, 
that updates not only the model it's been given, but other models as well.

In this case, an API call can return `_list_changes_`, to update other models. For example,
imagine we are creating a new Car model, which updates Stats model to reflect the number
of cars of the same make_id. Then our API, could return data like this:

```json
    {
        ...,
        "_list_changes_": {
            "\\app\\models\\Car": {
                "1067": {
                    "id": 1067,
                    "make_id": 3,
                    ...
                }
            },
            "\\app\\models\\Stat": {
                "3": {
                    "count": 52
                }
            }
        }
    }
```

We could now call `ModelsManagerRegister.updateListChanges( data )`, which, providing
we have created ModelsManager for both `Car` and `Stat` models, would update data in them.


## Creating JS model files, using a Yii2 command

You can easily create a JS model, by calling:
```
    yii vue-assets/generate-model [PHP_CLASS_NAME]
```

If you skip the [PHP_CLASS_NAME], you will be required to specify it, once the command runs.
This should be a fully namespaced class name.

You should also specify where to put the generated models in your config/console.php file:
```php
    'controllerMap' => [
        'vue-assets' => [
            'class' => \unique\yii2vue\commands\VueAssetsController::class,
            'vue_asset_models_path' => ... // path to directory where to put generated JS models,
        ]
    ],
```