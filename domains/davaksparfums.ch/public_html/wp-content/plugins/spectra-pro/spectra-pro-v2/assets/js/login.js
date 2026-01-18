// Adapted UAGBLogin logic for Spectra Pro v2 modular login block with reCAPTCHA and forgot password
( function( window ) {
	const spinner = `<svg width="20" height="20" viewBox="0 0 38 38" xmlns="http://www.w3.org/2000/svg" stroke="#fff"><g fill="none" fill-rule="evenodd"><g transform="translate(1 1)" stroke-width="2"><circle stroke-opacity=".5" cx="18" cy="18" r="18"/><path d="M36 18c0-9.94-8.06-18-18-18"><animateTransform attributeName="transform" type="rotate" from="0 18 18" to="360 18 18" dur="1s" repeatCount="indefinite"/></path></g></g></svg>`;
	const classes = {
		inputError: 'spectra-pro-login-form__input-error',
		fieldErrorMessage: 'spectra-pro-login-form__field-error-message',
	};

	function updateFormMessage( form, message, type, config ) {
		// Since view.js is disabled, work directly with form-message blocks
		// IMPORTANT: Message blocks are injected inside the form element (see view.php line 115-140)
		// First try to find message blocks inside the form
		let messageBlocks = form.querySelectorAll( '.wp-block-spectra-pro-form-message' );

		// If not found in form, try looking in the parent login block (backward compatibility)
		if ( messageBlocks.length === 0 ) {
			const loginBlock = form.closest( '.wp-block-spectra-pro-login' );
			if ( loginBlock ) {
				messageBlocks = loginBlock.querySelectorAll( '.wp-block-spectra-pro-form-message' );
			}
		}

		if ( messageBlocks.length > 0 ) {
			// Hide all message blocks first
			messageBlocks.forEach( block => {
				const wrapper = block.querySelector( '.spectra-pro-form-message__wrapper' );
				if ( wrapper ) {
					wrapper.style.display = 'none';
					// Use strong hiding styles to override any background/border styling
					block.style.cssText = 'display: none !important; visibility: hidden !important; opacity: 0 !important;';
					block.classList.remove( 'spectra-pro-form-message--visible' );
					block.classList.add( 'spectra-pro-form-message--hidden' );
				}
			} );

			// If we have a message to show
			if ( message && type ) {
				// Find the right message block (success or error)
				const targetBlock = [...messageBlocks].find( block =>
					block.classList.contains( `spectra-pro-form-message--${type}` )
				);

				if ( targetBlock ) {
					const wrapper = targetBlock.querySelector( '.spectra-pro-form-message__wrapper' );
					const textElement = targetBlock.querySelector( '.spectra-pro-form-message__text' );

					if ( wrapper && textElement ) {
						// Use server message if available, otherwise fall back to custom config message
						let displayMessage = message;
						if ( !message || message.trim() === '' ) {
							// Only use custom message as fallback if server didn't provide one
							if ( config ) {
								if ( type === 'success' && config.successMessageText && config.successMessageText.trim() !== '' ) {
									displayMessage = config.successMessageText;
								} else if ( type === 'error' && config.errorMessageText && config.errorMessageText.trim() !== '' ) {
									displayMessage = config.errorMessageText;
								}
							}
						}

						// Sanitize the message before rendering to prevent XSS
						if ( typeof displayMessage === 'string' ) {
							displayMessage = sanitizeErrorHTML( displayMessage );
						}

						// Update the text content - use innerHTML to render HTML tags from WordPress
						textElement.innerHTML = displayMessage;

						// Show the message - clear all hiding styles including !important ones
						wrapper.style.display = 'block';
						targetBlock.style.cssText = 'display: block !important; visibility: visible !important; opacity: 1 !important;';
						targetBlock.classList.remove( 'spectra-pro-form-message--hidden' );
						targetBlock.classList.add( 'spectra-pro-form-message--visible' );

						return true; // Success - message displayed
					}
				}
			}

			return true; // Handled (even if no message to show)
		}

		// Fallback to legacy method for backward compatibility
		return false; // Let calling code handle legacy approach
	}
	
	function getStatusDiv( form ) {
		// Try the new form-message structure (check siblings since form is at root level)
		let statusDiv = form.parentElement?.querySelector( '.wp-block-spectra-pro-form-message .spectra-pro-form-message__text' );
		// Fallback to legacy structure
		if ( !statusDiv ) {
			statusDiv = form.parentElement?.querySelector( '.spectra-pro-login-form-status' );
		}
		// If still not found, look for any status div we can use
		if ( !statusDiv ) {
			statusDiv = document.querySelector( '.spectra-pro-form-message__text' );
		}
		return statusDiv;
	}
	function getSubmitButton( form ) {
		return form.querySelector( 'button[type="submit"], .spectra-pro-login-form-submit-button' );
	}
	function getField( form, name ) {
		return form.querySelector( `[name="${name}"]` );
	}
	function showFieldError( field, message ) {
		field.classList.add( classes.inputError );
		let errorSpan = field.parentElement.querySelector( '.' + classes.fieldErrorMessage );
		if ( !errorSpan ) {
			errorSpan = document.createElement( 'span' );
			errorSpan.className = classes.fieldErrorMessage;
			field.parentElement.appendChild( errorSpan );
		}
		errorSpan.textContent = message;
	}
	function clearFieldError( field ) {
		field.classList.remove( classes.inputError );
		const errorSpan = field.parentElement.querySelector( '.' + classes.fieldErrorMessage );
		if ( errorSpan ) errorSpan.remove();
	}
	function sanitizeErrorHTML( html ) {
		if ( !html || typeof html !== 'string' ) {
			return html || '';
		}
		try {
			const parser = new DOMParser();
			const doc = parser.parseFromString( html, 'text/html' );
			if ( !doc || !doc.body ) {
				return html; // Return original if parsing fails
			}
			const validTags = ['strong', 'em', 'i', 'a', 'span'];
			const allowedAttributes = ['class', 'id', 'href'];
			function traverse( node ) {
				if ( !node ) return;
				if ( node.nodeType === 3 ) return; // TEXT_NODE
				if ( node.tagName && !validTags.includes( node.tagName.toLowerCase() ) ) {
					if ( node.parentNode ) {
						node.parentNode.removeChild( node );
					}
					return;
				}
				if ( node.attributes ) {
					Array.from( node.attributes ).forEach( attr => {
						if ( !allowedAttributes.includes( attr.name.toLowerCase() ) ) {
							node.removeAttribute( attr.name );
						}
					} );
				}
				Array.from( node.childNodes ).forEach( traverse );
			}
			traverse( doc.body );
			return doc.body.innerHTML;
		} catch ( e ) {
			// eslint-disable-next-line no-console
			console.error( 'Error sanitizing HTML:', e );
			return html; // Return original on error
		}
	}
	function processAjaxUrl( url ) {
		if ( !url ) return '';
		const pageProtocol = window.location.protocol;
		return url.replace( /^https?:/, pageProtocol );
	}
	function handleRedirect( redirectUrl ) {
		if ( redirectUrl ) {
			window.location.href = redirectUrl;
		} else {
			window.location.reload();
		}
	}

	function handleLogoutRedirect( form, config ) {
		const logoutLink = form.querySelector( '.spectra-pro-login-logged-in-message a, .spectra-pro-logout-link' );
		if ( !logoutLink ) return;
		
		logoutLink.addEventListener( 'click', function( e ) {
			// Check for custom redirect URL - either from data attribute or config
			let customRedirectUrl = logoutLink.dataset.redirectUrl || config.logoutRedirectUrl;
			

			
			if ( customRedirectUrl ) {
				e.preventDefault();
				
				// Ensure URL has proper protocol - if it doesn't start with http:// or https://, add https://
				if ( customRedirectUrl && !customRedirectUrl.match( /^https?:\/\//i ) ) {
					customRedirectUrl = 'https://' + customRedirectUrl;
				}
				

				
				// Perform logout first, then redirect to custom URL
				fetch( logoutLink.href, {
					method: 'GET',
					credentials: 'same-origin'
				} )
				.then( () => {
					// Use location.replace instead of location.href to replace the URL completely
					window.location.replace( customRedirectUrl );
				} )
				.catch( () => {
					// If logout request fails, still redirect to custom URL
					window.location.replace( customRedirectUrl );
				} );
			}
		} );
	}
	function loadRecaptchaIfNeeded( config ) {
		if ( !config || ( !config.enableReCaptcha && !config.enableRecaptcha ) ) return;
		const recaptchaVersion = config.recaptchaVersion || config.reCaptchaType || 'v2';
		const siteKey = config.recaptchaSiteKey;

		if ( !siteKey ) {
			// eslint-disable-next-line no-console
			console.warn( 'reCAPTCHA enabled but no site key found' );
			return;
		}
		
		if ( recaptchaVersion === 'v2' ) {
			if ( !document.querySelector( 'script[src*="recaptcha/api.js"]' ) ) {
				const recaptchaScript = document.createElement( 'script' );
				recaptchaScript.type = 'text/javascript';
				recaptchaScript.src = 'https://www.google.com/recaptcha/api.js';
				document.head.appendChild( recaptchaScript );
			}
		} else if ( recaptchaVersion === 'v3' ) {
			if ( !document.querySelector( 'script[src*="recaptcha/api.js?render"]' ) ) {
				const recaptchaScript = document.createElement( 'script' );
				recaptchaScript.type = 'text/javascript';
				recaptchaScript.src = 'https://www.google.com/recaptcha/api.js?render=' + siteKey;
				document.head.appendChild( recaptchaScript );
			}
		}
	}
	function handleForgotPassword( form, config ) {
		const forgotLink = form.querySelector( '.spectra-pro-login-forgot-password-link' );
		if ( !forgotLink ) return;
		const statusDiv = getStatusDiv( form );
		forgotLink.addEventListener( 'click', function( e ) {
			e.preventDefault();
			const usernameField = getField( form, 'log' );
			if ( !usernameField || !usernameField.value ) {
				const errorMsg = config.username_required || 'Username is required.';
				// Try modern Interactivity API first
				const modernHandled = updateFormMessage( form, errorMsg, 'error', config );
				if ( !modernHandled && statusDiv ) {
					statusDiv.innerHTML = `<div class="spectra-pro-login-form-status__error">${errorMsg}</div>`;
				}
				showFieldError( usernameField, 'Username is required.' );
				usernameField.focus();
				return;
			}
			clearFieldError( usernameField );
			const formData = new FormData();
			formData.append( 'action', 'spectra_pro_v2_block_login_forgot_password' );
			formData.append( '_nonce', config.nonce || '' );
			formData.append( 'log', usernameField.value );
			const ajaxUrl = processAjaxUrl( config.ajax_url );
			// Clear existing messages
			updateFormMessage( form, '', '', config );
			if ( statusDiv ) statusDiv.innerHTML = '';
			
			fetch( ajaxUrl, {
				method: 'POST',
				body: formData,
			} )
			.then( response => response.json() )
			.then( response => {
				if ( response.success ) {
					// Try modern Interactivity API first
					const modernHandled = updateFormMessage( form, response.data, 'success', config );
					if ( !modernHandled && statusDiv ) {
						statusDiv.innerHTML = `<div class="spectra-pro-login-form-status__success">${response.data}</div>`;
					}
				} else {
					let errorMsg = response.data;
					if ( !errorMsg ) {
						errorMsg = config.error_message || 'An error occurred. Please try again.';
					}
					if ( typeof errorMsg === 'string' ) {
						errorMsg = sanitizeErrorHTML( errorMsg );
					}
					// Try modern Interactivity API first
					const modernHandled = updateFormMessage( form, errorMsg, 'error', config );
					if ( !modernHandled && statusDiv ) {
						statusDiv.innerHTML = `<div class="spectra-pro-login-form-status__error">${errorMsg}</div>`;
					}
				}
			} )
			.catch( ( err ) => {
				// Try modern Interactivity API first
				const modernHandled = updateFormMessage( form, 'An error occurred. Please try again.', 'error', config );
				if ( !modernHandled && statusDiv ) {
					statusDiv.innerHTML = `<div class="spectra-pro-login-form-status__error">An error occurred. Please try again.</div>`;
				}
				// eslint-disable-next-line no-console
				console.error( 'Error:', err );
			} );
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function() {
		const loginBlocks = document.querySelectorAll( '.wp-block-spectra-pro-login' );
		
		// Hide all form-message blocks by default
		document.querySelectorAll( '.wp-block-spectra-pro-form-message' ).forEach( block => {
			const wrapper = block.querySelector( '.spectra-pro-form-message__wrapper' );
			if ( wrapper ) {
				wrapper.style.display = 'none';
			}
		} );
		loginBlocks.forEach( function( block ) {
			const configRaw = block.getAttribute( 'data-js-config' );
			let config = {};
			try {
				config = JSON.parse( configRaw );
			} catch ( e ) {
				// eslint-disable-next-line no-console
				console.error( 'Invalid login block config:', e );
			}
			// The block element itself might be the form, or it might contain a form
			const form = block.tagName === 'FORM' ? block : block.querySelector( 'form' );
			if ( !form ) return;
			const statusDiv = getStatusDiv( form );
			if ( statusDiv ) statusDiv.setAttribute( 'aria-live', 'polite' );
			const submitButton = getSubmitButton( form );
			const username = getField( form, 'log' ); // WordPress standard username field
			const password = getField( form, 'pwd' ); // WordPress standard password field

			// reCAPTCHA support
			loadRecaptchaIfNeeded( config );

			// Password visibility toggle is handled by the modern implementation below

			// Field validation on blur
			[username, password].forEach( field => {
				if ( !field ) return;
				field.addEventListener( 'focusout', function() {
					if ( !this.value.trim() ) {
						const fieldLabel = this.name === 'log' ? 'Username' : 'Password';
						showFieldError( this, fieldLabel + ' is required.' );
					} else {
						clearFieldError( this );
					}
				} );
			} );

			// Forgot password AJAX
			handleForgotPassword( form, config );

			// Logout redirect handling
			handleLogoutRedirect( form, config );

			form.addEventListener( 'submit', function( e ) {
				e.preventDefault();
				let valid = true;
				if ( !username || !username.value ) {
					if ( username ) {
						showFieldError( username, 'Username is required.' );
						username.focus();
					}
					valid = false;
				} else {
					clearFieldError( username );
				}
				if ( !password || !password.value ) {
					if ( password ) {
						showFieldError( password, 'Password is required.' );
						if ( valid ) password.focus();
					}
					valid = false;
				} else {
					clearFieldError( password );
				}
				if ( !valid ) {
					// Try modern Interactivity API first
					const modernHandled = updateFormMessage( form, 'Please fill in all required fields.', 'error', config );
					if ( !modernHandled && statusDiv ) {
						statusDiv.innerHTML = '<div class="spectra-pro-login-form-status__error">Please fill in all required fields.</div>';
					}
					return;
				}

				const formData = new FormData( form );
				formData.append( 'action', 'spectra_pro_v2_block_login' );
				formData.append( '_nonce', config.nonce || '' );
				
				// Add redirect URL if available (check both attribute names)
				const redirectUrl = config.redirectUrl || config.loginRedirectURL;
					if ( redirectUrl && redirectUrl.trim() !== '' ) {
						formData.append( 'redirectUrl', redirectUrl );
				} else {
					}

				function doAjaxLogin( token ) {
					const recaptchaEnabled = config.enableReCaptcha || config.enableRecaptcha;
					const recaptchaVersion = config.recaptchaVersion || config.reCaptchaType || 'v2';
					
					if ( recaptchaEnabled ) {
						if ( token ) {
							formData.append( 'g-recaptcha-response', token );
						} else if ( recaptchaVersion === 'v2' ) {
							// For v2, get the response from the widget
							const recaptchaResponse = document.querySelector( '.g-recaptcha-response' );
							if ( recaptchaResponse && recaptchaResponse.value ) {
								formData.append( 'g-recaptcha-response', recaptchaResponse.value );
							}
						}
						formData.append( 'recaptchaStatus', recaptchaEnabled );
						formData.append( 'reCaptchaType', recaptchaVersion );
					}
					if ( submitButton ) {
						submitButton.disabled = true;
						submitButton.dataset.original = submitButton.innerHTML;
						submitButton.innerHTML = spinner;
					}
					// Add form opacity feedback
					form.style.opacity = '0.45';
					// Clear existing messages
					updateFormMessage( form, '', '', config );
					if ( statusDiv ) statusDiv.innerHTML = '';
					const ajaxUrl = processAjaxUrl( config.ajax_url || '/wp-admin/admin-ajax.php' );
					fetch( ajaxUrl, {
						method: 'POST',
						body: formData,
					} )
					.then( response => response.json() )
					.then( response => {
							if ( response.success ) {
							// Handle different response formats
							const responseData = typeof response.data === 'object' ? response.data : { message: response.data };
							const successMessage = responseData.message || response.data;
							const loginRedirectUrl = responseData.redirectUrl || config.redirectUrl || config.loginRedirectURL;


							// Try modern Interactivity API first
							const modernHandled = updateFormMessage( form, successMessage, 'success', config );
							if ( !modernHandled && statusDiv ) {
								statusDiv.innerHTML = `<div class="spectra-pro-login-form-status__success">${successMessage}</div>`;
							}

							setTimeout( () => {
										handleRedirect( loginRedirectUrl );
							}, 1000 );
						} else {
							let errorMsg = response.data;
							if ( !errorMsg ) {
								errorMsg = config.error_message || 'An error occurred. Please try again.';
							}
							if ( typeof errorMsg === 'string' ) {
								errorMsg = sanitizeErrorHTML( errorMsg );
							}
							// Try modern Interactivity API first
							const modernHandled = updateFormMessage( form, errorMsg, 'error', config );
							if ( !modernHandled && statusDiv ) {
								statusDiv.innerHTML = `<div class="spectra-pro-login-form-status__error">${errorMsg}</div>`;
							}
						}
					} )
					.catch( ( err ) => {
						// Try modern Interactivity API first
						const modernHandled = updateFormMessage( form, 'An error occurred. Please try again.', 'error', config );
						if ( !modernHandled && statusDiv ) {
							statusDiv.innerHTML = `<div class="spectra-pro-login-form-status__error">An error occurred. Please try again.</div>`;
						}
						// eslint-disable-next-line no-console
						console.error( 'Error:', err );
					} )
					.finally( () => {
						if ( submitButton ) {
							submitButton.disabled = false;
							submitButton.innerHTML = submitButton.dataset.original || 'Log In';
						}
						// Restore form opacity
						form.style.opacity = '1';
					} );
				}

				const recaptchaEnabled = config.enableReCaptcha || config.enableRecaptcha;
				const recaptchaVersion = config.recaptchaVersion || config.reCaptchaType || 'v2';
				const siteKey = config.recaptchaSiteKey;
				
				if ( recaptchaEnabled && recaptchaVersion === 'v3' && window.grecaptcha && siteKey ) {
					grecaptcha.ready( function() {
						grecaptcha.execute( siteKey, { action: 'submit' } ).then( function( token ) {
							doAjaxLogin( token );
						} );
					} );
				} else if ( recaptchaEnabled && recaptchaVersion === 'v2' ) {
					// For v2, check if reCAPTCHA is completed
					const recaptchaResponse = document.querySelector( '.g-recaptcha-response' );
					if ( !recaptchaResponse || !recaptchaResponse.value ) {
						// Try modern Interactivity API first
						const modernHandled = updateFormMessage( form, 'Please complete the reCAPTCHA verification.', 'error', config );
						if ( !modernHandled && statusDiv ) {
							statusDiv.innerHTML = '<div class="spectra-pro-login-form-status__error">Please complete the reCAPTCHA verification.</div>';
						}
						return;
					}
					doAjaxLogin();
				} else {
					doAjaxLogin();
				}
			} );
		} );
	} );
} )( window );

// Password toggle and checkbox functionality - Shared handler
// Only initialize once to prevent duplicate handlers when both login and register blocks are present
if ( !window.spectraProFormsHandlersInitialized ) {
	window.spectraProFormsHandlersInitialized = true;

	document.addEventListener( 'DOMContentLoaded', function() {
		// Use event delegation at document level but ensure handler runs only once
		document.addEventListener( 'click', function( e ) {
			// Password toggle handling
			const button = e.target.closest( '.spectra-pro-form-input-field__password-toggle' );
			if ( button ) {
				e.preventDefault();
				e.stopPropagation();

				const wrapper = button.closest( '.wp-block-spectra-pro-form-field-wrapper' );
				const input = wrapper ? wrapper.querySelector( 'input[type="password"], input[type="text"]' ) : button.previousElementSibling;

				if ( input ) {
					const visibleIcon = button.querySelector( '.spectra-pro-password-toggle__icon--visible' );
					const hiddenIcon = button.querySelector( '.spectra-pro-password-toggle__icon--hidden' );

					if ( input.type === 'password' ) {
						input.type = 'text';
						if ( visibleIcon ) visibleIcon.style.setProperty( 'display', 'none', 'important' );
						if ( hiddenIcon ) hiddenIcon.style.setProperty( 'display', 'inline', 'important' );
						button.setAttribute( 'aria-label', 'Hide Password' );
					} else {
						input.type = 'password';
						if ( visibleIcon ) visibleIcon.style.setProperty( 'display', 'inline', 'important' );
						if ( hiddenIcon ) hiddenIcon.style.setProperty( 'display', 'none', 'important' );
						button.setAttribute( 'aria-label', 'Show Password' );
					}
				}
				return;
			}

			// Checkbox text click handling
			const textElement = e.target.closest( '.spectra-pro-form-checkbox__text' );
			if ( textElement ) {
				const checkboxWrapper = textElement.closest( '.wp-block-spectra-pro-form-checkbox' );
				const checkbox = checkboxWrapper?.querySelector( 'input[type="checkbox"]' );

				if ( checkbox ) {
					e.preventDefault();
					e.stopPropagation();
					checkbox.checked = !checkbox.checked;

					const changeEvent = new Event( 'change', { bubbles: true } );
					checkbox.dispatchEvent( changeEvent );
				}
			}
		}, true ); // Use capture phase to ensure this runs first
	} );
} 