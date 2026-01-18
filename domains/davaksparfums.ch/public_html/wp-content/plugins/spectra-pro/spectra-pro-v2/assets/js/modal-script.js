// Constants
const EXIT_INTENT_THRESHOLD = 20; // Pixels from top to trigger exit intent
const MS_PER_DAY = 24 * 60 * 60 * 1000; // Milliseconds in a day
const DEFAULT_HIDE_DAYS = 7; // Default days to hide modal with cookie

/**
 * Get a cookie value by name.
 *
 * @param {string} name - The name of the cookie.
 * @return {string|undefined} - The cookie value if found, otherwise undefined.
 */
const getCookie = ( name ) => {
	const value = `; ${document.cookie}`;
	const parts = value.split( `; ${name}=` );
	return parts.length === 2 ? parts.pop().split( ';' ).shift() : undefined;
};

/**
 * Set a cookie to hide a modal popup for a number of days.
 *
 * @param {string} blockId - The modal block ID.
 * @param {number} days    - Days to persist the cookie.
 */
const setPopupCookie = ( blockId, days ) => {
	const cookieKey = `block-${blockId}`;
	const expires = new Date( Date.now() + days * MS_PER_DAY ).toUTCString();
	document.cookie = `${cookieKey}=true; expires=${expires}; path=/`;
};

/**
 * Determine whether a modal should be shown.
 *
 * @param {Object} args - Configuration arguments.
 * @return {boolean} - Whether the modal can be shown.
 */
const canShow = ( args = {} ) => {
	const cookieName = `block-${args.blockId}`;
	const cookie = getCookie( cookieName );

	if ( args.enableCookies && cookie && args.modalTrigger === 'automatic' ) {
		return false;
	} else if ( !args.enableCookies ) {
		document.cookie = `${cookieName}=; max-age=-1; path=/`;
	}

	const modal = document.getElementById( args.blockId );
	return !( modal?.classList?.contains( 'active' ) );
};

/**
 * Show the modal by adding active class and dispatching events.
 *
 * @param {Object} args - Arguments containing blockId and trigger info.
 */
const openModal = ( args ) => {
	const modal = document.getElementById( args.blockId );
	if ( !modal ) return;

	modal.classList.add( 'active' );
	modal.setAttribute( 'data-modal-trigger', args.modalTrigger || 'manual' );

	document.dispatchEvent( new CustomEvent( 'spectra:modal:opened', {
		detail: {
			blockId: args.blockId,
			modalTrigger: args.modalTrigger || 'manual',
			triggerElement: args.triggerElement || null
		},
		bubbles: true
	} ) );
};

let exitIntentTriggered = false;
const exitIntentModals = new Map(); // Store all exit intent modals

/**
 * Register a modal for exit intent triggering.
 *
 * @param {Object} args - Modal configuration.
 */
const attachExitIntent = ( args = {} ) => exitIntentModals.set( args.blockId, args );

/**
 * Automatically show modal after a delay.
 *
 * @param {Object} args - Modal configuration.
 */
const attachAutomaticOpen = ( args = {} ) => {
	const delay = args.noOfSecondsToShow === 0 ? 0 : args.noOfSecondsToShow * 1000;
	
	setTimeout( () => {
		if ( canShow( args ) ) {
			openModal( args );
			if ( args.enableCookies && args.setCookiesOn === 'page-refresh' ) {
				setPopupCookie( args.blockId, args.hideForDays || DEFAULT_HIDE_DAYS );
			}
		}
	}, delay );
};

/**
 * Attach click and keyboard handlers to custom trigger elements.
 *
 * @param {Object} args - Modal configuration with cssClass or cssId.
 */
const attachCustomTriggers = ( args = {} ) => {
	const setupTrigger = ( trigger ) => {
		const handleOpen = ( e ) => {
			e.preventDefault();
			openModal( { ...args, triggerElement: trigger } );
		};

		trigger.addEventListener( 'click', handleOpen );

		if ( trigger.tagName !== 'BUTTON' && trigger.tagName !== 'A' ) {
			trigger.setAttribute( 'tabindex', trigger.getAttribute( 'tabindex' ) || '0' );
			trigger.setAttribute( 'role', trigger.getAttribute( 'role' ) || 'button' );
			trigger.addEventListener( 'keydown', ( e ) => {
				if ( e.key === ' ' || e.key === 'Enter' ) handleOpen( e );
			} );
		} else if ( trigger.tagName === 'A' ) {
			trigger.addEventListener( 'keydown', ( e ) => {
				if ( e.key === 'Enter' ) handleOpen( e );
			} );
		}

		if ( !trigger.getAttribute( 'aria-label' ) && !trigger.textContent.trim() ) {
			trigger.setAttribute( 'aria-label', 'Open modal' );
		}
	};

	if ( args.modalTrigger === 'custom-class' && args.cssClass ) {
		document.querySelectorAll( `.${args.cssClass}` ).forEach( setupTrigger );
	} else if ( args.modalTrigger === 'custom-id' && args.cssId ) {
		const trigger = document.getElementById( args.cssId );
		if ( trigger ) setupTrigger( trigger );
	}
};

