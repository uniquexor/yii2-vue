class Relation {

    static get TYPE_HAS_ONE() {
        return 'has_one';
    }

    static get TYPE_HAS_MANY() {
        return 'has_many';
    }

    /**
     * Must be one of Relation constants: TYPE_HAS_ONE or TYPE_HAS_MANY
     * @type {string}
     */
    type = null;

    /**
     * Relational object class
     * @type {{mixed}}
     */
    class_name = null;

    /**
     * Provides a way for TYPE_HAS_MANY relations to specify which attribute to use for grouping object in an array By default uses primaryKey().
     * @type {string|null}
     */
    key = null;

    /**
     *
     * @param {string} type - Must be one of Relation constants: TYPE_HAS_ONE or TYPE_HAS_MANY.
     * @param {{mixed}} class_name - Relational object class
     * @param {string|null} key - Provides a way for TYPE_HAS_MANY relations to specify which attribute to use for grouping object in an array By default uses primaryKey().
     */
    constructor( type, class_name, key ) {

        if ( type !== Relation.TYPE_HAS_MANY && type !== Relation.TYPE_HAS_ONE ) {

            throw 'Unknown Relation type';
        }

        this.type = type;
        this.class_name = class_name;

        if ( typeof( key ) !== 'undefined' ) {

            this.key = key;
        }
    }
}