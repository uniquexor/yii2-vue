/**
 * A base Model class.
 */
class Model {

    /**
     * Indicates if the record does not exist in the DB. If so, create action will be used to save it, otherwise - update.
     * @type {boolean}
     */
    is_new_record = true;

    /**
     * Indicates if the model is being loaded. It is set to true, once an API call has been made and set to false, once the response has been received.
     * @type {boolean}
     */
    is_loading = false;

    /**
     * Stores individual is_loading states for each of the field in the Model (if there is a need for it).
     * @type {boolean[]}
     */
    field_loading = {};

    /**
     * Stores an error message for each of the field in the Model.
     * @type {String[]}
     */
    errors = {};

    /**
     * @type {ModelsManager}
     */
    models_manager;

    /**
     * Creates a model, assigning it its property and relation values.
     * @param {ModelsManager} models_manager
     * @param {Object} properties
     */
    constructor( models_manager, properties = {} ) {

        this.models_manager = models_manager;

        // First we initialize the default properties and relations:

        let relations = this.relations();

        for ( let attr in this.toBody() ) {

            if ( relations[ attr ] ) {

                if ( !( relations[ attr ] instanceof Relation ) ) {

                    throw this.constructor.name + '::' + attr + ' relation must be an instance of Relation class.';
                }

                this[ attr ] = ( relations[ attr ].type === Relation.TYPE_HAS_MANY ) ? {} : new relations[ attr ].class_name;
                delete relations[ attr ];
            } else {

                this[ attr ] = null;
            }
        }

        for ( let attr in relations ) {

            if ( !( relations[ attr ] instanceof Relation ) ) {

                throw this.constructor.name + '::' + attr + ' relation must be an instance of Relation class.';
            }

            this[ attr ] = ( relations[ attr ].type === Relation.TYPE_HAS_MANY ) ? {} : new relations[ attr ].class_name;
        }

        // Then we set values from properties:

        this.setProperties( properties );
    }

    /**
     * Sets or updates values for properties and creates Model's relations.
     * @param {Object} properties
     * @param {boolean} delete_missing
     */
    setProperties( properties, delete_missing = true ) {

        let relations = this.relations();

        for ( let key in properties ) {

            if ( relations[ key ] ) {

                let relation = relations[ key ];

                if ( relation.type === Relation.TYPE_HAS_MANY ) {

                    let used_keys = {};

                    for ( let i in properties[ key ] ) {

                        let rel_key = properties[ key ][i][ relation.key ];

                        used_keys[ rel_key ] = rel_key;
                        if ( this[ key ][ rel_key ] ) {

                            this[ key ][ rel_key ].setProperties( properties[ key ][ i ], delete_missing );
                        } else {

                            properties[ key ][i] = $.extend( { is_new_record : this.is_new_record }, properties[ key ][i] );

                            let relation_obj = Reflect.construct( relation.class_name, [ this.models_manager, properties[ key ][i] ] );
                            Vue.set( this[ key ], rel_key, relation_obj );
                        }
                    }

                    if ( !delete_missing ) {

                        for ( let i in this[ key ] ) {

                            if ( !used_keys[ i ] ) {

                                Vue.delete( this[ key ], i );
                            }
                        }
                    }
                } else {

                    this[ key ].setProperties( properties[ key ], delete_missing );
                }
            } else {

                if ( this[ key ] !== properties[ key ] ) {

                    this[ key ] = properties[ key ];
                }
            }
        }
    }

    /**
     * Defines Model's relational properties.
     * Each relation must be of {@see Relation} type.
     * Properties are indexed by the relation name.
     * @returns {{Relation}}
     */
    relations() {

        return {};
    }

    /**
     * Returns a primary key of the Object.
     * @returns {string|int|null}
     */
    getPrimaryKey() {

        return this.id;
    }

    /**
     * Set if a certain Model's property is being loaded.
     * @param {string} field
     * @param {boolean} is_loading
     */
    updatePropertyLoading( field, is_loading ) {

        Vue.set( this.field_loading, field, is_loading );
    }

    /**
     * The data to be passed to the API to create or update the model (like a serialization of the Model)
     * @returns {Object}
     */
    toBody() {

        let data = {};
        for ( let key in this ) {

            data[ key ] = this[ key ];
        }

        return data;
    }
}