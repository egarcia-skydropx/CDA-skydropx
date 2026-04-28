<?php
/**
 * Buscador reutilizable.
 * $args['variant'] = 'hero'    -> versión grande (homepage)
 * $args['variant'] = 'compact' -> versión pequeña (header)
 */
$variant     = isset( $args['variant'] ) ? $args['variant'] : 'compact';
$is_hero     = $variant === 'hero';

$wrap_class  = $is_hero
    ? 'relative max-w-xl mx-auto'
    : 'relative w-64';

$input_class = $is_hero
    ? 'w-full pl-12 pr-10 py-3.5 rounded-2xl border border-gray-200 bg-gray-50 text-gray-900 placeholder-gray-400 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition'
    : 'w-full pl-9 pr-8 py-2 rounded-xl border border-gray-200 bg-gray-50 text-gray-900 placeholder-gray-400 text-sm focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition';

$icon_class  = $is_hero
    ? 'absolute inset-y-0 left-4 flex items-center pointer-events-none text-gray-400'
    : 'absolute inset-y-0 left-3 flex items-center pointer-events-none text-gray-400';

$icon_size   = $is_hero ? 'w-5 h-5' : 'w-4 h-4';
?>

<div id="sxhc-search-wrap" class="<?php echo esc_attr( $wrap_class ); ?>">
    <div class="relative">

        <!-- Icono lupa -->
        <span class="<?php echo esc_attr( $icon_class ); ?>">
            <svg class="<?php echo esc_attr( $icon_size ); ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
            </svg>
        </span>

        <!-- Input -->
        <input
            id="sxhc-search-input"
            type="text"
            placeholder="Busca artículos, categorías…"
            autocomplete="off"
            class="<?php echo esc_attr( $input_class ); ?>"
        />

        <!-- Spinner -->
        <span id="sxhc-spinner" class="absolute inset-y-0 right-4 hidden items-center pointer-events-none">
            <svg class="w-4 h-4 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
            </svg>
        </span>

        <!-- Botón limpiar -->
        <button id="sxhc-clear"
                aria-label="Limpiar búsqueda"
                class="absolute inset-y-0 right-3 hidden items-center justify-center
                       w-7 h-7 my-auto rounded-full text-gray-400
                       hover:bg-gray-200 hover:text-gray-600 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

    </div>

    <!-- Dropdown -->
    <div id="sxhc-dropdown"
         class="hidden absolute top-full left-0 right-0 mt-1.5 bg-white border border-gray-200
                rounded-2xl shadow-lg overflow-hidden z-50 text-left">
        <ul id="sxhc-results" class="divide-y divide-gray-50 max-h-80 overflow-y-auto"></ul>
        <div id="sxhc-dropdown-footer"
             class="hidden px-4 py-2.5 border-t border-gray-100 bg-gray-50 text-center">
            <a id="sxhc-see-all" href="#"
               class="text-xs text-brand font-semibold hover:underline">
                Ver todos los resultados
            </a>
        </div>
    </div>
</div>
