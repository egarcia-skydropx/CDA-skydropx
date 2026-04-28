( function () {

    var el          = wp.element.createElement;
    var useState    = wp.element.useState;
    var useEffect   = wp.element.useEffect;
    var Fragment    = wp.element.Fragment;
    var RichText    = wp.blockEditor.RichText;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody   = wp.components.PanelBody;
    var TextControl = wp.components.TextControl;
    var ToggleControl = wp.components.ToggleControl;
    var registerBlockType = wp.blocks.registerBlockType;

    // ── SVG Icons ─────────────────────────────────────────────────────────
    var ICONS = {
        success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        danger:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        info:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        warning: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>',
        default: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        orange:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>',
    };

    var LABELS = {
        success: 'Success',
        danger:  'Danger',
        info:    'Info',
        warning: 'Warning',
        default: 'Default',
        orange:  'Orange',
    };

    // ── Transform: "-" al inicio de línea → bullet ────────────────────────
    function transformBullets( value ) {
        if ( ! value ) return value;

        // <p>- texto</p>  →  <li>texto</li>
        var result = value.replace( /<p>[-]\s+([^<]*)<\/p>/g, '<li>$1</li>' );

        // Agrupar <li> consecutivos dentro de <ul>
        result = result.replace( /(<li>(?:[^<]|<(?!\/li>))*<\/li>\s*)+/g, function ( match ) {
            return '<ul>' + match + '</ul>';
        } );

        return result;
    }

    // ── Render del icono SVG inline ────────────────────────────────────────
    function Icon( props ) {
        return el( 'span', {
            className: 'sxhc-alert__icon',
            dangerouslySetInnerHTML: { __html: ICONS[ props.type ] || ICONS.info }
        } );
    }

    // ── Selector visual de tipo ────────────────────────────────────────────
    function TypePicker( props ) {
        return el( 'div', { className: 'sxhc-type-picker' },
            Object.keys( LABELS ).map( function ( type ) {
                return el( 'button', {
                    key:       type,
                    type:      'button',
                    className: 'sxhc-type-btn t-' + type + ( props.value === type ? ' is-active' : '' ),
                    onClick:   function () { props.onChange( type ); },
                    title:     LABELS[ type ],
                    dangerouslySetInnerHTML: {
                        __html: ICONS[ type ] + '<span>' + LABELS[ type ] + '</span>'
                    }
                } );
            } )
        );
    }

    // ── Vista previa del bloque en el editor ───────────────────────────────
    function AlertPreview( props ) {
        var attr    = props.attributes;
        var set     = props.setAttributes;
        var type    = attr.alertType || 'info';
        var hasHead = attr.showHeading;

        var classes = 'sxhc-alert is-' + type + ( hasHead ? ' has-heading' : '' );

        // RichText del body (reutilizado en ambos layouts)
        var bodyField = attr.showBody
            ? el( RichText, {
                tagName:   'div',
                className: 'sxhc-alert__body',
                value:     attr.body,
                onChange:  function ( v ) { set( { body: transformBullets( v ) } ); },
                placeholder: 'Escribe el mensaje de la alerta…',
                allowedFormats: [
                    'core/bold', 'core/italic', 'core/link',
                    'core/code', 'core/underline'
                ],
            } )
            : null;

        return el( 'div', { className: classes },

            hasHead
                // ── Con heading: icono + título | body debajo ─────────────
                ? el( wp.element.Fragment, null,
                    el( 'div', { className: 'sxhc-alert__header' },
                        el( Icon, { type: type } ),
                        el( RichText, {
                            tagName:        'p',
                            className:      'sxhc-alert__heading',
                            value:          attr.heading,
                            onChange:       function ( v ) { set( { heading: v } ); },
                            placeholder:    'Alert heading…',
                            allowedFormats: [],
                        } )
                    ),
                    bodyField
                  )
                // ── Sin heading: icono + body en la misma línea ───────────
                : el( 'div', { className: 'sxhc-alert__header' },
                    el( Icon, { type: type } ),
                    bodyField
                  ),

            // Botón CTA
            attr.showButton
                ? el( 'a', {
                    className: 'sxhc-alert__btn',
                    href:      '#',
                    onClick:   function(e){ e.preventDefault(); }
                }, attr.buttonText || 'View more' )
                : null,

            // Link
            attr.showLink
                ? el( 'a', {
                    className: 'sxhc-alert__link',
                    href:      '#',
                    onClick:   function(e){ e.preventDefault(); }
                }, attr.linkText || 'Link example info here' )
                : null
        );
    }

    // ── Registro del bloque ────────────────────────────────────────────────
    registerBlockType( 'sxhc/alert', {
        title:       'Alerta',
        description: 'Alerta contextual: Success, Danger, Info, Warning, Default u Orange.',
        category:    'common',
        icon:        'warning',
        keywords:    [ 'alerta', 'alert', 'warning', 'info', 'danger', 'aviso' ],

        attributes: {
            alertType:   { type: 'string',  default: 'info' },
            showHeading: { type: 'boolean', default: true },
            heading:     { type: 'string',  default: '' },
            showBody:    { type: 'boolean', default: true },
            body:        { type: 'string',  default: '' },
            showButton:  { type: 'boolean', default: false },
            buttonText:  { type: 'string',  default: 'View more' },
            buttonUrl:   { type: 'string',  default: '' },
            showLink:    { type: 'boolean', default: false },
            linkText:    { type: 'string',  default: 'Link example info here' },
            linkUrl:     { type: 'string',  default: '' },
        },

        // ── Editor ──────────────────────────────────────────────────────
        edit: function ( props ) {
            var attr = props.attributes;
            var set  = props.setAttributes;

            return el( Fragment, null,

                // Panel lateral de ajustes
                el( InspectorControls, null,
                    el( PanelBody, { title: 'Tipo de alerta', initialOpen: true },
                        el( TypePicker, {
                            value:    attr.alertType,
                            onChange: function ( v ) { set( { alertType: v } ); }
                        } )
                    ),

                    el( PanelBody, { title: 'Contenido', initialOpen: true },
                        el( ToggleControl, {
                            label:    'Mostrar heading',
                            checked:  attr.showHeading,
                            onChange: function ( v ) { set( { showHeading: v } ); }
                        } ),
                        el( ToggleControl, {
                            label:    'Mostrar cuerpo',
                            checked:  attr.showBody,
                            onChange: function ( v ) { set( { showBody: v } ); }
                        } )
                    ),

                    el( PanelBody, { title: 'Botón CTA', initialOpen: false },
                        el( ToggleControl, {
                            label:    'Mostrar botón',
                            checked:  attr.showButton,
                            onChange: function ( v ) { set( { showButton: v } ); }
                        } ),
                        attr.showButton
                            ? el( Fragment, null,
                                el( TextControl, {
                                    label:    'Texto del botón',
                                    value:    attr.buttonText,
                                    onChange: function ( v ) { set( { buttonText: v } ); }
                                } ),
                                el( TextControl, {
                                    label:    'URL del botón',
                                    value:    attr.buttonUrl,
                                    type:     'url',
                                    onChange: function ( v ) { set( { buttonUrl: v } ); }
                                } )
                              )
                            : null
                    ),

                    el( PanelBody, { title: 'Link', initialOpen: false },
                        el( ToggleControl, {
                            label:    'Mostrar link',
                            checked:  attr.showLink,
                            onChange: function ( v ) { set( { showLink: v } ); }
                        } ),
                        attr.showLink
                            ? el( Fragment, null,
                                el( TextControl, {
                                    label:    'Texto del link',
                                    value:    attr.linkText,
                                    onChange: function ( v ) { set( { linkText: v } ); }
                                } ),
                                el( TextControl, {
                                    label:    'URL del link',
                                    value:    attr.linkUrl,
                                    type:     'url',
                                    onChange: function ( v ) { set( { linkUrl: v } ); }
                                } )
                              )
                            : null
                    )
                ),

                // Vista previa en el canvas
                el( AlertPreview, { attributes: attr, setAttributes: set } )
            );
        },

        // ── Frontend (save) ──────────────────────────────────────────────
        save: function ( props ) {
            var attr    = props.attributes;
            var type    = attr.alertType || 'info';
            var hasHead = attr.showHeading;

            var classes = 'sxhc-alert is-' + type + ( hasHead ? ' has-heading' : '' );

            var iconEl = el( 'span', {
                className: 'sxhc-alert__icon',
                dangerouslySetInnerHTML: { __html: ICONS[ type ] }
            } );

            var bodyEl = ( attr.showBody && attr.body )
                ? el( RichText.Content, {
                    tagName:   'div',
                    className: 'sxhc-alert__body',
                    value:     attr.body,
                } )
                : null;

            return el( 'div', { className: classes },

                hasHead
                    // Con heading
                    ? el( wp.element.Fragment, null,
                        el( 'div', { className: 'sxhc-alert__header' },
                            iconEl,
                            el( RichText.Content, {
                                tagName:   'p',
                                className: 'sxhc-alert__heading',
                                value:     attr.heading,
                            } )
                        ),
                        bodyEl
                      )
                    // Sin heading: icono + body en la misma fila
                    : el( 'div', { className: 'sxhc-alert__header' },
                        iconEl,
                        bodyEl
                      ),

                attr.showButton && attr.buttonText
                    ? el( 'a', {
                        className: 'sxhc-alert__btn',
                        href:      attr.buttonUrl || '#',
                        target:    attr.buttonUrl ? '_blank' : undefined,
                        rel:       attr.buttonUrl ? 'noopener noreferrer' : undefined,
                    }, attr.buttonText )
                    : null,

                attr.showLink && attr.linkText
                    ? el( 'a', {
                        className: 'sxhc-alert__link',
                        href:      attr.linkUrl || '#',
                        target:    attr.linkUrl ? '_blank' : undefined,
                        rel:       attr.linkUrl ? 'noopener noreferrer' : undefined,
                    }, attr.linkText )
                    : null
            );
        }
    } );

    console.log( '[SXHC] Bloque de alertas registrado.' );

} )();
