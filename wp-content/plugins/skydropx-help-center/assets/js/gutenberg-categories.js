( function () {

    // Compatibilidad con diferentes versiones de WordPress
    // PluginDocumentSettingPanel se movió en WP 6.2+
    var PanelComponent = null;

    if ( wp.editor && wp.editor.PluginDocumentSettingPanel ) {
        PanelComponent = wp.editor.PluginDocumentSettingPanel;
    } else if ( wp.editPost && wp.editPost.PluginDocumentSettingPanel ) {
        PanelComponent = wp.editPost.PluginDocumentSettingPanel;
    }

    if ( ! PanelComponent ) {
        console.warn( '[SXHC] PluginDocumentSettingPanel no encontrado.' );
        return;
    }

    var el          = wp.element.createElement;
    var useState    = wp.element.useState;
    var useEffect   = wp.element.useEffect;
    var useSelect   = wp.data.useSelect;
    var useDispatch = wp.data.useDispatch;
    var Panel       = PanelComponent;
    var options     = ( window.sxhcEditorData && window.sxhcEditorData.categoryOptions ) || [];

    // ── Componente ────────────────────────────────────────────────────────

    function CategoriesPanel() {

        var savedIds = useSelect( function ( select ) {
            return select( 'core/editor' ).getEditedPostAttribute( 'help_category' ) || [];
        } );

        var savedPrimary = useSelect( function ( select ) {
            var meta = select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {};
            return parseInt( meta._sxhc_primary_category || 0, 10 );
        } );

        var dispatch = useDispatch( 'core/editor' );

        var _rows   = useState( null );
        var rows    = _rows[0];
        var setRows = _rows[1];

        useEffect( function () {
            var initial = savedIds.length > 0 ? savedIds.slice() : [ 0 ];
            setRows( initial );
        }, [] ); // eslint-disable-line

        if ( rows === null ) {
            return el( 'p', { style: { fontSize: '12px', color: '#aaa', margin: '0' } }, 'Cargando…' );
        }

        function sync( newRows, newPrimary ) {
            var valid = newRows.filter( function ( id ) { return id > 0; } );
            dispatch.editPost( { help_category: valid } );
            if ( newPrimary ) {
                dispatch.editPost( { meta: { _sxhc_primary_category: newPrimary } } );
            }
        }

        function updateRow( index, val ) {
            var next       = rows.slice();
            next[ index ]  = val;
            setRows( next );
            var newPrimary = savedPrimary;
            if ( ! savedPrimary || rows[ index ] === savedPrimary ) {
                newPrimary = next.filter( function ( id ) { return id > 0; } )[ 0 ] || 0;
            }
            sync( next, newPrimary );
        }

        function addRow() {
            setRows( rows.concat( [ 0 ] ) );
        }

        function removeRow( index ) {
            if ( rows.length === 1 ) { updateRow( 0, 0 ); return; }
            var next    = rows.slice();
            var removed = next.splice( index, 1 )[ 0 ];
            setRows( next );
            var newPrimary = savedPrimary;
            if ( removed === savedPrimary ) {
                newPrimary = next.filter( function ( id ) { return id > 0; } )[ 0 ] || 0;
            }
            sync( next, newPrimary );
        }

        return el( 'div', null,

            rows.map( function ( termId, index ) {
                return el( 'div', { key: index, style: { marginBottom: '12px' } },

                    // Header de la fila
                    el( 'div', {
                        style: { display: 'flex', justifyContent: 'space-between',
                                 alignItems: 'center', marginBottom: '4px' }
                    },
                        el( 'span', {
                            style: { fontSize: '11px', fontWeight: '600', color: '#757575',
                                     textTransform: 'uppercase', letterSpacing: '0.5px' }
                        }, rows.length > 1 ? 'Categoría ' + ( index + 1 ) : 'Categoría' ),

                        ( rows.length > 1 || termId > 0 )
                            ? el( 'button', {
                                type: 'button',
                                onClick: function () { removeRow( index ); },
                                style: { background: 'none', border: 'none', cursor: 'pointer',
                                         color: '#bbb', fontSize: '18px', lineHeight: '1',
                                         padding: '0 2px', display: 'flex', alignItems: 'center' },
                                title: 'Quitar'
                            }, '×' )
                            : null
                    ),

                    // Select
                    el( 'select', {
                        value: termId > 0 ? String( termId ) : '',
                        onChange: function ( e ) {
                            updateRow( index, parseInt( e.target.value, 10 ) || 0 );
                        },
                        style: {
                            width: '100%', padding: '6px 8px', fontSize: '13px',
                            border: '1px solid #ddd', borderRadius: '4px',
                            background: '#fff', color: termId > 0 ? '#1d2327' : '#999',
                            cursor: 'pointer'
                        }
                    },
                        el( 'option', { value: '' }, 'Seleccionar categoría' ),
                        options.map( function ( o ) {
                            return el( 'option', { key: o.value, value: o.value }, o.label );
                        } )
                    )
                );
            } ),

            // Botón agregar
            el( 'button', {
                type: 'button',
                onClick: addRow,
                style: {
                    background: 'none', border: 'none', padding: '4px 0',
                    cursor: 'pointer', color: '#2271b1', fontSize: '13px',
                    textDecoration: 'underline', marginTop: '2px', display: 'block'
                }
            }, '+ Agregar otra categoría' )
        );
    }

    // ── Registrar el plugin ───────────────────────────────────────────────

    wp.plugins.registerPlugin( 'sxhc-categories-panel', {
        render: function () {
            return el( Panel, {
                name:  'sxhc-categories-panel',
                title: 'Categoría',
                icon:  'category',
            },
                el( CategoriesPanel, null )
            );
        }
    } );

} )();