/**
 * Handle modal initialization events and setup appropriate triggers.
 *
 * @param {Event} event - Custom event with modal configuration in detail.
 */
const handleModalInitialization = ( event ) => {
	const { detail } = event;
	const args = {
		...detail,
		cssClass: detail.cssClass || '',
		cssId: detail.cssId || '',
		modalTrigger: detail.modalTrigger || 'automatic',
		showAfterSeconds: detail.showAfterSeconds || false,
		noOfSecondsToShow: parseInt( detail.noOfSecondsToShow || '5', 10 ),
		hideForDays: parseInt( detail.hideForDays || '7', 10 ),
		enableCookies: detail.enableCookies === '1' || detail.enableCookies === 'true' || detail.enableCookies === true,
		setCookiesOn: detail.setCookiesOn || 'page-refresh'
	};

	if ( args.modalTrigger === 'custom-class' || args.modalTrigger === 'custom-id' ) {
		attachCustomTriggers( args );
	} else if ( args.modalTrigger === 'automatic' ) {
		if ( args.exitIntent ) {
			attachExitIntent( args );
		} else if ( args.showAfterSeconds ) {
			attachAutomaticOpen( args );
		}
	}
};

/**
 * Setup global mouse handlers for exit intent detection.
 */
const setupGlobalHandlers = () => {
	let isNearTop = false;
	let moveTimeout;

	document.addEventListener( 'mousemove', ( e ) => {
		if ( moveTimeout ) return;
		moveTimeout = setTimeout( () => { moveTimeout = null; }, 16 );
		isNearTop = e.clientY <= EXIT_INTENT_THRESHOLD;
	} );

	document.addEventListener( 'mouseleave', () => {
		if ( exitIntentTriggered || exitIntentModals.size === 0 || !isNearTop ) return;
		// eslint-disable-next-line no-unused-vars
		for ( const [blockId, modal] of exitIntentModals.entries() ) {
			if ( canShow( modal ) ) {
				openModal( modal );
				exitIntentTriggered = true;
				return;
			}
		}
	} );
};

/**
 * Process modals that are already in DOM with data-wp-context attributes.
 */
const processExistingModals = () => {
	document.querySelectorAll( '[data-wp-interactive="spectra/modal"][data-wp-context]' ).forEach( modal => {
		const contextAttr = modal.getAttribute( 'data-wp-context' );
		const contextMatch = contextAttr?.match( /^[^:]+::(.+)$/ );
		
		if ( contextMatch ) {
			try {
				modal.dispatchEvent( new CustomEvent( 'spectra:modal:initialized', {
					detail: JSON.parse( contextMatch[1] ),
					bubbles: true
				} ) );
			} catch ( e ) {
				// Silently fail if context parsing fails
			}
		}
	} );
};

/**
 * Initialize the modal script - setup handlers and process existing modals.
 */
const initialize = () => {
	setupGlobalHandlers();

	document.addEventListener( 'spectra:modal:initialized', handleModalInitialization );

	document.addEventListener( 'UAGModalEditor', ( event ) => {
		const { detail } = event;
		handleModalInitialization( {
			detail: {
				blockId: 'uagb-block-' + detail.block_id,
				modalTrigger: detail.modalTrigger || 'automatic',
				cssClass: detail.cssClass || '',
				cssId: detail.cssID || '',
				exitIntent: detail.exitIntent || false,
				showAfterSeconds: detail.showAfterSeconds || false,
				noOfSecondsToShow: detail.noOfSecondsToShow || '5',
				enableCookies: detail.enableCookies || false,
				setCookiesOn: detail.setCookiesOn || 'page-refresh',
				hideForDays: detail.hideForDays || '7'
			}
		} );
	} );

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', processExistingModals );
	} else {
		processExistingModals();
	}

	setTimeout( processExistingModals, 1000 );
};

initialize();