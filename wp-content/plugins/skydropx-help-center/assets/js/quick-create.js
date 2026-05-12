/**
 * SXHC Quick Create — botón "Nuevo artículo" / "Nueva pregunta" en el header del editor.
 *
 * Inserta un <button> nativo (con clases `components-button is-secondary` para que
 * herede el estilo de Gutenberg) al inicio del contenedor de acciones del header.
 *
 * El destino siempre es el MISMO post type que se está editando. PHP decide vía
 * `window.sxhcQuickCreate` (texto del botón + URL). Si la entrada tiene cambios
 * sin guardar, pide confirmación con window.confirm() antes de navegar.
 */
( function () {

    var data = window.sxhcQuickCreate || {};
    if ( ! data.targetUrl || ! data.buttonLabel ) {
        return;
    }

    if ( ! wp || ! wp.plugins || ! wp.plugins.registerPlugin || ! wp.element ) {
        // Si no hay APIs de Gutenberg disponibles, no hacemos nada (editor clásico).
        return;
    }

    var useEffect = wp.element.useEffect;
    var BUTTON_ID = 'sxhc-quick-create-btn';

    function handleClick( ev ) {
        if ( ev && ev.preventDefault ) {
            ev.preventDefault();
        }

        var isDirty = false;
        try {
            if ( wp.data && wp.data.select ) {
                var editor = wp.data.select( 'core/editor' );
                if ( editor && typeof editor.isEditedPostDirty === 'function' ) {
                    isDirty = editor.isEditedPostDirty();
                }
            }
        } catch ( e ) {
            // Ante cualquier falla del store, dejamos pasar sin bloquear.
        }

        if ( isDirty ) {
            var ok = window.confirm( data.confirmText );
            if ( ! ok ) {
                return;
            }
        }

        window.location.href = data.targetUrl;
    }

    function injectButton() {
        // Ya existe → nada que hacer.
        if ( document.getElementById( BUTTON_ID ) ) {
            return true;
        }

        // Posibles contenedores del header (varía entre versiones de WP/Gutenberg).
        var target = document.querySelector(
            '.editor-header__settings, .edit-post-header__settings'
        );
        if ( ! target ) {
            return false;
        }

        var btn = document.createElement( 'button' );
        btn.id          = BUTTON_ID;
        btn.type        = 'button';
        btn.className   = 'components-button is-secondary sxhc-quick-create';
        btn.textContent = data.buttonLabel;
        btn.setAttribute( 'aria-label', data.buttonLabel );
        btn.addEventListener( 'click', handleClick );

        // Lo metemos al inicio para que quede a la izquierda de "Guardar como borrador".
        target.insertBefore( btn, target.firstChild );
        return true;
    }

    function removeButton() {
        var btn = document.getElementById( BUTTON_ID );
        if ( btn && btn.parentNode ) {
            btn.parentNode.removeChild( btn );
        }
    }

    /**
     * Plugin "fantasma": no renderiza nada en el slot de plugins, solo nos sirve
     * de ciclo de vida para inyectar/limpiar el botón cuando el editor monta/desmonta.
     */
    function QuickCreatePlugin() {

        useEffect( function () {

            // Intento inmediato + reintentos por si el header aún no está montado.
            // (El MutationObserver de abajo cubre re-renders posteriores.)
            injectButton();

            var attempts    = 0;
            var maxAttempts = 40; // ~8 s a 200 ms
            var iv = setInterval( function () {
                attempts += 1;
                if ( injectButton() || attempts >= maxAttempts ) {
                    clearInterval( iv );
                }
            }, 200 );

            // MutationObserver para re-insertar si Gutenberg vuelve a renderizar el header.
            var observer = null;
            if ( typeof MutationObserver !== 'undefined' ) {
                observer = new MutationObserver( function () {
                    if ( ! document.getElementById( BUTTON_ID ) ) {
                        injectButton();
                    }
                } );
                var root = document.querySelector( '.interface-interface-skeleton__header, .edit-post-header, .editor-header' );
                if ( root ) {
                    observer.observe( root, { childList: true, subtree: true } );
                }
            }

            return function () {
                clearInterval( iv );
                if ( observer ) {
                    observer.disconnect();
                }
                removeButton();
            };

        }, [] );

        return null;
    }

    wp.plugins.registerPlugin( 'sxhc-quick-create', {
        render: QuickCreatePlugin
    } );

} )();
