/**
 * Preview Builder Script for Global Styles BlockDefaults
 *
 * This script runs inside the iframe and handles:
 * - Listening for postMessage events from the parent window
 * - Clearing existing blocks from the editor
 * - Creating and inserting the requested preview block
 *
 * @since 2.0.0-beta.1
 */

( function() {
	'use strict';

	/**
	 * Wait for WordPress editor to be ready
	 *
	 * @param {Function} callback
	 */
	const waitForEditor = ( callback ) => {
		if ( 
			window.wp && 
			window.wp.data && 
			window.wp.blocks && 
			window.wp.data.select( 'core/block-editor' ) 
		) {
			callback();
		} else {
			setTimeout( () => waitForEditor( callback ), 100 );
		}
	}

	/**
	 * Clear all blocks except the specified one from the editor
	 *
	 * @param {string} keepBlockClientId - Client ID of the block to keep
	 */
	const clearAllBlocksExcept = ( keepBlockClientId ) => {
		const { dispatch, select } = window.wp.data;
		
		// Get all top-level block client IDs
		const allBlocks = select( 'core/block-editor' ).getBlocks();
		const blocksToRemove = allBlocks
			.filter( block => block.clientId !== keepBlockClientId )
			.map( block => block.clientId );
		
		// Remove all blocks except the one we want to keep
		if ( blocksToRemove.length > 0 ) {
			dispatch( 'core/block-editor' ).removeBlocks( blocksToRemove );
		}
	}

	/**
	 * Create a block with the specified configuration
	 *
	 * @param {Object} blockConfig              - Block configuration object
	 * @param {string} blockConfig.name         - Block name (e.g., 'spectra/buttons')
	 * @param {Object} blockConfig.attributes   - Block attributes
	 * @param {Array}  blockConfig.inner_blocks - Array of inner block configurations
	 * @return {Object} Created block object
	 */
	const createPreviewBlock = ( blockConfig ) => {
		const { createBlock } = window.wp.blocks;
		
		const innerBlocks = [];
		if ( blockConfig.inner_blocks && Array.isArray( blockConfig.inner_blocks ) ) {
			blockConfig.inner_blocks.forEach( innerBlockConfig => {
				// Recursively create inner blocks using the same function
				innerBlocks.push( createPreviewBlock( innerBlockConfig ) );
			} );
		}

		return createBlock( 
			blockConfig.name, 
			blockConfig.attributes || {}, 
			innerBlocks 
		);
	}

	/**
	 * Insert the preview block into the editor
	 *
	 * @param {Object} blockData - Block data received from parent window
	 */
	const insertPreviewBlock = ( blockData ) => {
		try {
			const { dispatch, select } = window.wp.data;

			// Get current blocks before insertion
			const blocksBefore = select( 'core/block-editor' ).getBlocks();
			const clientIdsBefore = blocksBefore.map( block => block.clientId );

			// Create and insert the new block
			const block = createPreviewBlock( blockData );
			dispatch( 'core/block-editor' ).insertBlocks( [ block ] );

			// Get blocks after insertion to find the new one
			const blocksAfter = select( 'core/block-editor' ).getBlocks();
			const newBlock = blocksAfter.find( currentBlock => !clientIdsBefore.includes( currentBlock.clientId ) );

			if ( newBlock ) {
				// Now clear all other blocks except the one we just inserted
				clearAllBlocksExcept( newBlock.clientId );
			} else {
				// Fallback: clear all blocks and insert again
				const allClientIds = blocksAfter.map( currentBlock => currentBlock.clientId );
				dispatch( 'core/block-editor' ).removeBlocks( allClientIds );
				dispatch( 'core/block-editor' ).insertBlocks( [ block ] );
			}

			// Send success message back to parent
			window.parent.postMessage( {
				type: 'spectra-gs-preview-success',
				blockName: blockData.name
			}, '*' );

		} catch ( error ) {
			console.error( 'Error creating preview block:', error ); // eslint-disable-line no-console
			
			// Send error message back to parent
			window.parent.postMessage( {
				type: 'spectra-gs-preview-error',
				error: error.message,
				blockName: blockData.name
			}, '*' );
		}
	}


	/**
	 * Replace the content of the spectra-gs-dynamic-styles stylesheet.
	 *
	 * @param {string} css - The complete CSS content to replace with
	 */
	const replaceDynamicStylesContent = ( css ) => {
		// WordPress creates inline CSS with -inline-css suffix
		let styleElement = document.getElementById( 'spectra-gs-dynamic-styles-inline-css' );
		
		if ( ! styleElement ) {
			// Try the base ID as fallback
			styleElement = document.getElementById( 'spectra-gs-dynamic-styles' );
		}
		
		if ( ! styleElement ) {
			// If not found, look for it in stylesheets (might be a link element)
			const stylesheets = document.querySelectorAll( 'link[href*="spectra-gs-dynamic-styles"]' );
			if ( stylesheets.length > 0 ) {
				// Convert link to style element so we can modify content
				stylesheets.forEach( link => link.remove() );
			}
			
			// Create new style element for dynamic styles with the correct WordPress ID
			styleElement = document.createElement( 'style' );
			styleElement.id = 'spectra-gs-dynamic-styles-inline-css';
			styleElement.type = 'text/css';
			document.head.appendChild( styleElement );
		}
		
		// Replace the entire content
		styleElement.textContent = css;
	};

	/**
	 * Update a specific class in the spectra-gs-dynamic-styles stylesheet.
	 *
	 * @param {string} action         - 'add' or 'remove'
	 * @param {string} blockName      - The block name
	 * @param {string} pseudoSelector - The pseudo-selector
	 * @param {string} className      - The class name
	 * @param {string} css            - The CSS content (for add action)
	 */
	const updateClassInStylesheet = ( action, blockName, pseudoSelector, className, css ) => {
		// WordPress creates inline CSS with -inline-css suffix
		let styleElement = document.getElementById( 'spectra-gs-dynamic-styles-inline-css' );
		
		if ( ! styleElement ) {
			// Try the base ID as fallback
			styleElement = document.getElementById( 'spectra-gs-dynamic-styles' );
		}
		
		if ( ! styleElement ) {
			return;
		}
		
		const currentCSS = styleElement.textContent;
		let newCSS = currentCSS;
		
		if ( action === 'add' ) {
			// Add the new class CSS to the stylesheet
			// Look for the block/pseudo-selector section to insert into
			const sectionMarker = `/* START: ${blockName}-${pseudoSelector} */`;
			const endSectionMarker = `/* END: ${blockName}-${pseudoSelector} */`;
			
			if ( currentCSS.includes( sectionMarker ) ) {
				// Insert into existing section
				const sectionEnd = currentCSS.indexOf( endSectionMarker );
				if ( sectionEnd !== -1 ) {
					newCSS = currentCSS.slice( 0, sectionEnd ) + css + currentCSS.slice( sectionEnd );
				}
			} else {
				// Append new section
				newCSS += `\n${sectionMarker}\n${css}${endSectionMarker}\n`;
			}
		} else if ( action === 'remove' ) {
			// Remove the class CSS from the stylesheet using comment markers
			const classStartMarker = `/* CLASS: ${className} */`;
			const classEndMarker = `/* END CLASS: ${className} */`;
			
			const startIndex = currentCSS.indexOf( classStartMarker );
			if ( startIndex !== -1 ) {
				const endIndex = currentCSS.indexOf( classEndMarker, startIndex );
				if ( endIndex !== -1 ) {
					// Remove everything between and including the markers
					const endMarkerLength = classEndMarker.length;
					newCSS = currentCSS.slice( 0, startIndex ) + currentCSS.slice( endIndex + endMarkerLength );
				}
			}
		}
		
		// Update the stylesheet content
		styleElement.textContent = newCSS;
	};

	/**
	 * Handle postMessage events from parent window
	 *
	 * @param {MessageEvent} event - Message event
	 */
	const handleMessage = ( event ) => {
		// Verify the message has the required structure
		if ( ! event.data || ! event.data.type ) {
			return;
		}

		switch ( event.data.type ) {
			case 'spectra-gs-render-block':
				// Ensure we have block data
				if ( ! event.data.blockData ) {
					console.error( 'No block data provided for preview' ); // eslint-disable-line no-console
					return;
				}
				// Insert the preview block
				insertPreviewBlock( event.data.blockData );
				break;

			case 'spectra-gs-replace-stylesheet':
				// Replace the entire spectra-gs-dynamic-styles stylesheet
				replaceDynamicStylesContent( event.data.css || '' );
				break;

			case 'spectra-gs-update-class':
				// Update a specific class in the stylesheet
				updateClassInStylesheet( 
					event.data.action || 'add',
					event.data.blockName || '',
					event.data.pseudoSelector || 'default',
					event.data.className || '',
					event.data.css || ''
				);
				break;

			default:
				// Ignore other message types
				break;
		}
	}

	/**
	 * Monitor for unwanted paragraph blocks and remove them
	 */
	const cleanupUnwantedParagraphs = () => {
		const { dispatch, select, subscribe } = window.wp.data;
		
		// Monitor block changes
		let previousBlocks = [];
		
		const checkForUnwantedParagraphs = () => {
			const currentBlocks = select( 'core/block-editor' ).getBlocks();
			
			// If we have more than one block and one of them is an empty paragraph, remove it
			if ( currentBlocks.length > 1 ) {
				const paragraphBlocks = currentBlocks.filter( block => 
					block.name === 'core/paragraph' && 
					( !block.attributes.content || block.attributes.content.trim() === '' )
				);
				
				if ( paragraphBlocks.length > 0 ) {
					const paragraphIds = paragraphBlocks.map( block => block.clientId );
					dispatch( 'core/block-editor' ).removeBlocks( paragraphIds );
				}
			}
		};
		
		// Subscribe to block editor changes
		subscribe( () => {
			const currentBlocks = select( 'core/block-editor' ).getBlocks();
			
			// Only check if blocks have changed
			if ( JSON.stringify( currentBlocks ) !== JSON.stringify( previousBlocks ) ) {
				previousBlocks = currentBlocks;
				checkForUnwantedParagraphs();
			}
		} );
		
		// Also run cleanup every 500ms as a backup
		setInterval( checkForUnwantedParagraphs, 500 );
	};

	/**
	 * Disable WordPress autosave and post saving functionality
	 */
	const disablePostSaving = () => {
		// Wait for WordPress editor to be fully loaded
		const disableAfterEditorReady = () => {
			// Disable autosave completely but safely
			if ( window.wp && window.wp.autosave ) {
				// Suspend autosave functionality
				if ( window.wp.autosave.server && typeof window.wp.autosave.server.suspend === 'function' ) {
					window.wp.autosave.server.suspend();
				}
				
				// Disable local autosave as well
				if ( window.wp.autosave.local && typeof window.wp.autosave.local.suspend === 'function' ) {
					window.wp.autosave.local.suspend();
				}
			}

			// Disable post saving via data module
			if ( window.wp && window.wp.data && window.wp.data.dispatch ) {
				const { dispatch } = window.wp.data;
				
				// Override save actions when they occur
				const originalSavePost = {};
				
				try {
					const editorDispatch = dispatch( 'core/editor' );
					if ( editorDispatch && editorDispatch.savePost ) {
						originalSavePost.editor = editorDispatch.savePost;
						editorDispatch.savePost = () => {}; // No-op function
					}
				} catch ( error ) {
					// Silently handle if core/editor is not available
				}

				try {
					const editPostDispatch = dispatch( 'core/edit-post' );
					if ( editPostDispatch && editPostDispatch.savePost ) {
						originalSavePost.editPost = editPostDispatch.savePost;
						editPostDispatch.savePost = () => {}; // No-op function
					}
				} catch ( error ) {
					// Silently handle if core/edit-post is not available
				}
			}

			// Disable beforeunload handlers that trigger save
			window.addEventListener( 'beforeunload', ( event ) => {
				// Prevent default beforeunload behavior
				event.preventDefault();
				event.stopPropagation();
			}, true ); // Use capture phase to run first

			// Disable Ctrl+S / Cmd+S save shortcuts
			document.addEventListener( 'keydown', ( event ) => {
				if ( ( event.ctrlKey || event.metaKey ) && event.key === 's' ) {
					event.preventDefault();
					event.stopPropagation();
					return false;
				}
			}, true );

			// Monitor and disable any save buttons or save actions
			const disableSaveElements = () => {
				// Hide save buttons (but don't break them completely)
				const saveButtons = document.querySelectorAll( '.editor-post-save-draft, .editor-post-publish-button' );
				saveButtons.forEach( button => {
					button.style.display = 'none';
				} );

				// Hide auto-save indicators
				const autoSaveIndicators = document.querySelectorAll( '.editor-post-last-revision, .editor-post-saved-state' );
				autoSaveIndicators.forEach( indicator => {
					indicator.style.display = 'none';
				} );
			};

			// Run immediately and on DOM changes
			disableSaveElements();
			const observer = new MutationObserver( disableSaveElements ); // eslint-disable-line no-undef
			observer.observe( document.body, { childList: true, subtree: true } );
		};

		// Execute after a short delay to ensure WordPress is loaded
		setTimeout( disableAfterEditorReady, 500 );
	};

	/**
	 * Initialize the preview builder
	 */
	const initPreviewBuilder = () => {
		// Disable all post saving functionality first
		disablePostSaving();

		// Listen for messages from parent window
		window.addEventListener( 'message', handleMessage );

		// Start monitoring for unwanted paragraph blocks
		cleanupUnwantedParagraphs();

		// Send ready message to parent
		window.parent.postMessage( {
			type: 'spectra-gs-preview-ready'
		}, '*' );
	}

	// Wait for editor and initialize
	waitForEditor( initPreviewBuilder );

} )();