/**
 * Register form functionality
 */
( function() {
	'use strict';

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

	function updateFormMessage( form, message, type, config ) {
		// Since view.js is disabled, work directly with form-message blocks
		// IMPORTANT: Message blocks are injected inside the form element (see view.php line 102-127)
		// First try to find message blocks inside the form
		let messageBlocks = form.querySelectorAll( '.wp-block-spectra-pro-form-message' );

		// If not found in form, try looking in the parent register block (backward compatibility)
		if ( messageBlocks.length === 0 ) {
			const registerBlock = form.closest( '.wp-block-spectra-pro-register' );
			if ( registerBlock ) {
				messageBlocks = registerBlock.querySelectorAll( '.wp-block-spectra-pro-form-message' );
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

						return true;
					}
				}
			}

			return true; // Handled (even if no message to show)
		}

		return false; // Could not find form-message blocks
	}

	const SpectraProRegister = {
		settings: {},
		registerButtonInnerElement: '',
		spinner: `<svg width="20" height="20" viewBox="0 0 38 38" xmlns="http://www.w3.org/2000/svg" stroke="#fff">
			<g fill="none" fill-rule="evenodd">
				<g transform="translate(1 1)" stroke-width="2">
					<circle stroke-opacity=".5" cx="18" cy="18" r="18"/>
					<path d="M36 18c0-9.94-8.06-18-18-18">
						<animateTransform
							attributeName="transform"
							type="rotate"
							from="0 18 18"
							to="360 18 18"
							dur="1s"
							repeatCount="indefinite"/>
					</path>
				</g>
			</g>
		</svg>`,
		classes: {
			inputError: 'spectra-pro-register-form__input-error',
			inputSuccess: 'spectra-pro-register-form__input-success',
			fieldErrorMessage: 'spectra-pro-register-form__field-error-message',
		},

		init( formSelector, mainSelector, data = {} ) {
			
			this.settings[ mainSelector ] = data;

			const form = document.querySelector( formSelector );
			
			if ( form ) {
				this.validateOnEntry( mainSelector, formSelector );
				this.usernameAndEmailUniqueCheck( mainSelector, formSelector );
				
				if ( data.enableReCaptcha ) {
					this.reCaptcha( mainSelector, data.reCaptchaType );
				}

				this.formSubmitInit( mainSelector, formSelector, data.enableReCaptcha, data.reCaptchaType );
			} else {
				// eslint-disable-next-line no-console
				console.error( 'SpectraProRegister.init: Form not found with selector:', formSelector );
			}
		},

		_validateFields( mainSelector, formSelector, field ) {
			const currentForm = document.querySelector( formSelector );

			// Check presence of values
			if ( field.required ) {
				switch ( field.name ) {
					case 'first_name':
						if ( field.value.trim() === '' ) {
							this._setStatus(
								field,
								`${
									field?.previousElementSibling?.innerText
										? field.previousElementSibling.innerText
										: 'First Name'
								} cannot be blank`,
								'error'
							);
						} else {
							this._setStatus( field, null, 'success' );
						}
						break;

					case 'last_name':
						if ( field.value.trim() === '' ) {
							this._setStatus(
								field,
								`${
									field?.previousElementSibling?.innerText
										? field.previousElementSibling.innerText
										: 'Last Name'
								} cannot be blank`,
								'error'
							);
						} else {
							this._setStatus( field, null, 'success' );
						}
						break;

					case 'username':
						if ( field.value.trim() === '' ) {
							this._setStatus(
								field,
								this.settings[ mainSelector ].messageInvalidUsernameError,
								'error'
							);
						}
						break;

					case 'email':
						if ( field.value.trim() === '' ) {
							this._setStatus(
								field,
								this.settings[ mainSelector ].messageEmailMissingError,
								'error'
							);
						} else if ( field.value.trim() !== '' ) {
							const re = /\S+@\S+\.\S+/;
							if ( re.test( field.value ) ) {
								this._setStatus( field, null, 'success' );
							} else {
								this._setStatus(
									field,
									this.settings[ mainSelector ].messageInvalidEmailError,
									'error'
								);
							}
						}
						break;

					case 'password':
						if ( field.value.trim() === '' ) {
							this._setStatus(
								field,
								this.settings[ mainSelector ].messageInvalidPasswordError,
								'error'
							);
						} else {
							this._setStatus( field, null, 'success' );
						}
						
						// Also validate confirm password field when password changes
						const confirmPasswordField = currentForm.querySelector( 'input[name="confirm_password"]' );
						if ( confirmPasswordField && confirmPasswordField.value ) {
							if ( confirmPasswordField.value !== field.value ) {
								this._setStatus(
									confirmPasswordField,
									this.settings[ mainSelector ].messagePasswordConfirmError,
									'error'
								);
							} else {
								this._setStatus( confirmPasswordField, null, 'success' );
							}
						}
						break;

					case 'confirm_password':
						const passwordField = currentForm.querySelector( 'input[name="password"]' );
						if ( passwordField ) {
							// Check if passwords match
							if ( field.value !== passwordField.value ) {
								this._setStatus(
									field,
									this.settings[ mainSelector ].messagePasswordConfirmError,
									'error'
								);
							} else if ( field.value.trim() === '' && field.required ) {
								// Handle empty required field
								this._setStatus(
									field,
									'Confirm Password is required.',
									'error'
								);
							} else {
								this._setStatus( field, null, 'success' );
							}
						}
						break;

					case 'terms':
						if ( ! field.checked ) {
							this._setStatus( field, this.settings[ mainSelector ].messageTermsError, 'error' );
						} else {
							this._setStatus( field, null, 'success' );
						}
						break;

					default:
						if ( ! field.value !== '' ) {
							this._setStatus( field, this.settings[ mainSelector ].messageOtherError, 'error' );
						} else {
							this._setStatus( field, null, 'success' );
						}
						break;
				}
			}
		},

		_setStatus( field, message, status, color = null ) {
			const successWrap = field.parentElement.querySelector( '.spectra-pro-register-form__field-success-message' );
			const errorWrap = field.parentElement.querySelector( '.spectra-pro-register-form__field-error-message' );
			
			if ( status === 'success' ) {
				if ( errorWrap ) {
					field.classList.remove( this.classes.inputError );
					errorWrap.remove();
				}

				field.classList.add( this.classes.inputSuccess );
				if ( message && successWrap ) {
					successWrap.innerHTML = message;
					if ( color ) {
						successWrap.style.color = color;
					}
				} else if ( message ) {
					const successMessageNode = document.createElement( 'span' );
					successMessageNode.classList = 'spectra-pro-register-form__field-success-message';
					successMessageNode.innerHTML = message;
					if ( color ) {
						successMessageNode.style.color = color;
					}
					field.parentElement.appendChild( successMessageNode );
				}
			}

			if ( status === 'error' ) {
				field.classList.add( this.classes.inputError );
				if ( successWrap ) {
					field.classList.remove( this.classes.inputSuccess );
					successWrap.remove();
				}

				if ( errorWrap ) {
					errorWrap.innerHTML = message;
				} else {
					const errorMessageNode = document.createElement( 'span' );
					errorMessageNode.classList = 'spectra-pro-register-form__field-error-message';
					errorMessageNode.innerHTML = message;
					field.parentElement.appendChild( errorMessageNode );
				}
			}
		},

		_validateAllFields( mainSelector, formSelector ) {
			const currentForm = document.querySelector( formSelector );
			const fields = currentForm.querySelectorAll( 'input:not([type=hidden]):not([type=submit])' );
			
			// Validate each field
			fields.forEach( field => {
				if ( field.name ) {
					this._validateFields( mainSelector, formSelector, field );
				}
			} );
		},

		_isFormSubmittable( formSelector ) {
			const currentForm = document.querySelector( formSelector );
			const errorElements = currentForm.getElementsByClassName( this.classes.inputError );
			
			const isSubmittable = errorElements.length < 1;
			
			return isSubmittable;
		},

		_debounce( func, timeout = 500 ) {
			let timer;
			return ( ...args ) => {
				clearTimeout( timer );
				timer = setTimeout( () => {
					func.apply( this, args );
				}, timeout );
			};
		},

		_clearValidationMessage( formSelector ) {
			const currentForm = document.querySelector( formSelector );
			const errorMessage = currentForm.querySelector( '.spectra-pro-register-form__field-error-message' );
			if ( errorMessage ) errorMessage.remove();
			
			// Clear form-message blocks
			updateFormMessage( currentForm, '', '' );
		},

		_showValidationMessage( formSelector, errorLogs ) {
			const currentForm = document.querySelector( formSelector );
			Object.entries( errorLogs ).forEach( ( [ key, value ] ) => {
				const log = document.createElement( 'span' );
				log.classList = 'spectra-pro-register-form__field-error-message';
				log.innerHTML = value;
				const field = currentForm.querySelector( `input[name="${key}"]` );
				if ( field ) {
					field.parentElement.append( log );
				}
			} );
		},

		reCaptcha( mainSelector, reCaptchaType ) {
			const siteKey = this.settings[ mainSelector ].recaptchaSiteKey;
			if ( !siteKey ) {
				// eslint-disable-next-line no-console
				console.warn( 'reCAPTCHA enabled but no site key found' );
				return;
			}
			
			if ( reCaptchaType === 'v2' ) {
				if ( !document.querySelector( 'script[src*="recaptcha/api.js"]' ) ) {
					const recaptchaLink = document.createElement( 'script' );
					recaptchaLink.type = 'text/javascript';
					recaptchaLink.src = 'https://www.google.com/recaptcha/api.js';
					document.head.appendChild( recaptchaLink );
				}
			} else if ( reCaptchaType === 'v3' ) {
				if ( this.settings[ mainSelector ].hidereCaptchaBatch ) {
					// Hide badge after it's loaded
					setTimeout( () => {
						const badge = document.getElementsByClassName( 'grecaptcha-badge' )[ 0 ];
						if ( badge ) {
							badge.style.visibility = 'hidden';
						}
					}, 1000 );
				}
				if ( !document.querySelector( 'script[src*="recaptcha/api.js?render"]' ) ) {
					const api = document.createElement( 'script' );
					api.type = 'text/javascript';
					api.src = `https://www.google.com/recaptcha/api.js?render=${siteKey}`;
					document.head.appendChild( api );
				}
			}
		},

		getFormFields( formSelector ) {
			const currentForm = document.querySelector( formSelector );
			return currentForm.getElementsByTagName( 'input' );
		},

		validateOnEntry( mainSelector, formSelector ) {
			const self = this;
			const currentFormFields = this.getFormFields( formSelector );

			for ( const field of currentFormFields ) {
				if ( 'password' === field.type && field.name === 'password' ) {
					field.addEventListener( 'keyup', () => {
						self._checkPasswordStrength( mainSelector, field );
					} );
				}

				field.addEventListener( 'focusout', () => {
					self._validateFields( mainSelector, formSelector, field );
				} );
			}
		},

		_checkPasswordStrength( mainSelector, field ) {
			const password = field.value;
			let strength;
			
			if ( typeof wp !== 'undefined' && wp.passwordStrength ) {
				if ( this.settings[ mainSelector ].wp_version ) {
					strength = wp.passwordStrength.meter( password, wp.passwordStrength.userInputDisallowedList(), password );
				} else {
					strength = wp.passwordStrength.meter( password, wp.passwordStrength.userInputBlacklist(), password );
				}

				switch ( strength ) {
					case -1:
						this._setStatus( field, 'Unknown', 'success', '#cfcfcf' );
						break;
					case 2:
						this._setStatus( field, 'Weak', 'success', '#e07757' );
						break;
					case 3:
						this._setStatus( field, 'Good', 'success', '#f0ad4e' );
						break;
					case 4:
						this._setStatus( field, 'Strong', 'success', '#5cb85c' );
						break;
					case 5:
						this._setStatus( field, 'Mismatch', 'success', '#f0ad4e' );
						break;
					default:
						this._setStatus( field, 'Very weak', 'success', '#d9534f' );
				}
			}
		},

		usernameAndEmailUniqueCheck( mainSelector, formSelector ) {
			const currentForm = document.querySelector( formSelector );
			const settings = this.settings[ mainSelector ];
			const that = this;
			
			const validateHandler = this._debounce( ( e ) => {
				if ( ! e.target.value ) {
					return;
				}
				
				const formData = new FormData();
				formData.append( 'action', 'spectra_pro_block_register_unique_username_and_email' );
				formData.append( 'field_name', e.target.name );
				formData.append( 'field_value', e.target.value );
				formData.append( 'security', currentForm.querySelector( 'input[name="_nonce"]' ).value );
				
				// request send
				fetch( settings.ajax_url, {
					method: 'POST',
					credentials: 'same-origin',
					body: formData,
				} )
					.then( ( response ) => response.json() )
					.then( ( response ) => {
						if ( response.success ) {
							if ( 'username' === e.target.name ) {
								let errorMessage = null;
								if ( response.data?.has_error ) {
									// Try to get message from settings, fallback to response message or default
									errorMessage = response.data?.attribute && that.settings[ mainSelector ][ response.data.attribute ]
										? that.settings[ mainSelector ][ response.data.attribute ]
										: response.data?.message || 'This username is already registered.';
								}
								that._setStatus(
									currentForm.querySelector( 'input[name="username"]' ),
									errorMessage,
									response.data?.has_error ? 'error' : 'success'
								);
							} else {
								let errorMessage = null;
								if ( response.data?.has_error ) {
									// Try to get message from settings, fallback to response message or default
									errorMessage = response.data?.attribute && that.settings[ mainSelector ][ response.data.attribute ]
										? that.settings[ mainSelector ][ response.data.attribute ]
										: response.data?.message || 'This email is already registered.';
								}
								that._setStatus(
									currentForm.querySelector( 'input[name="email"]' ),
									errorMessage,
									response.data?.has_error ? 'error' : 'success'
								);
							}
						}
					} )
					.catch( ( err ) => {
						// eslint-disable-next-line no-console
						console.error( err );
					} );
			} );
			
			const usernameField = currentForm.querySelector( 'input[name="username"]' );
			const emailField = currentForm.querySelector( 'input[name="email"]' );
			
			if ( usernameField ) {
				usernameField.addEventListener( 'keypress', validateHandler, false );
				usernameField.addEventListener( 'focusout', validateHandler, false );
			}
			
			if ( emailField ) {
				emailField.addEventListener( 'keypress', validateHandler, false );
				emailField.addEventListener( 'focusout', validateHandler, false );
			}
		},

		formSubmitInit( mainSelector, formSelector, enableReCaptcha, recaptchaVersion ) {
			const currentForm = document.querySelector( formSelector );
			
			if ( !currentForm ) {
				return;
			}
			
			currentForm.addEventListener( 'submit', ( event ) => {
				event.preventDefault();
				
				// IMMEDIATE validation check - validate passwords before anything else
				const passwordField = currentForm.querySelector( 'input[name="password"]' );
				const confirmPasswordField = currentForm.querySelector( 'input[name="confirm_password"]' );
				
				if ( passwordField && confirmPasswordField ) {
					if ( passwordField.value !== confirmPasswordField.value ) {
						updateFormMessage( currentForm, 'Passwords do not match. Please check your passwords and try again.', 'error', this.settings[ mainSelector ] );
						return false; // Stop execution here
					}
				}
				
				
				if ( enableReCaptcha === true ) {
					if ( recaptchaVersion === 'v3' ) {
						if ( typeof grecaptcha === 'undefined' ) {
							updateFormMessage( currentForm, 'Error: reCAPTCHA not loaded. Please refresh and try again.', 'error', SpectraProRegister.settings[ mainSelector ] );
							return false;
						}
						
						grecaptcha.ready( function () {
							grecaptcha
								.execute( SpectraProRegister.settings[ mainSelector ].recaptchaSiteKey, { action: 'submit' } )
								.then( function ( token ) {
									SpectraProRegister.formSubmit( mainSelector, formSelector, token );
								} )
								.catch( function ( err ) {
									// eslint-disable-next-line no-console
									console.error( 'reCAPTCHA error:', err );
									updateFormMessage( currentForm, 'reCAPTCHA verification failed. Please try again.', 'error', SpectraProRegister.settings[ mainSelector ] );
								} );
						} );
					} else if ( recaptchaVersion === 'v2' ) {
						// For v2, check if reCAPTCHA is completed
						const recaptchaResponse = currentForm.querySelector( '.g-recaptcha-response' ) || document.querySelector( '.g-recaptcha-response' );
						if ( !recaptchaResponse || !recaptchaResponse.value ) {
							updateFormMessage( currentForm, 'Please complete the reCAPTCHA verification.', 'error', this.settings[ mainSelector ] );
							return false;
						}
						this.formSubmit( mainSelector, formSelector );
					} else {
						this.formSubmit( mainSelector, formSelector );
					}
				} else {
					this.formSubmit( mainSelector, formSelector );
				}
			} );
		},

		_dispatchRedirect( redirectUrl ) {
			window.location.href = redirectUrl;
		},

		formSubmit( mainSelector, formSelector, token = false ) {
			const currentForm = document.querySelector( formSelector );
			
			// Validate all required fields before submission
			this._validateAllFields( mainSelector, formSelector );

			if ( this._isFormSubmittable( formSelector ) ) {
				const formData = new FormData();
				
				formData.append( 'action', 'spectra_pro_block_register' );
				formData.append( 'post_id', this.settings[ mainSelector ].post_id );
				formData.append( 'block_id', this.settings[ mainSelector ].block_id );


				// Add redirect URL as POST parameter like login block does
				const redirectUrl = this.settings[ mainSelector ].autoRegisterRedirectURL?.url || 
								   this.settings[ mainSelector ].autoRegisterRedirectURL;
				if ( redirectUrl && typeof redirectUrl === 'string' && redirectUrl.trim() !== '' ) {
					formData.append( 'redirectUrl', redirectUrl );
				}

				
				for ( const item of currentForm.elements ) {
					if ( item.name && item.type !== 'submit' ) {
						if ( item.type === 'checkbox' ) {
							formData.append( item.name, item.checked ? item.value : '' );
						} else {
							formData.append( item.name, item.value );
						}
					}
				}
				
				
				if ( token ) {
					formData.append( 'g-recaptcha-response', token );
				}

				// Before Submit
				this._before_submit( formSelector );

				const fieldErrorMessageWrap = currentForm.querySelector( '.' + this.classes.fieldErrorMessage );
				if ( fieldErrorMessageWrap ) {
					fieldErrorMessageWrap.remove();
				}

				const processed_ajax_url = this.processAjaxUrl( this.settings[ mainSelector ].ajax_url );

				// request send
				fetch( processed_ajax_url, {
					method: 'POST',
					credentials: 'same-origin',
					body: formData,
				} )
					.then( ( response ) => response.json() )
					.then( ( response ) => {
						// Declare registerRedirectUrl in outer scope for setTimeout access
						let registerRedirectUrl = '';

						if ( response.success ) {
							// Handle different response formats (similar to login.js)
							const responseData = typeof response.data === 'object' ? response.data : { message: response.data };
							const successMessage = responseData.message || response.data || 'Registration successful!';

							// Use redirect_url from response (if provided and non-empty)
							// Priority: PHP response > settings autoRegisterRedirectURL
							// First check PHP response redirect_url (handles auto-login + redirect scenarios)
							if ( responseData.redirect_url && typeof responseData.redirect_url === 'string' && responseData.redirect_url.trim() !== '' ) {
								registerRedirectUrl = responseData.redirect_url;
							} else if ( this.settings[ mainSelector ]?.autoRegisterRedirectURL ) {
								// Fallback to settings only if no valid redirect_url from PHP
								const settingsRedirect = this.settings[ mainSelector ].autoRegisterRedirectURL;
								if ( typeof settingsRedirect === 'string' ) {
									registerRedirectUrl = settingsRedirect;
								} else if ( typeof settingsRedirect === 'object' && settingsRedirect.url ) {
									registerRedirectUrl = settingsRedirect.url;
								}
							}

							// Show success message using form-message blocks
							updateFormMessage( currentForm, successMessage, 'success', SpectraProRegister.settings[ mainSelector ] );
						} else {
							let errorMessage = '';
							if ( typeof response.data === 'object' ) {
								// Join multiple errors
								const errors = Object.values( response.data );
								errorMessage = errors.join( ' ' );
							} else {
								errorMessage = response.data || 'An error occurred during registration.';
							}

							// Show error message using form-message blocks
							updateFormMessage( currentForm, errorMessage, 'error', SpectraProRegister.settings[ mainSelector ] );
						}
						
						setTimeout( () => {
							// remove
							this._after_submit( formSelector );

							// redirect
							if ( response.success ) {
								if ( registerRedirectUrl && typeof registerRedirectUrl === 'string' && registerRedirectUrl.trim() !== '' ) {
									this._dispatchRedirect( registerRedirectUrl );
								} else {
									window.location.reload();
								}
							} else {
								this._showValidationMessage( formSelector, response.data );
							}
						}, 1000 );
					} )
					.catch( ( err ) => {
						// eslint-disable-next-line no-console
						console.error( err );
						this._after_submit( formSelector );
					} );
			} else {
				// Form validation failed - show error message
				updateFormMessage( currentForm, 'Please correct the errors in the form before submitting.', 'error' );
			}
		},

		_before_submit( formSelector ) {
			const currentForm = document.querySelector( formSelector );
			// before request
			const submitButton = currentForm.querySelector( '.spectra-pro-register-form__submit, button[type="submit"]' );
			if ( submitButton ) {
				submitButton.setAttribute( 'disabled', 'disabled' );
				// Always save the button's original innerHTML before replacing it
				this.registerButtonInnerElement = submitButton.innerHTML;
				submitButton.innerHTML = this.spinner;
				submitButton.style.opacity = '0.45';
			}
			updateFormMessage( currentForm, '', '' );
		},

		_after_submit( formSelector ) {
			const currentForm = document.querySelector( formSelector );
			const submitButton = currentForm.querySelector( '.spectra-pro-register-form__submit, button[type="submit"]' );
			if ( submitButton ) {
				submitButton.removeAttribute( 'disabled' );
				submitButton.innerHTML = this.registerButtonInnerElement;
				submitButton.style.opacity = '1';
			}
		},

		// WordPress functions like is_ssl() do not work in all cases so we process mismatching protocol (http/https) for admin-AJAX url in JS.
		processAjaxUrl( url ) {
			const processed_ajax_url = new URL( url );

			if ( processed_ajax_url.protocol !== window.location.protocol ) {
				processed_ajax_url.protocol = window.location.protocol;
			}

			return processed_ajax_url;
		},
	};

	// Initialize register forms when DOM is ready
	document.addEventListener( 'DOMContentLoaded', function() {
		const registerForms = document.querySelectorAll( '.wp-block-spectra-pro-register' );
		
		// Hide all form-message blocks by default
		document.querySelectorAll( '.wp-block-spectra-pro-form-message' ).forEach( block => {
			const wrapper = block.querySelector( '.spectra-pro-form-message__wrapper' );
			if ( wrapper ) {
				wrapper.style.display = 'none';
			}
			block.classList.add( 'spectra-pro-form-message--hidden' );
			block.classList.remove( 'spectra-pro-form-message--visible' );
		} );
		
		registerForms.forEach( function( form ) {
			const jsConfig = form.getAttribute( 'data-js-config' );
			
			if ( jsConfig ) {
				try {
					const config = JSON.parse( jsConfig );
					const blockId = config.blockId || config.block_id; // Support both camelCase and snake_case
					const formSelector = `#spectra-pro-register-form-${blockId}`;
					const mainSelector = `.uagb-block-${blockId}`;
					
					// Add ID to form for selector
					// The form element is actually the wrapper itself in this structure
					let formElement = form.querySelector( '.spectra-pro-register-form' );
					if ( !formElement && form.classList.contains( 'spectra-pro-register-form' ) ) {
						formElement = form; // The wrapper IS the form
					}
					
					if ( formElement ) {
						formElement.id = `spectra-pro-register-form-${blockId}`;
					}
					
					SpectraProRegister.init( formSelector, mainSelector, config );
				} catch ( e ) {
					// eslint-disable-next-line no-console
					console.error( 'Error initializing register form:', e );
				}
			}
		} );
	} );

	// Make it available globally for backward compatibility
	window.SpectraProRegister = SpectraProRegister;
} )();

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