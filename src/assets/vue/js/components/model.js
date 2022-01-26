class Model {

    is_loading = false;
    field_loading = {};
    errors = {};
    models_manager;
    is_new_record = true;

    constructor( models_manager, attrs ) {

        this.models_manager = models_manager;

        // First we initialize the default attributes and relations:

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

        // Then we set values from attrs:

        this.setAttributes( attrs );
    }

    setAttributes( attrs ) {

        let relations = this.relations();

        for ( let key in attrs ) {

            if ( relations[ key ] ) {

                let relation = relations[ key ];

                // @todo check for a setter?
                if ( Array.isArray( attrs[ key ] ) ) {

                    let relation_objs = {};
                    for ( let i in attrs[ key ] ) {

                        attrs[ key ][i] = $.extend( { is_new_record : this.is_new_record }, attrs[ key ][i] );

                        let relation_obj = Reflect.construct( relation.class_name, [ this.models_manager, attrs[ key ][i] ] );
                        relation_objs[ relation.key ? relation_obj[ relation.key ] : relation_obj.getPrimaryKey() ] = relation_obj;
                    }

                    Vue.set( this, key, relation_objs );
                } else {

                    attrs[ key ] = $.extend( { is_new_record : this.is_new_record }, attrs[ key ] );
                    Vue.set( this, key, Reflect.construct( relation.class_name, [ this.models_manager, attrs[ key ] ] ) );
                }
            } else {

                this[ key ] = attrs[ key ];
            }
        }
    }

    updateData( new_data ) {

        let relations = this.relations();

        for ( let key in new_data ) {

            if ( relations[ key ] ) {

                let relation = relations[ key ];

                if ( relation.type === Relation.TYPE_HAS_MANY ) {

                    let used_keys = {};

                    for ( let i in new_data[ key ] ) {

                        used_keys[ i ] = i;
                        if ( this[ key ][ i ] ) {

                            this[ key ][ i ].updateData( new_data[ key ][ i ] );
                        } else {

                            new_data[ key ][i] = $.extend( { is_new_record : false }, new_data[ key ][i] );

                            let relation_obj = Reflect.construct( relation.class_name, [ this.models_manager, new_data[ key ][i] ] );
                            Vue.set( this[ key ], i, relation_obj );
                        }
                    }

                    for ( let i in this[ key ] ) {

                        if ( !used_keys[ i ] ) {

                            Vue.delete( this[ key ], i );
                        }
                    }
                } else {

                    this[ key ].updateData( new_data[ key ] );
                }
            } else {

                if ( this[ key ] !== new_data[ key ] ) {

                    this[ key ] = new_data[ key ];
                }
            }
        }
    }

    relations() {

        return {};
    }

    getPrimaryKey() {

        return this.id;
    }

    updateLoadingField( field, is_loading ) {

        Vue.set( this.field_loading, field, is_loading );
    }

    toBody() {

        let data = {};
        for ( let key in this ) {

            data[ key ] = this[ key ];
        }

        return data;
    }
}